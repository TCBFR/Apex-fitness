

<link rel="stylesheet" href="<?= e(url('assets/css/pages/admin-dashboard.css')) ?>">
<div class="dashboard-pro">

    <!-- En-tête Exécutif -->
    <div class="pro-header">
        <div class="pro-header-title">
            <h2>Tableau de Bord Exécutif</h2>
            <p>Pilotage stratégique, financier et opérationnel du complexe <strong>Apex Fitness</strong></p>
        </div>
        <div class="pro-header-meta">
            <span class="badge-role">Admin : <?= e(($adminUser['prenomUsers'] ?? '') . ' ' . ($adminUser['nomUsers'] ?? '')) ?></span>
            <span class="badge-date"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- Indicateurs Clés de Performance (KPIs) -->
    <div class="pro-kpi-grid">
        <div class="pro-kpi-card">
            <div class="kpi-icon bg-blue">💰</div>
            <div class="kpi-content">
                <span>Chiffre d'Affaires Global</span>
                <h3><?= number_format($totalCA, 2, ',', ' ') ?> €</h3>
                <small class="text-muted"><?= $totalTransactions ?> transactions</small>
            </div>
        </div>

        <div class="pro-kpi-card">
            <div class="kpi-icon bg-green">📈</div>
            <div class="kpi-content">
                <span>CA Mois en Cours</span>
                <h3><?= number_format($caMoisEnCours, 2, ',', ' ') ?> €</h3>
                <small class="text-success">Performance courante</small>
            </div>
        </div>

        <div class="pro-kpi-card">
            <div class="kpi-icon bg-purple">🏋️</div>
            <div class="kpi-content">
                <span>Adhérents Actifs</span>
                <h3><?= $totalAdherents ?></h3>
                <small class="text-muted">Membres abonnés</small>
            </div>
        </div>

        <div class="pro-kpi-card">
            <div class="kpi-icon bg-orange">💳</div>
            <div class="kpi-content">
                <span>Panier Moyen</span>
                <h3><?= number_format($panierMoyen, 2, ',', ' ') ?> €</h3>
                <small class="text-muted">Par transaction</small>
            </div>
        </div>
    </div>

