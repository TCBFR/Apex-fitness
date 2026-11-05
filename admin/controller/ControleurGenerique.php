<?php

require_once __DIR__ . '/ControleurGeneriquePart1.php';
require_once __DIR__ . '/ControleurGeneriquePart2.php';
require_once __DIR__ . '/ControleurGeneriquePart6.php';
require_once __DIR__ . '/ControleurGeneriquePart3.php';
require_once __DIR__ . '/ControleurGeneriquePart4.php';
require_once __DIR__ . '/ControleurGeneriquePart5.php';

/** Applique les actions CRUD génériques aux entités administrables. */
class ControleurGenerique
{
    private const TABLES = [
        'utilisateur', 'fonction', 'employe', 'fournisseur', 'admin', 'adherent', 'qr_code',
        'offre', 'freemium', 'basic', 'abonnement_plus', 'abonnement', 'souscrire', 'paiement',
        'salle', 'activite', 'reservation', 'statut', 'etat_equipement', 'statut_maintenance',
        'type_equipement', 'equipement', 'demande_maintenance', 'interaction_adherent', 'avis', 'faq',
    ];

    private object $model;
    private PDO $pdo;
    private string $table;
    private ?array $columnCache = null;

    use ControleurGeneriquePart1;
    use ControleurGeneriquePart2;
    use ControleurGeneriquePart6;
    use ControleurGeneriquePart3;
    use ControleurGeneriquePart4;
    use ControleurGeneriquePart5;
}
