<?php

$activityId = (int)($input['id'] ?? 0);
            $q = db()->prepare('SELECT capacite_max, date_heure_debut FROM activite WHERE Numactivite=?');
            $q->execute([$activityId]);
            $activity = $q->fetch();
            if (!$activity || strtotime($activity['date_heure_debut']) <= time()) apiReply(['error'=>'Ce cours n’est plus disponible.'], 422);
            $q = db()->prepare("SELECT COUNT(*) FROM reservation WHERE Numactivite=? AND statut_reservation<>'Annulee'");
            $q->execute([$activityId]);
            if ((int)$q->fetchColumn() < (int)$activity['capacite_max']) apiReply(['error'=>'Une place est disponible. Réservez directement le cours.'], 422);
            $q = db()->prepare("SELECT COUNT(*) FROM reservation WHERE NumUsers=? AND Numactivite=? AND statut_reservation<>'Annulee'");
            $q->execute([$id, $activityId]);
            if ((int)$q->fetchColumn()) apiReply(['error'=>'Vous avez déjà réservé ce cours.'], 422);
            db()->prepare('INSERT IGNORE INTO liste_attente (NumUsers, Numactivite) VALUES (?, ?)')->execute([$id, $activityId]);
            apiReply(['message'=>'Vous êtes sur la liste d’attente.']);
