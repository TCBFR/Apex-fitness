<?php
$currentUser = ['name' => 'Lucas Martin', 'role' => 'Membre Premium', 'initials' => 'LM', 'color' => '#c6f24e'];

$conversations = [
    [
        'id' => 'thomas', 'name' => 'Thomas Durand', 'role' => 'coach', 'roleLabel' => 'Coach musculation',
        'initials' => 'TD', 'color' => '#4f7cff', 'online' => true, 'unread' => 2, 'time' => '10:42',
        'messages' => [
            ['date' => 'Hier'],
            ['from' => 'them', 'text' => "Salut Lucas ! J'ai regardé tes perfs de la semaine, super progression sur le squat 👏", 'time' => '18:20'],
            ['from' => 'me', 'text' => "Merci coach ! J'ai senti la différence avec le nouveau programme.", 'time' => '18:34', 'read' => true],
            ['from' => 'them', 'text' => "On va augmenter un peu la charge la semaine prochaine. Pense à bien t'hydrater et à dormir au moins 7h.", 'time' => '18:36'],
            ['date' => "Aujourd'hui"],
            ['from' => 'me', 'text' => 'Ok parfait. On garde la séance de jeudi à 18h ?', 'time' => '10:15', 'read' => true],
            ['from' => 'them', 'text' => 'Oui, jeudi 18h, salle 2. On attaque le haut du corps 💪', 'time' => '10:40'],
            ['from' => 'them', 'text' => "Et n'oublie pas ta ceinture de force !", 'time' => '10:42'],
        ],
    ],
    [
        'id' => 'accueil', 'name' => 'Accueil Athletix', 'role' => 'accueil', 'roleLabel' => 'Accueil',
        'initials' => 'AA', 'color' => '#c6f24e', 'online' => true, 'unread' => 1, 'time' => '09:15',
        'messages' => [
            ['date' => "Aujourd'hui"],
            ['from' => 'them', 'text' => 'Bonjour Lucas, votre badge d\'accès a bien été renouvelé. Il est disponible à l\'accueil dès maintenant.', 'time' => '09:15'],
        ],
    ],
    [
        'id' => 'sarah', 'name' => 'Sarah Benali', 'role' => 'coach', 'roleLabel' => 'Coach yoga & mobilité',
        'initials' => 'SB', 'color' => '#ff7a59', 'online' => false, 'unread' => 0, 'time' => 'Hier',
        'messages' => [
            ['date' => 'Lundi'],
            ['from' => 'them', 'text' => 'Hello ! Le cours de mobilité de mercredi est déplacé à 19h30.', 'time' => '14:02'],
            ['from' => 'me', 'text' => 'Merci pour l\'info, je serai là.', 'time' => '14:10', 'read' => true],
            ['date' => 'Hier'],
            ['from' => 'them', 'text' => 'Super séance hier ! Voici une routine d\'étirements à faire le matin : 5 min de cat-cow, 2 min de pigeon de chaque côté, 3 min de respiration diaphragmatique, puis quelques salutations au soleil pour terminer en douceur. Tiens-moi au courant de tes sensations après une semaine 🧘', 'time' => '20:45'],
            ['from' => 'me', 'text' => 'Top, je commence demain matin 🙏', 'time' => '21:02', 'read' => true],
        ],
    ],
    [
        'id' => 'admin', 'name' => 'Administration', 'role' => 'admin', 'roleLabel' => 'Service administratif',
        'initials' => 'AD', 'color' => '#a78bfa', 'online' => false, 'unread' => 0, 'time' => '12 juin',
        'messages' => [
            ['date' => '12 juin'],
            ['from' => 'them', 'text' => 'Votre facture du mois de juin est disponible dans votre espace Paiements.', 'time' => '08:00'],
            ['from' => 'me', 'text' => 'Bien reçu, merci.', 'time' => '08:30', 'read' => true],
        ],
    ],
    [
        'id' => 'karim', 'name' => 'Karim Haddad', 'role' => 'coach', 'roleLabel' => 'Coach cross-training',
        'initials' => 'KH', 'color' => '#22d3ee', 'online' => true, 'unread' => 0, 'time' => '10 juin',
        'messages' => [
            ['date' => '10 juin'],
            ['from' => 'them', 'text' => 'WOD de samedi : 5 rounds — 10 burpees, 15 kettlebell swings, 20 box jumps. Prêt ?', 'time' => '17:12'],
            ['from' => 'me', 'text' => 'Toujours prêt 🔥', 'time' => '17:20', 'read' => false],
        ],
    ],
    [
        'id' => 'planning', 'name' => 'Accueil — Planning', 'role' => 'accueil', 'roleLabel' => 'Réservations',
        'initials' => 'AP', 'color' => '#facc15', 'online' => false, 'unread' => 0, 'time' => '8 juin',
        'messages' => [
            ['date' => '8 juin'],
            ['from' => 'them', 'text' => 'Votre réservation du cours de spinning (samedi 10h) est confirmée.', 'time' => '11:05'],
        ],
    ],
    [
        'id' => 'compta', 'name' => 'Comptabilité', 'role' => 'admin', 'roleLabel' => 'Facturation',
        'initials' => 'CP', 'color' => '#f472b6', 'online' => false, 'unread' => 0, 'time' => '2 juin',
        'messages' => [
            ['date' => '2 juin'],
            ['from' => 'them', 'text' => 'Votre prélèvement mensuel a bien été effectué. Merci de votre confiance.', 'time' => '09:30'],
        ],
    ],
];

if (!function_exists('lastMessage')) {
    function lastMessage(array $conv): array {
        for ($i = count($conv['messages']) - 1; $i >= 0; $i--) {
            if (isset($conv['messages'][$i]['text'])) return $conv['messages'][$i];
        }
        return ['text' => '', 'from' => 'them'];
    }
}

// e() est déjà définie dans config/function.php : on ne la redéclare que si elle manque
if (!function_exists('e')) {
    function e($s): string {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}