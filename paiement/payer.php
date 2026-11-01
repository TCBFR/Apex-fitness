<?php

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/PaiementController.php';
require_once __DIR__ . '/../public/includes/public_icons.php';

exige_role('ADHERENT');
$controller = new PaiementController();
$memberId = (int) utilisateur()['NumUsers'];
$contractKey = 'abonnement_contrat_accepte';
$contract = $_SESSION[$contractKey] ?? null;
$planId = filter_var($_GET['plan'] ?? null, FILTER_VALIDATE_INT);
$offres = $controller->offres();
$offreSelectionnee = null;
if (is_int($planId) && is_array($contract) && (int) ($contract['plan_id'] ?? 0) === $planId) {
    foreach ($offres as $offre) {
        if ((int) $offre['Numabo'] === $planId) {
            $offreSelectionnee = $offre;
            break;
        }
    }
}
$contractIsValid = is_array($contract)
    && (int) ($contract['user_id'] ?? 0) === $memberId
    && is_int($planId)
    && (int) ($contract['plan_id'] ?? 0) === $planId
    && (int) ($contract['accepted_at'] ?? 0) >= time() - 1800
    && (int) ($contract['accepted_at'] ?? 0) <= time()
    && $offreSelectionnee !== null;
if ($contractIsValid) {
    $currentContract = ContratAbonnement::contenu($offreSelectionnee, !empty($contract['change']));
    $contractIsValid = ($contract['version'] ?? null) === ContratAbonnement::VERSION
        && is_string($contract['content'] ?? null)
        && hash_equals($currentContract, $contract['content'])
        && is_string($contract['hash'] ?? null)
        && hash_equals(hash('sha256', $currentContract), $contract['hash']);
}
if (!$contractIsValid) {
    unset($_SESSION[$contractKey]);
    flash('erreur', 'Veuillez choisir une formule et accepter le règlement intérieur avant de poursuivre.');
    redirige(
        is_int($planId) && $offreSelectionnee !== null
            ? 'adherent/view/offres.php?step=information&plan=' . $planId
            : 'adherent/view/offres.php'
    );
}

$erreur = null;
$changement = !empty($contract['change']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $postedPlanId = filter_var($_POST['abonnement'] ?? null, FILTER_VALIDATE_INT);
    if ($postedPlanId !== $planId) {
        $erreur = 'La formule sélectionnée ne correspond pas au contrat accepté.';
    } else {
        try {
            $montant = $controller->payer(
                $memberId,
                $planId,
                (string) ($_POST['moyen'] ?? ''),
                $changement,
                (string) $contract['version'],
                (string) $contract['content'],
                (string) $contract['hash'],
                (int) $contract['accepted_at']
            );
            unset($_SESSION[$contractKey]);
            journalise('PAIEMENT_ABONNEMENT', 'NumUsers=' . $memberId);
            flash('succes', $changement
                ? 'Votre changement est bien enregistré. Votre ancien abonnement se termine fin de mois et le nouveau commencera au début du mois prochain.'
                : 'Paiement de ' . euros($montant) . ' enregistré. Votre abonnement est actif immédiatement.');
            redirige('adherent/view/abonnement.php');
        } catch (Throwable $exception) {
            $erreur = $exception->getMessage();
        }
    }
}

$prix = match ($offreSelectionnee['type_abo']) {
    'BASIC' => (float) $offreSelectionnee['prix_annuel'],
    'PLUS' => (float) $offreSelectionnee['prix_mensuel_plus'],
    default => (float) $offreSelectionnee['prix_mensuel_reduit'],
};
$periode = $offreSelectionnee['type_abo'] === 'BASIC' ? '/ an' : '/ mois';
$moyenSelectionne = is_string($_POST['moyen'] ?? null) ? $_POST['moyen'] : 'CB';
$titrePage = 'Paiement';
$activeNav = 'abonnement';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paiement – ATHLETIX</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/athletix_public.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/adherent/abonnement.css?v=20261101-4')) ?>">
    <style>
        .payment-main{min-width:0;min-height:100vh;width:calc(100% - 250px);margin-left:250px;padding:22px 36px 60px}
        .payment-main > .account-subscriptions{padding-top:18px}
        @media(max-width:860px){.payment-main{width:100%;margin-left:0;padding:18px 16px 48px}}
    </style>
</head>
<body class="member-subscription">
<div class="app-shell">
    <?php include __DIR__ . '/../adherent/includes/sidebar.php'; ?>
    <main class="payment-main">
        <?php include __DIR__ . '/../adherent/includes/topbar.php'; ?>
        <div class="account-subscriptions">
            <nav class="subscription-steps progress-step-3" aria-label="Étapes de gestion de l’abonnement">
                <div class="subscription-step is-complete"><span>1</span><strong>Choix de la formule</strong></div>
                <div class="subscription-step is-complete"><span>2</span><strong>Informations</strong></div>
                <div class="subscription-step is-active" aria-current="step"><span>3</span><strong>Paiement</strong></div>
                <div class="subscription-step"><span>4</span><strong>Confirmation</strong></div>
            </nav>
            <section class="account-information-hero">
                <p class="eyebrow">Étape 3 sur 4</p>
                <h1>Règlement de votre formule</h1>
                <p>Votre règlement intérieur et votre contrat ont été acceptés. Choisissez un moyen de paiement pour terminer.</p>
            </section>
            <section class="account-information-content">
                <?php if ($erreur !== null): ?><p class="account-contract-error" role="alert"><?= e($erreur) ?></p><?php endif; ?>
                <div class="account-information-layout">
                    <section class="account-information-card payment-form-card">
                        <h2><?= publicIcon('lock', 'icon') ?> Moyen de paiement</h2>
                        <form method="post" class="account-payment-form">
                            <?= champ_csrf() ?>
                            <input type="hidden" name="abonnement" value="<?= (int) $offreSelectionnee['Numabo'] ?>">
                            <label for="moyen">Sélectionnez un moyen de paiement</label>
                            <select id="moyen" name="moyen" required>
                                <?php foreach (['CB' => 'Carte bancaire', 'Virement' => 'Virement', 'Prelevement' => 'Prélèvement', 'Especes' => 'Espèces à l’accueil'] as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $moyenSelectionne === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="account-payment-note">Le paiement enregistré confirme l’activation de la formule selon les conditions acceptées.</p>
                            <div class="account-information-actions">
                                <a class="btn btn-outline" href="<?= e(url('adherent/view/offres.php?step=information&plan=' . $planId)) ?>">Retour aux informations</a>
                                <button class="btn btn-primary" type="submit">Confirmer le paiement <?= publicIcon('arrow-right', 'icon') ?></button>
                            </div>
                        </form>
                    </section>
                    <aside class="account-information-summary">
                        <h2><?= publicIcon('info', 'icon') ?> Récapitulatif</h2>
                        <p class="account-selected-plan-name"><?= e($offreSelectionnee['libelle_abo']) ?></p>
                        <p class="account-selected-plan-price"><?= euros($prix) ?> <?= e($periode) ?></p>
                        <p><?= e($offreSelectionnee['description_abo'] ?? '') ?></p>
                        <div class="account-summary-row">
                            <span><?= publicIcon('shield-check', 'icon') ?> Contrat</span>
                            <strong>Accepté · v<?= e((string) $contract['version']) ?></strong>
                        </div>
                    </aside>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
