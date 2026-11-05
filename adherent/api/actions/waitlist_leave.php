<?php

$activityId = (int)($input['id'] ?? 0);
            db()->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$id, $activityId]);
            apiReply(['message'=>'Vous avez quitté la liste d’attente.']);
