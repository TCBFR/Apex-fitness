<?php

$messages = $pdo->query('SELECT * FROM message_contact ORDER BY date_envoi DESC LIMIT 30')->fetchAll();
$avisAdmin = (new AvisController())->index();

$maxRes = max(1, ...array_map(fn($r) => (int) $r['n'], $parActivite ?: [['n' => 1]]));
