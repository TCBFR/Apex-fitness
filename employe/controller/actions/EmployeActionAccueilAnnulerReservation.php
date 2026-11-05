<?php

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
