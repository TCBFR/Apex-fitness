<?php
require_once __DIR__ . '/../config/function.php';
$titrePage = $titrePage ?? APP_NOM;
$pageActive = $pageActive ?? '';
$nomSite = 'ATHLETIX';
$pagesCss = [
	'accueil' => 'index.css',
	'abonnements' => 'abonnements.css',
	'avis' => 'avis.css',
	'contact' => 'contact.css',
	'faq' => 'faq.css',
	'login' => 'login.css',
	'crud' => 'table-crud.css',
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
<title><?= e($titrePage) ?> &middot; <?= e($nomSite) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@100..125,400..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<?php if (in_array($pageActive, ['abonnements', 'avis', 'faq', 'contact'], true)): ?>
<link rel="stylesheet" href="<?= url('assets/css/pages/athletix_public.css') ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= url('assets/css/site_nav.css?v=2') ?>">
<?php if (isset($pagesCss[$pageActive]) && !in_array($pageActive, ['abonnements', 'avis', 'faq', 'contact'], true)): ?>
<link rel="stylesheet" href="<?= url('assets/css/pages/' . $pagesCss[$pageActive]) ?>">
<?php endif; ?>
</head>
<body class="page-<?= e($pageActive) ?>">
<a class="skip" href="#contenu">Aller au contenu</a>

<?php require_once __DIR__ . '/site_nav.php'; ?>

<?php $fl = flashs(); if ($fl): ?>
<div class="wrap flashs">
<?php foreach ($fl as $f): ?>
<p class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<main id="contenu">
