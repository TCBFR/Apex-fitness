<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';
exige_role('ADHERENT');
require_once __DIR__ . '/model/AdherentEspace.php';
require_once __DIR__ . '/controller/AdherentController.php';
require_once __DIR__ . '/includes/planning/data.php';
require_once __DIR__ . '/../planning/controller/planning.php';

header('Content-Type: application/json; charset=utf-8');
$id = (int) utilisateur()['NumUsers'];
$space = new AdherentEspace();
$adherentController = new AdherentController($space);
$planning = new PlanningController();
$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) $input = [];
$action = (string)($_GET['action'] ?? $input['action'] ?? '');

require_once __DIR__ . '/api_helpers.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string)($input['csrf'] ?? $_POST['csrf'] ?? '');
        if ($token === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
            apiReply(['error'=>'Session expirée. Rechargez la page.'], 419);
        }
    }

    switch ($action) {
        case 'events':
            require __DIR__ . '/api/actions/events.php';
            break;

        case 'my':
            require __DIR__ . '/api/actions/my.php';
            break;

        case 'reservation_history':
            require __DIR__ . '/api/actions/reservation_history.php';
            break;

        case 'waitlist_join':
            require __DIR__ . '/api/actions/waitlist_join.php';
            break;

        case 'waitlist_leave':
            require __DIR__ . '/api/actions/waitlist_leave.php';
            break;

        case 'next':
            require __DIR__ . '/api/actions/next.php';
            break;

        case 'book':
            require __DIR__ . '/api/actions/book.php';
            break;

        case 'cancel':
            require __DIR__ . '/api/actions/cancel.php';
            break;

        case 'profile':
            require __DIR__ . '/api/actions/profile.php';
            break;

        case 'profile_photo':
            require __DIR__ . '/api/actions/profile_photo.php';
            break;

        case 'messages':
            require __DIR__ . '/api/actions/messages.php';
            break;

        case 'unread_messages':
            require __DIR__ . '/api/actions/unread_messages.php';
            break;

        default:
            apiReply(['error'=>'Action inconnue.'], 404);
    }
} catch (InvalidArgumentException|RuntimeException $exception) {
    apiReply(['error'=>$exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log('[Adherent API] '.$exception->getMessage());
    apiReply(['error'=>'Une erreur est survenue. Réessayez.'], 500);
}
