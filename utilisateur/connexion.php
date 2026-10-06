<?php
require_once __DIR__ . '/../config/function.php';
require_once __DIR__ . '/controller/UtilisateurController.php';
if (connecte()) redirige(page_accueil_role());
$controller = new UtilisateurController();
$erreur = null;
$mail = '';
$pdo = db();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS compte_demo (
        NumUsers INT PRIMARY KEY,
        FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

// Migration des comptes de démonstration fournis avec les versions précédentes.
$anciensEmailsDemo = [
    'admin@apex.fr',
    'camille.roux@apex.fr',
    'marc.anselme@apex.fr',
    's.nabil@mail.fr',
    'awa.diallo@mail.fr',
    'h.lemoine@mail.fr',
    'lina.moreau@apex.fr',
    'noah.petit@apex.fr',
];
$ancienCompteQuery = $pdo->prepare(
    'SELECT NumUsers, passwordUsers FROM utilisateur WHERE mailUsers = ?'
);
$marquerDemo = $pdo->prepare('INSERT IGNORE INTO compte_demo (NumUsers) VALUES (?)');
foreach ($anciensEmailsDemo as $ancienEmailDemo) {
    $ancienCompteQuery->execute([$ancienEmailDemo]);
    $ancienCompte = $ancienCompteQuery->fetch();
    if ($ancienCompte && password_verify('apex2026', $ancienCompte['passwordUsers'])) {
        $marquerDemo->execute([(int) $ancienCompte['NumUsers']]);
    }
}

$comptesDemo = [];
$requeteDemo = $pdo->query(
    'SELECT u.mailUsers, u.passwordUsers,
            (a.NumUsers IS NOT NULL) AS est_admin,
            (e.NumUsers IS NOT NULL) AS est_employe,
            (d.NumUsers IS NOT NULL) AS est_adherent,
            f.libellefonc
     FROM utilisateur u
     JOIN compte_demo cd ON cd.NumUsers = u.NumUsers
     LEFT JOIN admin a ON a.NumUsers = u.NumUsers
     LEFT JOIN employe e ON e.NumUsers = u.NumUsers
     LEFT JOIN fonction f ON f.Numfonc = e.Numfonc
     LEFT JOIN adherent d ON d.NumUsers = u.NumUsers
     ORDER BY u.nomUsers, u.prenomUsers'
);
foreach ($requeteDemo->fetchAll() as $compteDemo) {
    $rolesDemo = [];
    if ($compteDemo['est_admin']) {
        $rolesDemo[] = 'administratrice';
    }
    if ($compteDemo['est_employe'] && $compteDemo['est_adherent']) {
        $rolesDemo[] = 'employé + adhérent';
    } elseif ($compteDemo['est_employe']) {
        $rolesDemo[] = str_contains(strtolower((string) $compteDemo['libellefonc']), 'maintenance')
            ? 'employé (maintenance)'
            : 'employé';
    } elseif ($compteDemo['est_adherent']) {
        $rolesDemo[] = 'adhérent';
    }
    if ($rolesDemo) {
        $comptesDemo[] = [
            $compteDemo['mailUsers'],
            implode(' et ', $rolesDemo),
            password_verify('apex2026', $compteDemo['passwordUsers']) ? 'apex2026' : '',
        ];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $mail = trim($_POST['mail'] ?? '');
    $erreur = $controller->set($mail, $_POST['motdepasse'] ?? '');
    if ($erreur === null) { flash('succes', 'Bonjour ' . utilisateur()['prenomUsers'] . ', vous êtes connecté.'); redirige($controller->get()); }
}
$titrePage = 'Connexion';
$pageActive = 'login';
require __DIR__ . '/view/login.php';
