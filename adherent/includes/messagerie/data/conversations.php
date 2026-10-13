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
    $name = trim($firstName . ' ' . $lastName);
    $sentAt = strtotime($message['date_envoi']);
    $day = date('Y-m-d', $sentAt);

    if (!isset($groups[$otherUserId])) {
        $groups[$otherUserId] = [
            'id' => (string) $otherUserId,
            'recipient' => $otherUserId,
            'name' => $name,
            'role' => 'coach',
            'roleLabel' => 'Équipe ATHLETIX',
            'initials' => mb_strtoupper(mb_substr($name, 0, 1)),
            'color' => '#4f7cff',
            'online' => false,
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

usort($groups, static fn(array $first, array $second): int => strcmp($second['time'], $first['time']));
$conversations = array_values($groups);

if ($conversations === []) {
    $conversations[] = [
        'id' => 'new',
        'recipient' => 0,
        'name' => 'Accueil ATHLETIX',
        'role' => 'accueil',
        'roleLabel' => 'Accueil',
        'initials' => 'A',
        'color' => '#c6f24e',
        'online' => false,
        'unread' => 0,
        'time' => '',
        'messages' => [],
    ];
}

function lastMessage(array $conversation): array
{
    for ($index = count($conversation['messages']) - 1; $index >= 0; $index--) {
        if (isset($conversation['messages'][$index]['text'])) {
            return $conversation['messages'][$index];
        }
    }

    return ['text' => '', 'from' => 'them'];
}
