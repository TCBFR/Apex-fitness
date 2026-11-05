<?php

if ($_SERVER['REQUEST_METHOD'] !== 'GET') apiReply(['error'=>'Méthode non autorisée.'], 405);
            apiReply(['unread_count'=>$space->messagesNonLus($id)]);
