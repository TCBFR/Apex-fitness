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
$discover = db()->query(
    "SELECT a.Numactivite, a.titre, a.type_activite, a.date_heure_debut, s.nomsalle
       FROM activite a
       JOIN salle s ON s.Numsalle = a.Numsalle
      WHERE a.date_heure_debut >= NOW() AND s.etatsalle = 'Ouverte'
      ORDER BY a.date_heure_debut
      LIMIT 3"
)->fetchAll(PDO::FETCH_ASSOC);
$activityIcons = [
    'musculation' => 'dumbbell',
    'cardio' => 'heart',
    'yoga' => 'lotus',
    'cross' => 'flame',
    'pilates' => 'activity',
    'abdos' => 'target',
    'boxe' => 'flame',
    'danse' => 'activity',
];
$heroImg = 'https://images.unsplash.com/photo-1590487988256-9ed24133863e?crop=entropy&cs=srgb&fm=jpg&q=80&w=1600';
