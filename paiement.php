<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/PaiementController.php';

exige_role('ADHERENT');
$controller = new PaiementController();
$moi = utilisateur()['NumUsers'];
$erreur = null;
$changement = !empty($_GET['switch']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    try {
        $montant = $controller->payer(
            $moi,
            (int) ($_POST['abonnement'] ?? 0),
            (string) ($_POST['moyen'] ?? ''),
            !empty($_POST['changement'])
        );
        journalise('PAIEMENT_ABONNEMENT', 'NumUsers=' . $moi);
        flash('succes', $changement
            ? 'Votre changement est bien enregistré. Votre ancien abonnement se termine fin de mois et le nouveau commencera au début du mois prochain.'
            : 'Paiement de ' . euros($montant) . ' enregistré. Votre abonnement est actif immédiatement.');
        redirige('adherent.php?onglet=abonnement');
    } catch (Throwable $exception) {
        $erreur = $exception->getMessage();
    }
}

$offres = $controller->offres();
$titrePage = 'Paiement';
$pageActive = 'adherent';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap page-head">
  <h1>Activer un abonnement</h1>
  <p class="lead">Le paiement renouvelle ou réactive immédiatement votre accès.</p>
</div>

<div class="wrap section">
  <?php if ($erreur): ?><p class="flash flash-erreur"><?= e($erreur) ?></p><?php endif; ?>
  <div class="form" style="max-width:620px">
    <form method="post">
      <?= champ_csrf() ?>
      <input type="hidden" name="changement" value="<?= $changement ? '1' : '0' ?>">
      <div class="form-ligne">
        <label for="abonnement">Formule</label>
        <select id="abonnement" name="abonnement" required>
          <?php foreach ($offres as $offre): ?>
            <?php $prix = $offre['type_abo'] === 'BASIC' ? $offre['prix_annuel'] : ($offre['type_abo'] === 'PLUS' ? $offre['prix_mensuel_plus'] : $offre['prix_mensuel_reduit']); ?>
            <option value="<?= (int) $offre['Numabo'] ?>"><?= e($offre['libelle_abo']) ?> - <?= euros($prix) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-ligne">
        <label for="moyen">Moyen de paiement</label>
        <select id="moyen" name="moyen" required>
          <option value="CB">Carte bancaire</option>
          <option value="Virement">Virement</option>
          <option value="Prelevement">Prélèvement</option>
          <option value="Especes">Espèces à l’accueil</option>
        </select>
      </div>
      <button class="btn btn-vert" type="submit">Payer et activer</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
