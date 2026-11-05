<?php

require_once __DIR__ . '/../config/function.php';

$titrePage = 'Nos équipes';
$pageActive = 'equipes';
$fonctionsCoachPubliques = [
    'coach',
    'danse fitness',
    'yoga',
    'coach de performance',
    'boxe',
    'coach principal',
    'pilates',
];
$placeholdersCoachPublic = implode(', ', array_fill(0, count($fonctionsCoachPubliques), '?'));
$requeteCoachs = db()->prepare(
    "SELECT u.prenomUsers, u.nomUsers, e.photo_profil, f.libellefonc
       FROM employe e
       JOIN utilisateur u ON u.NumUsers = e.NumUsers
       JOIN fonction f ON f.Numfonc = e.Numfonc
      WHERE LOWER(f.libellefonc) IN ($placeholdersCoachPublic)
      ORDER BY u.nomUsers, u.prenomUsers"
);
$requeteCoachs->execute($fonctionsCoachPubliques);
$coachs = $requeteCoachs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titrePage) ?> · ATHLETIX</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/pages/equipes.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/site_nav.css?v=4') ?>">
</head>
<body class="page-equipes">
<a class="skip" href="#contenu">Aller au contenu</a>
<?php require_once __DIR__ . '/../layout/site_nav.php'; ?>
<?php $fl = flashs(); if ($fl): ?>
<div class="wrap flashs">
<?php foreach ($fl as $f): ?>
<p class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
<main id="contenu">

<section class="team-hero" aria-labelledby="team-title">
    <div class="team-hero-photo" aria-hidden="true"><img src="<?= e(url('assets/img/login.png')) ?>" alt=""></div>
    <div class="team-wrap team-hero-content">
        <p class="team-eyebrow"><span aria-hidden="true"></span> Équipe enregistrée</p>
        <h1 id="team-title">Nos <em>coachs</em></h1>
        <p class="team-lead"><?= count($coachs) ?> coach<?= count($coachs) === 1 ? '' : 's' ?> enregistré<?= count($coachs) === 1 ? '' : 's' ?> dans l’équipe.</p>
    </div>
</section>

<section class="team-directory team-wrap" aria-label="Découvrir nos coachs">
    <div class="team-grid">
        <?php foreach ($coachs as $coach):
            $nom = trim($coach['prenomUsers'] . ' ' . $coach['nomUsers']);
            $initiales = mb_strtoupper(mb_substr($coach['prenomUsers'], 0, 1) . mb_substr($coach['nomUsers'], 0, 1));
        ?>
            <article class="coach-card">
                <div class="coach-photo">
                    <?php if (!empty($coach['photo_profil'])): ?>
                        <img src="<?= e(url($coach['photo_profil'])) ?>" alt="Photo de <?= e($nom) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="coach-photo-placeholder" aria-hidden="true"><?= e($initiales) ?></span>
                    <?php endif; ?>
                </div>
                <div class="coach-info">
                    <h2><?= e($nom) ?></h2>
                    <p class="coach-role"><?= e($coach['libellefonc']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$coachs): ?>
            <p class="team-empty">Aucun coach n’est enregistré dans la base de données.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layout/footer.php'; ?>
