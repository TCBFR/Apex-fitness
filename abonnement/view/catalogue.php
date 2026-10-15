<?php
$offres = $offres ?? [];
$aboCourant = $aboCourant ?? null;
require_once __DIR__ . '/../../public/includes/public_icons.php';
if (empty($catalogueDansEspaceAdherent)) require __DIR__ . '/../../layout/header.php';
?>

<section class="hero" style="--hero-img:url('https://images.unsplash.com/photo-1521805103424-d8f8430e8933?auto=format&fit=crop&w=1600&q=70')">
    <div class="container hero-content reveal"><p class="eyebrow">Nos formules</p><h1 class="hero-title">Choisis <span class="accent">ton rythme.</span></h1><p class="hero-lead">Des abonnements adaptés à tes objectifs, avec des conditions et des tarifs clairement présentés.</p></div>
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
                <article class="card plan-card reveal<?= $featured ? ' is-featured' : '' ?>" style="--delay:<?= (int) $index * 80 ?>ms">
                    <?php if ($featured): ?><span class="plan-badge">Le plus complet</span><?php endif; ?>
                    <p class="eyebrow"><?= e($detail) ?></p><h2 class="plan-name"><?= e($offre['libelle_abo']) ?></h2><p class="muted"><?= e($offre['description_abo']) ?></p>
                    <p class="plan-price"><strong><?= $prix ?></strong><span><?= e($periode) ?></span></p>
                    <ul class="check-list"><?php foreach ($points as $point): ?><li><?= publicIcon('check', 'icon') ?> <?= e($point) ?></li><?php endforeach; ?></ul>
                    <ul class="plan-meta"><li><?= publicIcon('clock', 'icon') ?> <?= e($detail) ?></li><li><?= publicIcon('shield-check', 'icon') ?> Conditions transparentes</li></ul>
                    <?php if ($courant): ?><span class="btn btn-outline btn-block" aria-current="true">Votre formule actuelle</span><?php elseif (a_role('ADHERENT')): ?><a class="btn <?= $featured ? 'btn-primary' : 'btn-outline' ?> btn-block" href="<?= e(url('paiement/payer.php?switch=1')) ?>">Choisir cette formule <?= publicIcon('arrow-right', 'icon') ?></a><?php else: ?><a class="btn <?= $featured ? 'btn-primary' : 'btn-outline' ?> btn-block" href="<?= e(url('message_contact/index.php?sujet=' . urlencode('Inscription : ' . $offre['libelle_abo']))) ?>">Choisir cette formule <?= publicIcon('arrow-right', 'icon') ?></a><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card banner reveal"><span class="icon-circle"><?= publicIcon('shield-check', 'icon') ?></span><div><p class="banner-title">Des prix transparents, sans frais cachés.</p><p class="muted small">Tu sais exactement ce que tu paies, avec les services inclus dans chaque formule.</p></div></div>
</div></section>

<?php if (empty($catalogueDansEspaceAdherent)) require __DIR__ . '/../../layout/footer.php'; ?>
