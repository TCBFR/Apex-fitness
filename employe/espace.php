<?php
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/../faq/controller/FaqController.php';
require_once __DIR__ . '/../message_contact/controller/MessageContactController.php';
require_once __DIR__ . '/../planning/controller/planning.php';
exige_role('EMPLOYE');

$moi    = utilisateur()['NumUsers'];
$onglet = $_GET['onglet'] ?? 'tableau';
$pdo    = db();
$faq = new FaqController();
$maintenance = est_technicien_maintenance();
$messageController = new MessageController();
$transitionsMaintenance = [
  'En attente' => ['En cours', 'En attente de piece', 'À valider'],
  'En cours' => ['En attente de piece', 'À valider'],
  'En attente de piece' => ['En cours', 'À valider'],
];
$planning = new PlanningController();

$pdo->exec(
  'CREATE TABLE IF NOT EXISTS intervention (
    Numintervention INT AUTO_INCREMENT PRIMARY KEY,
    date_intervention DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT NULL,
    Nummaint INT NOT NULL,
    NumUsers INT NOT NULL,
    Numstatut INT NOT NULL,
    FOREIGN KEY (Nummaint) REFERENCES demande_maintenance(Nummaint) ON DELETE CASCADE,
    FOREIGN KEY (NumUsers) REFERENCES employe(NumUsers),
    FOREIGN KEY (Numstatut) REFERENCES statut_maintenance(Numstatut)
  ) ENGINE=InnoDB'
);
$colonnesDemande = $pdo->query("SHOW COLUMNS FROM demande_maintenance LIKE 'NumUsers_technicien'")->fetch();
if (!$colonnesDemande) {
  $pdo->exec('ALTER TABLE demande_maintenance ADD COLUMN NumUsers_technicien INT NULL AFTER NumUsers');
}
$cleTechnicien = $pdo->query(
  "SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'demande_maintenance'
     AND COLUMN_NAME = 'NumUsers_technicien'
     AND REFERENCED_TABLE_NAME = 'employe'
   LIMIT 1"
)->fetchColumn();
if (!$cleTechnicien) {
  $pdo->exec(
    'ALTER TABLE demande_maintenance
     ADD CONSTRAINT fk_demande_maintenance_technicien
     FOREIGN KEY (NumUsers_technicien) REFERENCES employe(NumUsers) ON DELETE SET NULL'
  );
}
if ($maintenance && $onglet !== 'maintenance') {
  redirige('employe/espace.php?onglet=maintenance');
}
if (!$maintenance && $onglet === 'maintenance') {
  redirige('employe/espace.php?onglet=tableau');
}

/* =====================================================================
   ACTIONS
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

  $actionsTechnicien = ['avancer_maintenance', 'prendre_ticket', 'detacher_ticket'];
  if ($maintenance && !in_array($action, $actionsTechnicien, true)) {
    http_response_code(403);
    flash('erreur', 'Votre compte est réservé à la maintenance des équipements.');
    redirige('employe/espace.php?onglet=maintenance');
  }
  if (!$maintenance && in_array($action, $actionsTechnicien, true)) {
    http_response_code(403);
    flash('erreur', 'La maintenance est réservée au technicien maintenance.');
    redirige('employe/espace.php?onglet=tableau');
  }

  if ($maintenance && $action === 'fermer_ticket') {
    http_response_code(403);
    flash('erreur', 'Seul l’employé qui a créé le ticket peut le fermer après validation.');
    redirige('employe/espace.php?onglet=maintenance');
  }

  if (!$maintenance && $action === 'repondre_message') {
    try {
      $messageController->set($moi, (int) ($_POST['message_parent'] ?? 0), (string) ($_POST['message'] ?? ''));
      flash('succes', 'Réponse envoyée.');
    } catch (Throwable $exception) {
      flash('erreur', $exception->getMessage());
    }
    redirige('employe/espace.php?onglet=messages');
  }

  if (!$maintenance && $action === 'supprimer_message') {
    try {
      $messageController->delete($moi, (int) ($_POST['message_parent'] ?? 0));
      flash('succes', 'Conversation supprimée.');
    } catch (Throwable $exception) {
      flash('erreur', $exception->getMessage());
    }
    redirige('employe/espace.php?onglet=messages');
  }

    /* ---- planning : creer / supprimer un creneau ------------------- */
    if (!$maintenance && PlanningController::estAction('employe', $action)) {
        $planning->traiter('employe', (int) $moi, 'employe/espace.php?onglet=creneaux');
    }

    /* ---- declarer une panne ---------------------------------------- */
    if ($action === 'declarer_panne') {
        $numequip = (int) $_POST['equipement'];
        $desc     = trim($_POST['description'] ?? '');
        $priorite = in_array($_POST['priorite'] ?? '', ['Basse', 'Moyenne', 'Haute'], true)
                    ? $_POST['priorite'] : 'Moyenne';
        $horsService = isset($_POST['hors_service']);

        if (mb_strlen($desc) < 5) {
            flash('erreur', 'Décrivez le problème en quelques mots.');
            redirige('employe/espace.php?onglet=creer-ticket');
        }

        $ticketCree = false;
        try {
            $pdo->beginTransaction();

            $equipement = $pdo->prepare(
                "SELECT eq.Numstatut, s.libelle_statut
                 FROM equipement eq
                 JOIN statut s ON s.Numstatut = eq.Numstatut
                 WHERE eq.Numequip = ? FOR UPDATE"
            );
            $equipement->execute([$numequip]);
            $etatEquipement = $equipement->fetch();
            if (!$etatEquipement) {
                throw new RuntimeException('Cet équipement n’existe pas dans l’inventaire.');
            }
            $ticketActif = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM demande_maintenance d
                 JOIN statut s ON s.Numstatut = d.Numstatut
                 WHERE d.Numequip = ? AND s.libelle_statut <> 'Terminee'"
            );
            $ticketActif->execute([$numequip]);
            if ((int) $ticketActif->fetchColumn() > 0) {
                throw new RuntimeException('Un ticket de maintenance est déjà en cours pour cet équipement.');
            }

            // statut "Ouverte" de la maintenance, etat cible de l'equipement
            $statutOuverteQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'Ouverte'"
            );
            $statutOuverteQuery->execute();
            $statutOuverte = (int) $statutOuverteQuery->fetchColumn();
            $etatCibleQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut WHERE type_statut = 'EQUIPEMENT' AND libelle_statut = ?"
            );
            $etatCibleQuery->execute([$horsService ? 'Hors service' : 'Maintenance']);
            $etatCible = (int) $etatCibleQuery->fetchColumn();
            if (!$statutOuverte || !$etatCible) {
                throw new RuntimeException('Les statuts nécessaires à la création du ticket ne sont pas configurés.');
            }

            $pdo->prepare(
                'INSERT INTO demande_maintenance (description_problememaint, priorite, Numequip, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?, ?)')
                ->execute([$desc, $priorite, $numequip, $moi, $statutOuverte]);
            $nummaint = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)')
                ->execute(['Demande ouverte', $nummaint, $moi, $statutOuverte]);

            $pdo->prepare('UPDATE equipement SET Numstatut = ? WHERE Numequip = ?')
                ->execute([$etatCible, $numequip]);

            $pdo->commit();
            $ticketCree = true;
            journalise('CREATION_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket créé et transmis à la file des techniciens de maintenance.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être créé. Réessayez.');
        }
        redirige('employe/espace.php?onglet=' . ($ticketCree ? 'materiel' : 'creer-ticket'));
    }

    if (!$maintenance && $action === 'fermer_ticket') {
        $nummaint = (int) ($_POST['maintenance'] ?? 0);
        try {
            $pdo->beginTransaction();
            $ticket = $pdo->prepare(
                'SELECT NumUsers, Numequip, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande || (int) $demande['NumUsers'] !== (int) $moi) {
                throw new RuntimeException('Vous ne pouvez fermer que vos propres tickets.');
            }

            $statutActuel = $pdo->prepare('SELECT libelle_statut FROM statut WHERE Numstatut = ?');
            $statutActuel->execute([(int) $demande['Numstatut']]);
            if ($statutActuel->fetchColumn() !== 'À valider') {
                throw new RuntimeException('Le ticket doit d’abord être déclaré exécuté par le technicien.');
            }

            $statutTermineQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'Terminee'"
            );
            $statutTermineQuery->execute();
            $statutTermine = (int) $statutTermineQuery->fetchColumn();
            $statutDisponibleQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'EQUIPEMENT' AND libelle_statut = 'Disponible'"
            );
            $statutDisponibleQuery->execute();
            $statutDisponible = (int) $statutDisponibleQuery->fetchColumn();
            if (!$statutTermine || !$statutDisponible) {
                throw new RuntimeException('Les statuts nécessaires à la fermeture ne sont pas configurés.');
            }

            $pdo->prepare('UPDATE demande_maintenance SET Numstatut = ? WHERE Nummaint = ?')
                ->execute([$statutTermine, $nummaint]);
            $pdo->prepare(
                'UPDATE equipement
                 SET Numstatut = ?, date_derniere_maintenance = CURRENT_DATE
                 WHERE Numequip = ?'
            )
                ->execute([$statutDisponible, (int) $demande['Numequip']]);
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?)'
            )->execute(['Travaux vérifiés et ticket fermé par l’employé déclarant.', $nummaint, $moi, $statutTermine]);
            $pdo->commit();
            journalise('FERMETURE_TICKET_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket fermé. L’équipement est de nouveau disponible.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être fermé.');
        }
        redirige('employe/espace.php?onglet=materiel');
    }

    if ($action === 'prendre_ticket') {
        $nummaint = (int) ($_POST['maintenance'] ?? 0);
        try {
            $pdo->beginTransaction();
            $ticket = $pdo->prepare(
                'SELECT NumUsers_technicien, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande) {
                throw new RuntimeException('Ticket introuvable.');
            }
            if ($demande['NumUsers_technicien'] !== null) {
                throw new RuntimeException('Ce ticket est déjà pris par un technicien.');
            }
            $statutTicketQuery = $pdo->prepare(
                "SELECT libelle_statut FROM statut WHERE Numstatut = ? AND type_statut = 'MAINTENANCE'"
            );
            $statutTicketQuery->execute([(int) $demande['Numstatut']]);
            if ($statutTicketQuery->fetchColumn() !== 'Ouverte') {
                throw new RuntimeException('Seuls les tickets disponibles dans la file commune peuvent être pris.');
            }

            $statutAttenteQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'En attente'"
            );
            $statutAttenteQuery->execute();
            $statutAttente = (int) $statutAttenteQuery->fetchColumn();
            if (!$statutAttente) {
                throw new RuntimeException('Le statut « En attente » n’est pas configuré.');
            }

            $attribution = $pdo->prepare(
                'UPDATE demande_maintenance
                 SET NumUsers_technicien = ?, Numstatut = ?
                 WHERE Nummaint = ? AND NumUsers_technicien IS NULL'
            );
            $attribution->execute([$moi, $statutAttente, $nummaint]);
            if ($attribution->rowCount() !== 1) {
                throw new RuntimeException('Le ticket n’a pas pu être attribué.');
            }
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?)'
            )->execute(['Ticket attribué au technicien et mis en attente de prise en charge.', $nummaint, $moi, $statutAttente]);
            $pdo->commit();
            journalise('PRISE_TICKET_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket pris en charge.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être pris en charge.');
        }
        redirige('employe/espace.php?onglet=maintenance');
    }

    if ($action === 'detacher_ticket') {
        $nummaint = (int) ($_POST['maintenance'] ?? 0);
        try {
            $pdo->beginTransaction();
            $ticket = $pdo->prepare(
                'SELECT NumUsers_technicien, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande || (int) $demande['NumUsers_technicien'] !== (int) $moi) {
                throw new RuntimeException('Ce ticket ne vous est pas attribué.');
            }
            $termineQuery = $pdo->prepare(
                "SELECT libelle_statut FROM statut WHERE Numstatut = ?"
            );
            $termineQuery->execute([(int) $demande['Numstatut']]);
            if (in_array($termineQuery->fetchColumn(), ['Terminee', 'À valider'], true)) {
                throw new RuntimeException('Un ticket exécuté ou fermé ne peut pas être détaché.');
            }

            $statutOuverteQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'Ouverte'"
            );
            $statutOuverteQuery->execute();
            $statutOuverte = (int) $statutOuverteQuery->fetchColumn();
            if (!$statutOuverte) {
                throw new RuntimeException('Le statut « Ouverte » n’est pas configuré.');
            }

            $detachement = $pdo->prepare(
                'UPDATE demande_maintenance
                 SET NumUsers_technicien = NULL, Numstatut = ?
                 WHERE Nummaint = ? AND NumUsers_technicien = ?'
            );
            $detachement->execute([$statutOuverte, $nummaint, $moi]);
            if ($detachement->rowCount() !== 1) {
                throw new RuntimeException('Le ticket n’a pas pu être remis dans la file commune.');
            }
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?)'
            )->execute(['Ticket remis dans la file commune pour prise en charge par un autre technicien.', $nummaint, $moi, $statutOuverte]);
            $pdo->commit();
            journalise('DETACHEMENT_TICKET_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket remis dans la file commune.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être remis dans la file commune.');
        }
        redirige('employe/espace.php?onglet=maintenance');
    }

    /* ---- faire avancer une demande de maintenance ------------------- */
    if ($action === 'avancer_maintenance') {
        $nummaint  = (int) $_POST['maintenance'];
        $nouveau   = (int) $_POST['statut'];
        $commentaire = trim($_POST['commentaire'] ?? '') ?: null;

        try {
            $pdo->beginTransaction();

            $ticket = $pdo->prepare(
                'SELECT NumUsers_technicien, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande || (int) $demande['NumUsers_technicien'] !== (int) $moi) {
                throw new RuntimeException('Seul le technicien chargé du ticket peut le mettre à jour.');
            }

            $nouveauStatutQuery = $pdo->prepare(
                "SELECT s.libelle_statut
                 FROM statut s
                 JOIN statut_maintenance sm ON sm.Numstatut = s.Numstatut
                 WHERE s.Numstatut = ?"
            );
            $nouveauStatutQuery->execute([$nouveau]);
            $statut = $nouveauStatutQuery->fetch();
            $ancienStatutQuery = $pdo->prepare('SELECT libelle_statut FROM statut WHERE Numstatut = ?');
            $ancienStatutQuery->execute([(int) $demande['Numstatut']]);
            $ancienStatut = (string) $ancienStatutQuery->fetchColumn();
            if (
                !$statut
                || !in_array($statut['libelle_statut'], $transitionsMaintenance[$ancienStatut] ?? [], true)
            ) {
                throw new RuntimeException('Cette transition de statut n’est pas autorisée.');
            }

            // L'historique est conserve : une ligne par changement d'etat
            $pdo->prepare('UPDATE demande_maintenance SET Numstatut = ? WHERE Nummaint = ?')
                ->execute([$nouveau, $nummaint]);
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)')
                ->execute([$commentaire, $nummaint, $moi, $nouveau]);

            $libelle = $statut['libelle_statut'];

            $pdo->commit();
            journalise('MAJ_MAINTENANCE', 'Nummaint=' . $nummaint . ' -> ' . $libelle);
            flash('succes', $libelle === 'À valider'
                ? 'Intervention déclarée exécutée. L’employé déclarant peut maintenant fermer le ticket.'
                : 'Demande mise à jour.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Mise à jour impossible.');
        }
        redirige('employe/espace.php?onglet=maintenance');
    }

    /* ---- repondre a une question de FAQ ----------------------------- */
    if ($action === 'repondre_faq') {
        $num     = (int) $_POST['interaction'];
        $reponse = trim($_POST['reponse'] ?? '');
      $faq->repondre($num, $reponse, $_POST['categorie'] ?? 'General');
        journalise('REPONSE_FAQ', 'NumInteraction=' . $num);
        flash('succes', $reponse !== '' ? 'Réponse publiée.' : 'Réponse enregistrée comme brouillon.');
        redirige('employe/espace.php?onglet=faq');
    }

    if ($action === 'supprimer_faq') {
      $num = (int) $_POST['interaction'];
      $faq->supprimer($num);
      journalise('SUPPRESSION_FAQ', 'NumInteraction=' . $num);
      flash('succes', 'Question supprimée.');
      redirige('employe/espace.php?onglet=faq');
    }

}

/* =====================================================================
   DONNEES
   ===================================================================== */
$aujourdhui = date('Y-m-d');

$stats = [
    'adherents' => (int) $pdo->query(
    "SELECT COUNT(*) FROM adherent WHERE Date_finadh IS NULL OR Date_finadh >= CURDATE()"
  )->fetchColumn(),
    'reservations' => (int) $pdo->query(
        "SELECT COUNT(*) FROM reservation r JOIN activite a ON a.Numactivite=r.Numactivite
         WHERE DATE(a.date_heure_debut)=CURDATE() AND r.statut_reservation<>'Annulee'"
    )->fetchColumn(),
    'creneaux' => (int) $pdo->query(
        'SELECT COUNT(*) FROM activite WHERE DATE(date_heure_debut)=CURDATE()'
    )->fetchColumn(),
    'dispo' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Disponible'")->fetchColumn(),
    'maint' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Maintenance'")->fetchColumn(),
    'hs' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Hors service'")->fetchColumn(),
];

$remplissage = $pdo->query(
  "SELECT a.*, a.titre AS libelleact,
      (SELECT COUNT(*) FROM reservation r
       WHERE r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee') AS places_prises
   FROM activite a
   WHERE DATE(a.date_heure_debut) = CURDATE()
   ORDER BY a.date_heure_debut"
)->fetchAll();

$adherents = $pdo->query(
  "SELECT ad.*, u.nomUsers, u.prenomUsers, u.mailUsers, u.telUsers,
      CASE WHEN ad.Date_finadh IS NULL OR ad.Date_finadh >= CURDATE()
         THEN 'Actif' ELSE 'Expire' END AS statut_adherent,
      (SELECT abo.libelle_abo
       FROM souscrire su
       JOIN abonnement abo ON abo.Numabo = su.Numabo
       WHERE su.NumUsers = ad.NumUsers AND su.statut_souscription = 'En cours'
       ORDER BY su.date_debut DESC LIMIT 1) AS libelle_abo
   FROM adherent ad
   JOIN utilisateur u ON u.NumUsers = ad.NumUsers
   ORDER BY u.nomUsers, u.prenomUsers"
)->fetchAll();

$statutsAdherent = [
  ['libelle_statut' => 'Actif'],
  ['libelle_statut' => 'Expire'],
];

$planningDonnees = [];
if ($onglet === 'creneaux') {
  $planningDonnees = $planning->donneesEmploye((int) $moi);
}

$equipements = $pdo->query(
  "SELECT eq.*, eq.Numequip AS num_inventaire, t.libelletypeequip,
      NULL AS nomsalle, st.libelle_statut
     FROM equipement eq
     JOIN type_equipement t ON t.Numtypeequip = eq.Numtypeequip
     JOIN statut st ON st.Numstatut = eq.Numstatut
   ORDER BY eq.Numequip"
)->fetchAll();

$maintenances = $pdo->query(
  "SELECT d.*, eq.Numequip AS num_inventaire, eq.nomequip, st.libelle_statut,
      CASE st.libelle_statut
        WHEN 'Ouverte' THEN 1
        WHEN 'En attente' THEN 2
        WHEN 'En cours' THEN 3
        WHEN 'En attente de piece' THEN 4
        WHEN 'À valider' THEN 5
        WHEN 'Terminee' THEN 6
        ELSE 0
      END AS ordre,
      u.nomUsers, u.prenomUsers, t.nomUsers AS technicien_nom, t.prenomUsers AS technicien_prenom
     FROM demande_maintenance d
     JOIN equipement eq ON eq.Numequip = d.Numequip
     JOIN statut st ON st.Numstatut = d.Numstatut
     JOIN statut_maintenance sm ON sm.Numstatut = d.Numstatut
     JOIN utilisateur u ON u.NumUsers = d.NumUsers
     LEFT JOIN utilisateur t ON t.NumUsers = d.NumUsers_technicien
   ORDER BY (d.NumUsers_technicien IS NULL) DESC, ordre, d.date_demandemaint DESC"
)->fetchAll();
$ticketsEmploye = array_values(array_filter(
  $maintenances,
  static fn(array $ticket): bool => (int) $ticket['NumUsers'] === (int) $moi
));
$equipementsAvecTicketActif = [];
foreach ($maintenances as $ticket) {
  if ($ticket['libelle_statut'] !== 'Terminee') {
    $equipementsAvecTicketActif[(int) $ticket['Numequip']] = true;
  }
}
$equipementsTicketables = array_values(array_filter(
  $equipements,
  static fn(array $equipement): bool => !isset($equipementsAvecTicketActif[(int) $equipement['Numequip']])
));

$statutsMaint = $pdo->query(
  "SELECT s.Numstatut, s.libelle_statut,
      CASE s.libelle_statut
        WHEN 'Ouverte' THEN 1
        WHEN 'En attente' THEN 2
        WHEN 'En cours' THEN 3
        WHEN 'En attente de piece' THEN 4
        WHEN 'À valider' THEN 5
        WHEN 'Terminee' THEN 6
        ELSE 0
      END AS ordre
   FROM statut_maintenance sm
   JOIN statut s ON s.Numstatut = sm.Numstatut
   ORDER BY ordre"
)->fetchAll();

$questionsFaq = $faq->questions();
$messages = $maintenance ? [] : $messageController->getAllThreads($moi);
$statutsTickets = array_count_values(array_column($maintenances, 'libelle_statut'));
$employeUser = utilisateur();
$flashMessages = flashs();
$titresOnglets = [
  'tableau' => 'Tableau de bord',
  'creneaux' => 'Créneaux',
  'adherents' => 'Adhérents',
  'creer-ticket' => 'Créer un ticket',
  'materiel' => 'Matériel',
  'maintenance' => 'Gestion des tickets',
  'faq' => 'Questions',
  'messages' => 'Messages reçus',
];
$titrePage = $maintenance ? 'Gestion des tickets' : 'Espace employé';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titrePage) ?> - <?= e(APP_NOM) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/admin.css')) ?>">
  <?php if ($onglet === 'creneaux'): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/planning.css')) ?>">
  <?php endif; ?>
</head>
<body class="admin-page employee-page">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a class="admin-brand" href="<?= e(url('employe/espace.php?onglet=' . ($maintenance ? 'maintenance' : 'tableau'))) ?>">
      <span class="admin-brand-mark">A</span>
      <span><strong>Apex Fitness</strong><small><?= $maintenance ? 'Maintenance' : 'Espace employé' ?></small></span>
    </a>
    <nav class="admin-navigation" aria-label="<?= $maintenance ? 'Menu maintenance' : 'Menu employé' ?>">
      <?php foreach ($titresOnglets as $cle => $lib): ?>
        <?php if (($maintenance && $cle !== 'maintenance') || (!$maintenance && $cle === 'maintenance')) continue; ?>
        <a href="<?= e(url('employe/espace.php?onglet=' . $cle)) ?>" <?= $onglet === $cle ? 'aria-current="page"' : '' ?>><?= e($lib) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <div class="admin-main">
    <div class="admin-topbar">
      <div>
        <p class="admin-eyebrow">ESPACE SÉCURISÉ</p>
        <h1><?= $maintenance ? 'Maintenance' : 'Espace employé' ?></h1>
      </div>
      <div class="admin-account">
        <span><?= e(($employeUser['prenomUsers'] ?? '') . ' ' . ($employeUser['nomUsers'] ?? '')) ?></span>
        <a href="<?= e(url('utilisateur/deconnexion.php')) ?>">Déconnexion</a>
      </div>
    </div>
    <?php if ($flashMessages): ?>
      <div class="admin-flashes">
        <?php foreach ($flashMessages as $message): ?>
          <p class="flash flash-<?= e($message['type']) ?>"><?= e($message['message']) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <main class="admin-content">
      <div class="admin-heading">
        <div>
          <p class="admin-eyebrow"><?= $maintenance ? 'SUIVI DES INTERVENTIONS' : 'GESTION DE LA SALLE' ?></p>
          <h2><?= e($titresOnglets[$onglet] ?? 'Espace employé') ?></h2>
          <p class="employee-page-description"><?= e([
            'tableau' => 'Retrouvez en un coup d’œil les indicateurs et l’activité du club aujourd’hui.',
            'creneaux' => 'Planifiez les séances et consultez leur occupation.',
            'adherents' => 'Consultez les informations et le statut des adhérents.',
            'creer-ticket' => 'Signalez un problème matériel à l’équipe de maintenance.',
            'materiel' => 'Consultez l’inventaire et suivez les tickets que vous avez signalés.',
            'maintenance' => 'Suivez les demandes et consignez chaque intervention.',
            'faq' => 'Répondez aux questions et gérez les contenus de la FAQ.',
            'messages' => 'Consultez les échanges et répondez aux adhérents.',
          ][$onglet] ?? '') ?></p>
        </div>
      </div>
      <?php if ($maintenance): ?>
      <div class="compteurs">
        <div class="compteur"><div class="v"><?= count($maintenances) ?></div><div class="k">tickets au total</div></div>
        <div class="compteur warn"><div class="v"><?= count(array_filter($maintenances, static fn(array $ticket): bool => $ticket['NumUsers_technicien'] === null && $ticket['libelle_statut'] !== 'Terminee')) ?></div><div class="k">dans la file commune</div></div>
        <div class="compteur"><div class="v"><?= $statutsTickets['Terminee'] ?? 0 ?></div><div class="k">tickets fermés</div></div>
      </div>
      <?php endif; ?>

<?php if ($onglet === 'tableau'): ?>

  <div class="compteurs">
    <div class="compteur"><div class="v"><?= $stats['adherents'] ?></div><div class="k">adhérents actifs</div></div>
    <div class="compteur"><div class="v"><?= $stats['reservations'] ?></div><div class="k">réservations aujourd'hui</div></div>
    <div class="compteur"><div class="v"><?= $stats['creneaux'] ?></div><div class="k">créneaux aujourd'hui</div></div>
    <div class="compteur"><div class="v"><?= $stats['dispo'] ?></div><div class="k">matériel disponible</div></div>
    <div class="compteur warn"><div class="v"><?= $stats['maint'] ?></div><div class="k">en maintenance</div></div>
    <div class="compteur bad"><div class="v"><?= $stats['hs'] ?></div><div class="k">hors service</div></div>
  </div>

  <div class="barres">
    <h2 style="font-size:17px">Taux de remplissage du jour</h2>
    <?php if (!$remplissage): ?>
      <p class="muted">Aucun créneau programmé aujourd'hui.</p>
    <?php else: foreach ($remplissage as $r):
        $pct = $r['capacite_max'] > 0 ? round($r['places_prises'] / $r['capacite_max'] * 100) : 0; ?>
      <div class="barre">
        <span><?= e($r['libelleact']) ?></span>
        <span class="piste"><span class="part" style="width:<?= $pct ?>%"></span></span>
        <span class="n"><?= (int) $r['places_prises'] ?>/<?= (int) $r['capacite_max'] ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif ($onglet === 'adherents'): ?>

  <div class="barre-outils">
    <input type="search" data-filtre="#t-adherents" placeholder="Nom, matricule ou e-mail" aria-label="Rechercher un adhérent">
    <select data-filtre="#t-adherents" data-colonne="4" aria-label="Filtrer par statut">
      <option value="">Tous les statuts</option>
      <?php foreach ($statutsAdherent as $s): ?>
        <option value="<?= e($s['libelle_statut']) ?>"><?= e($s['libelle_statut']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="table-scroll">
    <table class="tableau" id="t-adherents">
      <thead><tr><th>Matricule</th><th>Nom</th><th>Contact</th><th>Formule</th><th>Statut</th><th>Fin</th></tr></thead>
      <tbody>
      <?php foreach ($adherents as $a):
          $cls = $a['statut_adherent'] === 'Actif' ? 'tag-ok'
               : ($a['statut_adherent'] === 'Suspendu' ? 'tag-warn' : 'tag-bad'); ?>
        <tr>
          <td class="mono"><?= e($a['matriculeadh']) ?></td>
          <td><?= e($a['nomUsers'] . ' ' . $a['prenomUsers']) ?></td>
          <td><?= e($a['mailUsers']) ?><br><span class="mono"><?= e($a['telUsers']) ?></span></td>
          <td><?= e($a['libelle_abo'] ?? '—') ?></td>
          <td><span class="tag <?= $cls ?>"><?= e($a['statut_adherent']) ?></span></td>
          <td class="mono"><?= dateFr($a['Date_finadh']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p class="vide" data-vide hidden>Aucun adhérent ne correspond à cette recherche.</p>
  </div>

<?php elseif ($onglet === 'creneaux'): ?>

  <?php $mode = 'employe'; require __DIR__ . '/../planning/view/planning.php'; ?>

<?php elseif ($onglet === 'creer-ticket'): ?>

  <section class="ticket-form-card">
    <div class="ticket-form-intro">
      <span class="ticket-form-icon" aria-hidden="true">+</span>
      <div>
        <p class="admin-eyebrow">DEMANDE D’INTERVENTION</p>
        <h3>Signaler un problème matériel</h3>
        <p>Renseignez l’équipement concerné et les détails du problème. Votre demande sera transmise à l’équipe de maintenance.</p>
      </div>
    </div>
    <?php if ($equipementsTicketables): ?>
      <form method="post" class="ticket-form">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="declarer_panne">
        <div class="ticket-form-grid">
          <div class="ticket-field ticket-field-full">
            <label for="ticket-equipement">Équipement concerné <span aria-hidden="true">*</span></label>
            <select id="ticket-equipement" name="equipement" required>
              <option value="">Sélectionnez un équipement</option>
              <?php foreach ($equipementsTicketables as $equipement): ?>
                <option value="<?= (int) $equipement['Numequip'] ?>">
                  <?= e($equipement['nomequip'] . ' — ' . $equipement['num_inventaire'] . ' · ' . $equipement['libelle_statut']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small>Les appareils ayant déjà un ticket ouvert ne peuvent pas être sélectionnés.</small>
          </div>
          <div class="ticket-field ticket-field-full">
            <label for="ticket-description">Description du problème <span aria-hidden="true">*</span></label>
            <textarea id="ticket-description" name="description" placeholder="Ex. : le tapis s’arrête après quelques minutes d’utilisation…" required minlength="5" rows="5"></textarea>
            <small>Décrivez les symptômes observés pour aider le technicien à préparer son intervention.</small>
          </div>
          <div class="ticket-field">
            <label for="ticket-priorite">Niveau de priorité</label>
            <select id="ticket-priorite" name="priorite">
              <option value="Moyenne">Normale</option>
              <option value="Haute">Haute</option>
              <option value="Basse">Basse</option>
            </select>
          </div>
          <label class="ticket-out-of-service">
            <input type="checkbox" name="hors_service">
            <span><strong>Mettre l’équipement hors service</strong><small>À cocher si l’appareil présente un risque ou ne peut plus être utilisé.</small></span>
          </label>
        </div>
        <div class="ticket-form-actions">
          <span><span aria-hidden="true">*</span> Champ obligatoire</span>
          <button class="btn btn-vert" type="submit">Transmettre le ticket</button>
        </div>
      </form>
    <?php else: ?>
      <p class="vide"><?= $equipements
        ? 'Tous les équipements ont déjà un ticket en cours. Vous pourrez créer un nouveau ticket lorsqu’un appareil sera libéré.'
        : 'Aucun équipement n’est enregistré dans l’inventaire pour le moment.' ?></p>
    <?php endif; ?>
  </section>
  <p class="ticket-form-back"><a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>">Consulter l’inventaire et le suivi des tickets</a></p>

<?php elseif ($onglet === 'materiel'): ?>

  <section class="carte" style="margin-bottom:22px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
      <div>
        <h3 style="margin:0 0 6px">Mes tickets signalés</h3>
        <p style="margin:0">Suivez l’avancement des demandes envoyées à l’équipe de maintenance.</p>
      </div>
      <a class="btn btn-vert" href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>">Créer un ticket</a>
    </div>
    <?php if (!$ticketsEmploye): ?>
      <p class="vide" style="margin-top:16px">Vous n’avez pas encore créé de ticket.</p>
    <?php else: ?>
      <div class="table-scroll" style="margin-top:16px">
        <table class="tableau">
          <thead><tr><th>N°</th><th>Équipement</th><th>Problème</th><th>Priorité</th><th>Statut</th><th>Technicien</th><th>Signalé le</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($ticketsEmploye as $ticket): ?>
            <tr>
              <td class="mono"><?= (int) $ticket['Nummaint'] ?></td>
              <td><?= e($ticket['nomequip']) ?><br><span class="mono"><?= e($ticket['num_inventaire']) ?></span></td>
              <td><?= e($ticket['description_problememaint']) ?></td>
              <td><?= e($ticket['priorite']) ?></td>
              <td><span class="tag <?= in_array($ticket['libelle_statut'], ['À valider', 'Terminee'], true) ? 'tag-ok' : 'tag-warn' ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Fermé' : e($ticket['libelle_statut']) ?></span></td>
              <td><?= $ticket['technicien_nom'] ? e($ticket['technicien_prenom'] . ' ' . $ticket['technicien_nom']) : 'File commune' ?></td>
              <td class="mono"><?= dateFr($ticket['date_demandemaint']) ?></td>
              <td>
                <?php if ($ticket['libelle_statut'] === 'À valider'): ?>
                  <form method="post">
                    <?= champ_csrf() ?>
                    <input type="hidden" name="action" value="fermer_ticket">
                    <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                    <button class="btn btn-sm btn-vert" type="submit" data-confirme="Confirmer que l’intervention est satisfaisante et fermer ce ticket ?">Confirmer et fermer</button>
                  </form>
                <?php elseif ($ticket['libelle_statut'] === 'Terminee'): ?>
                  <span class="tag tag-ok">Fermé</span>
                <?php else: ?>
                  <span class="tag tag-mute">En attente du technicien</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <h3 style="margin:28px 0 14px">Inventaire du matériel</h3>

  <div class="barre-outils">
    <input type="search" data-filtre="#t-materiel" placeholder="Nom, marque ou n° d'inventaire" aria-label="Rechercher du matériel">
    <select data-filtre="#t-materiel" data-colonne="4" aria-label="Filtrer par état">
      <option value="">Tous les états</option>
      <option value="Disponible">Disponible</option>
      <option value="Maintenance">Maintenance</option>
      <option value="Hors service">Hors service</option>
    </select>
  </div>

  <div class="table-scroll">
    <table class="tableau" id="t-materiel">
      <thead><tr><th>N° inventaire</th><th>Équipement</th><th>Type</th><th>Salle</th><th>État</th><th>Dernière maintenance</th></tr></thead>
      <tbody>
      <?php foreach ($equipements as $eq):
          $cls = $eq['libelle_statut'] === 'Disponible' ? 'tag-ok'
               : ($eq['libelle_statut'] === 'Maintenance' ? 'tag-warn' : 'tag-bad'); ?>
        <tr>
          <td class="mono"><?= e($eq['num_inventaire']) ?></td>
          <td><?= e($eq['nomequip']) ?><br><span class="mono"><?= e($eq['marque'] . ' ' . $eq['modele']) ?></span></td>
          <td><?= e($eq['libelletypeequip']) ?></td>
          <td><?= e($eq['nomsalle'] ?? '—') ?></td>
          <td><span class="tag <?= $cls ?>"><?= e($eq['libelle_statut']) ?></span></td>
          <td class="mono"><?= dateFr($eq['date_derniere_maintenance']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p class="vide" data-vide hidden>Aucun équipement ne correspond à cette recherche.</p>
  </div>

<?php elseif ($onglet === 'maintenance'): ?>

  <?php if (!$maintenances): ?>
    <div class="vide">Aucune demande de maintenance. Les pannes déclarées depuis l'onglet Matériel apparaissent ici.</div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="tableau">
        <thead><tr><th>N°</th><th>Équipement</th><th>Problème</th><th>Priorité</th><th>Signalée par</th><th>Statut</th><th>Responsable</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($maintenances as $m): ?>
          <tr>
            <td class="mono"><?= (int) $m['Nummaint'] ?></td>
            <td><?= e($m['num_inventaire']) ?><br><span class="mono"><?= e($m['nomequip']) ?></span></td>
            <td><?= e($m['description_problememaint']) ?></td>
            <td><span class="tag <?= $m['priorite'] === 'Haute' ? 'tag-bad' : 'tag-mute' ?>"><?= e($m['priorite']) ?></span></td>
            <td><?= e($m['prenomUsers'] . ' ' . $m['nomUsers']) ?><br>
                <span class="mono"><?= dateFr($m['date_demandemaint']) ?></span></td>
            <td><span class="tag <?= $m['libelle_statut'] === 'Terminee' ? 'tag-ok' : ($m['libelle_statut'] === 'En attente de piece' ? 'tag-bad' : 'tag-warn') ?>">
                <?= $m['libelle_statut'] === 'Terminee' ? 'Fermé' : e($m['libelle_statut']) ?></span></td>
            <td>
              <?php if ($m['technicien_nom']): ?>
                <?= e($m['technicien_prenom'] . ' ' . $m['technicien_nom']) ?>
                <?= (int) $m['NumUsers_technicien'] === (int) $moi ? '<br><span class="tag tag-ok">Vous</span>' : '' ?>
              <?php else: ?>
                <span class="tag tag-mute">File commune</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($m['NumUsers_technicien'] === null && $m['libelle_statut'] === 'Ouverte'): ?>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="prendre_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $m['Nummaint'] ?>">
                  <button class="btn btn-sm btn-vert" type="submit">Prendre le ticket</button>
                </form>
              <?php elseif (
                (int) $m['NumUsers_technicien'] === (int) $moi
                && !in_array($m['libelle_statut'], ['Terminee', 'À valider'], true)
              ): ?>
                <form method="post" style="display:grid;gap:6px;min-width:230px">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="avancer_maintenance">
                  <input type="hidden" name="maintenance" value="<?= (int) $m['Nummaint'] ?>">
                  <select name="statut" style="padding:6px 9px;border:1px solid #DCDAD2;border-radius:3px">
                    <?php foreach ($statutsMaint as $s):
                        if (!in_array($s['libelle_statut'], $transitionsMaintenance[$m['libelle_statut']] ?? [], true)) continue; ?>
                      <option value="<?= (int) $s['Numstatut'] ?>"><?= e($s['libelle_statut']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="commentaire" placeholder="Commentaire d'intervention"
                         style="padding:6px 9px;border:1px solid #DCDAD2;border-radius:3px">
                  <button class="btn btn-sm btn-ghost" type="submit">Enregistrer l’intervention</button>
                </form>
                <form method="post" style="margin-top:7px">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="detacher_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $m['Nummaint'] ?>">
                  <button class="btn btn-sm btn-ghost" type="submit" data-confirme="Remettre ce ticket dans la file commune pour qu’un autre technicien le prenne ?">Remettre dans la file</button>
                </form>
              <?php elseif ($m['libelle_statut'] === 'Terminee'): ?>
                <span class="tag tag-mute">Fermé</span>
              <?php elseif ($m['libelle_statut'] === 'À valider'): ?>
                <span class="tag tag-warn">En attente de validation employé</span>
              <?php elseif ((int) $m['NumUsers_technicien'] === (int) $moi): ?>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="detacher_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $m['Nummaint'] ?>">
                  <button class="btn btn-sm btn-ghost" type="submit" data-confirme="Remettre ce ticket dans la file commune pour qu’un autre technicien le prenne ?">Remettre dans la file</button>
                </form>
              <?php else: ?>
                <span class="tag tag-mute">Pris en charge</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="note">Chaque changement de statut crée une ligne dans <code>intervention</code> :
       l'historique demandé par le cahier des charges est conservé, y compris le commentaire et l'auteur.</p>
  <?php endif; ?>

<?php elseif ($onglet === 'messages'): ?>
  <?php require __DIR__ . '/../message_contact/view/admin-messages.php'; ?>

<?php else: ?>

  <?php if (!$questionsFaq): ?>
    <div class="vide">Aucune question posée pour le moment.</div>
  <?php else: foreach ($questionsFaq as $q): ?>
    <article class="carte" style="margin-bottom:14px">
      <p class="mono">
        <?= dateFr($q['date_interaction'], true) ?> &middot;
        <?= $q['nomUsers'] ? e($q['prenomUsers'] . ' ' . $q['nomUsers']) : 'Rédaction interne' ?> &middot;
        <span class="tag <?= $q['statut_faq'] === 'Publiee' ? 'tag-ok' : 'tag-warn' ?>"><?= e($q['statut_faq']) ?></span>
      </p>
      <h3><?= e($q['question']) ?></h3>
      <form method="post" style="display:grid;gap:10px;margin-top:10px">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="repondre_faq">
        <input type="hidden" name="interaction" value="<?= (int) $q['NumInteraction'] ?>">
        <textarea name="reponse" placeholder="Rédigez la réponse publiée sur la page FAQ"
                  style="min-height:90px;padding:9px 11px;border:1px solid #DCDAD2;border-radius:3px"><?= e($q['reponse'] ?? '') ?></textarea>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <input type="text" name="categorie" value="<?= e($q['categorie']) ?>" placeholder="Catégorie"
                 style="padding:8px 11px;border:1px solid #DCDAD2;border-radius:3px">
          <button class="btn btn-sm btn-vert" type="submit">Publier la réponse</button>
        </div>
      </form>
      <form method="post" style="margin-top:8px">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="supprimer_faq">
        <input type="hidden" name="interaction" value="<?= (int) $q['NumInteraction'] ?>">
        <button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer cette question ?">Supprimer</button>
      </form>
    </article>
  <?php endforeach; endif; ?>

<?php endif; ?>

    </main>
  </div>
</div>
<?php if ($onglet === 'creneaux'):
  foreach (PlanningController::FC_SCRIPTS as $src): ?>
    <script src="<?= e($src) ?>"></script>
  <?php endforeach; ?>
  <script src="<?= e(url('assets/js/planning.js')) ?>"></script>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
