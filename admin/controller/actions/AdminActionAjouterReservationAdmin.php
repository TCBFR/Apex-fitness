<?php

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
