<?php
require_once __DIR__ . '/../../config/function.php';
exige_role('ADHERENT');
require_once __DIR__ . '/../../abonnement/controller/AbonnementController.php';
require_once __DIR__ . '/../model/AdherentEspace.php';

$controller = new AbonnementController();
$offres = $controller->getAll();
$memberId = (int) utilisateur()['NumUsers'];
$aboCourant = $controller->get($memberId);
$memberProfile = (new AdherentEspace())->profil($memberId);
$activeSubscription = (new AdherentEspace())->abonnement($memberId);
$currentOffer = null;
foreach ($offres as $offer) {
    if ((int) $offer['Numabo'] === $aboCourant) {
        $currentOffer = $offer;
        break;
    }
}
$currentPrice = null;
if ($currentOffer !== null) {
    $currentPrice = match ($currentOffer['type_abo']) {
        'FREEMIUM' => euros($currentOffer['prix_mensuel_reduit']) . ' / mois',
        'BASIC' => euros($currentOffer['prix_annuel']) . ' / an',
        default => euros($currentOffer['prix_mensuel_plus']) . ' / mois',
    };
}
$catalogueDansEspaceAdherent = true;
$activeNav = 'abonnement';
$offresRetour = url('adherent/view/abonnement.php');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gérer mon abonnement – ATHLETIX</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/athletix_public.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/adherent/abonnement.css')) ?>">
    <style>
        .main-content {
            min-width: 0;
            min-height: 100vh;
            margin-left: 250px;
            padding: 22px 36px 60px;
        }

        @media (max-width: 860px) {
            .main-content {
                margin-left: 0;
                padding: 18px 16px 48px;
            }
        }
    </style>
</head>
<body class="member-subscription">
<div class="app-shell">
    <?php include dirname(__DIR__) . '/includes/sidebar.php'; ?>
    <main class="main-content">
        <?php include dirname(__DIR__) . '/includes/topbar.php'; ?>
        <div class="wrap" style="padding: 24px 0 8px">
            <a class="btn btn-outline" href="<?= e($offresRetour) ?>">← Retour à mon abonnement</a>
        </div>
        <?php require __DIR__ . '/../../abonnement/view/catalogue.php'; ?>
    </main>
</div>
<script src="<?= e(url('assets/js/pages/athletix.js')) ?>"></script>
</body>
</html>
