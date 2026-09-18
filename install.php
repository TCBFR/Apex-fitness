<?php
/**
 * A executer UNE FOIS apres l'import de sql/apex_fitness.sql.
 *
 * Le fichier SQL ne peut pas contenir de mots de passe haches : password_hash()
 * genere un sel different a chaque appel. Ce script les pose donc en PHP,
 * puis se desactive lui-meme.
 *
 * SUPPRIMEZ CE FICHIER une fois l'installation terminee.
 */

require_once __DIR__ . '/config/config.php';

$motDePasseDemo = 'apex2026';
$rapport = [];

try {
    $pdo = db();

    // Verification du schema
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $attendues = ['utilisateur', 'adherent', 'employe', 'administrateur', 'creneaux', 'reservation', 'abonnement'];
    $manquantes = array_diff($attendues, $tables);

    if ($manquantes) {
        throw new RuntimeException(
            'Tables manquantes : ' . implode(', ', $manquantes) .
            '. Importez d\'abord sql/apex_fitness.sql.');
    }

    // Pose des mots de passe
    $hash = password_hash($motDePasseDemo, PASSWORD_DEFAULT);
    $st = $pdo->prepare('UPDATE utilisateur SET passwordUsers = ?');
    $st->execute([$hash]);
    $rapport[] = $st->rowCount() . ' comptes initialises avec le mot de passe de demonstration.';

    // Controles de coherence
    $cumul = (int) $pdo->query(
        'SELECT COUNT(*) FROM employe e JOIN adherent a ON a.NumUsers = e.NumUsers')->fetchColumn();
    $rapport[] = "$cumul compte(s) cumulant EMPLOYE + ADHERENT (heritage T).";

    $creneaux = (int) $pdo->query('SELECT COUNT(*) FROM creneaux')->fetchColumn();
    $reserv   = (int) $pdo->query('SELECT COUNT(*) FROM reservation')->fetchColumn();
    $rapport[] = "$creneaux creneaux et $reserv reservations de demonstration.";

    $ok = true;
} catch (Throwable $ex) {
    $ok = false;
    $erreur = $ex->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation &middot; Apex Fitness</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="wrap section" style="max-width:720px">
  <h1>Installation</h1>

  <?php if (!empty($ok)): ?>
    <p class="flash flash-succes">Base prête.</p>
    <ul>
      <?php foreach ($rapport as $l): ?><li><?= e($l) ?></li><?php endforeach; ?>
    </ul>

    <div class="carte" style="margin-top:20px">
      <h3>Comptes de démonstration</h3>
      <p class="muted">Mot de passe commun : <code><?= e($motDePasseDemo) ?></code></p>
      <table class="tableau">
        <thead><tr><th>E-mail</th><th>Rôles</th></tr></thead>
        <tbody>
          <tr><td>admin@apex.fr</td><td>Administratrice</td></tr>
          <tr><td>camille.roux@apex.fr</td><td>Employée + adhérente</td></tr>
          <tr><td>marc.anselme@apex.fr</td><td>Employé (maintenance)</td></tr>
          <tr><td>s.nabil@mail.fr</td><td>Adhérent actif</td></tr>
          <tr><td>awa.diallo@mail.fr</td><td>Adhérente expirée</td></tr>
          <tr><td>h.lemoine@mail.fr</td><td>Adhérent suspendu</td></tr>
        </tbody>
      </table>
    </div>

    <p class="note" style="margin-top:20px">
      Supprimez maintenant <code>install.php</code> : il réinitialise les mots de passe
      de tous les comptes à chaque exécution.
    </p>
    <p><a class="btn btn-vert" href="index.php">Ouvrir le site</a></p>

  <?php else: ?>
    <p class="flash flash-erreur"><?= e($erreur) ?></p>
    <p class="muted">Vérifiez les identifiants dans <code>config/config.php</code>,
       puis importez <code>sql/apex_fitness.sql</code> avant de recharger cette page.</p>
  <?php endif; ?>
</div>
</body>
</html>
