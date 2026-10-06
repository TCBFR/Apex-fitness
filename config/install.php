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

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/demo_inventory.php';

$motDePasseDemo = 'apex2026';
$rapport = [];

try {
    $pdo = db();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS compte_demo (
            NumUsers INT PRIMARY KEY,
            FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );

    // Verification du schema
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $attendues = [
        'utilisateur', 'adherent', 'employe', 'administrateur', 'creneaux', 'reservation', 'abonnement',
        'fournisseur', 'statut', 'etat_equipement', 'statut_maintenance', 'type_equipement', 'equipement',
    ];
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

    $comptesEmployesDemo = [
        [
            'nom' => 'Moreau',
            'prenom' => 'Lina',
            'email' => 'lina.moreau@apex.fr',
            'matriculeEmploye' => 'DEMO-EMP-7',
            'fonction' => 'Coach',
            'adherent' => true,
            'matriculeAdherent' => 'DEMO-ADH-7',
        ],
        [
            'nom' => 'Petit',
            'prenom' => 'Noah',
            'email' => 'noah.petit@apex.fr',
            'matriculeEmploye' => 'DEMO-EMP-8',
            'fonction' => 'Accueil',
            'adherent' => false,
            'matriculeAdherent' => null,
        ],
    ];
    $fonctionQuery = $pdo->prepare('SELECT Numfonc FROM fonction WHERE libellefonc = ?');
    $createFonction = $pdo->prepare('INSERT INTO fonction (libellefonc) VALUES (?)');
    $accountQuery = $pdo->prepare('SELECT NumUsers FROM utilisateur WHERE mailUsers = ?');
    $createAccount = $pdo->prepare(
        'INSERT INTO utilisateur (nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers)
         VALUES (?, ?, ?, NULL, ?)'
    );
    $employeeQuery = $pdo->prepare('SELECT NumUsers FROM employe WHERE NumUsers = ?');
    $createEmployee = $pdo->prepare(
        'INSERT INTO employe (NumUsers, matriculeemp, Date_embaucheemp, Numfonc)
         VALUES (?, ?, CURRENT_DATE, ?)'
    );
    $memberQuery = $pdo->prepare('SELECT NumUsers FROM adherent WHERE NumUsers = ?');
    $createMember = $pdo->prepare(
        'INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh)
         VALUES (?, ?, CURRENT_DATE)'
    );

    foreach ($comptesEmployesDemo as $compte) {
        $accountQuery->execute([$compte['email']]);
        $userId = $accountQuery->fetchColumn();
        if ($userId === false) {
            $createAccount->execute([$compte['nom'], $compte['prenom'], $compte['email'], $hash]);
            $userId = $pdo->lastInsertId();
        }
        $userId = (int) $userId;

        $fonctionQuery->execute([$compte['fonction']]);
        $functionId = $fonctionQuery->fetchColumn();
        if ($functionId === false) {
            $createFonction->execute([$compte['fonction']]);
            $functionId = $pdo->lastInsertId();
        }
        $employeeQuery->execute([$userId]);
        if ($employeeQuery->fetchColumn() === false) {
            $createEmployee->execute([$userId, $compte['matriculeEmploye'], (int) $functionId]);
        }

        if ($compte['adherent']) {
            $memberQuery->execute([$userId]);
            if ($memberQuery->fetchColumn() === false) {
                $createMember->execute([$userId, $compte['matriculeAdherent']]);
            }
        }
    }
    $marquerDemo = $pdo->prepare(
        'INSERT IGNORE INTO compte_demo (NumUsers)
         SELECT NumUsers FROM utilisateur WHERE mailUsers IN (?, ?)'
    );
    $marquerDemo->execute(['lina.moreau@apex.fr', 'noah.petit@apex.fr']);
    $rapport[] = 'Deux comptes de démonstration (employé seul et employée + adhérente) créés ou vérifiés.';

    $equipementsAjoutes = initialiseInventaireFitnessDemo($pdo);
    $rapport[] = "$equipementsAjoutes appareil(s) de fitness de démonstration ajouté(s) à l’inventaire.";

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
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
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
          <tr><td>camille.roux@apex.fr</td><td>Employée + adhérente (coach)</td></tr>
          <tr><td>marc.anselme@apex.fr</td><td>Employé seul (maintenance)</td></tr>
          <tr><td>lina.moreau@apex.fr</td><td>Employée + adhérente (coach)</td></tr>
          <tr><td>noah.petit@apex.fr</td><td>Employé seul (accueil)</td></tr>
          <tr><td>s.nabil@mail.fr</td><td>Adhérent actif</td></tr>
          <tr><td>awa.diallo@mail.fr</td><td>Adhérente expirée</td></tr>
          <tr><td>h.lemoine@mail.fr</td><td>Adhérent suspendu</td></tr>
        </tbody>
      </table>
    </div>

    <p class="note" style="margin-top:20px">
      Supprimez maintenant <code>config/install.php</code> : il réinitialise les mots de passe
      de tous les comptes à chaque exécution.
    </p>
    <p><a class="btn btn-vert" href="<?= e(url('accueil/index.php')) ?>">Ouvrir le site</a></p>

  <?php else: ?>
    <p class="flash flash-erreur"><?= e($erreur) ?></p>
    <p class="muted">Vérifiez les identifiants dans <code>config/config.php</code>,
       puis importez <code>sql/apex_fitness.sql</code> avant de recharger cette page.</p>
  <?php endif; ?>
</div>
</body>
</html>
