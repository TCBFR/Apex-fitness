<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';

header('Content-Type: application/json; charset=utf-8');

function employeProfilReply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!a_role('EMPLOYE') || !est_technicien_maintenance()) {
    employeProfilReply(['error' => 'Accès interdit.'], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    employeProfilReply(['error' => 'Méthode non autorisée.'], 405);
}

$action = $_GET['action'] ?? null;
if (!is_string($action)) {
    employeProfilReply(['error' => 'Action inconnue.'], 404);
}

$id = (int) utilisateur()['NumUsers'];

require_once __DIR__ . '/profil_photo_action.php';
require_once __DIR__ . '/profil_update_action.php';

if ($action === 'profile_photo') {
    employeProfilPhoto($id);
}
if ($action !== 'profile') {
    employeProfilReply(['error' => 'Action inconnue.'], 404);
}
employeProfilUpdate($id);
