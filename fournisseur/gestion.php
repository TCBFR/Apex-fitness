<?php

require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/GestionnaireFournisseur.php';
require_once __DIR__ . '/controller/FournisseurController.php';

exige_role('EMPLOYE');
$maintenance = est_technicien_maintenance();
if (!a_role('ADMIN')) {
  http_response_code(403);
  flash('erreur', 'La gestion des fournisseurs est réservée à l’administration.');
  redirige($maintenance
    ? 'employe/espace.php?onglet=maintenance'
    : 'employe/espace.php?onglet=tableau');
}
$controller = new GestionnaireFournisseur();
$fournisseurController = new FournisseurController();
$onglet = $_GET['onglet'] ?? 'fournisseurs';
if ($maintenance && $onglet !== 'fournisseurs') {
  redirige('fournisseur/gestion.php?onglet=fournisseurs');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'fournisseur_enregistrer') {
            $id = ($_POST['id'] ?? '') === '' ? null : (int) $_POST['id'];
            $fournisseurController->enregistrer($_POST, $id);
            flash('succes', 'Fournisseur enregistré.');
        } elseif ($action === 'fournisseur_supprimer') {
            $fournisseurController->supprimer((int) $_POST['id']);
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
    redirige('fournisseur/gestion.php?onglet=' . urlencode($onglet));
}

$fournisseurs = $fournisseurController->tous();
$utilisateursFournisseur = $fournisseurController->utilisateursDisponibles();
$personnel = $controller->personnel();
$fonctions = $controller->fonctions();
$fournisseurModifie = null;
if (isset($_GET['modifier'])) {
  $fournisseurModifie = $fournisseurController->trouver((int) $_GET['modifier']);
}
$titrePage = 'Accueil et personnel';
$pageActive = 'employe';
require __DIR__ . '/../layout/header.php';
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
    <?php require __DIR__ . '/view/crud.php'; ?>
  <?php else: ?>
    <?php require __DIR__ . '/view/personnel.php'; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
