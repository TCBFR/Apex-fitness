<?php

if ($_SERVER['REQUEST_METHOD'] === 'GET') apiReply($space->messages($id));
            $space->envoyerMessage($id, (string)($input['message'] ?? ''), isset($input['recipient']) ? (int)$input['recipient'] : null);
            apiReply(['message'=>'Message envoyé.']);
