<?php
declare(strict_types=1);

// Import unique de la table commune. Usage : php commune/import_communes.php
require_once __DIR__ . '/../config/function.php';

$json = file_get_contents('https://geo.api.gouv.fr/communes?fields=nom,code,codesPostaux');
if ($json === false) {
    exit("Téléchargement impossible.\n");
}
$communes = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

$pdo = db();
$pdo->beginTransaction();
$insertion = $pdo->prepare(
    'INSERT IGNORE INTO commune (code_insee, nom_commune, code_postal) VALUES (:insee, :nom, :cp)'
);
foreach ($communes as $commune) {
    foreach ($commune['codesPostaux'] as $codePostal) {
        $insertion->execute(['insee' => $commune['code'], 'nom' => $commune['nom'], 'cp' => $codePostal]);
    }
}
$pdo->commit();

echo count($communes) . " communes importées.\n";