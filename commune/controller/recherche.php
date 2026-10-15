<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../model/commune.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode(
    CommuneModel::rechercher((string) ($_GET['q'] ?? '')),
    JSON_UNESCAPED_UNICODE
);