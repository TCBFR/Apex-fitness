<?php
require_once __DIR__ . '/../../config/function.php';
exige_role('ADHERENT');
require_once __DIR__ . '/../../abonnement/controller/AbonnementController.php';
require_once __DIR__ . '/../../abonnement/model/ContratAbonnement.php';
require_once __DIR__ . '/../model/AdherentEspace.php';

$controller = new AbonnementController();
$offres = $controller->getAll();
$memberId = (int) utilisateur()['NumUsers'];
$aboCourant = $controller->get($memberId);
$memberProfile = (new AdherentEspace())->profil($memberId);
$activeSubscription = (new AdherentEspace())->abonnement($memberId);
$requestedPlanId = filter_var($_GET['plan'] ?? null, FILTER_VALIDATE_INT);
$requestedStep = $_GET['step'] ?? 'selection';
$selectedOffer = null;
if ($requestedPlanId !== false && $requestedPlanId !== null) {
    foreach ($offres as $offer) {
        if ((int) $offer['Numabo'] === $requestedPlanId) {
            $selectedOffer = $offer;
            break;
        }
    }
}
$subscriptionError = null;
$contractSelection = 'abonnement_contrat_accepte';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept_contract') {
    verifie_csrf();
    $postedPlanId = filter_var($_POST['plan'] ?? null, FILTER_VALIDATE_INT);
    $postedOffer = null;
    if ($postedPlanId !== false && $postedPlanId !== null) {
        foreach ($offres as $offer) {
            if ((int) $offer['Numabo'] === $postedPlanId) {
                $postedOffer = $offer;
                break;
            }
        }
    }
    if ($postedOffer === null) {
        $subscriptionError = 'Cette formule n’est plus disponible. Choisissez une formule disponible.';
    } elseif (($_POST['accept_contract'] ?? null) !== '1') {
        $subscriptionError = 'Veuillez accepter le règlement intérieur et le contrat pour continuer.';
        $selectedOffer = $postedOffer;
        $requestedStep = 'information';
    } else {
        $changeRequested = $activeSubscription !== null && $aboCourant !== (int) $postedOffer['Numabo'];
        $contractContent = ContratAbonnement::contenu($postedOffer, $changeRequested);
        $_SESSION[$contractSelection] = [
            'user_id' => $memberId,
            'plan_id' => (int) $postedOffer['Numabo'],
            'version' => ContratAbonnement::VERSION,
            'content' => $contractContent,
            'hash' => hash('sha256', $contractContent),
            'accepted_at' => time(),
            'change' => $changeRequested,
        ];
        redirige(
            'paiement/payer.php?plan=' . (int) $postedOffer['Numabo']
            . ($changeRequested ? '&switch=1' : '')
        );
    }
}
$isInformationStep = $requestedStep === 'information' && $selectedOffer !== null;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $requestedStep === 'information' && !$isInformationStep) {
    flash('erreur', 'La formule demandée n’est plus disponible. Veuillez en choisir une autre.');
    redirige('adherent/view/offres.php');
}
$selectedPlanIsChange = $selectedOffer !== null
    && $activeSubscription !== null
    && $aboCourant !== (int) $selectedOffer['Numabo'];
$selectedContract = $selectedOffer !== null
    ? ContratAbonnement::contenu($selectedOffer, $selectedPlanIsChange)
    : '';
$catalogueDansEspaceAdherent = true;
$activeNav = 'abonnement';
$offresRetour = url('adherent/view/abonnement.php');
$offerFlashes = flashs();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gérer mon abonnement – ATHLETIX</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/athletix_public.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/adherent/abonnement.css?v=20261101-4')) ?>">
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
        <?php foreach ($offerFlashes as $offerFlash): ?>
            <p class="account-flash account-flash-<?= e($offerFlash['type']) ?>" role="status"><?= e($offerFlash['message']) ?></p>
        <?php endforeach; ?>
        <div class="wrap" style="padding: 24px 0 8px">
            <a class="btn btn-outline" href="<?= e($offresRetour) ?>">← Retour à mon abonnement</a>
        </div>
        <?php require __DIR__ . '/../../abonnement/view/catalogue.php'; ?>
    </main>
</div>
<script src="<?= e(url('assets/js/pages/athletix.js')) ?>"></script>
</body>
</html>
