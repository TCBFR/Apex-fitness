<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
$tables = [
    'utilisateur', 'fonction', 'employe', 'fournisseur', 'admin', 'adherent', 'qr_code',
    'offre', 'freemium', 'basic', 'abonnement_plus', 'abonnement', 'souscrire', 'paiement',
    'salle', 'activite', 'reservation', 'statut', 'etat_equipement', 'statut_maintenance',
    'type_equipement', 'equipement', 'demande_maintenance', 'interaction_adherent', 'avis', 'faq',
];
$titrePage = 'Tables de la base';
$pageActive = 'crud';
require __DIR__ . '/../layout/header.php';
?>
<main class="crud-page wrap">
    <div class="crud-heading">
        <div>
            <p class="crud-eyebrow">Administration</p>
            <h1>Tables de la base</h1>
        </div>
        <a class="btn btn-sm" href="<?= e(url('admin/espace.php')) ?>">Retour à l’administration</a>
    </div>
    <ul class="crud-table-catalog">
        <?php foreach ($tables as $table): ?>
            <li><a href="<?= e(url($table . '/index.php')) ?>"><?= e(str_replace('_', ' ', $table)) ?></a></li>
        <?php endforeach; ?>
    </ul>
</main>
<?php require __DIR__ . '/../layout/footer.php'; ?>