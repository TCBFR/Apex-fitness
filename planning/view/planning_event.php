<?php
/**
 * Flux JSON du planning pour FullCalendar (lecture seule).
 * Parametres GET : start, end (ISO 8601, envoyes par FullCalendar).
 * Reserve aux utilisateurs connectes.
 */
require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../controller/PlanningController.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit('[]');
}

$utilisateur = utilisateur();
$moi = !empty($utilisateur['NumUsers']) ? (int) $utilisateur['NumUsers'] : 0;
if ($moi === 0) {
    http_response_code(401);
    exit('[]');
}

$debut = strtotime((string) ($_GET['start'] ?? ''));
$fin   = strtotime((string) ($_GET['end'] ?? ''));

if ($debut === false || $fin === false || $fin <= $debut || ($fin - $debut) > 100 * 86400) {
    http_response_code(400);
    exit(json_encode(['erreur' => 'Plage de dates invalide.']));
}

echo json_encode(
    (new PlanningController())->evenements(date('Y-m-d H:i:s', $debut), date('Y-m-d H:i:s', $fin), $moi),
    JSON_UNESCAPED_UNICODE
);