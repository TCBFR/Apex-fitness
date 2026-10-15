<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';
exige_role('ADHERENT');
require_once __DIR__ . '/model/AdherentEspace.php';
require_once __DIR__ . '/includes/planning/data.php';
require_once __DIR__ . '/../planning/controller/planning.php';

header('Content-Type: application/json; charset=utf-8');
$id = (int) utilisateur()['NumUsers'];
$space = new AdherentEspace();
$planning = new PlanningController();
$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) $input = [];
$action = (string)($_GET['action'] ?? $input['action'] ?? '');

function apiReply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiSession(array $row): array
{
    $start = str_replace(' ', 'T', (string)$row['date_heure_debut']);
    $end = str_replace(' ', 'T', (string)$row['date_heure_fin']);
    $category = isset(CATEGORIES[$row['type_activite']]) ? $row['type_activite'] : 'cardio';
    $capacity = (int)$row['capacite_max'];
    $taken = (int)$row['places_prises'];
    $booked = !empty($row['Numres']);
    return [
        'id'=>(string)$row['Numactivite'], 'activity_id'=>(int)$row['Numactivite'],
        'reservation_id'=>(int)($row['Numres'] ?? 0), 'title'=>$row['titre'],
        'category'=>$category, 'coach'=>$row['coach'], 'coach_full'=>$row['coach'],
        'level'=>$row['niveau'] ?: 'Tous niveaux', 'room'=>$row['nomsalle'],
        'date'=>substr($start,0,10), 'start'=>$start, 'end'=>$end,
        'capacity'=>$capacity, 'booked'=>$taken, 'remaining'=>max(0,$capacity-$taken),
        'is_booked'=>$booked, 'is_past'=>strtotime($start)<time(),
        'is_waitlisted'=>(bool)($row['en_attente'] ?? false),
        'can_cancel'=>strtotime($start)-time() >= 2*3600,
        'color'=>CATEGORIES[$category]['color'], 'image'=>CAT_IMAGES[$category],
    ];
}

function notifyMember(int $memberId, int $reservationId, string $type, string $title, string $message): void
{
    $q = db()->prepare('INSERT IGNORE INTO notification_adherent (NumUsers, Numres, type_notification, titre, message) VALUES (?, ?, ?, ?, ?)');
    $q->execute([$memberId, $reservationId, $type, $title, $message]);
}

function promoteWaitlist(int $activityId, PlanningController $planning): ?int
{
    $q = db()->prepare('SELECT NumUsers FROM liste_attente WHERE Numactivite=? ORDER BY date_inscription, Numattente');
    $q->execute([$activityId]);
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $memberId) {
        $memberId = (int)$memberId;
        try {
            $planning->reserver($memberId, $activityId);
            $r = db()->prepare('SELECT Numres FROM reservation WHERE NumUsers=? AND Numactivite=?');
            $r->execute([$memberId, $activityId]);
            $reservationId = (int)$r->fetchColumn();
            db()->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$memberId, $activityId]);
            notifyMember($memberId, $reservationId, 'PLACE_LIBEREE', 'Une place s’est libérée', 'Votre inscription au cours est confirmée.');
            return $memberId;
        } catch (Throwable $error) {
            if (str_contains(mb_strtolower($error->getMessage()), 'complet')) return null;
            // Un adhérent devenu inéligible ne bloque pas les suivants dans la file.
            db()->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$memberId, $activityId]);
        }
    }
    return null;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = (string)($input['csrf'] ?? $_POST['csrf'] ?? '');
        if ($token === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
            apiReply(['error'=>'Session expirée. Rechargez la page.'], 419);
        }
    }

    switch ($action) {
        case 'events':
            $start = (string)($_GET['start'] ?? '');
            $end = (string)($_GET['end'] ?? '');
            if (!strtotime($start) || !strtotime($end)) apiReply(['error'=>'Période invalide.'], 400);
            $events = $planning->evenements(date('Y-m-d H:i:s', strtotime($start)), date('Y-m-d H:i:s', strtotime($end)), $id);
            if (!$events) apiReply(atx_demo_sessions($start, $end));
            foreach ($events as &$event) {
                $p = &$event['extendedProps'];
                $p['category'] = $p['type'];
                $p['coach_full'] = $p['coach'];
                $p['room'] = $p['salle'];
                $p['level'] = $p['typeLibelle'];
                $p['capacity'] = $p['capacite'];
                $p['booked'] = $p['prises'];
                $p['remaining'] = $p['restantes'];
                $p['is_booked'] = $p['reserve'];
                $p['is_waitlisted'] = $p['enAttente'];
                $p['can_cancel'] = strtotime($event['start']) - time() >= 2 * 3600;
                $p['is_past'] = $p['passe'];
                $p['color'] = CATEGORIES[$p['category']]['color'] ?? '#4F7DFF';
                $p['image'] = CAT_IMAGES[$p['category']] ?? '';
                $p['id'] = (string)$event['id'];
                $p['title'] = $event['title'];
                $p['start'] = $event['start'];
                $p['end'] = $event['end'];
                $p['date'] = substr($event['start'], 0, 10);
                unset($p);
            }
            apiReply($events);

        case 'my':
            apiReply(array_map('apiSession', $space->reservations($id)));

        case 'waitlist_join':
            $activityId = (int)($input['id'] ?? 0);
            $q = db()->prepare('SELECT capacite_max, date_heure_debut FROM activite WHERE Numactivite=?');
            $q->execute([$activityId]);
            $activity = $q->fetch();
            if (!$activity || strtotime($activity['date_heure_debut']) <= time()) apiReply(['error'=>'Ce cours n’est plus disponible.'], 422);
            $q = db()->prepare("SELECT COUNT(*) FROM reservation WHERE Numactivite=? AND statut_reservation<>'Annulee'");
            $q->execute([$activityId]);
            if ((int)$q->fetchColumn() < (int)$activity['capacite_max']) apiReply(['error'=>'Une place est disponible. Réservez directement le cours.'], 422);
            $q = db()->prepare("SELECT COUNT(*) FROM reservation WHERE NumUsers=? AND Numactivite=? AND statut_reservation<>'Annulee'");
            $q->execute([$id, $activityId]);
            if ((int)$q->fetchColumn()) apiReply(['error'=>'Vous avez déjà réservé ce cours.'], 422);
            db()->prepare('INSERT IGNORE INTO liste_attente (NumUsers, Numactivite) VALUES (?, ?)')->execute([$id, $activityId]);
            apiReply(['message'=>'Vous êtes sur la liste d’attente.']);

        case 'waitlist_leave':
            $activityId = (int)($input['id'] ?? 0);
            db()->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$id, $activityId]);
            apiReply(['message'=>'Vous avez quitté la liste d’attente.']);

        case 'next':
            $items = $space->reservations($id);
            if (isset($items[0])) apiReply(['session'=>apiSession($items[0])]);
            $available = array_values(array_filter(atx_all_sessions(), static fn($session) => $session['start'] > date('Y-m-d\TH:i:s')));
            if ($available) {
                $session = $available[0];
                $session['booked'] = $session['base_booked'];
                $session['remaining'] = max(0, $session['capacity'] - $session['base_booked']);
                $session['is_past'] = false;
                $session['is_demo'] = false;
                $session['color'] = CATEGORIES[$session['category']]['color'];
                $session['image'] = CAT_IMAGES[$session['category']];
                apiReply(['session'=>$session]);
            }
            $demo = atx_demo_sessions(date('Y-m-d'), date('Y-m-d', strtotime('+14 days')));
            $upcoming = array_values(array_filter($demo, static fn($event) => $event['start'] > date('Y-m-d\TH:i:s')));
            $session = $upcoming[0]['extendedProps'] ?? null;
            if ($session) $session['title'] = $upcoming[0]['title'];
            apiReply(['session'=>$session]);

        case 'book':
            $activityId = (int)($input['id'] ?? 0);
            $title = $planning->reserver($id, $activityId);
            db()->prepare('DELETE FROM liste_attente WHERE NumUsers=? AND Numactivite=?')->execute([$id, $activityId]);
            journalise('RESERVATION', 'Numactivite='.$activityId);
            $q=db()->prepare('SELECT a.Numactivite,a.titre,a.type_activite,a.niveau,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,u.prenomUsers AS coach,(SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation<>\'Annulee\') AS places_prises,r.Numres,(SELECT COUNT(*) FROM liste_attente la WHERE la.NumUsers=? AND la.Numactivite=a.Numactivite) AS en_attente FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur u ON u.NumUsers=a.NumUsers JOIN reservation r ON r.Numactivite=a.Numactivite AND r.NumUsers=? AND r.statut_reservation<>\'Annulee\' WHERE a.Numactivite=?');
            $q->execute([$id,$id,$activityId]);
            apiReply(['message'=>'Réservation confirmée : '.$title.'.','session'=>apiSession($q->fetch())]);

        case 'cancel':
            $activityId = (int)($input['id'] ?? 0);
            $q = db()->prepare("SELECT Numres FROM reservation WHERE NumUsers=? AND Numactivite=? AND statut_reservation <> 'Annulee'");
            $q->execute([$id,$activityId]);
            $reservationId = (int)$q->fetchColumn();
            if (!$reservationId) apiReply(['error'=>'Réservation introuvable.'], 404);
            $planning->annuler($id, $reservationId);
            db()->prepare("DELETE FROM notification_adherent WHERE Numres=? AND type_notification='RAPPEL_SEANCE'")->execute([$reservationId]);
            journalise('ANNULATION', 'Numres='.$reservationId);
            $promoted = promoteWaitlist($activityId, $planning);
            $q=db()->prepare('SELECT a.Numactivite,a.titre,a.type_activite,a.niveau,a.date_heure_debut,a.date_heure_fin,a.capacite_max,s.nomsalle,u.prenomUsers AS coach,(SELECT COUNT(*) FROM reservation r WHERE r.Numactivite=a.Numactivite AND r.statut_reservation<>\'Annulee\') AS places_prises,NULL AS Numres,0 AS en_attente FROM activite a JOIN salle s ON s.Numsalle=a.Numsalle JOIN utilisateur u ON u.NumUsers=a.NumUsers WHERE a.Numactivite=?');
            $q->execute([$activityId]);
            apiReply(['message'=>'Réservation annulée.'.($promoted ? ' La place a été attribuée à une personne en liste d’attente.' : ''),'session'=>apiSession($q->fetch())]);

        case 'profile':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') apiReply($space->profil($id));
            $space->updateProfile($id, $input);
            apiReply(['profile'=>$space->profil($id)]);

        case 'messages':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') apiReply($space->messages($id));
            $space->envoyerMessage($id, (string)($input['message'] ?? ''), isset($input['recipient']) ? (int)$input['recipient'] : null);
            apiReply(['message'=>'Message envoyé.']);

        default:
            apiReply(['error'=>'Action inconnue.'], 404);
    }
} catch (InvalidArgumentException|RuntimeException $exception) {
    apiReply(['error'=>$exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log('[Adherent API] '.$exception->getMessage());
    apiReply(['error'=>'Une erreur est survenue. Réessayez.'], 500);
}
