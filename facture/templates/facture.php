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
<?php require __DIR__ . '/facture-document.php'; ?>
