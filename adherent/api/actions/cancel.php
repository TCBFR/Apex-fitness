<?php

$activityId = (int)($input['id'] ?? 0);
            $q = db()->prepare("SELECT Numres FROM reservation WHERE NumUsers=? AND Numactivite=? AND statut_reservation <> 'Annulee'");
            $q->execute([$id,$activityId]);
            $reservationId = (int)$q->fetchColumn();
            if (!$reservationId) apiReply(['error'=>'Réservation introuvable.'], 404);
            $planning->annuler($id, $reservationId);
            db()->prepare("DELETE FROM notification_adherent WHERE Numres=? AND type_notification='RAPPEL_SEANCE'")->execute([$reservationId]);
            journalise('ANNULATION', 'Numres='.$reservationId);
            $promoted = promoteWaitlist($activityId, $planning);
            $q=db()->prepare('SELECT a.Numactivite,a.titre,a.type_activite,a.niveau,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,u.prenomUsers AS coach,(SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation<>\'Annulee\') AS places_prises,NULL AS Numres,0 AS en_attente FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur u ON u.NumUsers=a.NumUsers WHERE a.Numactivite=?');
            $q->execute([$activityId]);
            apiReply(['message'=>'Réservation annulée.'.($promoted ? ' La place a été attribuée à une personne en liste d’attente.' : ''),'session'=>apiSession($q->fetch())]);
