<?php
$number = sprintf('FA-%s-%04d', date('Y', strtotime($facture['date_paiement'])), (int)$facture['Numpaiement']);
$date = date('d/m/Y', strtotime($facture['date_paiement']));
$amount = number_format((float)$facture['montant'], 2, ',', ' ') . ' €';
$offerType = strtoupper((string) ($facture['type_offre'] ?? ''));
$periodicity = $offerType === 'BASIC' ? 'annuelle' : 'mensuelle';
$paymentMethod = match ((string)$facture['moyen_paiement']) {
    'CB' => 'Carte bancaire',
    'Virement' => 'Virement bancaire',
    'Especes' => 'Espèces',
    'Prelevement' => 'Prélèvement',
    default => (string)$facture['moyen_paiement'],
};
$description = trim((string)($facture['description_abo'] ?? ''));
$description = match ($offerType) {
    'FREEMIUM' => 'Abonnement mensuel Freemium — 20,00 € pour le mois.',
    'BASIC' => 'Abonnement Basic annuel.',
    default => $description !== '' ? $description : 'Abonnement ' . $periodicity . ' ' . $facture['libelle_abo'] . '.',
};
$clientPhoneRaw = trim((string) ($facture['telephone'] ?? ''));
$clientPhoneDigits = preg_replace('/\D+/', '', $clientPhoneRaw);
if (strlen($clientPhoneDigits) === 10) {
    $clientPhone = trim(chunk_split($clientPhoneDigits, 2, ' '));
} elseif (strlen($clientPhoneDigits) === 11 && str_starts_with($clientPhoneDigits, '33')) {
    $clientPhone = '+33 ' . substr($clientPhoneDigits, 2, 1) . ' ' . trim(chunk_split(substr($clientPhoneDigits, 3), 2, ' '));
} else {
    $clientPhone = $clientPhoneRaw !== '' ? $clientPhoneRaw : 'Téléphone non renseigné';
}
$clientAddress = array_filter([
    trim((string)($facture['adresse_postale'] ?? '')),
    trim((string)($facture['code_postal'] ?? '') . ' ' . (string)($facture['ville'] ?? '')),
    'France',
], static fn(string $line): bool => $line !== '');
$assetRoot = dirname(__DIR__, 2) . '/public/assets/img/';
$logoPath = $assetRoot . 'logo.png';
$bannerPath = __DIR__ . '/../assets/header.jpg';
$logoDataUri = is_file($logoPath) ? 'data:image/png;base64,' . base64_encode((string)file_get_contents($logoPath)) : '';
$bannerDataUri = is_file($bannerPath) ? 'data:image/jpeg;base64,' . base64_encode((string)file_get_contents($bannerPath)) : '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #edf3f0; background: #050a09; font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        .sheet { position: relative; padding: 0 44px 30px; }
        .hero { position: relative; height: 128px; margin: 0 -44px; padding: 0 44px; overflow: hidden; background: #07100d; border-bottom: 1px solid #26332c; }
        .hero-photo { position: absolute; top: -7px; right: 42px; width: 370px; height: 144px; opacity: .42; }
        .hero-content { position: relative; z-index: 2; width: 100%; height: 128px; border-collapse: collapse; }
        .hero-content td { vertical-align: middle; }
        .brand-cell { width: 68%; }
        .brand-lockup { border-collapse: collapse; }
        .brand-mark { width: 54px; height: 54px; padding-right: 8px; color: #b7ef30; font-size: 51px; font-weight: bold; line-height: 1; }
        .brand-logo { width: 48px; height: 52px; }
        .brand-name { color: #f6f8f6; font-size: 23px; font-weight: bold; letter-spacing: 3px; }
        .tagline { padding-top: 5px; color: #c0ccc5; font-size: 8px; font-weight: bold; letter-spacing: 3px; }
        .invoice-head { color: #e9efeb; font-size: 10px; font-weight: bold; letter-spacing: 2px; text-align: right; }
        .invoice-number { margin-top: 7px; color: #c6f432; font-size: 12px; letter-spacing: .5px; }
        .lime-rule { height: 3px; background: #c6f432; }
        .parties { width: 100%; margin: 25px 0 26px; border-collapse: collapse; }
        .parties td { width: 50%; padding: 0 22px 0 0; vertical-align: top; }
        .parties td + td { padding-left: 24px; border-left: 1px solid #34423b; }
        .section-title { margin: 0 0 9px; color: #c6f432; font-size: 9px; font-weight: bold; letter-spacing: 1.5px; }
        .party-name { margin-bottom: 4px; color: #f5f8f6; font-size: 11px; font-weight: bold; }
        .party-details { color: #c4d0ca; font-size: 9px; line-height: 1.65; }
        .contact-icon { color: #c6f432; font-size: 11px; font-weight: bold; }
        .contact-row { margin-top: 6px; }
        .separator { height: 1px; background: #34423b; }
        .invoice-overview { width: 100%; margin: 23px 0 25px; border-collapse: collapse; }
        .invoice-overview td { width: 53%; vertical-align: top; }
        .invoice-overview td + td { width: 47%; padding-left: 24px; border-left: 1px solid #34423b; }
        h1 { margin: 0 0 5px; color: #f4f7f5; font-size: 28px; letter-spacing: -.5px; }
        .subtitle { color: #aebdb5; font-size: 12px; font-weight: bold; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { width: 50%; padding: 5px 0; color: #aab8b0; font-size: 9px; }
        .meta td + td { padding-left: 8px; border: 0; color: #f1f5f2; font-weight: bold; text-align: right; }
        .items { width: 100%; border: 1px solid #263630; border-radius: 7px; border-collapse: separate; border-spacing: 0; }
        .items th { padding: 12px 10px; border-bottom: 1px solid #34423b; color: #c6f432; font-size: 8px; font-weight: bold; letter-spacing: 1.3px; text-align: left; }
        .items th.num, .items td.num { text-align: right; }
        .items td { padding: 17px 10px; border-bottom: 1px solid #1e2b25; vertical-align: middle; }
        .item-name { color: #f4f7f5; font-size: 10px; font-weight: bold; }
        .item-detail { margin-top: 5px; color: #aebdb5; font-size: 8px; line-height: 1.5; }
        .item-symbol { display: inline-block; width: 31px; height: 31px; margin-right: 9px; border: 1px solid #3f5a21; border-radius: 7px; background: #14200e; color: #c6f432; font-size: 15px; font-weight: bold; line-height: 31px; text-align: center; vertical-align: middle; }
        .thanks-totals { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .thanks-totals > tbody > tr > td { width: 55%; padding: 0 8px; vertical-align: middle; }
        .thanks-totals > tbody > tr > td + td { width: 45%; padding-left: 20px; }
        .thanks { padding: 12px; border: 1px solid #263b30; border-radius: 8px; background: #09130f; }
        .thanks-title { margin-bottom: 5px; color: #c6f432; font-size: 8px; font-weight: bold; }
        .thanks-copy { color: #aebdb5; font-size: 8px; line-height: 1.5; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 5px 0; color: #dce4df; font-size: 9px; }
        .totals td + td { color: #f4f7f5; font-weight: bold; text-align: right; }
        .grand-total td { padding-top: 10px; border-top: 1px solid #728b39; color: #c6f432; font-size: 10px; font-weight: bold; }
        .grand-total td + td { font-size: 15px; }
        .motto { width: 100%; margin-top: 18px; padding: 12px 0; border-top: 1px solid #34423b; border-bottom: 1px solid #34423b; border-collapse: collapse; }
        .motto td { width: 33.33%; padding: 3px 12px; color: #c3d0c8; font-size: 8px; line-height: 1.5; vertical-align: middle; }
        .motto td + td { border-left: 1px solid #34423b; }
        .motto-mark { padding-right: 7px; color: #c6f432; font-size: 17px; font-weight: bold; }
        .footer { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .footer td { width: 65%; color: #9dada4; font-size: 8px; line-height: 1.7; }
        .footer td + td { width: 35%; color: #c6f432; font-size: 10px; font-weight: bold; letter-spacing: 1.5px; text-align: right; }
    </style>
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
