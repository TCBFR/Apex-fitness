<?php

$parEtat = $pdo->query(
    "SELECT s.libelle_statut, COUNT(e.Numequip) AS n
     FROM statut s LEFT JOIN equipement e ON e.Numstatut = s.Numstatut
     WHERE s.type_statut = 'EQUIPEMENT' GROUP BY s.Numstatut")->fetchAll();
