<?php
require_once __DIR__ . '/auth.php';
$titrePage = $titrePage ?? APP_NOM;
$pageActive = $pageActive ?? '';
$pagesCss = [
	'accueil' => 'index.css',
	'abonnements' => 'abonnements.css',
	'avis' => 'avis.css',
	'contact' => 'contact.css',
	'faq' => 'faq.css',
	'login' => 'login.css',
];
$pagesJs = [
	'accueil' => 'index.js',
	'abonnements' => 'abonnements.js',
	'avis' => 'avis.js',
	'contact' => 'contact.js',
	'faq' => 'faq.js',
	'login' => 'login.js',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titrePage) ?> &middot; <?= APP_NOM ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?php if (isset($pagesCss[$pageActive])): ?>
<link rel="stylesheet" href="<?= url('assets/css/pages/' . $pagesCss[$pageActive]) ?>">
<?php endif; ?>
</head>
<body class="page-<?= e($pageActive) ?>">
<a class="skip" href="#contenu">Aller au contenu</a>

<header class="site-header">

<div class="header-banniere">
<div class="hb-titre">
<a class="logo" href="<?= url('index.php') ?>">
<img src="<?= url('assets/img/logo.png') ?>" alt="" class="logo-icon">
<span class="logo-texte">
<span class="logo-mot">ATHLETI<b>X</b></span>
<span class="logo-slogan">Plus fort chaque jour</span>
</span>
</a>
</div>
<div class="hb-photo" style="background-image:url('<?= url('assets/img/header.png') ?>')"></div>
<div class="hb-tags">
<ul class="hero-tags">
<li>Fitness</li>
<li>Musculation</li>
<li>Bien-être</li>
<li>Performance</li>
</ul>
</div>
</div>

<div class="wrap header-inner">
<button class="burger" id="burger" aria-expanded="false" aria-controls="menu">
<span></span><span></span><span></span>
<span class="sr">Ouvrir le menu</span>
</button>

<nav class="menu" id="menu" aria-label="Navigation principale">
<a href="<?= url('abonnements.php') ?>" <?= $pageActive === 'abonnements' ? 'aria-current="page"' : '' ?>>Abonnements</a>
<a href="<?= url('faq.php') ?>"         <?= $pageActive === 'faq' ? 'aria-current="page"' : '' ?>>Questions fréquentes</a>
<a href="<?= url('avis.php') ?>"         <?= $pageActive === 'avis' ? 'aria-current="page"' : '' ?>>Avis</a>
<a href="<?= url('contact.php') ?>"     <?= $pageActive === 'contact' ? 'aria-current="page"' : '' ?>>Contact</a>

<?php if (a_role('ADHERENT')): ?>
<a href="<?= url('adherent.php') ?>"  <?= $pageActive === 'adherent' ? 'aria-current="page"' : '' ?>>Mon espace</a>
<?php endif; ?>
<?php if (a_role('EMPLOYE')): ?>
<a href="<?= url('employe.php') ?>"   <?= $pageActive === 'employe' ? 'aria-current="page"' : '' ?>>Gestion</a>
<?php endif; ?>
<?php if (a_role('ADMIN')): ?>
<a href="<?= url('admin.php') ?>"     <?= $pageActive === 'admin' ? 'aria-current="page"' : '' ?>>Administration</a>
<?php endif; ?>

<?php if (connecte()): ?>
<span class="menu-user"><?= e(utilisateur()['prenomUsers']) ?></span>
<a class="btn btn-sm" href="<?= url('logout.php') ?>">Se déconnecter</a>
<?php else: ?>
<a href="<?= url('login.php') ?>" <?= $pageActive === 'login' ? 'aria-current="page"' : '' ?>>Connexion</a>
<?php endif; ?>
</nav>
</div>

</header>

<?php $fl = flashs(); if ($fl): ?>
<div class="wrap flashs">
<?php foreach ($fl as $f): ?>
<p class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<main id="contenu">