<?php
/** @var string $content @var string|null $title */
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf()) ?>">
    <title><?= e($title ?? 'ATHLETIX') ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/profil.css')) ?>">
</head>
<body data-url-verifier="<?= e(url('qr_code/verifier-api.php')) ?>">
<?= $content ?>
<script src="<?= e(url('assets/js/qr-verification.js')) ?>"></script>
</body>
</html>