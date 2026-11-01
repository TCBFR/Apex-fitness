<?php
require_once __DIR__ . '/../avis/controller/AvisController.php';
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/../utilisateur/model/utilisateur.php';
require_once __DIR__ . '/../planning/controller/planning.php';
require_once __DIR__ . '/../activite/model/ActiviteCatalogue.php';
require_once __DIR__ . '/../salle/model/SalleAdmin.php';
require_once __DIR__ . '/../employe/service/EquipmentInventorySchema.php';
exige_role('ADMIN');

$ongletsAutorises = [
    'dashboard', 'utilisateurs', 'adherents', 'coachs',
    'abonnements', 'paiements', 'planning', 'activites',
    'reservations', 'salles', 'equipements', 'maintenance',
    'avis', 'messages'
];

$onglet = $_GET['onglet'] ?? 'dashboard';
if (!is_string($onglet) || !in_array($onglet, $ongletsAutorises, true)) {
    $onglet = 'dashboard';
}

$vueCompte = $_GET['compte'] ?? 'liste';
$vueMaintenance = $_GET['maintenance'] ?? 'liste';
$pdo    = db();
if ($onglet === 'equipements') EquipmentInventorySchema::ensure($pdo);
$gestionPlanning = new PlanningController();
if ($onglet === 'salles') {
    SalleAdmin::assurer($pdo);
}
if ($onglet === 'equipements') {
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
    if (!$pdo->query("SHOW COLUMNS FROM demande_maintenance LIKE 'NumUsers_technicien'")->fetch()) {
        $pdo->exec('ALTER TABLE demande_maintenance ADD COLUMN NumUsers_technicien INT NULL AFTER NumUsers');
    }
}
if ($onglet === 'activites') {
    ActiviteCatalogue::assurer($pdo);
}
if ($onglet === 'abonnements') {
    $statutSouscrireColonne = $pdo->query("SHOW COLUMNS FROM souscrire LIKE 'statut_souscription'")->fetch(PDO::FETCH_ASSOC);
    if ($statutSouscrireColonne && !str_contains((string) $statutSouscrireColonne['Type'], 'Suspendue')) {
        $pdo->exec("ALTER TABLE souscrire MODIFY statut_souscription ENUM('En cours','Suspendue','Terminee','Resiliee') NOT NULL DEFAULT 'En cours'");
    }
}
if ($onglet === 'adherents') {
    Utilisateur::assurerColonneSexe($pdo);
}
Utilisateur::assurerColonneCommuneEmploye($pdo);
$fonctions = Utilisateur::toutesLesFonctions();
$specialisationsCoachNoms = [
    'Danse fitness', 'Yoga', 'Coach de performance', 'Boxe', 'Coach principal', 'Pilates',
];
if ($onglet === 'coachs') {
    $rechercheFonction = $pdo->prepare('SELECT Numfonc FROM fonction WHERE LOWER(libellefonc) = LOWER(?) LIMIT 1');
    foreach ($specialisationsCoachNoms as $specialisation) {
        $rechercheFonction->execute([$specialisation]);
        if (!$rechercheFonction->fetchColumn()) {
            $pdo->prepare('INSERT INTO fonction (libellefonc) VALUES (?)')->execute([$specialisation]);
        }
    }
    $fonctions = Utilisateur::toutesLesFonctions();
}
$fonctionsCoach = array_values(array_filter(
    $fonctions,
    static fn(array $fonction): bool => in_array(
        mb_strtolower((string) $fonction['libellefonc']),
        array_map('mb_strtolower', $specialisationsCoachNoms),
        true
    )
));
$nomsFonctionsCoachCompat = [...array_map('mb_strtolower', $specialisationsCoachNoms), 'coach'];
$placeholdersFonctionsCoach = implode(',', array_fill(0, count($nomsFonctionsCoachCompat), '?'));
$fonctionsEmployeNoms = array_values(array_filter(
    $fonctions,
    static function (array $fonction): bool {
        $nom = mb_strtolower((string) $fonction['libellefonc']);
        return str_contains($nom, 'maintenance') || str_contains($nom, 'accueil');
    }
));
$idsFonctionsEmploye = array_map('intval', array_column($fonctionsEmployeNoms, 'Numfonc'));
$fonctionMaintenance = null;
foreach ($fonctions as $fonction) {
    if (str_contains(strtolower($fonction['libellefonc']), 'maintenance')) {
        $fonctionMaintenance = $fonction;
        break;
    }
}

/* =====================================================================
   ACTIONS
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

    if ($onglet === 'equipements' && in_array($action, [
        'valider_commande_materiel', 'refuser_commande_materiel', 'recevoir_commande_materiel',
    ], true)) {
        $idCommande = filter_var($_POST['commande'] ?? null, FILTER_VALIDATE_INT);
        try {
            if (!$idCommande || $idCommande < 1) throw new RuntimeException('Cette commande est invalide.');
            $pdo->beginTransaction();
            $commandeQuery = $pdo->prepare(
                'SELECT Numcommande, Numequip, quantite, statut_commande
                   FROM commande_materiel WHERE Numcommande = ? FOR UPDATE'
            );
            $commandeQuery->execute([$idCommande]);
            $commande = $commandeQuery->fetch(PDO::FETCH_ASSOC);
            if (!$commande) throw new RuntimeException('Cette commande est introuvable.');

            if ($action === 'valider_commande_materiel' || $action === 'refuser_commande_materiel') {
                if ($commande['statut_commande'] !== 'En attente') {
                    throw new RuntimeException('Cette commande a déjà été traitée.');
                }
                $nouveauStatut = $action === 'valider_commande_materiel' ? 'Approuvée' : 'Refusée';
                $pdo->prepare(
                    'UPDATE commande_materiel
                     SET statut_commande = ?, date_decision = NOW(), NumUsers_admin = ?
                     WHERE Numcommande = ?'
                )->execute([$nouveauStatut, (int) utilisateur()['NumUsers'], $idCommande]);
            } else {
                if ($commande['statut_commande'] !== 'Approuvée') {
                    throw new RuntimeException('Seule une commande approuvée peut être réceptionnée.');
                }
                $materielQuery = $pdo->prepare('SELECT Numequip FROM equipement WHERE Numequip = ? FOR UPDATE');
                $materielQuery->execute([(int) $commande['Numequip']]);
                if (!$materielQuery->fetchColumn()) {
                    throw new RuntimeException('Le matériel de cette commande est introuvable.');
                }
                $pdo->prepare('UPDATE equipement SET stock_actuel = stock_actuel + ? WHERE Numequip = ?')
                    ->execute([(int) $commande['quantite'], (int) $commande['Numequip']]);
                $pdo->prepare(
                    "INSERT INTO mouvement_stock (Numequip, NumUsers, type_mouvement, quantite, commentaire)
                     VALUES (?, ?, 'ENTREE', ?, ?)"
                )->execute([
                    (int) $commande['Numequip'],
                    (int) utilisateur()['NumUsers'],
                    (int) $commande['quantite'],
                    'Réception de la commande #' . (int) $idCommande,
                ]);
                $pdo->prepare(
                    "UPDATE commande_materiel
                     SET statut_commande = 'Reçue', date_reception = NOW()
                     WHERE Numcommande = ?"
                )->execute([$idCommande]);
            }
            $pdo->commit();
            journalise(strtoupper($action), 'Numcommande=' . $idCommande);
            flash('succes', match ($action) {
                'valider_commande_materiel' => 'La commande a été approuvée. Le stock sera mis à jour à sa réception.',
                'refuser_commande_materiel' => 'La commande a été refusée.',
                default => 'La réception a été enregistrée et le stock mis à jour.',
            });
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'La commande n’a pas pu être mise à jour.');
            if (!$exception instanceof RuntimeException) {
                error_log('Traitement de commande matériel impossible : ' . $exception->getMessage());
            }
        }
        redirige('admin/espace.php?onglet=equipements');
    }

    if ($onglet === 'planning' && PlanningController::estAction('admin', (string) $action)) {
        $semaineRetour = (string) ($_POST['semaine'] ?? '');
        $retourPlanning = 'admin/espace.php?onglet=planning';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $semaineRetour)) {
            $retourPlanning .= '&semaine=' . rawurlencode($semaineRetour);
        }
        $gestionPlanning->traiter('admin', 0, $retourPlanning . '#planning-calendrier');
    }

    if ($onglet === 'reservations' && in_array($action, ['ajouter_reservation_admin', 'annuler_reservation_admin', 'modifier_reservation_admin', 'retirer_attente_admin'], true)) {
        $semaineRetourReservation = (string) ($_POST['semaine'] ?? '');
        $retourReservation = 'admin/espace.php?onglet=reservations';
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $semaineRetourReservation)) $retourReservation .= '&semaine=' . rawurlencode($semaineRetourReservation);
        try {
            if ($action === 'ajouter_reservation_admin') {
                $idAdherentReservation = (int) ($_POST['adherent'] ?? 0);
                $idActiviteReservation = (int) ($_POST['activite'] ?? 0);
                $pdo->beginTransaction();
                $stmtCoursReservation = $pdo->prepare("SELECT a.capacite_max,a.date_heure_debut,(SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle WHERE a.Numactivite=? AND s.etatsalle='Ouverte' FOR UPDATE");
                $stmtCoursReservation->execute([$idActiviteReservation]);
                $coursReservationAdmin = $stmtCoursReservation->fetch(PDO::FETCH_ASSOC);
                if (!$coursReservationAdmin || strtotime($coursReservationAdmin['date_heure_debut']) < time()) throw new RuntimeException('Choisissez un cours à venir dans une salle disponible.');
                if ((int) $coursReservationAdmin['places_prises'] >= (int) $coursReservationAdmin['capacite_max']) throw new RuntimeException('Ce cours est complet.');
                $stmtAdherentReservation = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers=?');
                $stmtAdherentReservation->execute([$idAdherentReservation]);
                if (!$stmtAdherentReservation->fetchColumn()) throw new RuntimeException('Choisissez un adhérent valide.');
                $stmtReservationExistante = $pdo->prepare('SELECT Numres,statut_reservation FROM reservation WHERE NumUsers=? AND Numactivite=? FOR UPDATE');
                $stmtReservationExistante->execute([$idAdherentReservation, $idActiviteReservation]);
                $reservationExistanteAdmin = $stmtReservationExistante->fetch(PDO::FETCH_ASSOC);
                if ($reservationExistanteAdmin && $reservationExistanteAdmin['statut_reservation'] !== 'Annulee') throw new RuntimeException('Cet adhérent possède déjà une réservation pour ce cours.');
                if ($reservationExistanteAdmin) {
                    $pdo->prepare("UPDATE reservation SET statut_reservation='Reservee',date_heure_reservation=NOW() WHERE Numres=?")->execute([(int) $reservationExistanteAdmin['Numres']]);
                } else {
                    $pdo->prepare('INSERT INTO reservation (NumUsers,Numactivite) VALUES (?,?)')->execute([$idAdherentReservation, $idActiviteReservation]);
                }
                $pdo->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$idAdherentReservation, $idActiviteReservation]);
                $pdo->commit();
                journalise('CREATION_RESERVATION_ADMIN', 'NumUsers=' . $idAdherentReservation . ', Numactivite=' . $idActiviteReservation);
                flash('succes', 'Réservation ajoutée.');
            } elseif ($action === 'annuler_reservation_admin') {
                $idReservationAdmin = (int) ($_POST['reservation'] ?? 0);
                $stmtReservationAdmin = $pdo->prepare("UPDATE reservation SET statut_reservation='Annulee' WHERE Numres=? AND statut_reservation <> 'Annulee'");
                $stmtReservationAdmin->execute([$idReservationAdmin]);
                if (!$stmtReservationAdmin->rowCount()) throw new RuntimeException('Cette réservation est introuvable ou déjà annulée.');
                journalise('ANNULATION_RESERVATION_ADMIN', 'Numres=' . $idReservationAdmin);
                flash('succes', 'Réservation annulée.');
            } elseif ($action === 'retirer_attente_admin') {
                $idAttenteAdmin = (int) ($_POST['attente'] ?? 0);
                $stmtAttenteAdmin = $pdo->prepare('DELETE FROM liste_attente WHERE Numattente=?');
                $stmtAttenteAdmin->execute([$idAttenteAdmin]);
                if (!$stmtAttenteAdmin->rowCount()) throw new RuntimeException('Cette demande en attente est introuvable.');
                journalise('SUPPRESSION_ATTENTE_ADMIN', 'Numattente=' . $idAttenteAdmin);
                flash('succes', 'Demande retirée de la liste d’attente.');
            } else {
                $idReservationAdmin = (int) ($_POST['reservation'] ?? 0);
                $nouvelleActiviteAdmin = (int) ($_POST['activite'] ?? 0);
                $pdo->beginTransaction();
                $stmtReservationAdmin = $pdo->prepare("SELECT NumUsers, statut_reservation FROM reservation WHERE Numres=? FOR UPDATE");
                $stmtReservationAdmin->execute([$idReservationAdmin]);
                $reservationAdmin = $stmtReservationAdmin->fetch(PDO::FETCH_ASSOC);
                if (!$reservationAdmin || $reservationAdmin['statut_reservation'] === 'Annulee') throw new RuntimeException('Cette réservation ne peut plus être modifiée.');
                $stmtActiviteAdmin = $pdo->prepare("SELECT a.capacite_max,a.date_heure_debut, (SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation <> 'Annulee' AND r.Numres <> ?) AS places_prises FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle WHERE a.Numactivite=? AND s.etatsalle='Ouverte' FOR UPDATE");
                $stmtActiviteAdmin->execute([$idReservationAdmin, $nouvelleActiviteAdmin]);
                $activiteAdmin = $stmtActiviteAdmin->fetch(PDO::FETCH_ASSOC);
                if (!$activiteAdmin || strtotime($activiteAdmin['date_heure_debut']) < time()) throw new RuntimeException('Choisissez un cours à venir dans une salle disponible.');
                if ((int) $activiteAdmin['places_prises'] >= (int) $activiteAdmin['capacite_max']) throw new RuntimeException('Ce cours est complet.');
                $stmtDoublonAdmin = $pdo->prepare('SELECT 1 FROM reservation WHERE NumUsers=? AND Numactivite=? AND Numres<>? AND statut_reservation <> \'Annulee\'');
                $stmtDoublonAdmin->execute([(int) $reservationAdmin['NumUsers'], $nouvelleActiviteAdmin, $idReservationAdmin]);
                if ($stmtDoublonAdmin->fetchColumn()) throw new RuntimeException('Cet adhérent est déjà inscrit à ce cours.');
                $pdo->prepare('UPDATE reservation SET Numactivite=? WHERE Numres=?')->execute([$nouvelleActiviteAdmin, $idReservationAdmin]);
                $pdo->commit();
                journalise('MODIFICATION_RESERVATION_ADMIN', 'Numres=' . $idReservationAdmin . ', Numactivite=' . $nouvelleActiviteAdmin);
                flash('succes', 'Réservation déplacée vers le nouveau cours.');
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige($retourReservation . '#liste-reservations');
    }

    if ($onglet === 'salles' && in_array($action, ['creer_salle', 'modifier_salle', 'supprimer_salle'], true)) {
        $retourSalles = 'admin/espace.php?onglet=salles';
        $nouvelleImageSalle = null;
        try {
            if ($action === 'supprimer_salle') {
                $idSalle = filter_var($_POST['salle'] ?? null, FILTER_VALIDATE_INT);
                $querySalle = $pdo->prepare('SELECT Numsalle, image_salle FROM salle WHERE Numsalle=?');
                $querySalle->execute([$idSalle]);
                $salleASupprimer = $querySalle->fetch(PDO::FETCH_ASSOC);
                if (!$salleASupprimer) throw new RuntimeException('Cette salle est introuvable.');
                $pdo->prepare('DELETE FROM salle WHERE Numsalle=?')->execute([(int) $idSalle]);
                SalleAdmin::supprimerImage($salleASupprimer['image_salle'] ?? null);
                journalise('SUPPRESSION_SALLE', 'Numsalle=' . (int) $idSalle);
                flash('succes', 'Salle supprimée.');
            } else {
                [$nomSalle, $capaciteSalle, $localisationSalle, $superficieSalle, $etatSalle, $descriptionSalle] = SalleAdmin::valider($_POST);
                $nouvelleImageSalle = SalleAdmin::recevoirImage($_FILES['image'] ?? []);
                if ($action === 'creer_salle') {
                    $pdo->prepare('INSERT INTO salle (nomsalle,capacitesalle,localisationsalle,superficiesalle,etatsalle,description_salle,image_salle) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$nomSalle, $capaciteSalle, $localisationSalle, $superficieSalle, $etatSalle, $descriptionSalle, $nouvelleImageSalle]);
                    $idSalle = (int) $pdo->lastInsertId();
                    $nouvelleImageSalle = null;
                    journalise('CREATION_SALLE', 'Numsalle=' . $idSalle);
                    flash('succes', 'Salle ajoutée à la liste.');
                    $retourSalles .= '&id=' . $idSalle;
                } else {
                    $idSalle = filter_var($_POST['salle'] ?? null, FILTER_VALIDATE_INT);
                    $querySalle = $pdo->prepare('SELECT Numsalle, image_salle FROM salle WHERE Numsalle=?');
                    $querySalle->execute([$idSalle]);
                    $salleAModifier = $querySalle->fetch(PDO::FETCH_ASSOC);
                    if (!$salleAModifier) throw new RuntimeException('Cette salle est introuvable.');
                    $ancienneImageSalle = $salleAModifier['image_salle'] ?? null;
                    $imageSalle = $nouvelleImageSalle ?? ($ancienneImageSalle && isset($_POST['retirer_image']) ? null : $ancienneImageSalle);
                    $pdo->prepare('UPDATE salle SET nomsalle=?,capacitesalle=?,localisationsalle=?,superficiesalle=?,etatsalle=?,description_salle=?,image_salle=? WHERE Numsalle=?')
                        ->execute([$nomSalle, $capaciteSalle, $localisationSalle, $superficieSalle, $etatSalle, $descriptionSalle, $imageSalle, (int) $idSalle]);
                    if ($nouvelleImageSalle || ($ancienneImageSalle && isset($_POST['retirer_image']))) SalleAdmin::supprimerImage($ancienneImageSalle);
                    $nouvelleImageSalle = null;
                    journalise('MODIFICATION_SALLE', 'Numsalle=' . (int) $idSalle);
                    flash('succes', 'Salle mise à jour.');
                    $retourSalles .= '&id=' . (int) $idSalle;
                }
            }
        } catch (PDOException $exception) {
            SalleAdmin::supprimerImage($nouvelleImageSalle);
            flash('erreur', $action === 'supprimer_salle' ? 'Cette salle est utilisée par le planning et ne peut pas être supprimée.' : 'La salle n’a pas pu être enregistrée. Vérifiez ses informations.');
            if ($action === 'modifier_salle') $retourSalles .= '&vue=modifier&id=' . (int) ($_POST['salle'] ?? 0);
            elseif ($action === 'creer_salle') $retourSalles .= '&vue=creer';
        } catch (Throwable $exception) {
            SalleAdmin::supprimerImage($nouvelleImageSalle);
            flash('erreur', $exception->getMessage());
            if ($action === 'modifier_salle') $retourSalles .= '&vue=modifier&id=' . (int) ($_POST['salle'] ?? 0);
            elseif ($action === 'creer_salle') $retourSalles .= '&vue=creer';
        }
        redirige($retourSalles . '#salle-formulaire');
    }

    if ($onglet === 'activites' && in_array($action, ['creer_activite_catalogue', 'modifier_activite_catalogue', 'supprimer_activite_catalogue'], true)) {
        $retourActivites = 'admin/espace.php?onglet=activites';
        $nouvelleImageActivite = null;
        try {
            if ($action === 'supprimer_activite_catalogue') {
                $idActivite = filter_var($_POST['activite'] ?? null, FILTER_VALIDATE_INT);
                $activiteASupprimer = $idActivite ? ActiviteCatalogue::trouver($pdo, (int) $idActivite) : null;
                if (!$activiteASupprimer) throw new RuntimeException('Activité introuvable.');
                $pdo->prepare('DELETE FROM activite_catalogue WHERE Numcatalogue=?')->execute([(int) $idActivite]);
                ActiviteCatalogue::supprimerImage($activiteASupprimer['image_path'] ?? null);
                journalise('SUPPRESSION_ACTIVITE', 'Numcatalogue=' . (int) $idActivite);
                flash('succes', 'Activité supprimée de la page publique.');
            } else {
                [$titreActivite, $typeActivite, $descriptionActivite] = ActiviteCatalogue::valider($_POST);
                $nouvelleImageActivite = ActiviteCatalogue::recevoirImage($_FILES['image'] ?? []);
                if ($action === 'creer_activite_catalogue') {
                    $pdo->prepare('INSERT INTO activite_catalogue (titre,type_activite,description,image_path) VALUES (?,?,?,?)')
                        ->execute([$titreActivite, $typeActivite, $descriptionActivite, $nouvelleImageActivite]);
                    $nouvelleImageActivite = null;
                    journalise('CREATION_ACTIVITE', 'type=' . $typeActivite);
                    flash('succes', 'Activité ajoutée à la page publique.');
                } else {
                    $idActivite = filter_var($_POST['activite'] ?? null, FILTER_VALIDATE_INT);
                    $activiteAModifier = $idActivite ? ActiviteCatalogue::trouver($pdo, (int) $idActivite) : null;
                    if (!$activiteAModifier) throw new RuntimeException('Activité introuvable.');
                    $ancienneImageActivite = $activiteAModifier['image_path'] ?? null;
                    $imageAConserver = $nouvelleImageActivite ?? ($ancienneImageActivite && isset($_POST['retirer_image']) ? null : $ancienneImageActivite);
                    $pdo->prepare('UPDATE activite_catalogue SET titre=?,type_activite=?,description=?,image_path=? WHERE Numcatalogue=?')
                        ->execute([$titreActivite, $typeActivite, $descriptionActivite, $imageAConserver, (int) $idActivite]);
                    if ($nouvelleImageActivite || ($ancienneImageActivite && isset($_POST['retirer_image']))) {
                        ActiviteCatalogue::supprimerImage($ancienneImageActivite);
                    }
                    $nouvelleImageActivite = null;
                    journalise('MODIFICATION_ACTIVITE', 'Numcatalogue=' . (int) $idActivite);
                    flash('succes', 'Activité modifiée sur la page publique.');
                    $retourActivites .= '&activite_id=' . (int) $idActivite . '&vue=voir';
                }
            }
        } catch (Throwable $exception) {
            ActiviteCatalogue::supprimerImage($nouvelleImageActivite);
            flash('erreur', $exception->getMessage());
            if ($action === 'modifier_activite_catalogue') $retourActivites .= '&activite_id=' . (int) ($_POST['activite'] ?? 0) . '&vue=modifier';
            elseif ($action === 'creer_activite_catalogue') $retourActivites .= '&vue=creer';
        }
        redirige($retourActivites);
    }

    if ($onglet === 'abonnements' && $action === 'creer_abonnement') {
        try {
            $adherentId = filter_var($_POST['adherent'] ?? null, FILTER_VALIDATE_INT);
            $formuleId = filter_var($_POST['formule'] ?? null, FILTER_VALIDATE_INT);
            $debut = trim((string) ($_POST['date_debut'] ?? ''));
            $fin = trim((string) ($_POST['date_fin'] ?? ''));
            $adherentCheck = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers=?');
            $adherentCheck->execute([$adherentId]);
            $formuleCheck = $pdo->prepare('SELECT 1 FROM abonnement WHERE Numabo=? AND actif_abo=1');
            $formuleCheck->execute([$formuleId]);
            if (!$adherentCheck->fetchColumn() || !$formuleCheck->fetchColumn()) throw new RuntimeException('Choisissez un adhérent et une formule valides.');
            $debutValide = DateTimeImmutable::createFromFormat('!Y-m-d', $debut);
            $finValide = $fin === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $fin);
            if (!$debutValide || $debutValide->format('Y-m-d') !== $debut || ($fin !== '' && (!$finValide || $finValide->format('Y-m-d') !== $fin || $finValide < $debutValide))) {
                throw new RuntimeException('Vérifiez les dates de l’abonnement.');
            }
            $pdo->prepare("INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription) VALUES (?, ?, ?, ?, 'En cours')")
                ->execute([(int) $adherentId, (int) $formuleId, $debut, $fin !== '' ? $fin : null]);
            journalise('CREATION_ABONNEMENT', 'NumUsers=' . (int) $adherentId . ', Numabo=' . (int) $formuleId);
            flash('succes', 'Abonnement créé.');
        } catch (Throwable $exception) { flash('erreur', $exception->getMessage()); }
        redirige('admin/espace.php?onglet=abonnements');
    }

    if ($onglet === 'abonnements' && $action === 'modifier_abonnement_admin') {
        $adherentId = (int) ($_POST['adherent'] ?? 0);
        $formuleId = (int) ($_POST['formule_actuelle'] ?? 0);
        $dateDebutActuelle = trim((string) ($_POST['date_debut_actuelle'] ?? ''));
        $dateFinNouvelle = trim((string) ($_POST['date_fin'] ?? ''));
        try {
            $dateDebutValidee = DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebutActuelle);
            $dateFinValidee = $dateFinNouvelle === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $dateFinNouvelle);
            if (!$adherentId || !$formuleId || !$dateDebutValidee || $dateDebutValidee->format('Y-m-d') !== $dateDebutActuelle) throw new RuntimeException('Abonnement introuvable.');
            if ($dateFinNouvelle !== '' && (!$dateFinValidee || $dateFinValidee->format('Y-m-d') !== $dateFinNouvelle || $dateFinValidee < $dateDebutValidee)) throw new RuntimeException('La date d’échéance doit être valide et postérieure au début de l’abonnement.');
            $pdo->beginTransaction();
            $verifierAbo = $pdo->prepare('SELECT 1 FROM souscrire WHERE NumUsers=? AND Numabo=? AND date_debut=? FOR UPDATE');
            $verifierAbo->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            if (!$verifierAbo->fetchColumn()) throw new RuntimeException('Abonnement introuvable.');
            $pdo->prepare('UPDATE souscrire SET date_fin=? WHERE NumUsers=? AND Numabo=? AND date_debut=?')->execute([$dateFinNouvelle !== '' ? $dateFinNouvelle : null, $adherentId, $formuleId, $dateDebutActuelle]);
            $pdo->commit();
            journalise('MODIFICATION_ECHEANCE_ABONNEMENT', 'NumUsers=' . $adherentId . ', Numabo=' . $formuleId);
            flash('succes', 'Échéance de l’abonnement mise à jour.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=abonnements&id=' . $adherentId . '&formule=' . $formuleId . '&debut=' . rawurlencode($dateDebutActuelle));
    }

    if ($onglet === 'abonnements' && in_array($action, ['resilier_abonnement', 'suspendre_abonnement', 'reactiver_abonnement', 'renouveler_abonnement', 'changer_formule_abonnement'], true)) {
        $adherentId = (int) ($_POST['adherent'] ?? 0);
        $formuleId = (int) ($_POST['formule_actuelle'] ?? 0);
        $dateDebutActuelle = (string) ($_POST['date_debut_actuelle'] ?? '');
        try {
            $dateCheck = DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebutActuelle);
            if (!$adherentId || !$formuleId || !$dateCheck || $dateCheck->format('Y-m-d') !== $dateDebutActuelle) throw new RuntimeException('Abonnement introuvable.');
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT statut_souscription FROM souscrire WHERE NumUsers=? AND Numabo=? AND date_debut=? FOR UPDATE');
            $lock->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            $statutActuel = $lock->fetchColumn();
            if (!$statutActuel) throw new RuntimeException('Abonnement introuvable.');
            if ($action === 'resilier_abonnement') {
                $pdo->prepare("UPDATE souscrire SET statut_souscription='Resiliee', date_fin=CURDATE() WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            } elseif ($action === 'suspendre_abonnement') {
                $pdo->prepare("UPDATE souscrire SET statut_souscription='Suspendue' WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            } elseif ($action === 'reactiver_abonnement') {
                $dateSet = $statutActuel === 'Resiliee' ? ', date_fin=NULL' : '';
                $pdo->prepare("UPDATE souscrire SET statut_souscription='En cours' $dateSet WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            } else {
                $nouvelleFormule = $action === 'renouveler_abonnement' ? $formuleId : (int) ($_POST['nouvelle_formule'] ?? 0);
                $offreQuery = $pdo->prepare('SELECT o.type_offre, b.duree_engagement_mois FROM abonnement a JOIN offre o ON o.Numoffre=a.Numoffre LEFT JOIN basic b ON b.Numoffre=o.Numoffre WHERE a.Numabo=? AND a.actif_abo=1');
                $offreQuery->execute([$nouvelleFormule]);
                $offre = $offreQuery->fetch(PDO::FETCH_ASSOC);
                if (!$offre) throw new RuntimeException('Choisissez une formule active.');
                $finExistanteQuery = $pdo->prepare('SELECT date_fin FROM souscrire WHERE NumUsers=? AND Numabo=? AND date_debut=?');
                $finExistanteQuery->execute([$adherentId, $formuleId, $dateDebutActuelle]);
                $finExistante = $finExistanteQuery->fetchColumn();
                $nouveauDebut = $finExistante && $finExistante >= date('Y-m-d') ? (new DateTimeImmutable($finExistante))->modify('+1 day') : new DateTimeImmutable('today');
                $mois = $offre['type_offre'] === 'BASIC' ? max(1, (int) $offre['duree_engagement_mois']) : 1;
                $nouvelleFin = $nouveauDebut->modify('+' . $mois . ' months')->modify('-1 day');
                $pdo->prepare("UPDATE souscrire SET statut_souscription='Terminee' WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
                $pdo->prepare("INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription) VALUES (?, ?, ?, ?, 'En cours')")
                    ->execute([$adherentId, $nouvelleFormule, $nouveauDebut->format('Y-m-d'), $nouvelleFin->format('Y-m-d')]);
            }
            $pdo->commit();
            journalise(strtoupper($action), 'NumUsers=' . $adherentId . ', Numabo=' . $formuleId);
            flash('succes', $action === 'resilier_abonnement' ? 'Abonnement résilié.' : ($action === 'suspendre_abonnement' ? 'Abonnement suspendu.' : ($action === 'reactiver_abonnement' ? 'Abonnement réactivé.' : 'Abonnement renouvelé ou formule modifiée.')));
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=abonnements&id=' . $adherentId . '&formule=' . $formuleId . '&debut=' . rawurlencode($dateDebutActuelle));
    }

    if ($action === 'creer_utilisateur') {
        try {
            $num = Utilisateur::creerCompte($_POST);
            journalise('CREATION_UTILISATEUR', 'NumUsers=' . $num);
            flash('succes', 'Compte créé.');
        } catch (Throwable $exception) {
            $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail est déjà utilisée.'
                : $exception->getMessage();
            flash('erreur', $message);
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }

    if ($action === 'creer_technicien') {
        try {
            if ($fonctionMaintenance === null) {
                throw new RuntimeException('Aucune fonction de maintenance n’est configurée.');
            }
            $donnees = $_POST;
            $donnees['roles'] = ['EMPLOYE'];
            $donnees['fonction'] = (int) $fonctionMaintenance['Numfonc'];
            $num = Utilisateur::creerCompte($donnees);
            journalise('CREATION_TECHNICIEN_MAINTENANCE', 'NumUsers=' . $num);
            flash('succes', 'Compte technicien de maintenance créé.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=maintenance');
    }

    if ($action === 'creer_coach') {
        try {
            if ((string) ($_POST['motdepasse'] ?? '') !== (string) ($_POST['confirmation'] ?? '')) {
                throw new InvalidArgumentException('Les mots de passe ne correspondent pas.');
            }
            $fonctionId = filter_var($_POST['fonction'] ?? null, FILTER_VALIDATE_INT);
            $fonctionValide = false;
            foreach ($fonctionsCoach as $fonction) {
                if ((int) $fonction['Numfonc'] === (int) $fonctionId) {
                    $fonctionValide = true;
                    break;
                }
            }
            if (!$fonctionValide) throw new InvalidArgumentException('Choisissez une spécialisation de coach valide.');
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $communeQuery = $pdo->prepare('SELECT id_commune FROM commune WHERE id_commune=?');
            $communeQuery->execute([$communeId]);
            if (!$communeQuery->fetchColumn()) throw new InvalidArgumentException('Choisissez une commune dans la liste.');

            $donnees = $_POST;
            $donnees['roles'] = ['EMPLOYE'];
            $donnees['fonction'] = (int) $fonctionId;
            $num = Utilisateur::creerCompte($donnees);
            $pdo->prepare('UPDATE employe SET id_commune=? WHERE NumUsers=?')->execute([$communeId, $num]);
            journalise('CREATION_COACH', 'NumUsers=' . $num);
            flash('succes', 'Le compte coach a été créé avec succès.');
        } catch (Throwable $exception) {
            $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail ou ce matricule est déjà utilisé.'
                : $exception->getMessage();
            flash('erreur', $message);
        }
        redirige('admin/espace.php?onglet=coachs');
    }

    if ($action === 'creer_employe') {
        try {
            if ((string) ($_POST['motdepasse'] ?? '') !== (string) ($_POST['confirmation'] ?? '')) {
                throw new InvalidArgumentException('Les mots de passe ne correspondent pas.');
            }
            $fonctionId = filter_var($_POST['fonction'] ?? null, FILTER_VALIDATE_INT);
            if (!$fonctionId || !in_array((int) $fonctionId, $idsFonctionsEmploye, true)) {
                throw new InvalidArgumentException('Choisissez la fonction Maintenance ou Accueil.');
            }
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $communeQuery = $pdo->prepare('SELECT id_commune FROM commune WHERE id_commune=?');
            $communeQuery->execute([$communeId]);
            if (!$communeQuery->fetchColumn()) throw new InvalidArgumentException('Choisissez une commune dans la liste.');
            $donnees = $_POST;
            $donnees['roles'] = ['EMPLOYE'];
            $donnees['fonction'] = (int) $fonctionId;
            $num = Utilisateur::creerCompte($donnees);
            $pdo->prepare('UPDATE employe SET id_commune=? WHERE NumUsers=?')->execute([$communeId, $num]);
            journalise('CREATION_EMPLOYE', 'NumUsers=' . $num);
            flash('succes', 'Le compte employé a été créé.');
        } catch (Throwable $exception) {
            $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail ou ce matricule est déjà utilisé.'
                : $exception->getMessage();
            flash('erreur', $message);
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }

    if ($action === 'modifier_technicien') {
        $num = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $technicien = $pdo->prepare(
                'SELECT e.NumUsers FROM employe e
                 JOIN fonction f ON f.Numfonc = e.Numfonc
                 WHERE e.NumUsers = ? AND LOWER(f.libellefonc) LIKE ?'
            );
            $technicien->execute([$num, '%maintenance%']);
            if (!$technicien->fetchColumn()) {
                throw new RuntimeException('Ce compte n’est pas un technicien de maintenance.');
            }
            Utilisateur::modifierCompte($num, $_POST);
            journalise('MODIFICATION_TECHNICIEN_MAINTENANCE', 'NumUsers=' . $num);
            flash('succes', 'Compte technicien mis à jour.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=maintenance');
    }

    if ($action === 'ajouter_adherent') {
        $num = (int) $_POST['utilisateur'];
        try {
            Utilisateur::ajouterAdherent($num);
            journalise('AJOUT_ROLE_ADHERENT', 'NumUsers=' . $num);
            flash('succes', 'Rôle adhérent ajouté : le cumul employé + adhérent est autorisé.');
        } catch (Throwable $exception) {
            flash('erreur', 'Ajout impossible : ' . $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }

    if ($action === 'modifier_utilisateur') {
        try {
            Utilisateur::modifierCompte((int) $_POST['utilisateur'], $_POST);
            journalise('MODIFICATION_UTILISATEUR', 'NumUsers=' . (int) $_POST['utilisateur']);
            flash('succes', 'Compte mis à jour.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }

    if ($action === 'modifier_adherent') {
        $id = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $adherentExiste = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
            $adherentExiste->execute([$id]);
            if (!$adherentExiste->fetchColumn()) {
                throw new RuntimeException('Cet adhérent est introuvable.');
            }

            $nom = trim((string) ($_POST['nom'] ?? ''));
            $prenom = trim((string) ($_POST['prenom'] ?? ''));
            $mail = mb_strtolower(trim((string) ($_POST['mail'] ?? '')));
            $tel = trim((string) ($_POST['tel'] ?? ''));
            $adresse = trim((string) ($_POST['adresse_postale'] ?? ''));
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $naissance = trim((string) ($_POST['date_naissance'] ?? ''));
            $sexe = trim((string) ($_POST['sexe'] ?? ''));
            $dateInscription = trim((string) ($_POST['Date_inscriptionadh'] ?? ''));
            $dateFin = trim((string) ($_POST['Date_finadh'] ?? ''));

            if ($nom === '' || mb_strlen($nom) > 60 || $prenom === '' || mb_strlen($prenom) > 60) {
                throw new InvalidArgumentException('Le nom et le prénom sont obligatoires (60 caractères maximum).');
            }
            if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Adresse e-mail invalide.');
            }
            if (mb_strlen($tel) > 20 || mb_strlen($adresse) > 255) {
                throw new InvalidArgumentException('Le téléphone ou l’adresse dépasse la longueur autorisée.');
            }
            $dateValide = static function (string $date, bool $optionnelle = false): bool {
                if ($optionnelle && $date === '') return true;
                $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                $errors = DateTimeImmutable::getLastErrors();
                return $value !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                    && $value->format('Y-m-d') === $date;
            };
            if (!$dateValide($naissance, true) || !$dateValide($dateInscription) || !$dateValide($dateFin, true)) {
                throw new InvalidArgumentException('Une des dates saisies est invalide.');
            }
            if (!in_array($sexe, ['Femme', 'Homme', 'Autre', 'Préfère ne pas répondre'], true)) {
                throw new InvalidArgumentException('Sélectionnez une option pour le sexe.');
            }
            $communeQuery = $pdo->prepare('SELECT nom_commune, code_postal FROM commune WHERE id_commune = ?');
            $communeQuery->execute([$communeId]);
            $commune = $communeQuery->fetch(PDO::FETCH_ASSOC);
            if (!$commune) throw new InvalidArgumentException('Sélectionnez une commune valide.');

            $pdo->beginTransaction();
            $pdo->prepare('UPDATE utilisateur SET nomUsers = ?, prenomUsers = ?, mailUsers = ?, telUsers = ? WHERE NumUsers = ?')
                ->execute([$nom, $prenom, $mail, $tel !== '' ? $tel : null, $id]);
            $pdo->prepare(
                'UPDATE adherent SET date_naissance = ?, sexe = ?, adresse_postale = ?, id_commune = ?, ville = ?, code_postal = ?, Date_inscriptionadh = ?, Date_finadh = ? WHERE NumUsers = ?'
            )->execute([
                $naissance !== '' ? $naissance : null,
                $sexe,
                $adresse !== '' ? $adresse : null,
                $communeId,
                $commune['nom_commune'],
                $commune['code_postal'],
                $dateInscription,
                $dateFin !== '' ? $dateFin : null,
                $id,
            ]);
            $pdo->commit();
            journalise('MODIFICATION_ADHERENT', 'NumUsers=' . $id);
            flash('succes', 'Fiche adhérent mise à jour.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=adherents&id=' . $id . '&vue=voir');
    }

    if ($action === 'repondre_avis') {
        $num = (int) ($_POST['num_interaction'] ?? 0);
        $reponse = trim((string) ($_POST['reponse'] ?? ''));

        try {
            $avisController = new AvisController();
            $avisController->repondre($num, $reponse);
            journalise('REPONSE_AVIS', 'NumInteraction=' . $num);
            flash('succes', 'Réponse enregistrée pour cet avis.');
        } catch (Throwable $ex) {
            flash('erreur', $ex->getMessage());
        }
        redirige('admin/espace.php?onglet=avis');
    }

    if ($action === 'modifier_coach') {
        $id = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $coachQuery = $pdo->prepare(
                "SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
                  WHERE e.NumUsers=? AND LOWER(f.libellefonc) IN ($placeholdersFonctionsCoach)"
            );
            $coachQuery->execute([$id, ...$nomsFonctionsCoachCompat]);
            if (!$coachQuery->fetchColumn()) throw new RuntimeException('Ce coach est introuvable.');

            $matricule = trim((string) ($_POST['matriculeemp'] ?? ''));
            $dateEmbauche = trim((string) ($_POST['Date_embaucheemp'] ?? ''));
            $numFonction = filter_var($_POST['Numfonc'] ?? null, FILTER_VALIDATE_INT);
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $fonctionQuery = $pdo->prepare("SELECT Numfonc FROM fonction WHERE Numfonc=? AND LOWER(libellefonc) IN ($placeholdersFonctionsCoach)");
            $fonctionQuery->execute([$numFonction, ...$nomsFonctionsCoachCompat]);
            if (!$fonctionQuery->fetchColumn()) throw new InvalidArgumentException('Sélectionnez une spécialisation valide.');
            $communeQuery = $pdo->prepare('SELECT id_commune FROM commune WHERE id_commune=?');
            $communeQuery->execute([$communeId]);
            if (!$communeQuery->fetchColumn()) throw new InvalidArgumentException('Choisissez une commune dans la liste.');
            if ($matricule === '' || mb_strlen($matricule) > 20) throw new InvalidArgumentException('Le matricule est obligatoire (20 caractères maximum).');
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateEmbauche);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count'])) || $date->format('Y-m-d') !== $dateEmbauche) {
                throw new InvalidArgumentException('La date d’embauche est invalide.');
            }

            $pdo->beginTransaction();
            Utilisateur::modifierCompte($id, $_POST);
            $pdo->prepare('UPDATE employe SET matriculeemp=?, Date_embaucheemp=?, Numfonc=?, id_commune=? WHERE NumUsers=?')
                ->execute([$matricule, $dateEmbauche, $numFonction, $communeId, $id]);
            $pdo->commit();
            journalise('MODIFICATION_COACH', 'NumUsers=' . $id);
            flash('succes', 'Fiche coach mise à jour.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=coachs&id=' . $id . '&vue=voir');
    }

    if ($action === 'modifier_employe') {
        $id = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $employeQuery = $pdo->prepare(
                "SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
                  WHERE e.NumUsers=? AND (LOWER(f.libellefonc) LIKE '%maintenance%' OR LOWER(f.libellefonc) LIKE '%accueil%')"
            );
            $employeQuery->execute([$id]);
            if (!$employeQuery->fetchColumn()) throw new RuntimeException('Cet employé de Maintenance ou d’Accueil est introuvable.');

            $matricule = trim((string) ($_POST['matriculeemp'] ?? ''));
            $dateEmbauche = trim((string) ($_POST['Date_embaucheemp'] ?? ''));
            $fonctionId = filter_var($_POST['Numfonc'] ?? null, FILTER_VALIDATE_INT);
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            if (!$fonctionId || !in_array((int) $fonctionId, $idsFonctionsEmploye, true)) {
                throw new InvalidArgumentException('La fonction doit être Maintenance ou Accueil.');
            }
            $communeQuery = $pdo->prepare('SELECT id_commune FROM commune WHERE id_commune=?');
            $communeQuery->execute([$communeId]);
            if (!$communeQuery->fetchColumn()) throw new InvalidArgumentException('Choisissez une commune dans la liste.');
            if ($matricule === '' || mb_strlen($matricule) > 20) throw new InvalidArgumentException('Le matricule est obligatoire (20 caractères maximum).');
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateEmbauche);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count'])) || $date->format('Y-m-d') !== $dateEmbauche) {
                throw new InvalidArgumentException('La date d’embauche est invalide.');
            }

            $pdo->beginTransaction();
            Utilisateur::modifierCompte($id, $_POST);
            $pdo->prepare('UPDATE employe SET matriculeemp=?, Date_embaucheemp=?, Numfonc=?, id_commune=? WHERE NumUsers=?')
                ->execute([$matricule, $dateEmbauche, $fonctionId, $communeId, $id]);
            $pdo->commit();
            journalise('MODIFICATION_EMPLOYE', 'NumUsers=' . $id);
            flash('succes', 'Fiche employé mise à jour.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=utilisateurs&id=' . $id . '&vue=voir');
    }

    if ($action === 'supprimer') {
        $num = (int) $_POST['utilisateur'];
        $retour = (string) ($_POST['retour'] ?? '');
        try {
            if ($retour === 'adherents') {
                $verifieAdherent = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
                $verifieAdherent->execute([$num]);
                if (!$verifieAdherent->fetchColumn()) {
                    throw new RuntimeException('Cet adhérent est introuvable.');
                }
                if ($num === (int) utilisateur()['NumUsers']) {
                    throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.');
                }
                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM paiement WHERE NumUsers = ?')->execute([$num]);
                Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
                $pdo->commit();
            } elseif ($retour === 'coachs') {
                $coachQuery = $pdo->prepare(
                    "SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
                      WHERE e.NumUsers=? AND LOWER(f.libellefonc) IN ($placeholdersFonctionsCoach)"
                );
                $coachQuery->execute([$num, ...$nomsFonctionsCoachCompat]);
                if (!$coachQuery->fetchColumn()) throw new RuntimeException('Ce coach est introuvable.');
                $activitesCoach = $pdo->prepare('SELECT COUNT(*) FROM activite WHERE NumUsers=?');
                $activitesCoach->execute([$num]);
                if ((int) $activitesCoach->fetchColumn() > 0) {
                    throw new RuntimeException('Ce coach est associé à des activités. Réaffectez-les avant de supprimer son compte.');
                }
                Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
            } elseif ($retour === 'employes') {
                $employeQuery = $pdo->prepare(
                    "SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
                      WHERE e.NumUsers=? AND (LOWER(f.libellefonc) LIKE '%maintenance%' OR LOWER(f.libellefonc) LIKE '%accueil%')"
                );
                $employeQuery->execute([$num]);
                if (!$employeQuery->fetchColumn()) throw new RuntimeException('Cet employé de Maintenance ou d’Accueil est introuvable.');
                if ($num === (int) utilisateur()['NumUsers']) throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.');
                $liaisonMaintenance = $pdo->prepare(
                    'SELECT (SELECT COUNT(*) FROM demande_maintenance WHERE NumUsers=?) + (SELECT COUNT(*) FROM intervention WHERE NumUsers=?)'
                );
                $liaisonMaintenance->execute([$num, $num]);
                if ((int) $liaisonMaintenance->fetchColumn() > 0) {
                    throw new RuntimeException('Cet employé est lié à l’historique de maintenance et ne peut pas être supprimé.');
                }
                Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
            } else {
                Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
            }
            journalise('SUPPRESSION_UTILISATEUR', 'NumUsers=' . $num);
            flash('succes', $retour === 'adherents' ? 'Adhérent supprimé.' : 'Compte supprimé.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige(match ($retour) {
            'maintenance' => 'admin/espace.php?onglet=maintenance',
            'adherents' => 'admin/espace.php?onglet=adherents',
            'coachs' => 'admin/espace.php?onglet=coachs',
            'employes' => 'admin/espace.php?onglet=utilisateurs',
            default => 'admin/espace.php?onglet=utilisateurs',
        });
    }
}

/* =====================================================================
   DONNEES
   ===================================================================== */
$utilisateurs = Utilisateur::tousLesComptes();
$adherents = $pdo->query(
    "SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers, u.telUsers, u.Date_CreationUsers,
            a.matriculeadh, a.Date_inscriptionadh, a.Date_finadh, a.date_naissance, a.sexe,
            a.adresse_postale, a.photo_profil, a.id_commune,
            COALESCE(c.nom_commune, a.ville) AS nom_commune,
            COALESCE(c.code_postal, a.code_postal) AS code_postal,
            (SELECT CONCAT(ab.libelle_abo, ' • ', s.statut_souscription)
               FROM souscrire s JOIN abonnement ab ON ab.Numabo = s.Numabo
              WHERE s.NumUsers = u.NumUsers ORDER BY s.date_debut DESC LIMIT 1) AS abonnement
       FROM utilisateur u JOIN adherent a ON a.NumUsers = u.NumUsers
       LEFT JOIN commune c ON c.id_commune = a.id_commune
      ORDER BY u.nomUsers, u.prenomUsers"
)->fetchAll(PDO::FETCH_ASSOC);
$communes = $pdo->query('SELECT id_commune, nom_commune, code_postal FROM commune ORDER BY nom_commune')->fetchAll(PDO::FETCH_ASSOC);
$activitesCatalogueAdmin = [];
$activiteCatalogueSelectionnee = null;
$vueActiviteCatalogue = (string) ($_GET['vue'] ?? 'voir');
if ($onglet === 'activites') {
    $activitesCatalogueAdmin = ActiviteCatalogue::toutes($pdo);
    $idActiviteSelectionnee = (int) ($_GET['activite_id'] ?? 0);
    if ($idActiviteSelectionnee > 0) $activiteCatalogueSelectionnee = ActiviteCatalogue::trouver($pdo, $idActiviteSelectionnee);
}
$abonnementsAdmin = [];
$formulesAdmin = [];
$abonnementSelectionne = null;
$paiementsAbonnement = [];
if ($onglet === 'abonnements') {
    $abonnementsAdmin = $pdo->query(
        "SELECT s.NumUsers, s.Numabo, s.date_debut, s.date_fin, s.statut_souscription,
                u.nomUsers, u.prenomUsers, u.mailUsers, ab.libelle_abo, o.type_offre,
                CASE o.type_offre WHEN 'FREEMIUM' THEN f.prix_mensuel_reduit
                     WHEN 'BASIC' THEN b.prix_annuel WHEN 'PLUS' THEN p.prix_mensuel_plus ELSE 0 END AS tarif,
                (SELECT pa.montant FROM paiement pa WHERE pa.NumUsers=s.NumUsers AND pa.Numabo=s.Numabo ORDER BY pa.date_paiement DESC, pa.Numpaiement DESC LIMIT 1) AS dernier_montant,
                (SELECT pa.date_paiement FROM paiement pa WHERE pa.NumUsers=s.NumUsers AND pa.Numabo=s.Numabo ORDER BY pa.date_paiement DESC, pa.Numpaiement DESC LIMIT 1) AS dernier_paiement,
                (SELECT pa.moyen_paiement FROM paiement pa WHERE pa.NumUsers=s.NumUsers AND pa.Numabo=s.Numabo ORDER BY pa.date_paiement DESC, pa.Numpaiement DESC LIMIT 1) AS moyen_paiement
           FROM souscrire s
           JOIN utilisateur u ON u.NumUsers=s.NumUsers
           JOIN abonnement ab ON ab.Numabo=s.Numabo
           JOIN offre o ON o.Numoffre=ab.Numoffre
           LEFT JOIN freemium f ON f.Numoffre=o.Numoffre
           LEFT JOIN basic b ON b.Numoffre=o.Numoffre
           LEFT JOIN abonnement_plus p ON p.Numoffre=o.Numoffre
          ORDER BY s.date_debut DESC, u.nomUsers, u.prenomUsers"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($abonnementsAdmin as &$abonnementRow) {
        if ($abonnementRow['statut_souscription'] === 'Suspendue') $abonnementRow['statut_affiche'] = 'Suspendu';
        elseif ($abonnementRow['statut_souscription'] === 'Resiliee') $abonnementRow['statut_affiche'] = 'Résilié';
        elseif ($abonnementRow['statut_souscription'] === 'Terminee' || ($abonnementRow['date_fin'] && $abonnementRow['date_fin'] < date('Y-m-d'))) $abonnementRow['statut_affiche'] = 'Expiré';
        elseif ($abonnementRow['date_fin'] && (strtotime($abonnementRow['date_fin']) - strtotime('today')) <= 30 * 86400) $abonnementRow['statut_affiche'] = 'À renouveler';
        else $abonnementRow['statut_affiche'] = 'Actif';
    }
    unset($abonnementRow);
    $formulesAdmin = $pdo->query('SELECT Numabo, libelle_abo FROM abonnement WHERE actif_abo=1 ORDER BY libelle_abo')->fetchAll(PDO::FETCH_ASSOC);
    $selectionId = (int) ($_GET['id'] ?? 0);
    $selectionFormule = (int) ($_GET['formule'] ?? 0);
    $selectionDate = (string) ($_GET['debut'] ?? '');
    foreach ($abonnementsAdmin as $aboRow) {
        if ((int) $aboRow['NumUsers'] === $selectionId
            && (!$selectionFormule || (int) $aboRow['Numabo'] === $selectionFormule)
            && ($selectionDate === '' || $aboRow['date_debut'] === $selectionDate)) {
            $abonnementSelectionne = $aboRow;
            break;
        }
    }
    if (!$abonnementSelectionne && !$selectionId && $abonnementsAdmin) {
        $abonnementSelectionne = $abonnementsAdmin[0];
    }
    if ($abonnementSelectionne) {
        $queryPaiementsAbo = $pdo->prepare('SELECT Numpaiement, date_paiement, montant, moyen_paiement FROM paiement WHERE NumUsers=? AND Numabo=? ORDER BY date_paiement DESC, Numpaiement DESC');
        $queryPaiementsAbo->execute([$abonnementSelectionne['NumUsers'], $abonnementSelectionne['Numabo']]);
        $paiementsAbonnement = $queryPaiementsAbo->fetchAll(PDO::FETCH_ASSOC);
    }
}
$planningDonneesAdmin = [];
$creneauAModifier = null;
if ($onglet === 'planning') {
    $semaineDemandee = (string) ($_GET['semaine'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $semaineDemandee)) {
        $semaineDemandee = (new DateTimeImmutable('monday this week'))->format('Y-m-d');
    }
    $planningDonneesAdmin = $gestionPlanning->donneesAdmin($semaineDemandee);
    $idCreneauEdition = (int) ($_GET['modifier'] ?? 0);
    if ($idCreneauEdition > 0) {
        $creneauAModifier = (new Planning())->detailsCreneau($idCreneauEdition);
    }
}
$reservationsAdmin = [];
$activitesDisponiblesReservation = [];
$adherentsReservationsAdmin = [];
$semaineReservationsAdmin = '';
if ($onglet === 'reservations') {
    $dateSemaineReservation = (string) ($_GET['semaine'] ?? '');
    $debutSemaineReservation = DateTimeImmutable::createFromFormat('!Y-m-d', $dateSemaineReservation);
    if (!$debutSemaineReservation || $debutSemaineReservation->format('Y-m-d') !== $dateSemaineReservation) {
        $debutSemaineReservation = new DateTimeImmutable('monday this week');
    } else {
        $debutSemaineReservation = $debutSemaineReservation->modify('monday this week');
    }
    $semaineReservationsAdmin = $debutSemaineReservation->format('Y-m-d');
    $finSemaineReservation = $debutSemaineReservation->modify('+7 days')->format('Y-m-d');
    $sqlReservationsBase = "SELECT %s AS id_ligne, %s AS type_ligne, %s AS date_demande,
            %s AS statut_reservation, u.NumUsers, u.prenomUsers, u.nomUsers, u.mailUsers,
            a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, a.date_heure_fin,
            a.capacite_max, s.Numsalle, s.nomsalle, coach.prenomUsers AS coach_prenom,
            coach.nomUsers AS coach_nom,
            (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises
          FROM %s
          JOIN adherent ad ON ad.NumUsers = %s
          JOIN utilisateur u ON u.NumUsers = ad.NumUsers
          JOIN activite a ON a.Numactivite = %s
          JOIN salle s ON s.Numsalle = a.Numsalle
          JOIN utilisateur coach ON coach.NumUsers = a.NumUsers
         WHERE a.date_heure_debut >= ? AND a.date_heure_debut < ?";
    $stmtReservationsAdmin = $pdo->prepare(sprintf($sqlReservationsBase,
        'r.Numres', "'reservation'", 'r.date_heure_reservation', 'r.statut_reservation',
        'reservation r', 'r.NumUsers', 'r.Numactivite'
    ));
    $stmtReservationsAdmin->execute([$semaineReservationsAdmin, $finSemaineReservation]);
    $reservationsAdmin = $stmtReservationsAdmin->fetchAll(PDO::FETCH_ASSOC);
    $stmtAttentesAdmin = $pdo->prepare(sprintf($sqlReservationsBase,
        'la.Numattente', "'attente'", 'la.date_inscription', "'En attente'",
        'liste_attente la', 'la.NumUsers', 'la.Numactivite'
    ));
    $stmtAttentesAdmin->execute([$semaineReservationsAdmin, $finSemaineReservation]);
    $reservationsAdmin = [...$reservationsAdmin, ...$stmtAttentesAdmin->fetchAll(PDO::FETCH_ASSOC)];
    usort($reservationsAdmin, static fn(array $a, array $b): int => strcmp($a['date_heure_debut'], $b['date_heure_debut']));
    $activitesDisponiblesReservation = $pdo->query(
        "SELECT a.Numactivite,a.titre,a.type_activite,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,
                (SELECT COUNT(*) FROM reservation rx WHERE rx.Numactivite=a.Numactivite AND rx.statut_reservation <> 'Annulee') AS places_prises
           FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle
          WHERE a.date_heure_debut >= NOW() AND s.etatsalle='Ouverte'
          ORDER BY a.date_heure_debut LIMIT 200"
    )->fetchAll(PDO::FETCH_ASSOC);
    $adherentsReservationsAdmin = $pdo->query('SELECT u.NumUsers,u.prenomUsers,u.nomUsers,u.mailUsers FROM adherent ad JOIN utilisateur u ON u.NumUsers=ad.NumUsers ORDER BY u.nomUsers,u.prenomUsers')->fetchAll(PDO::FETCH_ASSOC);
}
$adherentSelectionne = null;
if ($onglet === 'adherents' && (int) ($_GET['id'] ?? 0) > 0) {
    foreach ($adherents as $adherent) {
        if ((int) $adherent['NumUsers'] === (int) $_GET['id']) {
            $adherentSelectionne = $adherent;
            break;
        }
    }
}
$vueAdherent = (string) ($_GET['vue'] ?? 'voir');
$techniciensMaintenance = array_values(array_filter(
    $utilisateurs,
    static fn(array $user): bool => $user['est_employe']
        && str_contains(strtolower((string) ($user['libellefonc'] ?? '')), 'maintenance')
));
$coachs = array_values(array_filter(
    $utilisateurs,
    static fn(array $user): bool => $user['est_employe']
        && in_array(
            mb_strtolower((string) ($user['libellefonc'] ?? '')),
            [...array_map('mb_strtolower', $specialisationsCoachNoms), 'coach'],
            true
        )
));
$coachSelectionne = null;
$vueCoach = (string) ($_GET['vue'] ?? 'voir');
if ($onglet === 'coachs') {
    foreach ($coachs as $coach) {
        if ((int) $coach['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
            $coachSelectionne = $coach;
            break;
        }
    }
}
$employesGestion = array_values(array_filter(
    $utilisateurs,
    static function (array $user): bool {
        $fonction = mb_strtolower((string) ($user['libellefonc'] ?? ''));
        return $user['est_employe'] && (str_contains($fonction, 'maintenance') || str_contains($fonction, 'accueil'));
    }
));
$employeSelectionne = null;
if ($onglet === 'utilisateurs') {
    foreach ($employesGestion as $employeGestion) {
        if ((int) $employeGestion['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
            $employeSelectionne = $employeGestion;
            break;
        }
    }
}
$compteModifie = null;
$compteConsulte = null;
$technicienSelectionne = null;
foreach ($utilisateurs as $user) {
    if ((int) $user['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
        if ($vueCompte === 'modifier') {
            $compteModifie = $user;
        } elseif ($vueCompte === 'voir') {
            $compteConsulte = $user;
        }
    }
    foreach ($techniciensMaintenance as $technicien) {
        if ((int) $technicien['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
            $technicienSelectionne = $technicien;
            break;
        }
    }
}

$statsRoles = [
    'total'     => count($utilisateurs),
    'admins'    => count(array_filter($utilisateurs, fn($u) => $u['est_admin'])),
    'employes'  => count(array_filter($utilisateurs, fn($u) => $u['est_employe'])),
    'adherents' => count(array_filter($utilisateurs, fn($u) => $u['est_adherent'])),
    'cumuls'    => count(array_filter($utilisateurs, fn($u) => $u['est_employe'] && $u['est_adherent'])),
];
$stats = $statsRoles;

$parActivite = $pdo->query(
  "SELECT a.titre AS libelleact, COUNT(r.Numres) AS n
     FROM activite a
   LEFT JOIN reservation r ON r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee'
   GROUP BY a.Numactivite, a.titre ORDER BY n DESC")->fetchAll();

$parAbonnement = $pdo->query(
  "SELECT ab.libelle_abo, o.type_offre AS type_abo, COUNT(s.NumUsers) AS n
     FROM abonnement ab
   JOIN offre o ON o.Numoffre = ab.Numoffre
     LEFT JOIN souscrire s ON s.Numabo = ab.Numabo AND s.statut_souscription = 'En cours'
   GROUP BY ab.Numabo, ab.libelle_abo, o.type_offre ORDER BY n DESC")->fetchAll();

$recettes = $pdo->query(
    "SELECT DATE_FORMAT(date_paiement,'%Y-%m') AS mois, SUM(montant) AS total, COUNT(*) AS n
     FROM paiement GROUP BY mois ORDER BY mois DESC LIMIT 6")->fetchAll();

$historiquePaiements = [];
$paiementSelectionneAdmin = null;
if ($onglet === 'paiements') {
    $historiquePaiements = $pdo->query(
        'SELECT p.Numpaiement, p.date_paiement, p.montant, p.moyen_paiement,
                u.NumUsers, u.prenomUsers, u.nomUsers, u.mailUsers,
                ab.Numabo, ab.libelle_abo,
                (SELECT s.date_debut FROM souscrire s WHERE s.NumUsers=p.NumUsers AND s.Numabo=p.Numabo ORDER BY s.date_debut DESC LIMIT 1) AS date_debut_abo,
                (SELECT s.date_fin FROM souscrire s WHERE s.NumUsers=p.NumUsers AND s.Numabo=p.Numabo ORDER BY s.date_debut DESC LIMIT 1) AS date_fin_abo
           FROM paiement p
           JOIN adherent d ON d.NumUsers = p.NumUsers
           JOIN utilisateur u ON u.NumUsers = d.NumUsers
           JOIN abonnement ab ON ab.Numabo = p.Numabo
          ORDER BY p.date_paiement DESC, p.Numpaiement DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
    $idPaiementSelectionne = (int) ($_GET['paiement'] ?? 0);
    foreach ($historiquePaiements as $paiementAdmin) {
        if ((int) $paiementAdmin['Numpaiement'] === $idPaiementSelectionne) { $paiementSelectionneAdmin = $paiementAdmin; break; }
    }
    if (!$paiementSelectionneAdmin && !$idPaiementSelectionne && $historiquePaiements) $paiementSelectionneAdmin = $historiquePaiements[0];
}

$parEtat = $pdo->query(
    "SELECT s.libelle_statut, COUNT(e.Numequip) AS n
     FROM statut s LEFT JOIN equipement e ON e.Numstatut = s.Numstatut
     WHERE s.type_statut = 'EQUIPEMENT' GROUP BY s.Numstatut")->fetchAll();

$sallesAdmin = [];
$salleSelectionneeAdmin = null;
$vueSalleAdmin = (string) ($_GET['vue'] ?? 'liste');
if ($onglet === 'salles') {
    $sallesAdmin = $pdo->query('SELECT Numsalle,nomsalle,capacitesalle,localisationsalle,superficiesalle,etatsalle,description_salle,image_salle FROM salle ORDER BY nomsalle')->fetchAll(PDO::FETCH_ASSOC);
    $idSalleSelectionnee = (int) ($_GET['id'] ?? 0);
    if ($idSalleSelectionnee > 0) {
        foreach ($sallesAdmin as $salleAdmin) {
            if ((int) $salleAdmin['Numsalle'] === $idSalleSelectionnee) { $salleSelectionneeAdmin = $salleAdmin; break; }
        }
    }
}

$ticketsEquipementsAdmin = [];
$interventionsParTicketAdmin = [];
$commandesMaterielAdmin = [];
if ($onglet === 'equipements') {
    $ticketsEquipementsAdmin = $pdo->query(
        "SELECT d.Nummaint, d.description_problememaint, d.priorite, d.date_demandemaint,
                d.NumUsers_technicien, eq.Numequip, eq.nomequip, te.libelletypeequip,
                st.libelle_statut,
                demandeur.prenomUsers AS demandeur_prenom, demandeur.nomUsers AS demandeur_nom,
                fonction_demandeur.libellefonc AS demandeur_fonction,
                technicien.prenomUsers AS technicien_prenom, technicien.nomUsers AS technicien_nom,
                fonction_technicien.libellefonc AS technicien_fonction
           FROM demande_maintenance d
           JOIN equipement eq ON eq.Numequip = d.Numequip
           JOIN type_equipement te ON te.Numtypeequip = eq.Numtypeequip
           JOIN statut st ON st.Numstatut = d.Numstatut
           JOIN employe employe_demandeur ON employe_demandeur.NumUsers = d.NumUsers
           JOIN fonction fonction_demandeur ON fonction_demandeur.Numfonc = employe_demandeur.Numfonc
           JOIN utilisateur demandeur ON demandeur.NumUsers = d.NumUsers
           LEFT JOIN employe employe_technicien ON employe_technicien.NumUsers = d.NumUsers_technicien
           LEFT JOIN fonction fonction_technicien ON fonction_technicien.Numfonc = employe_technicien.Numfonc
           LEFT JOIN utilisateur technicien ON technicien.NumUsers = d.NumUsers_technicien
          ORDER BY CASE st.libelle_statut WHEN 'Terminee' THEN 2 WHEN 'À valider' THEN 1 ELSE 0 END,
                   d.date_demandemaint DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    if ($ticketsEquipementsAdmin) {
        $idsTickets = array_map(static fn(array $ticket): int => (int) $ticket['Nummaint'], $ticketsEquipementsAdmin);
        $parametresTickets = implode(',', array_fill(0, count($idsTickets), '?'));
        $queryInterventionsAdmin = $pdo->prepare(
            "SELECT i.Nummaint, i.date_intervention, i.commentaire, s.libelle_statut,
                    u.prenomUsers, u.nomUsers, f.libellefonc
               FROM intervention i
               JOIN utilisateur u ON u.NumUsers = i.NumUsers
               JOIN employe e ON e.NumUsers = i.NumUsers
               JOIN fonction f ON f.Numfonc = e.Numfonc
               JOIN statut s ON s.Numstatut = i.Numstatut
              WHERE i.Nummaint IN ($parametresTickets)
              ORDER BY i.date_intervention, i.Numintervention"
        );
        $queryInterventionsAdmin->execute($idsTickets);
        foreach ($queryInterventionsAdmin->fetchAll(PDO::FETCH_ASSOC) as $interventionAdmin) {
            $interventionsParTicketAdmin[(int) $interventionAdmin['Nummaint']][] = $interventionAdmin;
        }
    }
    $commandesMaterielAdmin = $pdo->query(
        "SELECT c.Numcommande, c.Numequip, c.quantite, c.message, c.statut_commande,
                c.date_demande, c.date_decision, c.date_reception,
                eq.nomequip, eq.stock_actuel, eq.seuil_alerte, te.libelletypeequip,
                demandeur.prenomUsers AS demandeur_prenom, demandeur.nomUsers AS demandeur_nom,
                validateur.prenomUsers AS validateur_prenom, validateur.nomUsers AS validateur_nom
           FROM commande_materiel c
           JOIN equipement eq ON eq.Numequip = c.Numequip
           JOIN type_equipement te ON te.Numtypeequip = eq.Numtypeequip
           JOIN utilisateur demandeur ON demandeur.NumUsers = c.NumUsers
           LEFT JOIN utilisateur validateur ON validateur.NumUsers = c.NumUsers_admin
          ORDER BY CASE c.statut_commande
                     WHEN 'En attente' THEN 0
                     WHEN 'Approuvée' THEN 1
                     ELSE 2
                   END, c.date_demande DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$messages = $pdo->query('SELECT * FROM message_contact ORDER BY date_envoi DESC LIMIT 30')->fetchAll();
$avisAdmin = (new AvisController())->index();

$maxRes = max(1, ...array_map(fn($r) => (int) $r['n'], $parActivite ?: [['n' => 1]]));

$flashMessages = flashs();
$adminUser = utilisateur();
$adminNavigation = [
    'dashboard' => ['Tableau de bord', 'home'],
    'adherents' => ['Adhérents', 'user'],
    'coachs' => ['Coachs', 'users'],
    'utilisateurs' => ['Employé', 'users'],
    'abonnements' => ['Abonnements', 'card'],
    'paiements' => ['Paiements', 'card'],
    'planning' => ['Planning', 'calendar'],
    'activites' => ['Activités', 'activity'],
    'reservations' => ['Réservations', 'bag'],
    'salles' => ['Salles', 'building'],
    'equipements' => ['Équipements', 'tool'],
    'avis' => ['Avis clients', 'star'],
];
$sidebarItems = [];
foreach ($adminNavigation as $id => [$label, $icon]) {
    $sidebarItems[] = [
        'id' => $id,
        'label' => $label,
        'icon' => $icon,
        'href' => url('admin/espace.php?onglet=' . $id),
    ];
}
$sidebarActive = $onglet;
$sidebarHome = url('admin/espace.php?onglet=dashboard');
$sidebarLabel = 'Navigation administration';
$sidebarSubtitle = 'Administration';
$topbarUser = utilisateur();
$topbarTitle = 'Administration';
$topbarContext = 'Espace sécurisé';
$topbarClass = 'admin-topbar';
$topbarLinks = [
    ['label' => 'Mon espace', 'href' => url('admin/espace.php?onglet=dashboard')],
    ['label' => 'Déconnexion', 'href' => url('utilisateur/deconnexion.php')],
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administration - <?= e(APP_NOM) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/admin.css')) ?>">
  <?php if ($onglet === 'planning'): ?><link rel="stylesheet" href="<?= e(url('assets/css/pages/planning.css')) ?>"><?php endif; ?>
  <?php if (in_array($onglet, ['coachs', 'utilisateurs'], true)): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/commune/commune.css')) ?>">
  <?php endif; ?>
</head>
<body class="admin-page<?= $onglet === 'salles' ? ' admin-rooms-page' : '' ?><?= $onglet === 'planning' ? ' admin-weekly-page' : '' ?><?= $onglet === 'reservations' ? ' admin-reservations-page' : '' ?><?= $onglet === 'abonnements' ? ' admin-subscriptions-page' : '' ?><?= $onglet === 'paiements' ? ' admin-payments-page' : '' ?>">
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
<?php if ($onglet === 'dashboard'): ?>
  <?php require __DIR__ . '/view/dashboard.php'; ?>

<?php elseif ($onglet === 'avis'): ?>
  <?php if (!$avisAdmin): ?>
    <div class="vide">Aucun avis publié pour le moment.</div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="tableau">
        <thead><tr><th>Adhérent</th><th>Note</th><th>Avis</th><th>Réponse</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($avisAdmin as $avis): ?>
          <tr>
            <td>
              <?= e($avis['prenomUsers'] . ' ' . $avis['nomUsers']) ?><br>
              <span class="mono"><?= dateFr($avis['date_interaction']) ?></span>
            </td>
            <td class="mono"><?= (int) $avis['noteavis'] ?> / 5</td>
            <td><?= nl2br(e($avis['commentaire'])) ?></td>
            <td>
              <form method="post" style="margin:0">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="repondre_avis">
                <input type="hidden" name="num_interaction" value="<?= (int) $avis['NumInteraction'] ?>">
                <textarea name="reponse" rows="4" placeholder="Réponse de l’équipe..." required><?= e($avis['reponse'] ?? '') ?></textarea>
                <button class="btn btn-sm btn-vert" type="submit" style="margin-top:8px">Enregistrer</button>
              </form>
            </td>
            <td>
              <?php if (!empty($avis['reponse'])): ?><span class="tag tag-ok">Répondu</span><?php else: ?><span class="tag tag-mute">À répondre</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php elseif ($onglet === 'utilisateurs'): ?>
  <?php require __DIR__ . '/view/employes.php'; ?>

<?php elseif ($onglet === 'adherents'): ?>
  <?php require __DIR__ . '/view/adherents.php'; ?>

<?php elseif ($onglet === 'coachs'): ?>
  <?php require __DIR__ . '/view/coachs.php'; ?>

<?php elseif ($onglet === 'paiements'): ?>
  <?php require __DIR__ . '/view/paiements.php'; ?>

<?php elseif ($onglet === 'abonnements'): ?>
  <?php require __DIR__ . '/view/abonnements.php'; ?>

<?php elseif ($onglet === 'planning'): ?>
  <?php require __DIR__ . '/view/planning.php'; ?>

<?php elseif ($onglet === 'reservations'): ?>
  <?php require __DIR__ . '/view/reservations.php'; ?>

<?php elseif ($onglet === 'activites'): ?>
  <?php require __DIR__ . '/view/activites.php'; ?>

<?php elseif ($onglet === 'equipements'): ?>
  <?php require __DIR__ . '/view/equipements.php'; ?>

<?php elseif ($onglet === 'salles'): ?>
  <?php require __DIR__ . '/view/salles.php'; ?>

<?php elseif ($onglet === 'maintenance'): ?>
  <?php if ($vueMaintenance === 'creer'): ?>
    <?php require __DIR__ . '/view/creer-technicien.php'; ?>
  <?php elseif ($vueMaintenance === 'modifier' && $technicienSelectionne): ?>
    <?php require __DIR__ . '/view/modifier-technicien.php'; ?>
  <?php elseif ($vueMaintenance === 'voir' && $technicienSelectionne): ?>
    <?php require __DIR__ . '/view/voir-technicien.php'; ?>
  <?php else: ?>
    <?php require __DIR__ . '/view/techniciens-maintenance.php'; ?>
  <?php endif; ?>

<?php elseif ($onglet === 'messages'): ?>
  <?php require __DIR__ . '/../message_contact/view/admin-contact.php'; ?>

<?php else: ?>
  <div class="dashboard-box" style="background:#fff; padding:20px; border-radius:12px;">
    <h3>Module en cours de développement</h3>
    <p>La section <strong><?= e($onglet) ?></strong> est en cours de configuration et sera bientôt disponible.</p>
  </div>
<?php endif; ?>
    </main>
  </div>
</div>
<?php if ($onglet === 'planning'): foreach (PlanningController::FC_SCRIPTS as $src): ?>
  <script src="<?= e($src) ?>"></script>
<?php endforeach; endif; ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?php if (in_array($onglet, ['coachs', 'utilisateurs'], true)): ?>
  <script src="<?= e(url('assets/js/commune/commune.js')) ?>"></script>
<?php endif; ?>
</body>
</html>
