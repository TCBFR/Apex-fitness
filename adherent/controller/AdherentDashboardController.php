<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/model/AdherentEspace.php';

final class AdherentDashboardController
{
    public function __construct(private ?AdherentEspace $space = null)
    {
        $this->space ??= new AdherentEspace();
    }

    public function donnees(int $memberId): array
    {
        $dashboard = $this->space->dashboard($memberId);
        $profile = $dashboard['profile'];
        $subscription = $dashboard['subscription'];
        $name = trim(($profile['prenom'] ?? '') . ' ' . ($profile['nom'] ?? ''));

        return [
            'user' => ['initial' => mb_strtoupper(mb_substr($profile['prenom'] ?? '?', 0, 1)) . '.', 'name' => $name],
            'upcomingReservations' => $this->space->reservations($memberId),
            'stats' => [
                ['label' => 'Abonnement', 'value' => $subscription ? $subscription['libelle_abo'] : 'Inactif', 'icon' => 'idcard', 'accent' => true],
                ['label' => 'Réservations', 'value' => $dashboard['upcoming'] . ' à venir', 'icon' => 'calendar', 'accent' => true],
                ['label' => 'Séances ce mois', 'value' => (string) $dashboard['sessions_month'], 'icon' => 'dumbbell', 'accent' => false],
                ['label' => 'Membre depuis', 'value' => date('m/Y', strtotime($profile['inscrit_le'] ?: $profile['cree_le'])), 'icon' => 'target', 'accent' => true],
            ],
            'discover' => $this->space->activitesAdecouvrir(),
            'activityIcons' => [
                'musculation' => 'dumbbell',
                'cardio' => 'heart',
                'yoga' => 'lotus',
                'cross' => 'flame',
                'pilates' => 'activity',
                'abdos' => 'target',
                'boxe' => 'flame',
                'danse' => 'activity',
            ],
            'heroImg' => 'https://images.unsplash.com/photo-1590487988256-9ed24133863e?crop=entropy&cs=srgb&fm=jpg&q=80&w=1600',
        ];
    }

    public function lienPage(string $page): string
{
    $pages = [
        'dashboard' => 'espace',
        'planning' => 'planning',
        'reservations' => 'reservations',
        'abonnement' => 'abonnement',
        'messages' => 'messagerie',
        'profil' => 'profil',
    ];

    return isset($pages[$page])
        ? 'index.php?route=adherent/' . $pages[$page]
        : '?page=' . $page;
}
}
