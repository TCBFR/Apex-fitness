<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/GestionController.php';
require_once __DIR__ . '/controller/crud/fournisseurs/FournisseurCrudController.php';

exige_role('EMPLOYE');
$maintenance = est_technicien_maintenance();
if (!$maintenance && !a_role('ADMIN')) {
  http_response_code(403);
  flash('erreur', 'Les fournisseurs sont réservés au technicien maintenance.');
  redirige('employe.php?onglet=tableau');
}
$controller = new GestionController();
$fournisseurCrud = new FournisseurCrudController();
$onglet = $_GET['onglet'] ?? 'fournisseurs';
if ($maintenance && $onglet !== 'fournisseurs') {
  redirige('gestion.php?onglet=fournisseurs');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'fournisseur_enregistrer') {
            $id = ($_POST['id'] ?? '') === '' ? null : (int) $_POST['id'];
          if ($id === null) $fournisseurCrud->create($_POST);
          else $fournisseurCrud->update($_POST, $id);
            flash('succes', 'Fournisseur enregistré.');
        } elseif ($action === 'fournisseur_supprimer') {
          $fournisseurCrud->delete((int) $_POST['id']);
            flash('succes', 'Fournisseur supprimé.');
        } elseif ($action === 'fonction_modifier') {
            $controller->modifierFonction((int) $_POST['utilisateur'], (int) $_POST['fonction']);
            flash('succes', 'Fonction de l’employé mise à jour.');
        } elseif ($action === 'employe_supprimer') {
            $controller->supprimerEmploye((int) $_POST['utilisateur']);
            flash('succes', 'Employé retiré du personnel.');
        }
    } catch (Throwable $exception) {
        flash('erreur', $exception->getMessage());
    }
    redirige('gestion.php?onglet=' . urlencode($onglet));
}

$fournisseurs = $fournisseurCrud->getAll();
$personnel = $controller->personnel();
$fonctions = $controller->fonctions();
$fournisseurModifie = null;
if (isset($_GET['modifier'])) {
  foreach ($fournisseurs as $fournisseur) {
    if ((int) $fournisseur['Numfourn'] === (int) $_GET['modifier']) {
      $fournisseurModifie = $fournisseur;
      break;
    }
  }
}
$titrePage = 'Accueil et personnel';
$pageActive = 'employe';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap page-head">
  <h1>Accueil et personnel</h1>
  <p class="lead">Gérez les fournisseurs, l’accueil et les coachs depuis un seul écran.</p>
</div>

<div class="wrap section">
  <nav class="onglets">
    <a href="?onglet=fournisseurs" <?= $onglet === 'fournisseurs' ? 'aria-current="page"' : '' ?>>Fournisseurs</a>
    <?php if (!$maintenance): ?><a href="?onglet=personnel" <?= $onglet === 'personnel' ? 'aria-current="page"' : '' ?>>Accueil et coachs</a><?php endif; ?>
  </nav>

  <?php if ($onglet === 'fournisseurs'): ?>
    <?php require __DIR__ . '/views/crud/fournisseurs/index.php'; ?>
  <?php else: ?>
    <div class="table-scroll">
      <table class="tableau">
        <thead><tr><th>Nom</th><th>E-mail</th><th>Fonction</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($personnel as $employe): ?>
          <tr>
            <td><?= e($employe['prenomUsers'] . ' ' . $employe['nomUsers']) ?><br><span class="mono"><?= e($employe['matriculeemp']) ?></span></td>
            <td><?= e($employe['mailUsers']) ?></td>
            <td>
              <form method="post" style="display:flex;gap:8px;margin:0">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="fonction_modifier">
                <input type="hidden" name="utilisateur" value="<?= (int) $employe['NumUsers'] ?>">
                <select name="fonction">
                  <?php foreach ($fonctions as $fonction): ?>
                    <option value="<?= (int) $fonction['Numfonc'] ?>" <?= (int) $fonction['Numfonc'] === (int) $employe['Numfonc'] ? 'selected' : '' ?>><?= e($fonction['libellefonc']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-ghost" type="submit">Modifier</button>
              </form>
            </td>
            <td>
              <form method="post" style="margin:0">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="employe_supprimer">
                <input type="hidden" name="utilisateur" value="<?= (int) $employe['NumUsers'] ?>">
                <button class="btn btn-sm btn-danger" type="submit" data-confirme="Retirer cet employé du personnel ?">Retirer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="note">Les fonctions disponibles sont celles du modèle: coach sportif, accueil, maintenance et responsable.</p>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
