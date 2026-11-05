<?php

$PE = [
    'events_url'       => url('planning/events.php'),
    'action_creer'     => 'creer_creneau',
    'action_supprimer' => 'supprimer_creneau',
];

/* ---- Types d'activité (PlanningController::TYPES) + couleurs ------------- */
$palette = !empty($accueil)
    ? ['#4b8ff7', '#ad64e8', '#f27675', '#e26b9e', '#e3a32f', '#54ad68', '#29a7a5', '#80a640']
    : ['#CCFF00', '#4DA3FF', '#FF7A59', '#B07CFF', '#2EC4B6', '#FFC857', '#F06292', '#8BC34A'];
$types = [];
$n = 0;
foreach (PlanningController::TYPES as $cle => $libelle) {
    $types[$cle] = ['label' => $libelle, 'color' => $palette[$n++ % count($palette)]];
}
