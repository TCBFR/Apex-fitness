<?php

$activityId = (int)($input['id'] ?? 0);
            $title = $planning->reserver($id, $activityId);
            db()->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$id, $activityId]);
            journalise('RESERVATION', 'Numactivite='.$activityId);
            $q=db()->prepare('SELECT a.Numactivite,a.titre,a.type_activite,a.niveau,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,u.prenomUsers AS coach,(SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation<>\'Annulee\') AS places_prises,r.Numres,(SELECT COUNT(*) FROM liste_attente la WHERE la.NumUsers=? AND la.Numactivite=a.Numactivite) AS en_attente FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur u ON u.NumUsers=a.NumUsers JOIN reservation r ON r.Numactivite=a.Numactivite AND r.NumUsers=? AND r.statut_reservation<>\'Annulee\' WHERE a.Numactivite=?');
            $q->execute([$id,$id,$activityId]);
            apiReply(['message'=>'Réservation confirmée : '.$title.'.','session'=>apiSession($q->fetch())]);
