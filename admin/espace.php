<?php
require_once __DIR__ . '/../avis/controller/AvisController.php';
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/../utilisateur/model/utilisateur.php';
exige_role('ADMIN');

$onglet = $_GET['onglet'] ?? 'utilisateurs';
if (!is_string($onglet) || !in_array($onglet, ['utilisateurs', 'maintenance', 'avis', 'statistiques', 'messages'], true)) {
    $onglet = 'utilisateurs';
}
$vueCompte = $_GET['compte'] ?? 'liste';
$vueMaintenance = $_GET['maintenance'] ?? 'liste';
$pdo    = db();
$fonctions = Utilisateur::toutesLesFonctions();
$fonctionMaintenance = null;
foreach ($fonctions as $fonction) {
    if (str_contains(strtolower($fonction['libellefonc']), 'maintenance')) {
        $fonctionMaintenance = $fonction;
        break;
    }
}
/* =====================================================================
   ACTIONS
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'creer_utilisateur') {
        try {
        $num = Utilisateur::creerCompte($_POST);
        journalise('CREATION_UTILISATEUR', 'NumUsers=' . $num);
        flash('succes', 'Compte créé.');
      } catch (Throwable $exception) {
        $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail est déjà utilisée.'
          : $exception->getMessage();
        flash('erreur', $message);
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }

    if ($action === 'creer_technicien') {
        try {
            if ($fonctionMaintenance === null) {
                throw new RuntimeException('Aucune fonction de maintenance n’est configurée.');
            }
            $donnees = $_POST;
            $donnees['roles'] = ['EMPLOYE'];
            $donnees['fonction'] = (int) $fonctionMaintenance['Numfonc'];
            $num = Utilisateur::creerCompte($donnees);
            journalise('CREATION_TECHNICIEN_MAINTENANCE', 'NumUsers=' . $num);
            flash('succes', 'Compte technicien de maintenance créé.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=maintenance');
    }

    if ($action === 'modifier_technicien') {
        $num = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $technicien = $pdo->prepare(
                'SELECT e.NumUsers FROM employe e
                 JOIN fonction f ON f.Numfonc = e.Numfonc
                 WHERE e.NumUsers = ? AND LOWER(f.libellefonc) LIKE ?'
            );
            $technicien->execute([$num, '%maintenance%']);
            if (!$technicien->fetchColumn()) {
                throw new RuntimeException('Ce compte n’est pas un technicien de maintenance.');
            }
            Utilisateur::modifierCompte($num, $_POST);
            journalise('MODIFICATION_TECHNICIEN_MAINTENANCE', 'NumUsers=' . $num);
            flash('succes', 'Compte technicien mis à jour.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=maintenance');
    }

    if ($action === 'ajouter_adherent') {
        $num = (int) $_POST['utilisateur'];
        try {
        Utilisateur::ajouterAdherent($num);
            journalise('AJOUT_ROLE_ADHERENT', 'NumUsers=' . $num);
            flash('succes', 'Rôle adhérent ajouté : le cumul employé + adhérent est autorisé.');
      } catch (Throwable $exception) {
        flash('erreur', 'Ajout impossible : ' . $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }

      if ($action === 'modifier_utilisateur') {
        try {
          Utilisateur::modifierCompte((int) $_POST['utilisateur'], $_POST);
          journalise('MODIFICATION_UTILISATEUR', 'NumUsers=' . (int) $_POST['utilisateur']);
          flash('succes', 'Compte mis à jour.');
        } catch (Throwable $exception) {
          flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=utilisateurs');
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
        redirige('admin/espace.php?onglet=avis');
    }

    if ($action === 'supprimer') {
        $num = (int) $_POST['utilisateur'];
      try {
        Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
            journalise('SUPPRESSION_UTILISATEUR', 'NumUsers=' . $num);
            flash('succes', 'Compte supprimé.');
      } catch (Throwable $exception) {
        flash('erreur', $exception->getMessage());
        }
        redirige(($_POST['retour'] ?? '') === 'maintenance'
            ? 'admin/espace.php?onglet=maintenance'
            : 'admin/espace.php?onglet=utilisateurs');
    }
}

/* =====================================================================
   DONNEES
   ===================================================================== */
$utilisateurs = Utilisateur::tousLesComptes();
$techniciensMaintenance = array_values(array_filter(
    $utilisateurs,
    static fn(array $user): bool => $user['est_employe']
        && str_contains(strtolower((string) ($user['libellefonc'] ?? '')), 'maintenance')
));
$compteModifie = null;
$compteConsulte = null;
$technicienSelectionne = null;
foreach ($utilisateurs as $user) {
  if ((int) $user['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
    if ($vueCompte === 'modifier') {
      $compteModifie = $user;
    } elseif ($vueCompte === 'voir') {
      $compteConsulte = $user;
    }
  }
  foreach ($techniciensMaintenance as $technicien) {
      if ((int) $technicien['NumUsers'] === (int) ($_GET['id'] ?? 0)) {
          $technicienSelectionne = $technicien;
          break;
      }
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
  "SELECT a.titre AS libelleact, COUNT(r.Numres) AS n
     FROM activite a
   LEFT JOIN reservation r ON r.Numactivite = a.Numactivite AND r.statut_reservation <> 'Annulee'
   GROUP BY a.Numactivite, a.titre ORDER BY n DESC")->fetchAll();

$parAbonnement = $pdo->query(
  "SELECT ab.libelle_abo, o.type_offre AS type_abo, COUNT(s.NumUsers) AS n
     FROM abonnement ab
   JOIN offre o ON o.Numoffre = ab.Numoffre
     LEFT JOIN souscrire s ON s.Numabo = ab.Numabo AND s.statut_souscription = 'En cours'
   GROUP BY ab.Numabo, ab.libelle_abo, o.type_offre ORDER BY n DESC")->fetchAll();

$recettes = $pdo->query(
    "SELECT DATE_FORMAT(date_paiement,'%Y-%m') AS mois, SUM(montant) AS total, COUNT(*) AS n
     FROM paiement GROUP BY mois ORDER BY mois DESC LIMIT 6")->fetchAll();

$parEtat = $pdo->query(
    "SELECT s.libelle_statut, COUNT(e.Numequip) AS n
     FROM statut s LEFT JOIN equipement e ON e.Numstatut = s.Numstatut
     WHERE s.type_statut = 'EQUIPEMENT' GROUP BY s.Numstatut")->fetchAll();

$messages = $pdo->query('SELECT * FROM message_contact ORDER BY date_envoi DESC LIMIT 30')->fetchAll();
$avisAdmin = (new AvisController())->index();

$maxRes = max(1, ...array_map(fn($r) => (int) $r['n'], $parActivite ?: [['n' => 1]]));

$flashMessages = flashs();
$adminUser = utilisateur();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Administration - <?= e(APP_NOM) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('assets/css/pages/admin.css')) ?>">
</head>
<body class="admin-page">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a class="admin-brand" href="<?= e(url('admin/espace.php')) ?>">
      <span class="admin-brand-mark">A</span>
      <span><strong>Apex Fitness</strong><small>Administration</small></span>
    </a>
    <nav class="admin-navigation" aria-label="Menu administration">
    <?php foreach ([
        'utilisateurs' => 'Utilisateurs et rôles',
        'maintenance'  => 'Techniciens de maintenance',
        'avis'         => 'Avis clients',
        'statistiques' => 'Statistiques',
        'messages'     => 'Messages reçus',
    ] as $cle => $lib): ?>
      <a href="<?= e(url('admin/espace.php?onglet=' . $cle)) ?>" <?= $onglet === $cle ? 'aria-current="page"' : '' ?>><?= e($lib) ?></a>
    <?php endforeach; ?>
    </nav>
    <a class="admin-back" href="<?= e(url('accueil/index.php')) ?>">Retour au site</a>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div>
        <p class="admin-eyebrow">ESPACE SÉCURISÉ</p>
        <h1>Administration</h1>
      </div>
      <div class="admin-account">
        <span><?= e(($adminUser['prenomUsers'] ?? '') . ' ' . ($adminUser['nomUsers'] ?? '')) ?></span>
        <a href="<?= e(url('utilisateur/deconnexion.php')) ?>">Déconnexion</a>
      </div>
    </div>

    <?php if ($flashMessages): ?>
      <div class="admin-flashes">
        <?php foreach ($flashMessages as $message): ?>
          <p class="flash flash-<?= e($message['type']) ?>"><?= e($message['message']) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <main class="admin-content">
      <div class="admin-heading">
        <div>
          <p class="admin-eyebrow">TABLEAU DE BORD</p>
          <h2><?= e([
              'utilisateurs' => 'Utilisateurs et rôles',
              'maintenance' => 'Techniciens de maintenance',
              'avis' => 'Avis clients',
              'statistiques' => 'Statistiques',
              'messages' => 'Messages reçus',
          ][$onglet] ?? 'Administration') ?></h2>
        </div>
      </div>
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
  <?php if ($vueCompte === 'creer'): ?>
    <?php require __DIR__ . '/view/creer-compte.php'; ?>
  <?php elseif ($vueCompte === 'modifier' && $compteModifie): ?>
    <?php require __DIR__ . '/view/modifier-compte.php'; ?>
  <?php elseif ($vueCompte === 'voir' && $compteConsulte): ?>
    <?php require __DIR__ . '/view/voir-compte.php'; ?>
  <?php else: ?>
    <?php require __DIR__ . '/view/comptes.php'; ?>
  <?php endif; ?>

<?php elseif ($onglet === 'maintenance'): ?>
  <?php if ($vueMaintenance === 'creer'): ?>
    <?php require __DIR__ . '/view/creer-technicien.php'; ?>
  <?php elseif ($vueMaintenance === 'modifier' && $technicienSelectionne): ?>
    <?php require __DIR__ . '/view/modifier-technicien.php'; ?>
  <?php elseif ($vueMaintenance === 'voir' && $technicienSelectionne): ?>
    <?php require __DIR__ . '/view/voir-technicien.php'; ?>
  <?php else: ?>
    <?php require __DIR__ . '/view/techniciens-maintenance.php'; ?>
  <?php endif; ?>

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
  <?php require __DIR__ . '/../message_contact/view/admin-contact.php'; ?>
<?php endif; ?>
    </main>
  </div>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
