<?php
require_once __DIR__ . '/../../config/function.php';
exige_role('ADHERENT');
require dirname(__DIR__) . '/includes/profil/data.php';
$subscriptionFlashes = flashs();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon abonnement – ATHLETIX</title>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/adherent/abonnement.css?v=20261101-4')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/adherent/abonnement-inline.css')) ?>">
</head>
<body class="member-subscription">

<div class="app-shell">
    <?php $activeNav = 'abonnement'; include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include dirname(__DIR__) . '/includes/topbar.php'; ?>
        <?php foreach ($subscriptionFlashes as $subscriptionFlash): ?>
            <p class="account-flash account-flash-<?= e($subscriptionFlash['type']) ?>" role="status"><?= e($subscriptionFlash['message']) ?></p>
        <?php endforeach; ?>


<?php include __DIR__ . '/abonnement-hero.php'; ?>

        <div class="grid-2">
            <div class="col-main">
                <?php include __DIR__ . '/abonnement-card.php'; ?>
                <?php include __DIR__ . '/abonnement-history.php'; ?>
            </div>
            <?php include __DIR__ . '/abonnement-sidebar.php'; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/abonnement-scripts.php'; ?>
</body>
</html>
