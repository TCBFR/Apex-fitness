<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <?php require __DIR__ . '/facture-style.php'; ?>
</head>
<body>
<div class="sheet">
    <div class="hero">
        <?php if ($bannerDataUri !== ''): ?><img class="hero-photo" src="<?= $safe($bannerDataUri) ?>" alt=""><?php endif; ?>
        <table class="hero-content">
            <tr>
                <td class="brand-cell">
                    <table class="brand-lockup"><tr>
                        <td class="brand-mark"><?php if ($logoDataUri !== ''): ?><img class="brand-logo" src="<?= $safe($logoDataUri) ?>" alt=""><?php else: ?>Λ<?php endif; ?></td>
                        <td><div class="brand-name">ATHLETIX</div><div class="tagline">TRAIN HARDER. TOGETHER.</div></td>
                    </tr></table>
                </td>
                <td><div class="invoice-head">FACTURE</div><div class="invoice-number">#<?= $safe($number) ?></div></td>
            </tr>
        </table>
    </div>
    <div class="lime-rule"></div>

    <table class="parties">
        <tr>
            <td>
                <h2 class="section-title">ATHLETIX</h2>
                <div class="party-details">12 Rue des Sports<br>75000 Paris<br>France</div>
                <div class="contact-row"><span class="contact-icon">✉</span> &nbsp; contact@athletix.fr</div>
                <div class="contact-row"><span class="contact-icon">◎</span> &nbsp; www.athletix.fr</div>
                <div class="contact-row"><span class="contact-icon">☎</span> &nbsp; +33 1 23 45 67 89</div>
            </td>
            <td>
                <h2 class="section-title">CLIENT</h2>
                <div class="party-name"><?= $safe($facture['prenomUsers'] . ' ' . $facture['nomUsers']) ?></div>
                <div class="contact-row"><?= implode('<br>', array_map($safe, $clientAddress)) ?></div>
                <div class="contact-row"><span class="contact-icon">✉</span> &nbsp;<?= $safe($facture['mailUsers']) ?></div>
                <div class="contact-row"><span class="contact-icon">☎</span> &nbsp;<?= $safe($clientPhone) ?></div>
            </td>
        </tr>
    </table>

    <div class="separator"></div>
    <table class="invoice-overview">
        <tr>
            <td><h1>Facture</h1><div class="subtitle"><?= $safe($facture['libelle_abo']) ?> · Formule <?= $safe($periodicity) ?></div></td>
            <td>
                <table class="meta">
                    <tr><td>Date d’émission</td><td><?= $safe($date) ?></td></tr>
                    <tr><td>Statut</td><td>Payée</td></tr>
                    <tr><td>Mode de paiement</td><td><?= $safe($paymentMethod) ?></td></tr>
                    <tr><td>Périodicité</td><td><?= $safe(ucfirst($periodicity)) ?></td></tr>
                    <tr><td>Numéro de facture</td><td><?= $safe($number) ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead><tr><th colspan="2">DESCRIPTION</th><th class="num">QUANTITÉ</th><th class="num">PRIX UNITAIRE</th><th class="num">TOTAL</th></tr></thead>
        <tbody><tr>
            <td style="width:42px"><span class="item-symbol">↗</span></td>
            <td><div class="item-name"><?= $safe($facture['libelle_abo']) ?></div><div class="item-detail"><?= $safe($description) ?></div></td>
            <td class="num">1</td><td class="num"><?= $safe($amount) ?></td><td class="num"><strong><?= $safe($amount) ?></strong></td>
        </tr></tbody>
    </table>

    <table class="thanks-totals"><tbody><tr>
        <td><div class="thanks"><div class="thanks-title">MERCI DE FAIRE PARTIE DE LA COMMUNAUTÉ ATHLETIX !</div><div class="thanks-copy">Votre engagement nous motive chaque jour à vous offrir une expérience sportive unique.</div></div></td>
        <td><table class="totals">
            <tr><td>Total de la prestation</td><td><?= $safe($amount) ?></td></tr>
            <tr class="grand-total"><td>TOTAL PAYÉ</td><td><?= $safe($amount) ?></td></tr>
        </table></td>
    </tr></tbody></table>

    <table class="motto"><tr>
        <td><span class="motto-mark">↗</span> Plus qu’une salle de sport,<br>une communauté.</td>
        <td><span class="motto-mark">⌖</span> 12 Rue des Sports, 75000 Paris<br>www.athletix.fr</td>
        <td><span class="motto-mark">✳</span> Ensemble pour un mode de vie<br>plus sain et plus actif.</td>
    </tr></table>

    <table class="footer"><tr>
        <td>Toute l’équipe ATHLETIX vous remercie pour votre confiance.<br>Pour toute question, contactez-nous à contact@athletix.fr</td>
        <td>Λ ATHLETIX</td>
    </tr></table>
</div>
</body>
</html>
