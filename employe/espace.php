<?php
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/../faq/controller/FaqController.php';
require_once __DIR__ . '/../message_contact/controller/MessageContactController.php';
require_once __DIR__ . '/../planning/controller/planning.php';
require_once __DIR__ . '/service/EquipmentInventorySchema.php';
require_once __DIR__ . '/../utilisateur/model/utilisateur.php';
exige_role('EMPLOYE');

$moi    = utilisateur()['NumUsers'];
$onglet = $_GET['onglet'] ?? 'tableau';
$pdo    = db();
$faq = new FaqController();
$maintenance = est_technicien_maintenance();
$accueil = fonction_employe((int) $moi) === 'Accueil';
$messageController = new MessageController();
if ($maintenance && $onglet === 'materiel') EquipmentInventorySchema::ensure($pdo);
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
if ($maintenance && !in_array($onglet, ['tableau', 'maintenance', 'materiel', 'profil'], true)) {
  redirige('employe/espace.php?onglet=tableau');
}
if ($maintenance && $onglet === 'profil') {
  redirige('employe/profil.php');
}
if (!$maintenance && $onglet === 'maintenance') {
  redirige('employe/espace.php?onglet=tableau');
}
if (!$maintenance && !$accueil && $onglet === 'suivi-tickets') {
  redirige('employe/espace.php?onglet=tableau');
}
if (!$maintenance && !$accueil && $onglet === 'messages') {
  redirige('employe/espace.php?onglet=tableau');
}
if (!$maintenance && !$accueil && $onglet === 'reservations') {
  redirige('employe/espace.php?onglet=tableau');
}

/* =====================================================================
   ACTIONS
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

  if ($action === 'devenir_adherent') {
    if (!$maintenance && !$accueil) {
      http_response_code(403);
      flash('erreur', 'Cette option est réservée aux employés d’accueil et de maintenance.');
      redirige('employe/espace.php?onglet=tableau');
    }

    try {
      $adherentQuery = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
      $adherentQuery->execute([(int) $moi]);
      if (!$adherentQuery->fetchColumn()) {
        Utilisateur::ajouterAdherent((int) $moi);
      }

      $_SESSION['user']['roles'] = roles_de((int) $moi);
      journalise('ACTIVATION_ESPACE_ADHERENT', 'Espace membre');
      flash('succes', 'Votre espace membre est activé. Votre compte employé et ses accès sont conservés.');
      redirige('adherent/espace.php');
    } catch (Throwable $exception) {
      error_log('[Activation espace adhérent] ' . $exception->getMessage());
      flash('erreur', 'L’activation de votre espace membre a échoué. Réessayez dans quelques instants.');
      redirige('employe/espace.php?onglet=tableau');
    }
  }

  $actionsTechnicien = [
    'avancer_maintenance', 'prendre_ticket', 'detacher_ticket', 'modifier_materiel',
    'declarer_utilisation_materiel', 'commander_materiel', 'commenter_ticket',
  ];
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

  if ($maintenance && $action === 'commenter_ticket') {
    $nummaint = filter_var($_POST['maintenance'] ?? null, FILTER_VALIDATE_INT);
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
    try {
      if (!$nummaint || $nummaint < 1 || $commentaire === '' || mb_strlen($commentaire) > 2000) {
        throw new RuntimeException('Le commentaire est obligatoire et ne doit pas dépasser 2 000 caractères.');
      }
      $pdo->beginTransaction();
      $ticketQuery = $pdo->prepare(
        'SELECT NumUsers_technicien, Numstatut FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
      );
      $ticketQuery->execute([$nummaint]);
      $ticket = $ticketQuery->fetch(PDO::FETCH_ASSOC);
      if (!$ticket || (int) $ticket['NumUsers_technicien'] !== (int) $moi) {
        throw new RuntimeException('Seul le technicien chargé du ticket peut ajouter un commentaire.');
      }
      $statutQuery = $pdo->prepare(
        "SELECT libelle_statut FROM statut
         WHERE Numstatut = ? AND type_statut = 'MAINTENANCE'"
      );
      $statutQuery->execute([(int) $ticket['Numstatut']]);
      $statutActuel = $statutQuery->fetchColumn();
      if (!$statutActuel || in_array($statutActuel, ['Terminee', 'À valider'], true)) {
        throw new RuntimeException('Ce ticket ne peut plus recevoir de commentaire.');
      }
      $pdo->prepare(
        'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)'
      )->execute([$commentaire, $nummaint, (int) $moi, (int) $ticket['Numstatut']]);
      $pdo->commit();
      journalise('COMMENTAIRE_MAINTENANCE', 'Nummaint=' . $nummaint);
      flash('succes', 'Commentaire ajouté au suivi du ticket.');
    } catch (RuntimeException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash('erreur', $exception->getMessage());
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('Ajout de commentaire au ticket impossible : ' . $exception->getMessage());
      flash('erreur', 'Le commentaire n’a pas pu être ajouté.');
    }
    redirige('employe/espace.php?onglet=maintenance&ticket=' . (int) $nummaint);
  }

  if ($maintenance && $action === 'declarer_utilisation_materiel') {
    $idMateriel = filter_var($_POST['materiel'] ?? null, FILTER_VALIDATE_INT);
    $quantite = filter_var($_POST['quantite'] ?? null, FILTER_VALIDATE_INT);
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
    try {
      if (!$idMateriel || $idMateriel < 1 || !$quantite || $quantite < 1) {
        throw new RuntimeException('Indiquez un matériel et une quantité valides.');
      }
      if (mb_strlen($commentaire) > 255) {
        throw new RuntimeException('Le commentaire ne doit pas dépasser 255 caractères.');
      }
      $pdo->beginTransaction();
      $materielQuery = $pdo->prepare('SELECT stock_actuel FROM equipement WHERE Numequip = ? FOR UPDATE');
      $materielQuery->execute([$idMateriel]);
      $materiel = $materielQuery->fetch(PDO::FETCH_ASSOC);
      if (!$materiel) {
        throw new RuntimeException('Ce matériel n’existe plus dans l’inventaire.');
      }
      if ((int) $materiel['stock_actuel'] < $quantite) {
        throw new RuntimeException('Le stock disponible est insuffisant pour cette utilisation.');
      }
      $pdo->prepare('UPDATE equipement SET stock_actuel = stock_actuel - ? WHERE Numequip = ?')
        ->execute([$quantite, $idMateriel]);
      $pdo->prepare(
        "INSERT INTO mouvement_stock (Numequip, NumUsers, type_mouvement, quantite, commentaire)
         VALUES (?, ?, 'SORTIE', ?, ?)"
      )->execute([$idMateriel, (int) $moi, $quantite, $commentaire ?: null]);
      $pdo->commit();
      journalise('UTILISATION_MATERIEL', 'Numequip=' . $idMateriel . ', quantite=' . $quantite);
      flash('succes', 'L’utilisation du matériel a été enregistrée.');
    } catch (RuntimeException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash('erreur', $exception->getMessage());
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('Enregistrement de l’utilisation du matériel impossible : ' . $exception->getMessage());
      flash('erreur', 'L’utilisation n’a pas pu être enregistrée. Réessayez.');
    }
    redirige('employe/espace.php?onglet=materiel&materiel=' . (int) $idMateriel);
  }

  if ($maintenance && $action === 'commander_materiel') {
    $idMateriel = filter_var($_POST['materiel'] ?? null, FILTER_VALIDATE_INT);
    $quantite = filter_var($_POST['quantite'] ?? null, FILTER_VALIDATE_INT);
    $messageCommande = trim((string) ($_POST['message'] ?? ''));
    try {
      if (!$idMateriel || $idMateriel < 1 || !$quantite || $quantite < 1 || $quantite > 10000) {
        throw new RuntimeException('Indiquez un matériel et une quantité entre 1 et 10 000.');
      }
      if (mb_strlen($messageCommande) > 500) {
        throw new RuntimeException('Le message ne doit pas dépasser 500 caractères.');
      }
      $materielQuery = $pdo->prepare('SELECT 1 FROM equipement WHERE Numequip = ?');
      $materielQuery->execute([$idMateriel]);
      if (!$materielQuery->fetchColumn()) {
        throw new RuntimeException('Ce matériel n’existe plus dans l’inventaire.');
      }
      $pdo->prepare(
        "INSERT INTO commande_materiel (Numequip, NumUsers, quantite, message)
         VALUES (?, ?, ?, ?)"
      )->execute([$idMateriel, (int) $moi, $quantite, $messageCommande ?: null]);
      $idCommande = (int) $pdo->lastInsertId();
      journalise('DEMANDE_COMMANDE_MATERIEL', 'Numcommande=' . $idCommande);
      flash('succes', 'La demande de commande a été envoyée à l’administrateur.');
    } catch (RuntimeException $exception) {
      flash('erreur', $exception->getMessage());
    } catch (Throwable $exception) {
      error_log('Création de la demande de commande impossible : ' . $exception->getMessage());
      flash('erreur', 'La demande de commande n’a pas pu être envoyée. Réessayez.');
    }
    redirige('employe/espace.php?onglet=materiel&commande=1');
  }

  if ($maintenance && $action === 'modifier_materiel') {
    require_once __DIR__ . '/service/EquipmentImageStorage.php';
    $imageStorage = new EquipmentImageStorage();
    $imageEnregistree = null;
    $ancienneImage = null;
    $idMateriel = filter_var($_POST['materiel'] ?? null, FILTER_VALIDATE_INT);
    try {
      if (!$idMateriel || $idMateriel < 1) {
        throw new RuntimeException('Le matériel sélectionné est invalide.');
      }

      $nom = trim((string) ($_POST['nom'] ?? ''));
      $marque = trim((string) ($_POST['marque'] ?? ''));
      $modele = trim((string) ($_POST['modele'] ?? ''));
      $idType = filter_var($_POST['categorie'] ?? null, FILTER_VALIDATE_INT);
      $idStatut = filter_var($_POST['statut'] ?? null, FILTER_VALIDATE_INT);
      $seuilAlerte = filter_var($_POST['seuil_alerte'] ?? null, FILTER_VALIDATE_INT);
      if ($nom === '' || mb_strlen($nom) > 80) {
        throw new RuntimeException('Le nom est obligatoire (80 caractères maximum).');
      }
      if (mb_strlen($marque) > 60 || mb_strlen($modele) > 60) {
        throw new RuntimeException('La marque et le modèle ne doivent pas dépasser 60 caractères.');
      }
      if (!$idType || !$idStatut || $seuilAlerte === false || $seuilAlerte < 0 || $seuilAlerte > 10000) {
        throw new RuntimeException('Choisissez une catégorie, un statut et un seuil d’alerte valides.');
      }

      $pdo->beginTransaction();
      $materielQuery = $pdo->prepare('SELECT image_equipement FROM equipement WHERE Numequip = ? FOR UPDATE');
      $materielQuery->execute([$idMateriel]);
      $materielActuel = $materielQuery->fetch(PDO::FETCH_ASSOC);
      if (!$materielActuel) {
        throw new RuntimeException('Ce matériel n’existe plus dans l’inventaire.');
      }
      $ancienneImage = $materielActuel['image_equipement'];

      $categorieQuery = $pdo->prepare('SELECT 1 FROM type_equipement WHERE Numtypeequip = ?');
      $categorieQuery->execute([$idType]);
      $statutQuery = $pdo->prepare("SELECT libelle_statut FROM statut WHERE Numstatut = ? AND type_statut = 'EQUIPEMENT'");
      $statutQuery->execute([$idStatut]);
      $libelleStatut = $statutQuery->fetchColumn();
      if (!$categorieQuery->fetchColumn() || $libelleStatut === false) {
        throw new RuntimeException('La catégorie ou le statut sélectionné n’est pas valide.');
      }
      if ($libelleStatut === 'Disponible') {
        $ticketActif = $pdo->prepare(
          "SELECT COUNT(*)
           FROM demande_maintenance d
           JOIN statut s ON s.Numstatut = d.Numstatut AND s.type_statut = 'MAINTENANCE'
           WHERE d.Numequip = ? AND s.libelle_statut <> 'Terminee'"
        );
        $ticketActif->execute([$idMateriel]);
        if ((int) $ticketActif->fetchColumn() > 0) {
          throw new RuntimeException('Un matériel avec un ticket de maintenance actif ne peut pas être marqué disponible.');
        }
      }

      if (isset($_FILES['image_equipement'])) {
        $imageEnregistree = $imageStorage->store($_FILES['image_equipement']);
      }
      $imageFinale = $imageEnregistree ?? $ancienneImage;
      $updateMateriel = $pdo->prepare(
        'UPDATE equipement
         SET nomequip = ?, marque = ?, modele = ?, image_equipement = ?, Numtypeequip = ?, Numstatut = ?, seuil_alerte = ?
         WHERE Numequip = ?'
      );
      $updateMateriel->execute([
        $nom,
        $marque !== '' ? $marque : null,
        $modele !== '' ? $modele : null,
        $imageFinale,
        $idType,
        $idStatut,
        $seuilAlerte,
        $idMateriel,
      ]);
      $pdo->commit();

      if ($imageEnregistree !== null && $ancienneImage !== $imageEnregistree) {
        $imageStorage->remove($ancienneImage);
      }
      journalise('MODIFICATION_EQUIPEMENT', 'Numequip=' . $idMateriel);
      flash('succes', 'Le matériel a été modifié.');
    } catch (RuntimeException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $imageStorage->remove($imageEnregistree);
      flash('erreur', $exception->getMessage());
    } catch (PDOException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $imageStorage->remove($imageEnregistree);
      error_log('Modification du matériel impossible : ' . $exception->getMessage());
      flash('erreur', 'Le matériel n’a pas pu être modifié. Réessayez.');
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $imageStorage->remove($imageEnregistree);
      error_log('Erreur inattendue lors de la modification du matériel : ' . $exception->getMessage());
      flash('erreur', 'Une erreur inattendue a empêché la modification du matériel.');
    }

    redirige('employe/espace.php?onglet=materiel&materiel=' . (int) $idMateriel);
  }

  if (!$accueil && in_array($action, ['repondre_message', 'supprimer_message'], true)) {
    http_response_code(403);
    flash('erreur', 'La messagerie des adhérents est réservée à l’employé d’accueil.');
    redirige('employe/espace.php?onglet=tableau');
  }

  if ($onglet === 'reservations' && $accueil && in_array($action, ['accueil_ajouter_reservation', 'accueil_annuler_reservation', 'accueil_modifier_reservation'], true)) {
    $retourReservations = 'employe/espace.php?onglet=reservations';
    try {
      if ($action === 'accueil_annuler_reservation') {
        $idReservation = (int) ($_POST['reservation'] ?? 0);
        $pdo->beginTransaction();
        $reservation = $pdo->prepare(
          'SELECT r.statut_reservation, a.date_heure_debut
             FROM reservation r
             JOIN activite a ON a.Numactivite = r.Numactivite
            WHERE r.Numres = ?
            FOR UPDATE'
        );
        $reservation->execute([$idReservation]);
        $reservationCourante = $reservation->fetch(PDO::FETCH_ASSOC);
        if (!$reservationCourante || $reservationCourante['statut_reservation'] === 'Annulee') {
          throw new RuntimeException('Cette réservation est introuvable ou déjà annulée.');
        }
        if (strtotime($reservationCourante['date_heure_debut']) < time()) {
          throw new RuntimeException('Une réservation passée ne peut pas être annulée.');
        }
        $pdo->prepare("UPDATE reservation SET statut_reservation='Annulee' WHERE Numres=?")->execute([$idReservation]);
        $pdo->commit();
        flash('succes', 'Réservation annulée.');
      } elseif ($action === 'accueil_ajouter_reservation') {
        $idAdherent = (int) ($_POST['adherent'] ?? 0);
        $idActivite = (int) ($_POST['activite'] ?? 0);
        $pdo->beginTransaction();
        $cours = $pdo->prepare("SELECT a.capacite_max,a.date_heure_debut,(SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation <> 'Annulee') AS inscrits FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle WHERE a.Numactivite=? AND s.etatsalle='Ouverte' FOR UPDATE");
        $cours->execute([$idActivite]);
        $detailsCours = $cours->fetch(PDO::FETCH_ASSOC);
        if (!$detailsCours || strtotime($detailsCours['date_heure_debut']) < time()) throw new RuntimeException('Choisissez un cours à venir dans une salle disponible.');
        if ((int) $detailsCours['inscrits'] >= (int) $detailsCours['capacite_max']) throw new RuntimeException('Ce cours est complet.');
        $adherentExiste = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers=?');
        $adherentExiste->execute([$idAdherent]);
        if (!$adherentExiste->fetchColumn()) throw new RuntimeException('Choisissez un adhérent valide.');
        $existante = $pdo->prepare('SELECT Numres,statut_reservation FROM reservation WHERE NumUsers=? AND Numactivite=? FOR UPDATE');
        $existante->execute([$idAdherent, $idActivite]);
        $reservationExistante = $existante->fetch(PDO::FETCH_ASSOC);
        if ($reservationExistante && $reservationExistante['statut_reservation'] !== 'Annulee') throw new RuntimeException('Cet adhérent possède déjà une réservation pour ce cours.');
        if ($reservationExistante) $pdo->prepare("UPDATE reservation SET statut_reservation='Reservee',date_heure_reservation=NOW() WHERE Numres=?")->execute([(int) $reservationExistante['Numres']]);
        else $pdo->prepare('INSERT INTO reservation (NumUsers,Numactivite) VALUES (?,?)')->execute([$idAdherent, $idActivite]);
        $pdo->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$idAdherent, $idActivite]);
        $pdo->commit();
        flash('succes', 'Réservation ajoutée.');
      } else {
        $idReservation = (int) ($_POST['reservation'] ?? 0);
        $idActivite = (int) ($_POST['activite'] ?? 0);
        $pdo->beginTransaction();
        $reservation = $pdo->prepare(
          'SELECT r.NumUsers, r.statut_reservation, a.date_heure_debut
             FROM reservation r
             JOIN activite a ON a.Numactivite = r.Numactivite
            WHERE r.Numres = ?
            FOR UPDATE'
        );
        $reservation->execute([$idReservation]);
        $reservationCourante = $reservation->fetch(PDO::FETCH_ASSOC);
        if (!$reservationCourante || $reservationCourante['statut_reservation'] === 'Annulee') throw new RuntimeException('Cette réservation ne peut plus être modifiée.');
        if (strtotime($reservationCourante['date_heure_debut']) < time()) throw new RuntimeException('Une réservation passée ne peut pas être modifiée.');
        $cours = $pdo->prepare("SELECT a.capacite_max,a.date_heure_debut,(SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation <> 'Annulee' AND r.Numres<>?) AS inscrits FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle WHERE a.Numactivite=? AND s.etatsalle='Ouverte' FOR UPDATE");
        $cours->execute([$idReservation, $idActivite]);
        $detailsCours = $cours->fetch(PDO::FETCH_ASSOC);
        if (!$detailsCours || strtotime($detailsCours['date_heure_debut']) < time()) throw new RuntimeException('Choisissez un cours à venir dans une salle disponible.');
        if ((int) $detailsCours['inscrits'] >= (int) $detailsCours['capacite_max']) throw new RuntimeException('Ce cours est complet.');
        $doublon = $pdo->prepare("SELECT 1 FROM reservation WHERE NumUsers=? AND Numactivite=? AND Numres<>? AND statut_reservation<>'Annulee'");
        $doublon->execute([(int) $reservationCourante['NumUsers'], $idActivite, $idReservation]);
        if ($doublon->fetchColumn()) throw new RuntimeException('Cet adhérent est déjà inscrit à ce cours.');
        $pdo->prepare('UPDATE reservation SET Numactivite=? WHERE Numres=?')->execute([$idActivite, $idReservation]);
        $pdo->commit();
        flash('succes', 'Réservation déplacée vers le nouveau cours.');
      }
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash('erreur', $exception->getMessage());
    }
    redirige($retourReservations . '#liste-reservations');
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
        redirige('employe/espace.php?onglet=' . ($ticketCree && $accueil ? 'suivi-tickets' : ($ticketCree ? 'materiel' : 'creer-ticket')));
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
        redirige('employe/espace.php?onglet=' . ($accueil ? 'suivi-tickets&ticket=' . $nummaint : 'materiel'));
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

$reservationsAccueil = [];
$activitesDisponiblesAccueil = [];
if ($onglet === 'reservations' && $accueil) {
  $stmtReservationsAccueil = $pdo->query(
    "SELECT r.Numres AS id_ligne, r.statut_reservation, u.NumUsers, u.prenomUsers, u.nomUsers, u.mailUsers, u.telUsers,
            ad.matriculeadh, a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
            a.capacite_max, s.Numsalle, s.nomsalle, coach.prenomUsers AS coach_prenom, coach.nomUsers AS coach_nom,
            (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises,
            (SELECT ab.libelle_abo FROM souscrire su JOIN abonnement ab ON ab.Numabo=su.Numabo WHERE su.NumUsers=u.NumUsers AND su.statut_souscription='En cours' ORDER BY su.date_debut DESC LIMIT 1) AS libelle_abo,
            (SELECT COUNT(*) FROM reservation ra JOIN activite aa ON aa.Numactivite=ra.Numactivite WHERE ra.NumUsers=u.NumUsers AND ra.statut_reservation<>'Annulee' AND aa.date_heure_debut>=NOW()) AS reservations_a_venir
       FROM reservation r JOIN adherent ad ON ad.NumUsers=r.NumUsers JOIN utilisateur u ON u.NumUsers=ad.NumUsers
       JOIN activite a ON a.Numactivite=r.Numactivite JOIN salle s ON s.Numsalle=a.Numsalle
       JOIN utilisateur coach ON coach.NumUsers=a.NumUsers
      ORDER BY a.date_heure_debut DESC, u.nomUsers, u.prenomUsers"
  );
  $reservationsAccueil = $stmtReservationsAccueil->fetchAll(PDO::FETCH_ASSOC);
  $activitesDisponiblesAccueil = $pdo->query(
    "SELECT a.Numactivite,a.titre,a.type_activite,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,
            (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation<>'Annulee') AS places_prises
       FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle WHERE a.date_heure_debut>=NOW() AND s.etatsalle='Ouverte' ORDER BY a.date_heure_debut LIMIT 200"
  )->fetchAll(PDO::FETCH_ASSOC);
}

$equipements = $pdo->query(
  "SELECT eq.*, eq.Numequip AS num_inventaire, t.libelletypeequip,
      NULL AS nomsalle, st.libelle_statut, f.nomfourn
     FROM equipement eq
     JOIN type_equipement t ON t.Numtypeequip = eq.Numtypeequip
     JOIN statut st ON st.Numstatut = eq.Numstatut
     LEFT JOIN fournisseur f ON f.NumUsers = eq.NumUsers_fourn
   ORDER BY eq.Numequip"
)->fetchAll();

$maintenances = $pdo->query(
  "SELECT d.*, eq.Numequip AS num_inventaire, eq.nomequip, st.libelle_statut, te.libelletypeequip,
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
     JOIN type_equipement te ON te.Numtypeequip = eq.Numtypeequip
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
$messages = $accueil ? $messageController->getAllThreads((int) $moi) : [];
$statutsTickets = array_count_values(array_column($maintenances, 'libelle_statut'));
$employeUser = utilisateur();
$flashMessages = flashs();
$ticketsResolusCetteSemaine = 0;
if ($maintenance) {
  $ticketsResolusCetteSemaine = (int) $pdo->query(
    "SELECT COUNT(DISTINCT i.Nummaint)
       FROM intervention i
       JOIN statut s ON s.Numstatut = i.Numstatut
      WHERE s.libelle_statut = 'Terminee'
        AND i.date_intervention >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)"
  )->fetchColumn();
}
$ticketsOuvertsMaintenance = count(array_filter(
  $maintenances,
  static fn(array $ticket): bool => $ticket['libelle_statut'] !== 'Terminee'
));
$ticketsEnAttenteMaintenance = count(array_filter(
  $maintenances,
  static fn(array $ticket): bool => in_array($ticket['libelle_statut'], ['Ouverte', 'En attente', 'En attente de piece', 'À valider'], true)
));
$ticketsResolus30Jours = 0;
$activiteMaintenanceRecente = [];
$equipementsMaintenanceAControler = [];
if ($maintenance) {
  $ticketsResolus30Jours = (int) $pdo->query(
    "SELECT COUNT(DISTINCT i.Nummaint)
       FROM intervention i
       JOIN statut s ON s.Numstatut = i.Numstatut
      WHERE s.libelle_statut = 'Terminee'
        AND i.date_intervention >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
  )->fetchColumn();
  $activiteMaintenanceRecente = $pdo->query(
    'SELECT i.Nummaint, i.date_intervention, i.commentaire, s.libelle_statut,
            u.prenomUsers, u.nomUsers
       FROM intervention i
       JOIN statut s ON s.Numstatut = i.Numstatut
       JOIN utilisateur u ON u.NumUsers = i.NumUsers
      ORDER BY i.date_intervention DESC
      LIMIT 5'
  )->fetchAll();
  $equipementsMaintenanceAControler = array_values(array_filter(
    $equipements,
    static fn(array $equipement): bool => $equipement['libelle_statut'] !== 'Disponible'
  ));
}
$prenomTechnicien = trim((string) ($employeUser['prenomUsers'] ?? ''));
$titresOnglets = [
  'tableau' => 'Tableau de bord',
  'creneaux' => $accueil ? 'Planning des activités' : 'Créneaux',
  'reservations' => 'Réservations',
  'adherents' => 'Adhérents',
  'creer-ticket' => 'Créer un ticket',
  'materiel' => 'Matériel',
  'maintenance' => 'Gestion des tickets',
  'suivi-tickets' => 'Suivi des tickets',
  'faq' => 'Questions',
  'messages' => 'Messages reçus',
];
$titrePage = $maintenance ? 'Maintenance' : 'Espace employé';
$employeeNavigation = $maintenance
  ? [
      'tableau' => ['Tableau de bord', 'home'],
      'maintenance' => ['Mes tickets', 'tool'],
      'materiel' => ['Matériel', 'building'],
      'profil' => ['Mon profil', 'user'],
    ]
  : [
      'tableau' => ['Tableau de bord', 'home'],
      'creneaux' => [$accueil ? 'Planning' : 'Créneaux', 'calendar'],
      ...($accueil ? ['reservations' => ['Réservations', 'calendar']] : []),
      'adherents' => ['Adhérents', 'users'],
      'creer-ticket' => ['Créer un ticket', 'plus'],
      ...($accueil ? ['suivi-tickets' => ['Suivi des tickets', 'tool']] : []),
      ...(!$accueil ? ['materiel' => ['Matériel', 'tool']] : []),
      'faq' => ['Questions', 'help'],
      'messages' => ['Messages reçus', 'message'],
    ];
if (!$accueil) {
  unset($employeeNavigation['messages']);
}
$sidebarItems = [];
foreach ($employeeNavigation as $id => [$label, $icon]) {
  $sidebarItems[] = [
    'id' => $id,
    'label' => $label,
    'icon' => $icon,
    'href' => $id === 'profil'
      ? url('employe/profil.php')
      : url('employe/espace.php?onglet=' . $id),
  ];
}
$sidebarActive = $onglet;
$sidebarHome = url('employe/espace.php?onglet=tableau');
$sidebarBrand = null;
$sidebarLabel = $maintenance ? 'Navigation maintenance' : 'Navigation employé';
$sidebarSubtitle = $maintenance ? 'Maintenance' : 'Espace employé';
$sidebarMemberEnabled = $maintenance || $accueil;
$sidebarMemberUnlocked = a_role('ADHERENT');
$sidebarMemberAction = url('employe/espace.php');
$topbarUser = utilisateur();
$topbarTitle = $maintenance ? 'Maintenance' : 'Espace employé';
$topbarContext = 'Espace sécurisé';
$topbarClass = 'admin-topbar';
$topbarLinks = [
  ['label' => 'Mon espace', 'href' => $sidebarHome],
  ['label' => 'Déconnexion', 'href' => url('utilisateur/deconnexion.php')],
];
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
<body class="admin-page employee-page<?= $maintenance ? ' maintenance-page' : '' ?><?= $accueil && $onglet === 'reservations' ? ' admin-reservations-page' : '' ?>">
<div class="admin-shell">
  <?php require __DIR__ . '/../layout/sidebar.php'; ?>
  <div class="admin-main">
    <?php require __DIR__ . '/../layout/topbar.php'; ?>
    <?php if ($flashMessages): ?>
      <div class="admin-flashes">
        <?php foreach ($flashMessages as $message): ?>
          <p class="flash flash-<?= e($message['type']) ?>"><?= e($message['message']) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <main class="admin-content">
      <?php if ((!$maintenance || !in_array($onglet, ['tableau', 'maintenance'], true)) && !($accueil && in_array($onglet, ['suivi-tickets', 'creneaux', 'reservations'], true))): ?>
      <div class="admin-heading">
        <div>
          <p class="admin-eyebrow"><?= $maintenance ? 'SUIVI DES INTERVENTIONS' : 'GESTION DE LA SALLE' ?></p>
          <h2><?= e($titresOnglets[$onglet] ?? 'Espace employé') ?></h2>
          <p class="employee-page-description"><?= e([
            'tableau' => 'Retrouvez en un coup d’œil les indicateurs et l’activité du club aujourd’hui.',
            'creneaux' => $accueil ? 'Consultez le planning des activités et organisez les séances dans les salles du club.' : 'Planifiez les séances et consultez leur occupation.',
            'adherents' => 'Consultez les informations et le statut des adhérents.',
            'creer-ticket' => 'Signalez un problème matériel à l’équipe de maintenance.',
            'materiel' => 'Consultez l’inventaire et suivez les tickets que vous avez signalés.',
            'maintenance' => 'Suivez les demandes et consignez chaque intervention.',
            'faq' => 'Répondez aux questions et gérez les contenus de la FAQ.',
            'messages' => 'Consultez les échanges et répondez aux adhérents.',
          ][$onglet] ?? '') ?></p>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($maintenance && !in_array($onglet, ['tableau', 'maintenance', 'materiel'], true)): ?>
      <div class="compteurs">
        <div class="compteur"><div class="v"><?= count($maintenances) ?></div><div class="k">tickets au total</div></div>
        <div class="compteur warn"><div class="v"><?= count(array_filter($maintenances, static fn(array $ticket): bool => $ticket['NumUsers_technicien'] === null && $ticket['libelle_statut'] !== 'Terminee')) ?></div><div class="k">dans la file commune</div></div>
        <div class="compteur"><div class="v"><?= $statutsTickets['Terminee'] ?? 0 ?></div><div class="k">tickets fermés</div></div>
      </div>
      <?php endif; ?>

<?php if ($maintenance && $onglet === 'tableau'): ?>

  <section class="maintenance-dashboard" aria-labelledby="maintenance-welcome-title">
    <header class="maintenance-welcome">
      <div>
        <p class="maintenance-eyebrow">ESPACE MAINTENANCE</p>
        <h2 id="maintenance-welcome-title">Bonjour <?= e($prenomTechnicien !== '' ? $prenomTechnicien : 'Technicien') ?>,</h2>
        <p>Voici un aperçu de votre activité maintenance.</p>
      </div>
    </header>

    <div class="maintenance-metrics">
      <a class="maintenance-metric metric-open" href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">▤</span>
        <span class="maintenance-metric-copy"><strong><?= $ticketsOuvertsMaintenance ?></strong><span>Tickets ouverts</span><small>Voir mes tickets →</small></span>
      </a>
      <a class="maintenance-metric metric-done" href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">✓</span>
        <span class="maintenance-metric-copy"><strong><?= $ticketsResolus30Jours ?></strong><span>Tickets résolus (30 j)</span><small>Voir l’historique →</small></span>
      </a>
      <a class="maintenance-metric metric-waiting" href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">◷</span>
        <span class="maintenance-metric-copy"><strong><?= $ticketsEnAttenteMaintenance ?></strong><span>En attente</span><small>Voir les détails →</small></span>
      </a>
      <a class="maintenance-metric metric-equipment" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>">
        <span class="maintenance-metric-icon" aria-hidden="true">⬡</span>
        <span class="maintenance-metric-copy"><strong><?= count($equipementsMaintenanceAControler) ?></strong><span>Équipements à contrôler</span><small>Voir le matériel →</small></span>
      </a>
    </div>

    <div class="maintenance-dashboard-grid">
      <section class="maintenance-panel maintenance-tickets-panel" aria-labelledby="maintenance-tickets-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-tickets-title"><span aria-hidden="true">▤</span> Tickets de maintenance</h3>
          <a href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>">Voir tous →</a>
        </header>
        <?php if (!$maintenances): ?>
          <p class="maintenance-empty">Aucun ticket de maintenance pour le moment.</p>
        <?php else: ?>
          <div class="maintenance-table-wrap">
            <table class="maintenance-table">
              <thead><tr><th>Ticket</th><th>Description</th><th>Équipement</th><th>Priorité</th><th>Statut</th><th>Signalé le</th></tr></thead>
              <tbody>
              <?php foreach (array_slice($maintenances, 0, 5) as $ticket): ?>
                <tr>
                  <td class="maintenance-ticket-number">TKT-<?= (int) $ticket['Nummaint'] ?></td>
                  <td><?= e($ticket['description_problememaint']) ?></td>
                  <td><?= e($ticket['nomequip']) ?></td>
                  <td><span class="maintenance-priority<?= $ticket['priorite'] === 'Haute' ? ' priority-high' : ($ticket['priorite'] === 'Moyenne' ? ' priority-medium' : '') ?>"><?= e($ticket['priorite']) ?></span></td>
                  <td><span class="maintenance-status<?= $ticket['libelle_statut'] === 'Terminee' ? ' status-done' : (in_array($ticket['libelle_statut'], ['Ouverte', 'En attente'], true) ? ' status-open' : ' status-progress') ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Terminée' : e($ticket['libelle_statut']) ?></span></td>
                  <td><?= e(dateFr($ticket['date_demandemaint'])) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="maintenance-panel maintenance-equipment-panel" aria-labelledby="maintenance-equipment-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-equipment-title"><span aria-hidden="true">⬡</span> Équipements à contrôler</h3>
          <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>">Voir tout →</a>
        </header>
        <?php if (!$equipementsMaintenanceAControler): ?>
          <p class="maintenance-empty">Tous les équipements sont disponibles.</p>
        <?php else: ?>
          <ul class="maintenance-equipment-list">
            <?php foreach (array_slice($equipementsMaintenanceAControler, 0, 5) as $equipement): ?>
              <li>
                <span class="equipment-indicator" aria-hidden="true">⚙</span>
                <span class="equipment-name"><?= e($equipement['nomequip']) ?></span>
                <span class="maintenance-status<?= $equipement['libelle_statut'] === 'Hors service' ? ' status-open' : ' status-progress' ?>"><?= e($equipement['libelle_statut']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <a class="maintenance-panel-link" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>">Consulter l’inventaire →</a>
      </section>

      <section class="maintenance-panel maintenance-activity-panel" aria-labelledby="maintenance-activity-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-activity-title"><span aria-hidden="true">◷</span> Activité récente</h3>
        </header>
        <?php if (!$activiteMaintenanceRecente): ?>
          <p class="maintenance-empty">Aucune intervention enregistrée.</p>
        <?php else: ?>
          <ol class="maintenance-activity-list">
            <?php foreach ($activiteMaintenanceRecente as $activite): ?>
              <li>
                <span class="activity-dot" aria-hidden="true"></span>
                <div>
                  <strong>Ticket TKT-<?= (int) $activite['Nummaint'] ?> — <?= e($activite['libelle_statut']) ?></strong>
                  <?php if (trim((string) $activite['commentaire']) !== ''): ?>
                    <p><?= e($activite['commentaire']) ?></p>
                  <?php endif; ?>
                  <small>par <?= e(trim($activite['prenomUsers'] . ' ' . $activite['nomUsers'])) ?> · <?= e(dateFr($activite['date_intervention'], true)) ?></small>
                </div>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </section>

      <section class="maintenance-panel maintenance-shortcuts-panel" aria-labelledby="maintenance-shortcuts-title">
        <header class="maintenance-panel-heading">
          <h3 id="maintenance-shortcuts-title"><span aria-hidden="true">ϟ</span> Raccourcis</h3>
        </header>
        <div class="maintenance-shortcuts">
          <a href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>"><span aria-hidden="true">⚒</span>Voir mes tickets</a>
          <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>"><span aria-hidden="true">⬡</span>Gérer le matériel</a>
        </div>
      </section>
    </div>
  </section>

<?php elseif ($onglet === 'tableau'): ?>

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

<?php elseif ($onglet === 'reservations' && $accueil): ?>

  <section class="admin-reservations-page-content">
    <header class="admin-reservations-heading">
      <div class="admin-reservations-heading-copy">
        <span aria-hidden="true">▦</span>
        <div>
          <h2>Réservations des adhérents</h2>
          <p>Consultez toutes les réservations, puis modifiez ou annulez les séances à venir.</p>
        </div>
      </div>
    </header>

    <section class="admin-reservations-filters" aria-label="Filtres des réservations">
      <select id="filtre-reservation-activite" aria-label="Filtrer par activité">
        <option value="">Toutes les activités</option>
        <?php foreach (PlanningController::TYPES as $typeReservation => $libelleReservation): ?>
          <option value="<?= e($typeReservation) ?>"><?= e($libelleReservation) ?></option>
        <?php endforeach; ?>
      </select>
      <select id="filtre-reservation-statut" aria-label="Filtrer par statut">
        <option value="">Tous les statuts</option>
        <option value="Reservee">Confirmées</option>
        <option value="En attente">En attente</option>
        <option value="Annulee">Annulées</option>
      </select>
      <label class="admin-reservations-search">
        <span aria-hidden="true">⌕</span>
        <input type="search" id="recherche-reservation" placeholder="Rechercher un adhérent ou un cours…" autocomplete="off">
      </label>
    </section>

    <section class="admin-reservations-list" id="liste-reservations">
      <header>
        <h3>Liste des réservations <span>(<?= count($reservationsAccueil) ?>)</span></h3>
      </header>
      <div class="admin-reservation-table-scroll">
        <table class="admin-reservation-table">
          <thead>
            <tr><th>Date &amp; heure</th><th>Activité</th><th>Adhérent</th><th>Salle</th><th>Statut</th><th>Places</th><th>Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($reservationsAccueil as $reservation):
            $dateCoursReservation = new DateTimeImmutable($reservation['date_heure_debut']);
            $typeActiviteReservation = isset(PlanningController::TYPES[$reservation['type_activite']]) ? $reservation['type_activite'] : 'cardio';
            $nomAdherentReservation = trim($reservation['prenomUsers'] . ' ' . $reservation['nomUsers']);
            $nomCoachReservation = trim($reservation['coach_prenom'] . ' ' . $reservation['coach_nom']);
            $statutReservation = $reservation['statut_reservation'];
            $libelleStatutReservation = $statutReservation === 'Reservee' ? 'Confirmée' : ($statutReservation === 'Annulee' ? 'Annulée' : 'En attente');
            $reservationFuture = strtotime($reservation['date_heure_debut']) >= time();
            $motsRechercheReservation = mb_strtolower($nomAdherentReservation . ' ' . $reservation['mailUsers'] . ' ' . $reservation['titre'] . ' ' . $reservation['nomsalle'] . ' ' . $nomCoachReservation);
          ?>
            <tr data-reservation-row data-type="<?= e($typeActiviteReservation) ?>" data-status="<?= e($statutReservation) ?>" data-search="<?= e($motsRechercheReservation) ?>">
              <td>
                <strong><?= e($dateCoursReservation->format('d/m/Y')) ?></strong>
                <span><?= e($dateCoursReservation->format('H:i')) ?> – <?= e((new DateTimeImmutable($reservation['date_heure_fin']))->format('H:i')) ?></span>
              </td>
              <td><div><strong><?= e($reservation['titre']) ?></strong><span>Coach : <?= e($nomCoachReservation) ?></span></div></td>
              <td><div><strong><?= e($nomAdherentReservation) ?></strong><span><?= e($reservation['mailUsers']) ?></span></div></td>
              <td><?= e($reservation['nomsalle']) ?></td>
              <td><span class="admin-reservation-status status-<?= $statutReservation === 'Reservee' ? 'confirmed' : ($statutReservation === 'Annulee' ? 'cancelled' : 'waiting') ?>"><?= e($libelleStatutReservation) ?></span></td>
              <td><?= (int) $reservation['places_prises'] ?>/<?= (int) $reservation['capacite_max'] ?></td>
              <td>
                <div class="admin-reservation-actions">
                  <?php if ($statutReservation !== 'Annulee' && $reservationFuture): ?>
                    <button type="button" data-reservation-edit data-id="<?= (int) $reservation['id_ligne'] ?>" data-current-course="<?= (int) $reservation['Numactivite'] ?>" aria-label="Modifier la réservation de <?= e($nomAdherentReservation) ?>">Modifier</button>
                    <form method="post" class="admin-reservation-action-form">
                      <?= champ_csrf() ?>
                      <input type="hidden" name="action" value="accueil_annuler_reservation">
                      <input type="hidden" name="reservation" value="<?= (int) $reservation['id_ligne'] ?>">
                      <button type="submit" class="is-danger" data-confirme="Annuler la réservation de <?= e($nomAdherentReservation) ?> ?" aria-label="Annuler la réservation">Annuler</button>
                    </form>
                  <?php else: ?>
                    <span class="admin-reservation-no-action"><?= $statutReservation === 'Annulee' ? 'Aucune action' : 'Séance passée' ?></span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$reservationsAccueil): ?>
            <tr><td colspan="7" class="admin-reservation-no-data">Aucune réservation enregistrée.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
      <p class="admin-reservation-filter-empty" id="reservations-filtre-vide" hidden>Aucune réservation ne correspond aux filtres.</p>
    </section>
  </section>

  <dialog class="admin-reservation-dialog" id="reservation-dialogue">
    <button type="button" class="reservation-dialog-close" aria-label="Fermer">×</button>
    <form method="post" id="modifier-reservation-form">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="accueil_modifier_reservation">
      <input type="hidden" name="reservation" id="dialog-reservation-id">
      <h3>Modifier la réservation</h3>
      <label for="dialog-reservation-course">Choisir un autre cours</label>
      <select name="activite" id="dialog-reservation-course" required>
        <option value="">Sélectionner un cours</option>
        <?php foreach ($activitesDisponiblesAccueil as $activiteOption):
          $coursComplet = (int) $activiteOption['places_prises'] >= (int) $activiteOption['capacite_max'];
        ?>
          <option value="<?= (int) $activiteOption['Numactivite'] ?>" data-complet="<?= $coursComplet ? '1' : '0' ?>" <?= $coursComplet ? 'disabled' : '' ?>>
            <?= e($activiteOption['titre']) ?> · <?= e(date('d/m/Y H:i', strtotime($activiteOption['date_heure_debut']))) ?> · <?= e($activiteOption['nomsalle']) ?> (<?= (int) $activiteOption['places_prises'] ?>/<?= (int) $activiteOption['capacite_max'] ?>)
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Enregistrer le changement</button>
    </form>
  </dialog>
  <script>
    (() => {
      const rows = [...document.querySelectorAll('[data-reservation-row]')];
      const activityFilter = document.getElementById('filtre-reservation-activite');
      const statusFilter = document.getElementById('filtre-reservation-statut');
      const search = document.getElementById('recherche-reservation');
      const empty = document.getElementById('reservations-filtre-vide');
      const filterRows = () => {
        const activity = activityFilter.value;
        const status = statusFilter.value;
        const query = search.value.trim().toLocaleLowerCase('fr');
        let visibleCount = 0;
        rows.forEach(row => {
          const visible = (!activity || row.dataset.type === activity)
            && (!status || row.dataset.status === status)
            && row.dataset.search.includes(query);
          row.hidden = !visible;
          if (visible) visibleCount++;
        });
        empty.hidden = visibleCount > 0;
      };
      activityFilter.addEventListener('change', filterRows);
      statusFilter.addEventListener('change', filterRows);
      search.addEventListener('input', filterRows);

      const dialog = document.getElementById('reservation-dialogue');
      const reservationId = document.getElementById('dialog-reservation-id');
      const courseSelect = document.getElementById('dialog-reservation-course');
      document.querySelectorAll('[data-reservation-edit]').forEach(button => {
        button.addEventListener('click', () => {
          reservationId.value = button.dataset.id;
          courseSelect.value = '';
          courseSelect.querySelectorAll('option[data-complet]').forEach(option => {
            option.disabled = option.dataset.complet === '1';
          });
          const currentCourse = courseSelect.querySelector(`[value="${button.dataset.currentCourse}"]`);
          if (currentCourse) currentCourse.disabled = false;
          dialog.showModal();
        });
      });
      dialog.querySelector('.reservation-dialog-close').addEventListener('click', () => dialog.close());
    })();
  </script>

<?php elseif ($onglet === 'creneaux'): ?>

  <?php require __DIR__ . '/view/planning.php'; ?>

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
  <p class="ticket-form-back"><a href="<?= e(url('employe/espace.php?onglet=' . ($accueil ? 'suivi-tickets' : 'materiel'))) ?>"><?= $accueil ? 'Consulter le suivi de mes tickets' : 'Consulter l’inventaire et le suivi des tickets' ?></a></p>

<?php elseif ($onglet === 'materiel'): ?>

  <?php if (!$maintenance): ?>
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
  <?php endif; ?>

  <?php if ($maintenance): ?>
    <?php
    $materielDisponible = count(array_filter($equipements, static fn(array $item): bool => $item['libelle_statut'] === 'Disponible'));
    $materielEnMaintenance = count(array_filter($equipements, static fn(array $item): bool => $item['libelle_statut'] === 'Maintenance'));
    $materielHorsService = count(array_filter($equipements, static fn(array $item): bool => $item['libelle_statut'] === 'Hors service'));
    $nombreMaterielsAlerte = count(array_filter(
      $equipements,
      static fn(array $item): bool => (int) $item['stock_actuel'] <= (int) $item['seuil_alerte']
    ));
    $unitesEnStock = array_sum(array_map(static fn(array $item): int => (int) $item['stock_actuel'], $equipements));
    $materielSelectionneId = filter_var($_GET['materiel'] ?? null, FILTER_VALIDATE_INT);
    $materielSelectionne = null;
    foreach ($equipements as $equipement) {
      if ($materielSelectionneId && (int) $equipement['Numequip'] === $materielSelectionneId) {
        $materielSelectionne = $equipement;
        break;
      }
    }
    $typesEquipement = $pdo->query(
      'SELECT Numtypeequip, libelletypeequip FROM type_equipement ORDER BY libelletypeequip'
    )->fetchAll(PDO::FETCH_ASSOC);
    $statutsEquipement = $pdo->query(
      "SELECT Numstatut, libelle_statut FROM statut WHERE type_statut = 'EQUIPEMENT' ORDER BY libelle_statut"
    )->fetchAll(PDO::FETCH_ASSOC);
    $modeEditionMateriel = isset($_GET['modifier']) && $_GET['modifier'] === '1' && $materielSelectionne !== null;
    $modeCommandeMateriel = isset($_GET['commander']) && $_GET['commander'] === '1';
    $historiqueMaterielComplet = isset($_GET['historique']) && $_GET['historique'] === '1';
    $commandesEmploye = $pdo->prepare(
      'SELECT c.Numcommande, c.Numequip, c.quantite, c.message, c.statut_commande, c.date_demande,
              eq.nomequip
         FROM commande_materiel c
         JOIN equipement eq ON eq.Numequip = c.Numequip
        WHERE c.NumUsers = ?
        ORDER BY c.date_demande DESC LIMIT 10'
    );
    $commandesEmploye->execute([(int) $moi]);
    $commandesEmploye = $commandesEmploye->fetchAll(PDO::FETCH_ASSOC);
    $mouvementsMateriel = [];
    $nombreMouvementsMateriel = 0;
    $derniereEntreeMateriel = null;
    if ($materielSelectionne) {
      $compteMouvements = $pdo->prepare('SELECT COUNT(*) FROM mouvement_stock WHERE Numequip = ?');
      $compteMouvements->execute([(int) $materielSelectionne['Numequip']]);
      $nombreMouvementsMateriel = (int) $compteMouvements->fetchColumn();
      $mouvementEntree = $pdo->prepare(
        "SELECT date_mouvement FROM mouvement_stock
         WHERE Numequip = ? AND type_mouvement = 'ENTREE'
         ORDER BY date_mouvement DESC, Nummouvement DESC LIMIT 1"
      );
      $mouvementEntree->execute([(int) $materielSelectionne['Numequip']]);
      $derniereEntreeMateriel = $mouvementEntree->fetchColumn() ?: null;
      $sqlMouvements = 'SELECT m.type_mouvement, m.quantite, m.date_mouvement, m.commentaire,
                               u.prenomUsers, u.nomUsers
                          FROM mouvement_stock m
                          JOIN utilisateur u ON u.NumUsers = m.NumUsers
                         WHERE m.Numequip = ?
                         ORDER BY m.date_mouvement DESC, m.Nummouvement DESC';
      if (!$historiqueMaterielComplet) $sqlMouvements .= ' LIMIT 5';
      $queryMouvements = $pdo->prepare($sqlMouvements);
      $queryMouvements->execute([(int) $materielSelectionne['Numequip']]);
      $mouvementsMateriel = $queryMouvements->fetchAll(PDO::FETCH_ASSOC);
    }
    ?>
    <section class="maintenance-inventory-page" aria-label="Gestion du matériel">
      <header class="inventory-page-heading">
        <div><h2>Gestion du matériel</h2><p>Suivez les niveaux de stock et les mouvements de l’inventaire.</p></div>
        <a class="inventory-order-link" href="<?= e(url('employe/espace.php?onglet=materiel&commander=1')) ?>"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Commander un matériel</a>
      </header>
      <div class="inventory-summary">
        <article class="inventory-summary-card inventory-summary-total">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
          <div><strong><?= count($equipements) ?></strong><span>Matériels enregistrés</span><small>Équipements dans l’inventaire</small></div>
        </article>
        <article class="inventory-summary-card inventory-summary-available">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
          <div><strong><?= $unitesEnStock ?></strong><span>Unités en stock</span><small>Quantité disponible</small></div>
        </article>
        <article class="inventory-summary-card inventory-summary-maintenance">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <div><strong><?= $nombreMaterielsAlerte ?></strong><span>Stock faible</span><small>Au seuil d’alerte ou en dessous</small></div>
        </article>
        <article class="inventory-summary-card inventory-summary-out">
          <span class="inventory-summary-icon" aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></span>
          <div><strong><?= $materielEnMaintenance ?></strong><span>En maintenance</span><small><?= $materielDisponible ?> disponible<?= $materielDisponible === 1 ? '' : 's' ?> · <?= $materielHorsService ?> hors service</small></div>
        </article>
      </div>

      <?php if ($modeCommandeMateriel): ?>
        <section class="inventory-order-form-panel" aria-label="Demander une commande de matériel">
          <header><div><h3>Commander un matériel</h3><p>La demande sera transmise à l’administrateur pour validation.</p></div><a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>" aria-label="Fermer"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a></header>
          <?php if (!$equipements): ?>
            <p>Aucun matériel n’est disponible pour une demande de réapprovisionnement.</p>
          <?php else: ?>
            <form method="post">
              <?= champ_csrf() ?>
              <input type="hidden" name="action" value="commander_materiel">
              <label>Matériel<select name="materiel" required><?php foreach ($equipements as $equipement): ?><option value="<?= (int) $equipement['Numequip'] ?>"><?= e($equipement['nomequip']) ?> · stock <?= (int) $equipement['stock_actuel'] ?></option><?php endforeach; ?></select></label>
              <label>Quantité souhaitée<input type="number" name="quantite" min="1" max="10000" required value="1"></label>
              <label>Message à l’administrateur<textarea name="message" maxlength="500" rows="3" placeholder="Précisez le besoin ou l’urgence (facultatif)"></textarea></label>
              <button type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Envoyer la demande</button>
            </form>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if ($commandesEmploye): ?>
        <section class="inventory-my-orders" aria-label="Mes demandes de commande">
          <h3>Mes demandes de commande</h3>
          <div class="inventory-orders-list">
            <?php foreach ($commandesEmploye as $commande): ?>
              <article><span><strong><?= e($commande['nomequip']) ?> · <?= (int) $commande['quantite'] ?> unité<?= (int) $commande['quantite'] === 1 ? '' : 's' ?></strong><small>Demande du <?= dateFr($commande['date_demande']) ?><?= $commande['message'] ? ' · ' . e($commande['message']) : '' ?></small></span><b class="is-order-<?= e(match ($commande['statut_commande']) { 'En attente' => 'pending', 'Approuvée' => 'approved', 'Reçue' => 'received', default => 'rejected' }) ?>"><?= e($commande['statut_commande']) ?></b></article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <?php if (!$equipements): ?>
        <div class="inventory-empty">
          <span aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
          <h3>Aucun matériel enregistré</h3>
          <p>L’inventaire ne contient encore aucun équipement.</p>
        </div>
      <?php else: ?>
        <div class="inventory-workspace<?= $materielSelectionne ? '' : ' inventory-workspace-no-detail' ?>">
          <section class="inventory-list-panel" aria-label="Inventaire des équipements">
            <div class="inventory-toolbar">
              <label class="inventory-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" data-inventory-search placeholder="Rechercher un matériel…" aria-label="Rechercher un matériel"></label>
              <label class="inventory-filter"><span class="sr-only">Filtrer par catégorie</span><select data-inventory-category><option value="">Toutes les catégories</option><?php foreach ($typesEquipement as $type): ?><option value="<?= e($type['libelletypeequip']) ?>"><?= e($type['libelletypeequip']) ?></option><?php endforeach; ?></select></label>
              <label class="inventory-filter"><span class="sr-only">Filtrer par statut</span><select data-inventory-status><option value="">Tous les statuts</option><option value="Disponible">Disponible</option><option value="Maintenance">Maintenance</option><option value="Hors service">Hors service</option></select></label>
              <button type="button" class="inventory-reset" data-inventory-reset><i class="fa-solid fa-filter" aria-hidden="true"></i> Réinitialiser</button>
            </div>
            <div class="inventory-table-scroll">
              <table class="inventory-table">
                <thead><tr><th>Matériel</th><th>Catégorie</th><th>Stock actuel</th><th>État</th><th>Dernière maintenance</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($equipements as $equipement):
                  $statusClass = match ($equipement['libelle_statut']) {
                    'Disponible' => 'available',
                    'Maintenance' => 'maintenance',
                    default => 'out',
                  };
                  $searchText = implode(' ', [
                    $equipement['Numequip'],
                    $equipement['nomequip'],
                    $equipement['marque'] ?? '',
                    $equipement['modele'] ?? '',
                    $equipement['libelletypeequip'],
                    $equipement['libelle_statut'],
                    $equipement['nomfourn'] ?? '',
                  ]);
                  $isSelected = $materielSelectionne !== null
                    && (int) $equipement['Numequip'] === (int) $materielSelectionne['Numequip'];
                  ?>
                  <tr data-inventory-row data-inventory-id="<?= (int) $equipement['Numequip'] ?>" data-category="<?= e($equipement['libelletypeequip']) ?>" data-status="<?= e($equipement['libelle_statut']) ?>" data-search="<?= e(mb_strtolower($searchText)) ?>"<?= $isSelected ? ' class="is-selected"' : '' ?>>
                    <td class="inventory-product">
                      <?php if (!empty($equipement['image_equipement'])): ?>
                        <img class="inventory-product-image" src="<?= e(url($equipement['image_equipement'])) ?>" alt="">
                      <?php else: ?>
                        <span class="inventory-product-icon" aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
                      <?php endif; ?>
                      <span><strong><?= e($equipement['nomequip']) ?></strong><small><?= e(trim(($equipement['marque'] ?? '') . ' ' . ($equipement['modele'] ?? '')) ?: 'Modèle non renseigné') ?></small></span>
                    </td>
                    <td><span class="inventory-category"><?= e($equipement['libelletypeequip']) ?></span></td>
                    <td><span class="inventory-stock-count<?= (int) $equipement['stock_actuel'] <= (int) $equipement['seuil_alerte'] ? ' is-low' : '' ?>"><?= (int) $equipement['stock_actuel'] ?> unité<?= (int) $equipement['stock_actuel'] === 1 ? '' : 's' ?></span><small class="inventory-stock-threshold">Seuil : <?= (int) $equipement['seuil_alerte'] ?></small></td>
                    <td><span class="inventory-status is-<?= e($statusClass) ?>"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= e($equipement['libelle_statut']) ?></span></td>
                    <td class="inventory-date"><?= dateFr($equipement['date_derniere_maintenance']) ?></td>
                    <td>
                      <div class="inventory-actions">
                        <a class="inventory-view-button" href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $equipement['Numequip'])) ?>"<?= $isSelected && !$modeEditionMateriel ? ' aria-current="true"' : '' ?>>Voir</a>
                        <a class="inventory-edit-button" href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $equipement['Numequip'] . '&modifier=1')) ?>"<?= $isSelected && $modeEditionMateriel ? ' aria-current="true"' : '' ?>>Modifier</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
              <p class="inventory-no-results" data-inventory-no-results hidden>Aucun matériel ne correspond à ces filtres.</p>
            </div>
            <footer class="inventory-table-footer">
              <span data-inventory-range></span>
              <nav class="inventory-pagination" aria-label="Pagination de l’inventaire">
                <button type="button" data-inventory-prev aria-label="Page précédente"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                <span data-inventory-pages></span>
                <button type="button" data-inventory-next aria-label="Page suivante"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
              </nav>
            </footer>
          </section>

          <?php if ($materielSelectionne): ?>
          <aside class="inventory-detail-panel" aria-label="<?= $modeEditionMateriel ? 'Modifier le matériel' : 'Détails du matériel' ?>">
            <?php if ($materielSelectionne && $modeEditionMateriel): ?>
              <header class="inventory-detail-heading">
                <h3><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Modifier le matériel</h3>
                <a href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $materielSelectionne['Numequip'])) ?>" aria-label="Annuler la modification"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a>
              </header>
              <form class="inventory-edit-form" method="post" enctype="multipart/form-data">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="modifier_materiel">
                <input type="hidden" name="materiel" value="<?= (int) $materielSelectionne['Numequip'] ?>">
                <label>Nom du matériel<input type="text" name="nom" maxlength="80" required value="<?= e($materielSelectionne['nomequip']) ?>"></label>
                <label>Marque<input type="text" name="marque" maxlength="60" value="<?= e($materielSelectionne['marque'] ?? '') ?>"></label>
                <label>Modèle<input type="text" name="modele" maxlength="60" value="<?= e($materielSelectionne['modele'] ?? '') ?>"></label>
                <label>Catégorie<select name="categorie" required><?php foreach ($typesEquipement as $type): ?><option value="<?= (int) $type['Numtypeequip'] ?>"<?= (int) $type['Numtypeequip'] === (int) $materielSelectionne['Numtypeequip'] ? ' selected' : '' ?>><?= e($type['libelletypeequip']) ?></option><?php endforeach; ?></select></label>
                <label>Statut<select name="statut" required><?php foreach ($statutsEquipement as $statut): ?><option value="<?= (int) $statut['Numstatut'] ?>"<?= (int) $statut['Numstatut'] === (int) $materielSelectionne['Numstatut'] ? ' selected' : '' ?>><?= e($statut['libelle_statut']) ?></option><?php endforeach; ?></select></label>
                <label>Seuil d’alerte<input type="number" name="seuil_alerte" min="0" max="10000" required value="<?= (int) $materielSelectionne['seuil_alerte'] ?>"></label>
                <label class="inventory-image-field">Image du matériel
                  <?php if (!empty($materielSelectionne['image_equipement'])): ?><img src="<?= e(url($materielSelectionne['image_equipement'])) ?>" alt="Image actuelle du matériel"><?php endif; ?>
                  <input type="file" name="image_equipement" accept="image/jpeg,image/png,image/webp">
                  <small>JPEG, PNG ou WebP · 5 Mo maximum. Laissez vide pour conserver l’image actuelle.</small>
                </label>
                <div class="inventory-edit-actions">
                  <a class="inventory-cancel-button" href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $materielSelectionne['Numequip'])) ?>">Annuler</a>
                  <button type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Enregistrer</button>
                </div>
              </form>
            <?php elseif ($materielSelectionne):
              $statusClass = match ($materielSelectionne['libelle_statut']) {
                'Disponible' => 'available',
                'Maintenance' => 'maintenance',
                default => 'out',
              };
              ?>
              <header class="inventory-detail-heading">
                <a class="inventory-detail-back" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>" aria-label="Retour à l’inventaire"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-cube" aria-hidden="true"></i> Détails du matériel</h3>
                <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>" aria-label="Fermer le détail"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a>
              </header>
              <div class="inventory-detail-product">
                <?php if (!empty($materielSelectionne['image_equipement'])): ?>
                  <img class="inventory-detail-product-image" src="<?= e(url($materielSelectionne['image_equipement'])) ?>" alt="">
                <?php else: ?>
                <span class="inventory-detail-product-icon" aria-hidden="true"><i class="fa-solid fa-cube"></i></span>
                <?php endif; ?>
                <div><p>N° <?= (int) $materielSelectionne['Numequip'] ?></p><h4><?= e($materielSelectionne['nomequip']) ?></h4><small><?= e(trim(($materielSelectionne['marque'] ?? '') . ' ' . ($materielSelectionne['modele'] ?? '')) ?: 'Modèle non renseigné') ?></small></div>
              </div>
              <span class="inventory-stock-badge<?= (int) $materielSelectionne['stock_actuel'] <= (int) $materielSelectionne['seuil_alerte'] ? ' is-low' : '' ?>"><i class="fa-solid fa-box" aria-hidden="true"></i><?= (int) $materielSelectionne['stock_actuel'] === 0 ? 'Rupture de stock' : ((int) $materielSelectionne['stock_actuel'] <= (int) $materielSelectionne['seuil_alerte'] ? 'Stock faible' : 'En stock') ?></span>
              <span class="inventory-status is-<?= e($statusClass) ?>"><i class="fa-solid fa-circle" aria-hidden="true"></i><?= e($materielSelectionne['libelle_statut']) ?></span>
              <dl class="inventory-detail-facts">
                <div><dt><i class="fa-solid fa-cube" aria-hidden="true"></i></dt><dd>Catégorie : <?= e($materielSelectionne['libelletypeequip']) ?></dd></div>
                <div><dt><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></dt><dd>Stock actuel : <strong><?= (int) $materielSelectionne['stock_actuel'] ?> unité<?= (int) $materielSelectionne['stock_actuel'] === 1 ? '' : 's' ?></strong></dd></div>
                <div><dt><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></dt><dd>Seuil d’alerte : <strong><?= (int) $materielSelectionne['seuil_alerte'] ?> unité<?= (int) $materielSelectionne['seuil_alerte'] === 1 ? '' : 's' ?></strong></dd></div>
                <div><dt><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></dt><dd>Fournisseur : <?= e($materielSelectionne['nomfourn'] ?: 'Non renseigné') ?></dd></div>
                <div><dt><i class="fa-regular fa-calendar" aria-hidden="true"></i></dt><dd>Dernière entrée : <?= $derniereEntreeMateriel ? dateFr($derniereEntreeMateriel) : 'Aucune entrée enregistrée' ?></dd></div>
                <div><dt><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i></dt><dd>Dernière maintenance : <?= dateFr($materielSelectionne['date_derniere_maintenance']) ?></dd></div>
              </dl>
              <section class="inventory-movement-history" aria-labelledby="inventory-movement-history-title">
                <header>
                  <h4 id="inventory-movement-history-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Historique des mouvements</h4>
                  <span><?= $nombreMouvementsMateriel ?></span>
                  <?php if ($nombreMouvementsMateriel > 5): ?><a href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $materielSelectionne['Numequip'] . ($historiqueMaterielComplet ? '' : '&historique=1'))) ?>"><?= $historiqueMaterielComplet ? 'Voir moins' : 'Voir tout' ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><?php endif; ?>
                </header>
                <?php if (!$mouvementsMateriel): ?>
                  <p>Aucun mouvement de stock enregistré.</p>
                <?php else: ?>
                  <ul>
                    <?php foreach ($mouvementsMateriel as $mouvement): ?>
                      <li class="<?= $mouvement['type_mouvement'] === 'ENTREE' ? 'is-entry' : 'is-exit' ?>">
                        <i class="fa-solid <?= $mouvement['type_mouvement'] === 'ENTREE' ? 'fa-arrow-up' : 'fa-arrow-down' ?>" aria-hidden="true"></i>
                        <span><strong><?= $mouvement['type_mouvement'] === 'ENTREE' ? 'Entrée de stock' : 'Sortie (utilisation)' ?></strong><small><?= e($mouvement['commentaire'] ?: $mouvement['prenomUsers'] . ' ' . $mouvement['nomUsers']) ?></small></span>
                        <b><?= $mouvement['type_mouvement'] === 'ENTREE' ? '+' : '−' ?><?= (int) $mouvement['quantite'] ?></b>
                        <time datetime="<?= e(date('c', strtotime($mouvement['date_mouvement']))) ?>"><?= dateFr($mouvement['date_mouvement']) ?></time>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </section>
              <details class="inventory-use-panel">
                <summary><i class="fa-solid fa-cube" aria-hidden="true"></i> Déclarer une utilisation</summary>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="declarer_utilisation_materiel">
                  <input type="hidden" name="materiel" value="<?= (int) $materielSelectionne['Numequip'] ?>">
                  <label>Quantité utilisée<input type="number" name="quantite" min="1" max="<?= (int) $materielSelectionne['stock_actuel'] ?>" required></label>
                  <label>Commentaire facultatif<input type="text" name="commentaire" maxlength="255" placeholder="Ex. matériel sorti pour intervention"></label>
                  <button type="submit"<?= (int) $materielSelectionne['stock_actuel'] < 1 ? ' disabled' : '' ?>>Enregistrer la sortie</button>
                </form>
              </details>
              <?php
              $ticketsEquipement = array_values(array_filter(
                $maintenances,
                static fn(array $ticket): bool => (int) $ticket['Numequip'] === (int) $materielSelectionne['Numequip']
              ));
              ?>
              <section class="inventory-ticket-history" aria-labelledby="inventory-ticket-history-title">
                <header><h4 id="inventory-ticket-history-title"><i class="fa-regular fa-clock" aria-hidden="true"></i> Historique maintenance</h4><span><?= count($ticketsEquipement) ?></span></header>
                <?php if (!$ticketsEquipement): ?>
                  <p>Aucun ticket de maintenance enregistré.</p>
                <?php else: ?>
                  <ul>
                    <?php foreach (array_slice($ticketsEquipement, 0, 3) as $ticket): ?>
                      <li><span><strong>TKT-<?= (int) $ticket['Nummaint'] ?></strong><small><?= e($ticket['libelle_statut']) ?> · <?= dateFr($ticket['date_demandemaint']) ?></small></span><a href="<?= e(url('employe/espace.php?onglet=' . ($accueil ? 'suivi-tickets' : 'maintenance') . '&ticket=' . (int) $ticket['Nummaint'])) ?>">Voir</a></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </section>
            <?php endif; ?>
          </aside>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php else: ?>
    <?php if (!$accueil): ?>
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
    <?php endif; ?>
  <?php endif; ?>

<?php elseif ($onglet === 'maintenance' || ($accueil && $onglet === 'suivi-tickets')): ?>

  <?php
  $ongletSuiviTickets = $maintenance ? 'maintenance' : 'suivi-tickets';
  $ticketsPourSuivi = $maintenance ? $maintenances : $ticketsEmploye;
  $statutsPourSuivi = array_count_values(array_column($ticketsPourSuivi, 'libelle_statut'));
  $ticketsEnCoursMaintenance = $statutsPourSuivi['En cours'] ?? 0;
  $ticketsEnAttenteMaintenance = count(array_filter(
    $ticketsPourSuivi,
    static fn(array $ticket): bool => in_array(
      $ticket['libelle_statut'],
      ['Ouverte', 'En attente', 'En attente de piece', 'À valider'],
      true
    )
  ));
  $ticketsResoluMaintenance = $statutsPourSuivi['Terminee'] ?? 0;
  $ticketSelectionneId = filter_var($_GET['ticket'] ?? null, FILTER_VALIDATE_INT);
  $ticketSelectionne = null;
  foreach ($ticketsPourSuivi as $ticket) {
    if ($ticketSelectionneId && (int) $ticket['Nummaint'] === $ticketSelectionneId) {
      $ticketSelectionne = $ticket;
      break;
    }
  }
  $vueEditionTicket = $maintenance && $ticketSelectionne && (($_GET['vue_ticket'] ?? '') === 'modifier');
  $categoriesTickets = array_values(array_unique(array_column($ticketsPourSuivi, 'libelletypeequip')));
  sort($categoriesTickets, SORT_NATURAL | SORT_FLAG_CASE);
  $prioritesTickets = array_values(array_unique(array_column($ticketsPourSuivi, 'priorite')));
  sort($prioritesTickets, SORT_NATURAL | SORT_FLAG_CASE);
  $interventionsTicket = [];
  if ($ticketSelectionne) {
    $sqlInterventionsTicket =
      'SELECT i.date_intervention, i.commentaire, i.Numstatut,
             s.libelle_statut, u.prenomUsers, u.nomUsers
        FROM intervention i
        JOIN statut s ON s.Numstatut = i.Numstatut
        JOIN utilisateur u ON u.NumUsers = i.NumUsers
        WHERE i.Nummaint = ?
        ORDER BY i.date_intervention ASC, i.Numintervention ASC';
    $queryInterventionsTicket = $pdo->prepare($sqlInterventionsTicket);
    $queryInterventionsTicket->execute([(int) $ticketSelectionne['Nummaint']]);
    $interventionsTicket = $queryInterventionsTicket->fetchAll(PDO::FETCH_ASSOC);
  }
  ?>
  <section class="maintenance-ticket-page<?= $maintenance ? ' is-technician' : '' ?>" aria-label="<?= $maintenance ? 'Mes tickets de maintenance' : 'Suivi de mes tickets' ?>">
    <header class="maintenance-ticket-page-heading">
      <div><?php if ($maintenance): ?><p class="ticket-page-eyebrow">ESPACE MAINTENANCE</p><?php endif; ?><h2><?= $maintenance ? 'Mes tickets' : 'Suivi des tickets' ?></h2><p><?= $maintenance ? 'Retrouvez la file des demandes et suivez l’avancement des interventions.' : 'Consultez l’état de vos demandes et suivez leur avancement.' ?></p></div>
    </header>
    <?php if (!$maintenance): ?>
    <div class="maintenance-ticket-summary">
      <article class="ticket-summary-card ticket-summary-total">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-ticket"></i></span>
        <div><strong><?= count($ticketsPourSuivi) ?></strong><span><?= $maintenance ? 'Tickets dans la file' : 'Mes tickets' ?></span><small><?= $maintenance ? 'Toutes les demandes de maintenance' : 'Demandes signalées par vous' ?></small></div>
      </article>
      <article class="ticket-summary-card ticket-summary-progress">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-wrench"></i></span>
        <div><strong><?= $ticketsEnCoursMaintenance ?></strong><span>En cours</span><small>Interventions actives</small></div>
      </article>
      <article class="ticket-summary-card ticket-summary-resolved">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
        <div><strong><?= $ticketsResoluMaintenance ?></strong><span>Résolus</span><small><?= $maintenance ? $ticketsResolusCetteSemaine . ' cette semaine' : 'Demandes clôturées' ?></small></div>
      </article>
      <article class="ticket-summary-card ticket-summary-waiting">
        <span class="ticket-summary-icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
        <div><strong><?= $ticketsEnAttenteMaintenance ?></strong><span>En attente</span><small><?= $maintenance ? 'À prendre en charge ou valider' : 'En attente d’intervention' ?></small></div>
      </article>
    </div>
    <?php endif; ?>

    <?php if (!$ticketsPourSuivi): ?>
      <div class="maintenance-ticket-empty">
        <span aria-hidden="true"><i class="fa-solid fa-ticket"></i></span>
        <h3><?= $maintenance ? 'Aucun ticket pour le moment' : 'Vous n’avez pas encore créé de ticket' ?></h3>
        <p><?= $maintenance ? 'Les demandes de maintenance apparaîtront ici dès leur création.' : 'Les tickets que vous signalez à l’équipe de maintenance apparaîtront ici.' ?></p>
        <?php if (!$maintenance): ?><a class="btn btn-vert" href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>">Créer un ticket</a><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="maintenance-ticket-workspace<?= $maintenance ? ' is-technician' . ($ticketSelectionne ? ' has-selection' : '') . ($vueEditionTicket ? ' is-editing' : '') : '' ?>">
        <?php if (!$vueEditionTicket): ?>
        <section class="maintenance-ticket-list-panel" aria-label="Liste des tickets">
          <div class="ticket-toolbar">
            <div class="ticket-status-tabs" role="group" aria-label="Filtrer par statut">
              <button type="button" class="ticket-tab is-active" data-status-filter="all" aria-pressed="true"><i class="fa-solid fa-ticket" aria-hidden="true"></i> Tous <span><?= count($ticketsPourSuivi) ?></span></button>
              <button type="button" class="ticket-tab" data-status-filter="progress" aria-pressed="false"><i class="fa-regular fa-clock" aria-hidden="true"></i> En cours <span><?= $ticketsEnCoursMaintenance ?></span></button>
              <button type="button" class="ticket-tab" data-status-filter="resolved" aria-pressed="false"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Résolus <span><?= $ticketsResoluMaintenance ?></span></button>
              <button type="button" class="ticket-tab" data-status-filter="waiting" aria-pressed="false"><i class="fa-regular fa-hourglass-half" aria-hidden="true"></i> En attente <span><?= $ticketsEnAttenteMaintenance ?></span></button>
            </div>
            <div class="ticket-filters">
              <label class="ticket-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" data-ticket-search placeholder="Rechercher un ticket…" aria-label="Rechercher un ticket"></label>
              <label class="ticket-category-filter"><span class="sr-only">Filtrer par catégorie</span><select data-ticket-category><option value="">Toutes catégories</option><?php foreach ($categoriesTickets as $categorieTicket): ?><option value="<?= e($categorieTicket) ?>"><?= e($categorieTicket) ?></option><?php endforeach; ?></select></label>
              <label class="ticket-priority-filter"><span class="sr-only">Filtrer par priorité</span><select data-ticket-priority><option value="">Toutes priorités</option><?php foreach ($prioritesTickets as $prioriteTicket): ?><option value="<?= e($prioriteTicket) ?>"><?= e($prioriteTicket) ?></option><?php endforeach; ?></select></label>
            </div>
          </div>

          <div class="ticket-table-scroll">
            <table class="maintenance-ticket-table">
              <thead><tr><th># Ticket</th><th>Objet</th><th>Catégorie</th><th>Statut</th><th>Priorité</th><th>Date</th><th>Actions</th></tr></thead>
              <tbody>
              <?php foreach ($ticketsPourSuivi as $index => $ticket):
                $statusGroup = $ticket['libelle_statut'] === 'Terminee'
                  ? 'resolved'
                  : ($ticket['libelle_statut'] === 'En cours' ? 'progress' : 'waiting');
                $statusClass = match ($ticket['libelle_statut']) {
                  'Terminee' => 'resolved',
                  'En cours' => 'progress',
                  'À valider' => 'validation',
                  'En attente de piece' => 'parts',
                  default => 'waiting',
                };
                $ticketSearchText = implode(' ', [
                  'TKT-' . (int) $ticket['Nummaint'],
                  $ticket['description_problememaint'],
                  $ticket['nomequip'],
                  $ticket['libelletypeequip'],
                  $ticket['prenomUsers'] . ' ' . $ticket['nomUsers'],
                  $ticket['priorite'],
                  $ticket['libelle_statut'],
                  $ticket['technicien_prenom'] ?? '',
                  $ticket['technicien_nom'] ?? '',
                ]);
                $isSelected = $ticketSelectionne !== null
                  && (int) $ticket['Nummaint'] === (int) $ticketSelectionne['Nummaint'];
                ?>
                <tr data-ticket-row data-ticket-id="<?= (int) $ticket['Nummaint'] ?>" data-status-group="<?= e($statusGroup) ?>" data-category="<?= e($ticket['libelletypeequip']) ?>" data-priority="<?= e($ticket['priorite']) ?>" data-search="<?= e(mb_strtolower($ticketSearchText)) ?>"<?= $isSelected ? ' class="is-selected"' : '' ?>>
                  <td class="ticket-row-id">TKT-<?= (int) $ticket['Nummaint'] ?></td>
                  <td class="ticket-row-subject" title="<?= e($ticket['description_problememaint']) ?>"><?= e($ticket['description_problememaint']) ?><small><?= e($ticket['nomequip']) ?></small></td>
                  <td><?= e($ticket['libelletypeequip']) ?></td>
                  <td><span class="ticket-status-badge is-<?= e($statusClass) ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Résolu' : e($ticket['libelle_statut']) ?></span></td>
                  <td><span class="ticket-priority-badge is-<?= e(mb_strtolower($ticket['priorite'])) ?>"><?= e($ticket['priorite']) ?></span></td>
                  <td class="ticket-row-date"><?= dateFr($ticket['date_demandemaint'], true) ?></td>
                  <td><div class="ticket-row-actions">
                    <a class="ticket-view-button" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>"<?= $isSelected ? ' aria-current="true"' : '' ?>><i class="fa-regular fa-eye" aria-hidden="true"></i> Voir</a>
                    <?php if ($maintenance): ?><a class="ticket-view-button ticket-row-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'] . '&vue_ticket=modifier')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier</a><?php endif; ?>
                  </div></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <p class="ticket-no-results" data-ticket-no-results hidden>Aucun ticket ne correspond à ces filtres.</p>
          </div>
          <footer class="ticket-table-footer">
            <span data-ticket-range></span>
            <nav class="ticket-pagination" aria-label="Pagination des tickets">
              <button type="button" data-ticket-prev aria-label="Page précédente"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
              <span data-ticket-pages></span>
              <button type="button" data-ticket-next aria-label="Page suivante"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
            </nav>
          </footer>
        </section>
        <?php endif; ?>

        <?php if ($ticketSelectionne): ?>
        <aside class="maintenance-ticket-detail-panel" data-ticket-detail-panel aria-label="Détails du ticket">
            <?php
            $ticket = $ticketSelectionne;
            $statusClass = match ($ticket['libelle_statut']) {
              'Terminee' => 'resolved',
              'En cours' => 'progress',
              'À valider' => 'validation',
              'En attente de piece' => 'parts',
              default => 'waiting',
            };
            ?>
            <header class="ticket-detail-heading">
              <?php if ($vueEditionTicket): ?>
                <a class="ticket-detail-back" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>" aria-label="Retour au détail du ticket"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier le ticket</h3>
                <a class="ticket-detail-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'])) ?>"><i class="fa-regular fa-eye" aria-hidden="true"></i> Voir</a>
              <?php else: ?>
                <a class="ticket-detail-back" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets)) ?>" aria-label="Retour à la liste des tickets"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
                <h3><i class="fa-solid fa-ticket" aria-hidden="true"></i> Détails du ticket</h3>
                <?php if ($maintenance): ?>
                <a class="ticket-detail-edit" href="<?= e(url('employe/espace.php?onglet=' . $ongletSuiviTickets . '&ticket=' . (int) $ticket['Nummaint'] . '&vue_ticket=modifier')) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier</a>
                <?php endif; ?>
              <?php endif; ?>
            </header>
            <?php if ($vueEditionTicket): ?>
            <div class="ticket-edit-summary">
              <p class="ticket-detail-id">TKT-<?= (int) $ticket['Nummaint'] ?></p>
              <h4><?= e($ticket['description_problememaint']) ?></h4>
              <p><i class="fa-solid fa-cube" aria-hidden="true"></i> <?= e($ticket['nomequip']) ?> · <?= e($ticket['libelletypeequip']) ?></p>
              <span class="ticket-status-badge is-<?= e($statusClass) ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Résolu' : e($ticket['libelle_statut']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!$vueEditionTicket): ?>
            <div class="ticket-detail-title-row">
              <p class="ticket-detail-id">TKT-<?= (int) $ticket['Nummaint'] ?></p>
              <span class="ticket-priority-badge is-<?= e(mb_strtolower($ticket['priorite'])) ?>"><?= e($ticket['priorite']) ?> priorité</span>
            </div>
            <h4 class="ticket-detail-subject"><?= e($ticket['description_problememaint']) ?></h4>
            <?php
            $dateDebutIntervention = null;
            $dateFinIntervention = null;
            foreach ($interventionsTicket as $interventionTicket) {
              if ($dateDebutIntervention === null && in_array($interventionTicket['libelle_statut'], ['En cours', 'En attente de piece', 'À valider', 'Terminee'], true)) {
                $dateDebutIntervention = $interventionTicket['date_intervention'];
              }
              if (in_array($interventionTicket['libelle_statut'], ['À valider', 'Terminee'], true)) {
                $dateFinIntervention = $interventionTicket['date_intervention'];
              }
            }
            $ticketResolu = $ticket['libelle_statut'] === 'Terminee';
            $interventionDemarree = $dateDebutIntervention !== null || in_array($ticket['libelle_statut'], ['En cours', 'En attente de piece', 'À valider', 'Terminee'], true);
            ?>
            <ol class="ticket-progress-steps" aria-label="Avancement du ticket">
              <li class="is-complete"><span><i class="fa-solid fa-check" aria-hidden="true"></i></span><strong>Créé</strong><small><?= dateFr($ticket['date_demandemaint'], true) ?></small></li>
              <li class="<?= $interventionDemarree ? 'is-complete' : '' ?>"><span><?= $interventionDemarree ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : '2' ?></span><strong><?= $interventionDemarree ? 'En cours' : 'En attente' ?></strong><small><?= $dateDebutIntervention ? dateFr($dateDebutIntervention, true) : 'Non renseignée' ?></small></li>
              <li class="<?= $ticketResolu ? 'is-complete' : '' ?>"><span><?= $ticketResolu ? '<i class="fa-solid fa-check" aria-hidden="true"></i>' : '3' ?></span><strong><?= $ticketResolu ? 'Résolu' : 'Résolution' ?></strong><small><?= $dateFinIntervention ? dateFr($dateFinIntervention, true) : 'Non renseignée' ?></small></li>
            </ol>
            <dl class="ticket-detail-facts">
              <div><dt><i class="fa-solid fa-wrench" aria-hidden="true"></i></dt><dd>Catégorie : <?= e($ticket['libelletypeequip']) ?></dd></div>
              <div><dt><i class="fa-solid fa-cube" aria-hidden="true"></i></dt><dd>Équipement : <?= e($ticket['nomequip']) ?> <small>(<?= e($ticket['num_inventaire']) ?>)</small></dd></div>
              <div><dt><i class="fa-solid fa-user" aria-hidden="true"></i></dt><dd>Demandeur : <?= e($ticket['prenomUsers'] . ' ' . $ticket['nomUsers']) ?></dd></div>
              <div class="ticket-detail-description"><dt><i class="fa-solid fa-file-lines" aria-hidden="true"></i></dt><dd>Description<p><?= nl2br(e($ticket['description_problememaint'])) ?></p></dd></div>
            </dl>
            <div class="ticket-detail-assignment">
              <h4><i class="fa-solid fa-wrench" aria-hidden="true"></i> Intervention</h4>
              <p><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Technicien assigné :
                <?php if (!$ticket['technicien_nom']): ?>File commune
                <?php elseif ((int) $ticket['NumUsers_technicien'] === (int) $moi): ?>Vous
                <?php else: ?><?= e($ticket['technicien_prenom'] . ' ' . $ticket['technicien_nom']) ?><?php endif; ?>
              </p>
            </div>
            <?php endif; ?>
            <?php if ($maintenance && $vueEditionTicket): ?>
            <div class="ticket-detail-actions" id="ticket-detail-actions">
              <h4>Actions de maintenance</h4>
              <?php if (
                (int) $ticket['NumUsers_technicien'] === (int) $moi
                && !in_array($ticket['libelle_statut'], ['Terminee', 'À valider'], true)
              ): ?>
                <form class="ticket-comment-form" method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="commenter_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <label class="sr-only" for="ticket-new-comment-<?= (int) $ticket['Nummaint'] ?>">Ajouter un commentaire</label>
                  <textarea id="ticket-new-comment-<?= (int) $ticket['Nummaint'] ?>" name="commentaire" rows="1" maxlength="2000" placeholder="Ajouter un commentaire…" required></textarea>
                  <button type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>Envoyer</span></button>
                </form>
              <?php endif; ?>
              <?php if ($ticket['NumUsers_technicien'] === null && $ticket['libelle_statut'] === 'Ouverte'): ?>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="prendre_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <button class="ticket-action-primary" type="submit"><i class="fa-solid fa-hand" aria-hidden="true"></i> Prendre en charge</button>
                </form>
              <?php elseif (
                (int) $ticket['NumUsers_technicien'] === (int) $moi
                && !in_array($ticket['libelle_statut'], ['Terminee', 'À valider'], true)
              ): ?>
                <form method="post" class="ticket-intervention-form">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="avancer_maintenance">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <label for="ticket-status-<?= (int) $ticket['Nummaint'] ?>">Mettre à jour le statut</label>
                  <select id="ticket-status-<?= (int) $ticket['Nummaint'] ?>" name="statut" required>
                    <?php foreach ($statutsMaint as $statut):
                      if (!in_array($statut['libelle_statut'], $transitionsMaintenance[$ticket['libelle_statut']] ?? [], true)) continue; ?>
                      <option value="<?= (int) $statut['Numstatut'] ?>"><?= e($statut['libelle_statut']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <label for="ticket-comment-<?= (int) $ticket['Nummaint'] ?>">Note d’intervention</label>
                  <textarea id="ticket-comment-<?= (int) $ticket['Nummaint'] ?>" name="commentaire" rows="3" placeholder="Ajouter une note ou un compte rendu…"></textarea>
                  <button class="ticket-action-primary" type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i> Enregistrer l’intervention</button>
                </form>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="detacher_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <button class="ticket-action-secondary" type="submit" data-confirme="Remettre ce ticket dans la file commune pour qu’un autre technicien le prenne ?"><i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Remettre dans la file commune</button>
                </form>
              <?php elseif ($ticket['libelle_statut'] === 'À valider'): ?>
                <p class="ticket-action-notice">Intervention terminée, en attente de validation par le demandeur.</p>
              <?php elseif ($ticket['libelle_statut'] === 'Terminee'): ?>
                <p class="ticket-action-notice is-resolved">Ce ticket est résolu.</p>
              <?php else: ?>
                <p class="ticket-action-notice">Ce ticket est pris en charge par <?= e(trim(($ticket['technicien_prenom'] ?? '') . ' ' . ($ticket['technicien_nom'] ?? ''))) ?>.</p>
              <?php endif; ?>
              <a class="ticket-action-secondary" href="<?= e(url('employe/espace.php?onglet=materiel')) ?>"><i class="fa-solid fa-cube" aria-hidden="true"></i> Consulter le matériel</a>
            </div>
            <?php elseif (!$maintenance): ?>
            <div class="ticket-detail-actions">
              <h4>Suivi de la demande</h4>
              <?php if ($ticket['libelle_statut'] === 'À valider'): ?>
                <p class="ticket-action-notice">L’intervention est terminée. Confirmez sa réalisation pour clôturer votre ticket.</p>
                <form method="post">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="fermer_ticket">
                  <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                  <button class="ticket-action-primary" type="submit" data-confirme="Confirmer que l’intervention est satisfaisante et fermer ce ticket ?"><i class="fa-solid fa-check" aria-hidden="true"></i> Confirmer et fermer</button>
                </form>
              <?php elseif ($ticket['libelle_statut'] === 'Terminee'): ?>
                <p class="ticket-action-notice is-resolved">Votre ticket est résolu.</p>
              <?php elseif ($ticket['libelle_statut'] === 'En cours'): ?>
                <p class="ticket-action-notice">Un technicien intervient actuellement sur votre demande.</p>
              <?php else: ?>
                <p class="ticket-action-notice">Votre demande est enregistrée et suivie par l’équipe de maintenance.</p>
              <?php endif; ?>
              <a class="ticket-action-secondary" href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>"><i class="fa-solid fa-ticket" aria-hidden="true"></i> Signaler un autre problème</a>
            </div>
            <?php endif; ?>
        </aside>
        <?php endif; ?>
      </div>

    <?php endif; ?>
  </section>

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
<?php endif; ?>
<?php if ($maintenance && $onglet === 'materiel' && $equipements): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const rows = [...document.querySelectorAll('[data-inventory-row]')];
  const search = document.querySelector('[data-inventory-search]');
  const category = document.querySelector('[data-inventory-category]');
  const status = document.querySelector('[data-inventory-status]');
  const reset = document.querySelector('[data-inventory-reset]');
  const previous = document.querySelector('[data-inventory-prev]');
  const next = document.querySelector('[data-inventory-next]');
  const pages = document.querySelector('[data-inventory-pages]');
  const range = document.querySelector('[data-inventory-range]');
  const noResults = document.querySelector('[data-inventory-no-results]');
  const pageSize = 6;
  const selectedRow = rows.find(row => row.classList.contains('is-selected'));
  let currentPage = selectedRow ? Math.floor(rows.indexOf(selectedRow) / pageSize) + 1 : 1;

  const render = () => {
    const query = search.value.trim().toLocaleLowerCase('fr');
    const selectedCategory = category.value;
    const selectedStatus = status.value;
    const filteredRows = rows.filter(row =>
      (!selectedCategory || row.dataset.category === selectedCategory)
      && (!selectedStatus || row.dataset.status === selectedStatus)
      && row.dataset.search.includes(query)
    );
    const pageCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
    currentPage = Math.min(currentPage, pageCount);
    const firstIndex = (currentPage - 1) * pageSize;
    const pageRows = filteredRows.slice(firstIndex, firstIndex + pageSize);
    rows.forEach(row => row.hidden = !pageRows.includes(row));
    noResults.hidden = filteredRows.length !== 0;
    range.textContent = filteredRows.length
      ? `Affichage de ${firstIndex + 1} à ${Math.min(firstIndex + pageSize, filteredRows.length)} sur ${filteredRows.length} matériel${filteredRows.length === 1 ? '' : 's'}`
      : 'Aucun matériel à afficher';
    previous.disabled = currentPage <= 1;
    next.disabled = currentPage >= pageCount;
    pages.replaceChildren();
    for (let pageNumber = 1; pageNumber <= pageCount; pageNumber++) {
      const pageButton = document.createElement('button');
      pageButton.type = 'button';
      pageButton.textContent = String(pageNumber);
      pageButton.setAttribute('aria-label', `Page ${pageNumber}`);
      if (pageNumber === currentPage) {
        pageButton.className = 'is-current';
        pageButton.setAttribute('aria-current', 'page');
      }
      pageButton.addEventListener('click', () => {
        currentPage = pageNumber;
        render();
      });
      pages.append(pageButton);
    }
  };

  search.addEventListener('input', () => {
    currentPage = 1;
    render();
  });
  category.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  status.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  reset.addEventListener('click', () => {
    search.value = '';
    category.value = '';
    status.value = '';
    currentPage = 1;
    render();
  });
  previous.addEventListener('click', () => {
    currentPage--;
    render();
  });
  next.addEventListener('click', () => {
    currentPage++;
    render();
  });

  render();
});
</script>
<?php endif; ?>
<?php if (($maintenance && $onglet === 'maintenance' && $maintenances) || ($accueil && $onglet === 'suivi-tickets' && $ticketsEmploye)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const rows = [...document.querySelectorAll('[data-ticket-row]')];
  const search = document.querySelector('[data-ticket-search]');
  const priority = document.querySelector('[data-ticket-priority]');
  const category = document.querySelector('[data-ticket-category]');
  const tabs = [...document.querySelectorAll('[data-status-filter]')];
  const previous = document.querySelector('[data-ticket-prev]');
  const next = document.querySelector('[data-ticket-next]');
  const pages = document.querySelector('[data-ticket-pages]');
  const range = document.querySelector('[data-ticket-range]');
  const noResults = document.querySelector('[data-ticket-no-results]');
  const pageSize = 6;
  let activeStatus = 'all';
  let currentPage = 1;

  const render = () => {
    const query = search.value.trim().toLocaleLowerCase('fr');
    const selectedPriority = priority.value;
    const selectedCategory = category.value;
    const filteredRows = rows.filter(row => {
      const statusMatches = activeStatus === 'all' || row.dataset.statusGroup === activeStatus;
      const priorityMatches = !selectedPriority || row.dataset.priority === selectedPriority;
      const categoryMatches = !selectedCategory || row.dataset.category === selectedCategory;
      return statusMatches && priorityMatches && categoryMatches && row.dataset.search.includes(query);
    });
    const pageCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
    currentPage = Math.min(currentPage, pageCount);
    const firstIndex = (currentPage - 1) * pageSize;
    const pageRows = filteredRows.slice(firstIndex, firstIndex + pageSize);
    rows.forEach(row => row.hidden = !pageRows.includes(row));
    noResults.hidden = filteredRows.length !== 0;
    range.textContent = filteredRows.length
      ? `Affichage de ${firstIndex + 1} à ${Math.min(firstIndex + pageSize, filteredRows.length)} sur ${filteredRows.length} ticket${filteredRows.length === 1 ? '' : 's'}`
      : 'Aucun ticket à afficher';
    previous.disabled = currentPage <= 1;
    next.disabled = currentPage >= pageCount;
    pages.replaceChildren();
    for (let pageNumber = 1; pageNumber <= pageCount; pageNumber++) {
      const pageButton = document.createElement('button');
      pageButton.type = 'button';
      pageButton.textContent = String(pageNumber);
      pageButton.setAttribute('aria-label', `Page ${pageNumber}`);
      if (pageNumber === currentPage) {
        pageButton.className = 'is-current';
        pageButton.setAttribute('aria-current', 'page');
      }
      pageButton.addEventListener('click', () => {
        currentPage = pageNumber;
        render();
      });
      pages.append(pageButton);
    }
  };

  tabs.forEach(tab => tab.addEventListener('click', () => {
    activeStatus = tab.dataset.statusFilter;
    currentPage = 1;
    tabs.forEach(item => {
      const active = item === tab;
      item.classList.toggle('is-active', active);
      item.setAttribute('aria-pressed', String(active));
    });
    render();
  }));
  search.addEventListener('input', () => {
    currentPage = 1;
    render();
  });
  priority.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  category.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  previous.addEventListener('click', () => {
    currentPage--;
    render();
  });
  next.addEventListener('click', () => {
    currentPage++;
    render();
  });

  render();
});
</script>
<?php endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
