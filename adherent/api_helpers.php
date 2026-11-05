<?php

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
    $category = (string)$row['type_activite'];
    $capacity = (int)$row['capacite_max'];
    $taken = (int)$row['places_prises'];
    $booked = !empty($row['Numres']);
    return [
        'id'=>(string)$row['Numactivite'], 'activity_id'=>(int)$row['Numactivite'],
        'reservation_id'=>(int)($row['Numres'] ?? 0), 'title'=>$row['titre'],
        'category'=>$category, 'coach'=>$row['coach'], 'coach_full'=>$row['coach'],
        'level'=>(string)($row['niveau'] ?? ''), 'room'=>$row['nomsalle'],
        'date'=>substr($start,0,10), 'start'=>$start, 'end'=>$end,
        'capacity'=>$capacity, 'booked'=>$taken, 'remaining'=>max(0,$capacity-$taken),
        'is_booked'=>$booked, 'is_past'=>strtotime($start)<time(),
        'is_waitlisted'=>(bool)($row['en_attente'] ?? false),
        'can_cancel'=>strtotime($start)-time() >= 2*3600,
        'color'=>CATEGORIES[$category]['color'] ?? '#4F7DFF', 'image'=>CAT_IMAGES[$category] ?? '',
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
