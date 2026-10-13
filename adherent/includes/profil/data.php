<?php
// Données fictives partagées pour les deux pages
$user = [
    'prenom'         => 'Thomas',
    'nom'            => 'Martin',
    'email'          => 'thomas.martin@gmail.com',
    'telephone'      => '06 12 34 56 78',
    'ville'          => 'Bordeaux, France',
    'adresse_l1'     => '12 Rue des Érables',
    'adresse_l2'     => '33000 Bordeaux',
    'date_naissance' => '15/08/2000',
    'sexe'           => 'Homme',
    'membre_depuis'  => '12 janvier 2025',
    'num_membre'     => 'ATX-2587',
    'avatar'         => 'assets/images/avatar.png',
    'initiale'       => 'S.',
];

$abonnement = [
    'formule'          => 'Premium',
    'statut'           => 'Actif',
    'duree'            => '12 mois',
    'date_debut'       => '15 janvier 2025',
    'prochaine_echeance' => '14 janvier 2026',
    'renouvellement'   => 'automatique',
    'mois_restants'    => 8,
    'mois_total'       => 12,
    'acces' => [
        ['icon' => 'dumbbell',   'label' => 'Musculation',     'detail' => 'Accès libre'],
        ['icon' => 'heart-pulse','label' => 'Cardio',          'detail' => 'Accès libre'],
        ['icon' => 'users',      'label' => 'Cours collectifs','detail' => 'Inclus'],
        ['icon' => 'leaf',       'label' => 'Yoga & Bien-être','detail' => 'Inclus'],
    ],
    'avantages' => [
        'Accès illimité à la salle',
        'Cours collectifs illimités',
        'Accès espace bien-être (sauna, hammam)',
        'Suivi personnalisé (inclus selon formule)',
        'Réductions partenaires',
    ],
    'historique' => [
        ['date' => '14/01/2025', 'prestation' => 'Abonnement Premium (12 mois)', 'montant' => '499,00 €', 'statut' => 'Payé'],
        ['date' => '14/01/2024', 'prestation' => 'Abonnement Premium (12 mois)', 'montant' => '499,00 €', 'statut' => 'Payé'],
        ['date' => '14/01/2023', 'prestation' => 'Abonnement Premium (12 mois)', 'montant' => '499,00 €', 'statut' => 'Payé'],
    ],
];
