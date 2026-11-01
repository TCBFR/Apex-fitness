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
    <nav class="subscription-steps<?= !empty($isInformationStep) ? ' progress-step-2' : ' progress-step-1' ?>" aria-label="Étapes de gestion de l’abonnement">
        <div class="subscription-step<?= !empty($isInformationStep) ? ' is-complete' : ' is-active' ?>"<?= empty($isInformationStep) ? ' aria-current="step"' : '' ?>><span>1</span><strong>Choix de la formule</strong></div>
        <div class="subscription-step<?= !empty($isInformationStep) ? ' is-active' : '' ?>"<?= !empty($isInformationStep) ? ' aria-current="step"' : '' ?>><span>2</span><strong>Informations</strong></div>
        <div class="subscription-step"><span>3</span><strong>Paiement</strong></div>
        <div class="subscription-step"><span>4</span><strong>Confirmation</strong></div>
    </nav>
<?php endif; ?>

<?php if (!empty($catalogueDansEspaceAdherent) && !empty($isInformationStep)): ?>
<section class="account-information-hero">
    <div>
        <p class="eyebrow">Étape 2 sur 4</p>
        <h1>Vos informations et votre contrat</h1>
        <p>Vérifiez vos coordonnées, prenez connaissance des règles du club et validez votre contrat.</p>
    </div>
    <span class="account-information-secure"><?= publicIcon('lock', 'icon') ?> Validation sécurisée</span>
</section>
<section class="account-information-content">
    <?php if (!empty($subscriptionError)): ?><p class="account-contract-error" role="alert"><?= e($subscriptionError) ?></p><?php endif; ?>
    <div class="account-information-layout account-information-layout--contract">
        <div class="account-information-main">
            <section class="account-information-card account-information-card--profile">
                <h2><span class="account-section-number">01</span><?= publicIcon('user', 'icon') ?> Vos informations</h2>
                <p class="account-information-description">Coordonnées associées à votre compte.</p>
                <dl class="account-member-details">
                    <div><dt>Nom</dt><dd><?= e(trim(($memberProfile['prenom'] ?? '') . ' ' . ($memberProfile['nom'] ?? ''))) ?></dd></div>
                    <div><dt>E-mail</dt><dd><?= e($memberProfile['email'] ?? '') ?></dd></div>
                    <div><dt>Téléphone</dt><dd><?= e($memberProfile['telephone'] ?? 'Non renseigné') ?></dd></div>
                </dl>
            </section>
            <section class="account-information-card account-information-card--rules">
                <h2><span class="account-section-number">02</span><?= publicIcon('shield-check', 'icon') ?> Règlement de la salle</h2>
                <p class="account-information-description">Quelques règles pour une pratique sûre et respectueuse.</p>
                <ul class="account-house-rules">
                    <?php foreach (ContratAbonnement::reglementSalle() as $rule): ?>
                        <li><?= publicIcon('check', 'icon') ?> <?= e($rule) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <section class="account-information-card account-information-card--contract">
                <h2><span class="account-section-number">03</span><?= publicIcon('info', 'icon') ?> Contrat d’adhésion</h2>
                <p class="account-information-description">Lisez les conditions liées à votre formule avant de les accepter.</p>
                <pre class="account-contract-text"><?= e($selectedContract) ?></pre>
            </section>
            <form method="post" class="account-contract-form account-contract-form--confirmation">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="accept_contract">
                <input type="hidden" name="plan" value="<?= (int) $selectedOffer['Numabo'] ?>">
                <label class="account-contract-consent">
                    <input type="checkbox" name="accept_contract" value="1" required>
                    <span><strong>Je confirme mon accord</strong><small>J’ai lu et j’accepte le règlement intérieur et le contrat d’adhésion ci-dessus.</small></span>
                </label>
                <div class="account-information-actions">
                    <button class="btn btn-primary" type="submit">Continuer vers le paiement <?= publicIcon('arrow-right', 'icon') ?></button>
                </div>
            </form>
        </div>
    </div>
</section>
<?php else: ?>
<section class="hero" style="--hero-img:url('https://images.unsplash.com/photo-1521805103424-d8f8430e8933?auto=format&fit=crop&w=1600&q=70')">
    <div class="container hero-content reveal"><?php if (empty($catalogueDansEspaceAdherent)): ?><p class="eyebrow">Nos formules</p><?php endif; ?><h1 class="hero-title">Choisis <span class="accent">ton rythme.</span></h1><p class="hero-lead">Des abonnements adaptés à tes objectifs, avec des conditions et des tarifs clairement présentés.</p></div>
</section>

<section class="section section-tight"><div class="container">
    <?php if (!$offres): ?><div class="empty-state"><h2>Aucune formule n’est disponible</h2><p>Revenez consulter le catalogue prochainement.</p></div><?php else: ?>
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
                    <ul class="plan-meta">
                        <li><?= publicIcon('clock', 'icon') ?> <?= e($detail) ?></li>
                        <?php if (empty($catalogueDansEspaceAdherent)): ?><li><?= publicIcon('shield-check', 'icon') ?> Conditions transparentes</li><?php endif; ?>
                    </ul>
                    <?php if (!empty($catalogueDansEspaceAdherent)): ?>
                        <?php if ($courant): ?>
                            <p class="plan-current"><?= publicIcon('check', 'icon') ?> Votre formule actuelle</p>
                        <?php else: ?>
                            <a class="btn <?= $featured ? 'btn-primary' : 'btn-outline' ?> account-plan-select" href="<?= e(url('adherent/view/offres.php?step=information&plan=' . (int) $offre['Numabo'])) ?>">
                                Choisir cette formule <?= publicIcon('arrow-right', 'icon') ?>
                            </a>
                        <?php endif; ?>
                    <?php elseif ($courant): ?>
                        <p class="plan-current"><?= publicIcon('check', 'icon') ?> Votre formule actuelle</p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($offres && empty($catalogueDansEspaceAdherent)): ?>
        <div class="card banner reveal"><span class="icon-circle"><?= publicIcon('shield-check', 'icon') ?></span><div><p class="banner-title">Des prix transparents, sans frais cachés.</p><p class="muted small">Tu sais exactement ce que tu paies, avec les services inclus dans chaque formule.</p></div></div>
        <?php if (!connecte() || a_role('ADHERENT')): ?>
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
<?php endif; ?>

<?php if (!empty($catalogueDansEspaceAdherent)): ?></div><?php endif; ?>

<?php if (empty($catalogueDansEspaceAdherent)) require __DIR__ . '/../../layout/footer.php'; ?>
