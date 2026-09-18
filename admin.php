<?php
require_once __DIR__ . '/controller/AvisController.php';
require_once __DIR__ . '/controller/crud/comptes/CompteCrudController.php';
require_once __DIR__ . '/includes/auth.php';
exige_role('ADMIN');

$onglet = $_GET['onglet'] ?? 'utilisateurs';
$pdo    = db();
$compteCrud = new CompteCrudController();

/* =====================================================================
   ACTIONS
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'creer_utilisateur') {
        try {
        $num = $compteCrud->create($_POST);
        journalise('CREATION_UTILISATEUR', 'NumUsers=' . $num);
        flash('succes', 'Compte créé.');
      } catch (Throwable $exception) {
        $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail est déjà utilisée.'
          : $exception->getMessage();
        flash('erreur', $message);
        }
        redirige('admin.php?onglet=utilisateurs');
    }

    if ($action === 'ajouter_adherent') {
        $num = (int) $_POST['utilisateur'];
        try {
        $compteCrud->addAdherent($num);
            journalise('AJOUT_ROLE_ADHERENT', 'NumUsers=' . $num);
            flash('succes', 'Rôle adhérent ajouté : le cumul employé + adhérent est autorisé.');
      } catch (Throwable $exception) {
        flash('erreur', 'Ajout impossible : ' . $exception->getMessage());
        }
        redirige('admin.php?onglet=utilisateurs');
    }

      if ($action === 'modifier_utilisateur') {
        try {
          $compteCrud->update((int) $_POST['utilisateur'], $_POST);
          journalise('MODIFICATION_UTILISATEUR', 'NumUsers=' . (int) $_POST['utilisateur']);
          flash('succes', 'Compte mis à jour.');
        } catch (Throwable $exception) {
          flash('erreur', $exception->getMessage());
        }
        redirige('admin.php?onglet=utilisateurs');
      }

    /* ---- repondre a un avis ----------------------------------------- */
    if ($action === 'repondre_avis') {
        $num = (int) ($_POST['num_interaction'] ?? 0);
        $reponse = trim((string) ($_POST['reponse'] ?? ''));

        try {
            $avisController = new AvisController();
            $avisController->repondre($num, $reponse);
            journalise('REPONSE_AVIS', 'NumInteraction=' . $num);
            flash('succes', 'Réponse enregistrée pour cet avis.');
        } catch (Throwable $ex) {
            flash('erreur', $ex->getMessage());
        }
        redirige('admin.php?onglet=avis');
    }

    if ($action === 'supprimer') {
        $num = (int) $_POST['utilisateur'];
      try {
        $compteCrud->delete($num, (int) utilisateur()['NumUsers']);
            journalise('SUPPRESSION_UTILISATEUR', 'NumUsers=' . $num);
            flash('succes', 'Compte supprimé.');
      } catch (Throwable $exception) {
        flash('erreur', $exception->getMessage());
        }
        redirige('admin.php?onglet=utilisateurs');
    }
}

/* =====================================================================
   DONNEES
   ===================================================================== */
$utilisateurs = $compteCrud->getAll();
$fonctions = $compteCrud->getFonctions();
$compteModifie = null;
foreach ($utilisateurs as $user) {
  if ((int) $user['NumUsers'] === (int) ($_GET['modifier'] ?? 0)) {
    $compteModifie = $user;
    break;
  }
}

$statsRoles = [
    'total'     => count($utilisateurs),
    'admins'    => count(array_filter($utilisateurs, fn($u) => $u['est_admin'])),
    'employes'  => count(array_filter($utilisateurs, fn($u) => $u['est_employe'])),
    'adherents' => count(array_filter($utilisateurs, fn($u) => $u['est_adherent'])),
    'cumuls'    => count(array_filter($utilisateurs, fn($u) => $u['est_employe'] && $u['est_adherent'])),
];

$parActivite = $pdo->query(
    "SELECT a.libelleact, COUNT(r.Numres) AS n
     FROM activite a
     LEFT JOIN creneaux c ON c.Numact = a.Numact
     LEFT JOIN reservation r ON r.Numcreneaux = c.Numcreneaux AND r.statut_reservation <> 'Annulee'
     GROUP BY a.Numact ORDER BY n DESC")->fetchAll();

$parAbonnement = $pdo->query(
    "SELECT ab.libelle_abo, ab.type_abo, COUNT(s.NumUsers) AS n
     FROM abonnement ab
     LEFT JOIN souscrire s ON s.Numabo = ab.Numabo AND s.statut_souscription = 'En cours'
     GROUP BY ab.Numabo ORDER BY n DESC")->fetchAll();

$recettes = $pdo->query(
    "SELECT DATE_FORMAT(date_paiement,'%Y-%m') AS mois, SUM(montant) AS total, COUNT(*) AS n
     FROM paiement GROUP BY mois ORDER BY mois DESC LIMIT 6")->fetchAll();

$parEtat = $pdo->query(
    "SELECT s.libelle_statut, COUNT(e.Numequip) AS n
     FROM statut s LEFT JOIN equipement e ON e.Numstatut = s.Numstatut
     WHERE s.type_statut = 'EQUIPEMENT' GROUP BY s.Numstatut")->fetchAll();

$journal = $pdo->query(
    'SELECT j.*, u.nomUsers, u.prenomUsers FROM journal j
     LEFT JOIN utilisateur u ON u.NumUsers = j.NumUsers
     ORDER BY j.date_action DESC LIMIT 80')->fetchAll();

$messages = $pdo->query('SELECT * FROM message_contact ORDER BY date_envoi DESC LIMIT 30')->fetchAll();
$avisAdmin = (new AvisController())->index();

$maxRes = max(1, ...array_map(fn($r) => (int) $r['n'], $parActivite ?: [['n' => 1]]));

$titrePage  = 'Administration';
$pageActive = 'admin';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap page-head">
  <h1>Administration</h1>
  <p class="lead">Comptes, rôles, statistiques et journal d'activité.</p>
</div>

<div class="wrap section">
  <nav class="onglets">
    <?php foreach ([
        'utilisateurs' => 'Utilisateurs et rôles',
        'avis'         => 'Avis clients',
        'statistiques' => 'Statistiques',
        'messages'     => 'Messages reçus',
        'journal'      => "Journal d'activité",
    ] as $cle => $lib): ?>
      <a href="?onglet=<?= $cle ?>" <?= $onglet === $cle ? 'aria-current="page"' : '' ?>><?= $lib ?></a>
    <?php endforeach; ?>
  </nav>

<?php if ($onglet === 'avis'): ?>

  <?php if (!$avisAdmin): ?>
    <div class="vide">Aucun avis publié pour le moment.</div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="tableau">
        <thead><tr><th>Adhérent</th><th>Note</th><th>Avis</th><th>Réponse</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($avisAdmin as $avis): ?>
          <tr>
            <td>
              <?= e($avis['prenomUsers'] . ' ' . $avis['nomUsers']) ?><br>
              <span class="mono"><?= dateFr($avis['date_interaction']) ?></span>
            </td>
            <td class="mono"><?= (int) $avis['noteavis'] ?> / 5</td>
            <td><?= nl2br(e($avis['commentaire'])) ?></td>
            <td>
              <form method="post" style="margin:0">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="repondre_avis">
                <input type="hidden" name="num_interaction" value="<?= (int) $avis['NumInteraction'] ?>">
                <textarea name="reponse" rows="4" placeholder="Réponse de l’équipe..." required><?= e($avis['reponse'] ?? '') ?></textarea>
                <button class="btn btn-sm btn-vert" type="submit" style="margin-top:8px">Enregistrer</button>
              </form>
            </td>
            <td>
              <?php if (!empty($avis['reponse'])): ?><span class="tag tag-ok">Répondu</span><?php else: ?><span class="tag tag-mute">À répondre</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php elseif ($onglet === 'utilisateurs'): ?>
  <?php require __DIR__ . '/views/crud/comptes/index.php'; ?>

<?php elseif ($onglet === 'statistiques'): ?>

  <div class="barres">
    <h2 style="font-size:17px">Réservations par activité</h2>
    <?php foreach ($parActivite as $r):
        $pct = round((int) $r['n'] / $maxRes * 100); ?>
      <div class="barre">
        <span><?= e($r['libelleact']) ?></span>
        <span class="piste"><span class="part" style="width:<?= $pct ?>%"></span></span>
        <span class="n"><?= (int) $r['n'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="grille-3" style="margin-top:26px;align-items:start">
    <article class="carte">
      <h3>Adhérents par formule</h3>
      <table class="tableau" style="border:0">
        <tbody>
        <?php foreach ($parAbonnement as $a): ?>
          <tr><td><?= e($a['libelle_abo']) ?> <span class="mono">(<?= e($a['type_abo']) ?>)</span></td>
              <td style="text-align:right"><?= (int) $a['n'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </article>

    <article class="carte">
      <h3>Matériel par état</h3>
      <table class="tableau" style="border:0">
        <tbody>
        <?php foreach ($parEtat as $x): ?>
          <tr><td><?= e($x['libelle_statut']) ?></td><td style="text-align:right"><?= (int) $x['n'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </article>

    <article class="carte">
      <h3>Recettes par mois</h3>
      <table class="tableau" style="border:0">
        <tbody>
        <?php foreach ($recettes as $r): ?>
          <tr><td class="mono"><?= e($r['mois']) ?></td>
              <td style="text-align:right"><?= euros($r['total']) ?> <span class="mono">(<?= (int) $r['n'] ?>)</span></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </article>
  </div>

<?php elseif ($onglet === 'messages'): ?>
  <?php require __DIR__ . '/views/crud/messages/contact.php'; ?>

<?php else: ?>

  <div class="barre-outils">
    <input type="search" data-filtre="#t-journal" placeholder="Action, utilisateur ou cible" aria-label="Filtrer le journal">
  </div>
  <div class="table-scroll">
    <table class="tableau" id="t-journal">
      <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Cible</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($journal as $j): ?>
        <tr>
          <td class="mono"><?= dateFr($j['date_action'], true) ?></td>
          <td><?= $j['nomUsers'] ? e($j['prenomUsers'] . ' ' . $j['nomUsers']) : 'Anonyme' ?></td>
          <td><span class="tag tag-mute"><?= e($j['action']) ?></span></td>
          <td class="mono"><?= e($j['cible']) ?></td>
          <td class="mono"><?= e($j['ip']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p class="vide" data-vide hidden>Aucune entrée ne correspond.</p>
  </div>

<?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
