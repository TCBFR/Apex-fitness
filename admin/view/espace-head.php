<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administration - <?= e(APP_NOM) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/admin.css?v=20261101-salles-1')) ?>">
  <?php if ($onglet === 'planning'): ?><link rel="stylesheet" href="<?= e(url('assets/css/pages/planning.css')) ?>"><?php endif; ?>
  <?php if (in_array($onglet, ['coachs', 'utilisateurs'], true)): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/commune/commune.css')) ?>">
  <?php endif; ?>
</head>
<body class="admin-page<?= $onglet === 'salles' ? ' admin-rooms-page' : '' ?><?= $onglet === 'planning' ? ' admin-weekly-page' : '' ?><?= $onglet === 'reservations' ? ' admin-reservations-page' : '' ?><?= $onglet === 'abonnements' ? ' admin-subscriptions-page' : '' ?><?= $onglet === 'paiements' ? ' admin-payments-page' : '' ?>">
<div class="admin-shell">
  <?php require __DIR__ . '/../../layout/sidebar.php'; ?>

  <div class="admin-main">
    <?php require __DIR__ . '/../../layout/topbar.php'; ?>

    <?php if ($flashMessages): ?>
      <div class="admin-flashes">
        <?php foreach ($flashMessages as $message): ?>
          <p class="flash flash-<?= e($message['type']) ?>"><?= e($message['message']) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <main class="admin-content">
