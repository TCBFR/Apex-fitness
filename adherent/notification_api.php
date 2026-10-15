<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';
exige_role('ADHERENT');
require_once __DIR__ . '/controller/NotificationController.php';

header('Content-Type: application/json; charset=utf-8');
$controller = new NotificationController();
$adherentId = (int)utilisateur()['NumUsers'];

function notificationReply(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        notificationReply($controller->menu($adherentId));
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        notificationReply(['error' => 'Méthode non autorisée.'], 405);
    }

    $input = json_decode(file_get_contents('php://input') ?: '[]', true);
    $token = (string)($input['csrf'] ?? '');
    if (!hash_equals(csrf(), $token)) {
        notificationReply(['error' => 'Session expirée. Rechargez la page.'], 419);
    }

    $notificationId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$notificationId || $notificationId < 1) {
        notificationReply(['error' => 'Notification invalide.'], 422);
    }

    notificationReply($controller->marquerCommeLue($adherentId, $notificationId));
} catch (Throwable $error) {
    error_log('[Notifications adhérent] ' . $error->getMessage());
    notificationReply(['error' => 'Les notifications sont indisponibles pour le moment.'], 500);
}
