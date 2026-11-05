<?php
// Vue d'entrée appelée par le routeur : prépare les variables puis affiche le catalogue public.
$titrePage = $viewData['titrePage'] ?? 'Abonnements';
$offres = $viewData['offres'] ?? [];
$aboCourant = $viewData['aboCourant'] ?? null;

require __DIR__ . '/catalogue.php';