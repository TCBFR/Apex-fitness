<?php

require_once __DIR__ . '/../../config/function.php';
exige_role('ADHERENT');
require_once __DIR__ . '/../model/AdherentEspace.php';
require __DIR__ . '/../includes/dashboard/data.php';
require __DIR__ . '/../includes/dashboard/icons.php';
?>
<?php include __DIR__ . '/../includes/dashboard/head.php'; ?>
<body>
<div class="app">
    <?php $activeNav = $active; include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <section class="hero reveal" data-testid="hero-section" style="--hero:url('<?= e($heroImg) ?>')">
            <div class="hero-content">
                <p class="eyebrow">Bonjour, <?= e($user['name']) ?></p>
                <h1 data-testid="hero-title">Prêt à repousser<br>tes <em>limites</em> ?</h1>
                <p class="hero-sub">Bienvenue dans ton espace ATHLETIX.</p>
                <a href="<?= e($navHref('planning')) ?>" class="btn btn-lime" data-testid="hero-explore-planning-btn">
                    Explorer le planning <?= icon('chevron', 16) ?>
                </a>
            </div>
            <div class="hero-sign" aria-hidden="true">PLUS<br>FORT<br>CHAQUE<br>JOUR</div>
        </section>

        <section class="stats">
            <?php foreach ($stats as $i => $s): ?>
                <?php $statPages = ['abonnement','reservations','abonnement','profil']; ?>
                <a href="<?= e($navHref($statPages[$i])) ?>" class="stat reveal" style="--d:<?= ($i + 1) * 70 ?>ms" data-testid="stat-card-<?= $i ?>">
                    <span class="stat-icon"><?= icon($s['icon'], 30) ?></span>
                    <span class="stat-label"><?= e($s['label']) ?></span>
                    <span class="stat-value<?= $s['accent'] ? ' lime' : '' ?>" data-testid="stat-value-<?= $i ?>"><?= e($s['value']) ?></span>
                    <span class="stat-arrow"><?= icon('chevron', 16) ?></span>
                </a>
            <?php endforeach; ?>
        </section>

        <section class="bottom">
            <div class="panel appointments reveal" style="--d:350ms" data-testid="appointments-panel">
                <div class="panel-head"><?= icon('calendar', 24) ?><h2>Tes prochains rendez-vous</h2></div>
                <?php if ($upcomingReservations): ?>
                <div class="empty upcoming-list">
                    <?php foreach (array_slice($upcomingReservations, 0, 3) as $session): ?>
                    <p class="empty-title"><?= e($session['titre']) ?></p>
                    <p class="empty-sub"><?= e(dateFr($session['date_heure_debut'], true)) ?> · <?= e($session['nomsalle']) ?></p>
                    <?php endforeach; ?>
                    <a class="btn btn-lime btn-wide" href="<?= e($navHref('reservations')) ?>">Voir mes réservations <?= icon('chevron', 16) ?></a>
                </div>
                <?php else: ?>
                <div class="empty">
                    <span class="empty-icon"><?= icon('calendar', 34) ?></span>
                    <p class="empty-title" data-testid="appointments-empty-text">Aucune séance prévue pour le moment</p>
                    <p class="empty-sub">Réserve une séance et garde le rythme !</p>
                    <a href="<?= e($navHref('reservations')) ?>" class="btn btn-lime btn-wide" data-testid="book-session-btn">
                        <?= icon('calendar', 16) ?> Réserver une séance <?= icon('chevron', 16) ?>
                    </a>
                </div>
                <?php endif; ?>
                <span class="corner corner-tr"></span>
                <span class="corner corner-bl"></span>
            </div>

            <div class="panel discover reveal" style="--d:420ms" data-testid="discover-panel">
                <div class="panel-head">
                    <?= icon('star', 24) ?><h2>À découvrir chez ATHLETIX</h2>
                    <a href="<?= e($navHref('planning')) ?>" class="see-all" data-testid="discover-see-all">Voir tout <?= icon('chevron', 13) ?></a>
                </div>
                <div class="cards">
                    <?php foreach ($discover as $i => $activity): ?>
                        <a href="<?= e($navHref('planning')) ?>" class="dcard" data-testid="discover-card-<?= $i ?>">
                            <div class="dcard-body">
                                <span class="dcard-icon"><?= icon($activityIcons[$activity['type_activite']] ?? 'calendar', 22) ?></span>
                                <h3><?= e($activity['titre']) ?></h3>
                                <p><?= e(dateFr($activity['date_heure_debut'], true)) ?> · <?= e($activity['nomsalle']) ?></p>
                                <span class="dcard-arrow"><?= icon('arrow', 14) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$discover): ?>
                        <p class="empty-sub">Aucune séance à venir n’est enregistrée.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>
</body>
</html>
