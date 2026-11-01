<?php
$offres = $offres ?? [];
$aboCourant = $aboCourant ?? null;
require_once __DIR__ . '/../../public/includes/public_icons.php';
?>
<?php if (empty($catalogueDansEspaceAdherent)): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titrePage) ?> · ATHLETIX</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/pages/athletix_public.css') ?>">
<link rel="stylesheet" href="<?= url('assets/css/site_nav.css?v=4') ?>">
</head>
<body class="page-abonnements">
<a class="skip" href="#contenu">Aller au contenu</a>
<?php require_once __DIR__ . '/../../layout/site_nav.php'; ?>
<?php $fl = flashs(); if ($fl): ?>
<div class="wrap flashs">
<?php foreach ($fl as $f): ?>
<p class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
<main id="contenu">
<?php endif; ?>

<?php if (!empty($catalogueDansEspaceAdherent)): ?>
<div class="account-subscriptions">
    <nav class="subscription-steps" aria-label="Étapes de gestion de l’abonnement">
        <div class="subscription-step is-active" aria-current="step"><span>1</span><strong>Choix de la formule</strong></div>
        <div class="subscription-step"><span>2</span><strong>Informations</strong></div>
        <div class="subscription-step"><span>3</span><strong>Paiement</strong></div>
        <div class="subscription-step"><span>4</span><strong>Confirmation</strong></div>
    </nav>
<?php endif; ?>

<section class="hero" style="--hero-img:url('https://images.unsplash.com/photo-1521805103424-d8f8430e8933?auto=format&fit=crop&w=1600&q=70')">
    <div class="container hero-content reveal"><p class="eyebrow">Nos formules</p><h1 class="hero-title">Choisis <span class="accent">ton rythme.</span></h1><p class="hero-lead">Des abonnements adaptés à tes objectifs, avec des conditions et des tarifs clairement présentés.</p></div>
</section>

<section class="section section-tight"><div class="container">
    <?php if (!$offres): ?><div class="empty-state"><h2>Aucune formule n’est disponible</h2><p>Revenez consulter le catalogue prochainement.</p></div><?php else: ?>
        <?php if (!empty($catalogueDansEspaceAdherent)): ?>
            <div class="account-subscriptions-layout">
                <div class="account-subscriptions-main">
        <?php endif; ?>
        <div class="grid grid-3 plans">
            <?php foreach ($offres as $index => $offre):
                $courant = $aboCourant !== null && $aboCourant === (int) $offre['Numabo'];
                if ($offre['type_abo'] === 'FREEMIUM') {
                    $prix = euros($offre['prix_mensuel_reduit']);
                    $periode = '/ mois';
                    $detail = 'Réduction de ' . (int) $offre['taux_reduction'] . ' %';
                    $points = [(int) $offre['nb_reservations_max_mois'] . ' réservations par mois', 'Justificatif : ' . $offre['justificatif_requis'], 'Sans engagement'];
                } elseif ($offre['type_abo'] === 'BASIC') {
                    $prix = euros($offre['prix_annuel']);
                    $periode = '/ an';
                    $detail = 'Engagement de ' . (int) $offre['duree_engagement_mois'] . ' mois';
                    $points = ['Réservations illimitées', $offre['reconduction_tacite'] ? 'Reconduction tacite' : 'Sans reconduction', 'Accès aux trois salles'];
                } else {
                    $prix = euros($offre['prix_mensuel_plus']);
                    $periode = '/ mois';
                    $detail = 'Coaching personnalisé inclus';
                    $points = [(int) $offre['seances_coach_incluses'] . ' séances de coach incluses', $offre['acces_illimite'] ? 'Accès illimité' : 'Accès limité', 'Résiliable chaque mois'];
                }
                $featured = $offre['type_abo'] === 'PLUS';
            ?>
                <article class="card plan-card reveal<?= $featured ? ' is-featured' : '' ?><?= !empty($catalogueDansEspaceAdherent) ? ' account-plan-card' : '' ?>" style="--delay:<?= (int) $index * 80 ?>ms">
                    <?php if ($featured): ?><span class="plan-badge">Le plus complet</span><?php endif; ?>
                    <p class="eyebrow"><?= e($detail) ?></p><h2 class="plan-name"><?= e($offre['libelle_abo']) ?></h2><p class="muted"><?= e($offre['description_abo']) ?></p>
                    <p class="plan-price"><strong><?= $prix ?></strong><span><?= e($periode) ?></span></p>
                    <ul class="check-list"><?php foreach ($points as $point): ?><li><?= publicIcon('check', 'icon') ?> <?= e($point) ?></li><?php endforeach; ?></ul>
                    <ul class="plan-meta"><li><?= publicIcon('clock', 'icon') ?> <?= e($detail) ?></li><li><?= publicIcon('shield-check', 'icon') ?> Conditions transparentes</li></ul>
                    <?php if ($courant): ?><p class="plan-current"><?= publicIcon('check', 'icon') ?> Votre formule actuelle</p><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($catalogueDansEspaceAdherent)): ?>
                </div>
                <aside class="account-subscription-summary">
                    <h2><?= publicIcon('info', 'icon') ?> Récapitulatif</h2>
                    <div class="account-member-summary">
                        <span class="account-member-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) ($memberProfile['prenom'] ?? ''), 0, 1))) ?></span>
                        <div>
                            <strong><?= e(trim(($memberProfile['prenom'] ?? '') . ' ' . ($memberProfile['nom'] ?? ''))) ?></strong>
                            <span>Membre depuis le : <?= e(dateFr($memberProfile['inscrit_le'] ?? $memberProfile['cree_le'] ?? null)) ?></span>
                        </div>
                    </div>
                    <div class="account-summary-row">
                        <span><?= publicIcon('star', 'icon') ?> Formule actuelle</span>
                        <strong>
                            <?= e($activeSubscription['libelle_abo'] ?? 'Aucune formule active') ?>
                            <?php if ($activeSubscription && $currentPrice !== null): ?><small><?= e($currentPrice) ?></small><?php endif; ?>
                        </strong>
                    </div>
                    <div class="account-summary-row">
                        <span><?= publicIcon('clock', 'icon') ?> Début de l’abonnement</span>
                        <strong><?= e(isset($activeSubscription['date_debut']) ? dateFr($activeSubscription['date_debut']) : '—') ?></strong>
                    </div>
                    <p class="account-subscription-note">
                        <?= publicIcon('info', 'icon') ?>
                        <?= $activeSubscription
                            ? 'Votre abonnement est actif selon les informations enregistrées.'
                            : 'Aucun abonnement actif n’est actuellement enregistré sur votre compte.' ?>
                    </p>
                </aside>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($offres): ?>
        <div class="card banner reveal"><span class="icon-circle"><?= publicIcon('shield-check', 'icon') ?></span><div><p class="banner-title">Des prix transparents, sans frais cachés.</p><p class="muted small">Tu sais exactement ce que tu paies, avec les services inclus dans chaque formule.</p></div></div>
        <?php if (empty($catalogueDansEspaceAdherent) && (!connecte() || a_role('ADHERENT'))): ?>
            <div class="plans-cta">
                <?php if (a_role('ADHERENT')): ?>
                    <a class="btn btn-primary btn-lg" href="<?= e(url('adherent/espace.php?onglet=abonnement')) ?>">Gérer mon abonnement <?= publicIcon('arrow-right', 'icon') ?></a>
                <?php else: ?>
                    <a class="btn btn-primary btn-lg" href="<?= e(url('utilisateur/inscription.php')) ?>">Inscription <?= publicIcon('arrow-right', 'icon') ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div></section>

<?php if (!empty($catalogueDansEspaceAdherent)): ?></div><?php endif; ?>

<?php if (empty($catalogueDansEspaceAdherent)) require __DIR__ . '/../../layout/footer.php'; ?>
