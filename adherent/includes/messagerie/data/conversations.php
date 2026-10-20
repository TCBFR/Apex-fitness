<?php
require_once __DIR__ . '/../../../model/AdherentEspace.php';

$memberId = (int) utilisateur()['NumUsers'];
$messages = (new AdherentEspace())->messages($memberId);
$groups = [];

foreach ($messages as $message) {
    $sentByMember = (int) $message['NumUsers_emetteur'] === $memberId;
    $otherUserId = (int) ($sentByMember
        ? $message['NumUsers_destinataire']
        : $message['NumUsers_emetteur']);
    $firstName = $sentByMember ? $message['recipient_first'] : $message['emitter_first'];
    $lastName = $sentByMember ? $message['recipient_last'] : $message['emitter_last'];
    $otherRole = $sentByMember ? ($message['recipient_role'] ?? '') : ($message['emitter_role'] ?? '');
    $name = trim($firstName . ' ' . $lastName);
    $sentAt = strtotime($message['date_envoi']);
    $day = date('Y-m-d', $sentAt);

    if (!isset($groups[$otherUserId])) {
        $groups[$otherUserId] = [
            'id' => (string) $otherUserId,
            'recipient' => $otherUserId,
            'name' => $name,
            'role' => $otherRole === 'Accueil' ? 'accueil' : 'coach',
            'roleLabel' => $otherRole === 'Accueil' ? 'Accueil' : ($otherRole !== '' ? $otherRole : 'Équipe ATHLETIX'),
            'initials' => mb_strtoupper(mb_substr($name, 0, 1)),
            'color' => '#4f7cff',
            'unread' => 0,
            'time' => '',
            'messages' => [],
        ];
    }

    $conversation = &$groups[$otherUserId];
    $lastMessage = $conversation['messages'][count($conversation['messages']) - 1] ?? null;
    if ($lastMessage === null || ($lastMessage['day'] ?? null) !== $day) {
        $conversation['messages'][] = ['date' => dateFr($day), 'day' => $day];
    }

    $conversation['messages'][] = [
        'from' => $sentByMember ? 'me' : 'them',
        'text' => $message['message'],
        'time' => date('H:i', $sentAt),
        'read' => (bool) $message['lu'],
        'day' => $day,
    ];
    if (!$sentByMember && !(bool) $message['lu']) {
        $conversation['unread']++;
    }
    $conversation['time'] = date('H:i', $sentAt);
    unset($conversation);
}

foreach ($groups as &$conversation) {
    foreach ($conversation['messages'] as &$message) {
        unset($message['day']);
    }
    unset($message);
}
unset($conversation);

if (!array_filter($groups, static fn(array $conversation): bool => $conversation['role'] === 'accueil')) {
    $groups['new'] = [
        'id' => 'new',
        'recipient' => 0,
        'name' => 'Accueil ATHLETIX',
        'role' => 'accueil',
        'roleLabel' => 'Accueil',
        'initials' => 'A',
        'color' => '#c6f24e',
        'unread' => 0,
        'time' => '',
        'messages' => [],
    ];
}

usort($groups, static function (array $first, array $second): int {
    if ($first['id'] === 'new') return -1;
    if ($second['id'] === 'new') return 1;
    return strcmp($second['time'], $first['time']);
});
$conversations = array_values($groups);

function lastMessage(array $conversation): array
{
    for ($index = count($conversation['messages']) - 1; $index >= 0; $index--) {
        if (isset($conversation['messages'][$index]['text'])) {
            return $conversation['messages'][$index];
        }
    }

    return ['text' => '', 'from' => 'them'];
}
