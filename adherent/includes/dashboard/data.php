<?php
$memberId = (int) utilisateur()['NumUsers'];
$memberSpace = new AdherentEspace();
$dashboard = $memberSpace->dashboard($memberId);
$upcomingReservations = $memberSpace->reservations($memberId);
$profile = $dashboard['profile'];
$name = trim(($profile['prenom'] ?? '') . ' ' . ($profile['nom'] ?? ''));
$user = ['initial'=>mb_strtoupper(mb_substr($profile['prenom'] ?? '?', 0, 1)).'.', 'name'=>$name];
$active = $_GET['page'] ?? 'dashboard';
$subscription = $dashboard['subscription'];
$stats = [
    ['label'=>'Abonnement', 'value'=>$subscription ? $subscription['libelle_abo'] : 'Inactif', 'icon'=>'idcard', 'accent'=>true],
    ['label'=>'Réservations', 'value'=>$dashboard['upcoming'].' à venir', 'icon'=>'calendar', 'accent'=>true],
    ['label'=>'Séances ce mois', 'value'=>(string)$dashboard['sessions_month'], 'icon'=>'dumbbell', 'accent'=>false],
    ['label'=>'Membre depuis', 'value'=>date('m/Y', strtotime($profile['inscrit_le'] ?: $profile['cree_le'])), 'icon'=>'target', 'accent'=>true],
];
$discover = [
    ['title'=>'Musculation', 'text'=>'Bâtis ta force, un entraînement à la fois.', 'icon'=>'dumbbell', 'img'=>'https://images.unsplash.com/photo-1734630341082-0fec0e10126c?crop=entropy&cs=srgb&fm=jpg&q=80&w=800'],
    ['title'=>'Cardio', 'text'=>'Énergie, endurance, performance.', 'icon'=>'heart', 'img'=>'https://images.unsplash.com/photo-1593079831268-3381b0db4a77?crop=entropy&cs=srgb&fm=jpg&q=80&w=800'],
    ['title'=>'Yoga & bien-être', 'text'=>'Équilibre du corps et de l\'esprit.', 'icon'=>'lotus', 'img'=>'https://images.unsplash.com/photo-1767327863182-a5395c2db77d?crop=entropy&cs=srgb&fm=jpg&q=80&w=800'],
];
$heroImg = 'https://images.unsplash.com/photo-1590487988256-9ed24133863e?crop=entropy&cs=srgb&fm=jpg&q=80&w=1600';
