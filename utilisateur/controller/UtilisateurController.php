<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/utilisateur.php';

/** Gère la connexion, l'inscription et le CRUD des comptes utilisateurs. */
final class UtilisateurController extends ControleurGenerique
{
    private const MOT_DE_PASSE_DEMO = 'apex2026';

    private const EMAILS_DEMO = [
        'admin@apex.fr', 'camille.roux@apex.fr', 'marc.anselme@apex.fr',
        's.nabil@mail.fr', 'awa.diallo@mail.fr', 'h.lemoine@mail.fr',
        'lina.moreau@apex.fr', 'noah.petit@apex.fr',
    ];

    public function __construct()
    {
        parent::__construct(Utilisateur::class);
    }

    /** CRUD des comptes utilisateurs (utilisateur/index.php). */
    public function index(): array
    {
        return [
            'crud'       => $this->traiter(),
            'titrePage'  => 'Utilisateur',
            'pageActive' => 'utilisateur',
        ];
    }

    public function connexion(): array
    {
        if (connecte()) {
            redirige(page_accueil_role());
        }

        $erreur = null;
        $mail = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifie_csrf();
            $mail = trim($_POST['mail'] ?? '');
            $erreur = $this->tenterConnexion($mail, $_POST['motdepasse'] ?? '', isset($_POST['se_souvenir']));
            if ($erreur === null) {
                flash('succes', 'Bonjour ' . utilisateur()['prenomUsers'] . ', vous êtes connecté.');
                redirige($this->urlRetour());
            }
        }

        return [
            'layout'      => 'nu',
            'titrePage'   => 'Connexion',
            'pageActive'  => 'utilisateur',
            'erreur'      => $erreur,
            'mail'        => $mail,
            'comptesDemo' => $this->comptesDemo(),
        ];
    }

    /** Affiche le formulaire d'inscription (le traitement du POST reste à brancher). */
    public function inscription(): array
    {
        return [
            'layout'     => 'nu',
            'titrePage'  => 'Inscription',
            'pageActive' => 'utilisateur',
            'donnees'    => [
                'nom' => '', 'prenom' => '', 'date_naissance' => '',
                'sexe' => '', 'mail' => '', 'adresse_postale' => '',
            ],
            'commune'    => ['id_commune' => '', 'nom_commune' => '', 'code_postal' => ''],
            'erreur'     => null,
        ];
    }

    public function deconnexion(): never
    {
        deconnexion(); // fonction globale de Authentification.php
        session_start();
        flash('info', 'Vous êtes déconnecté.');
        redirige('utilisateur/connexion.php');
    }

    private function tenterConnexion(string $mail, string $motDePasse, bool $seSouvenir): ?string
    {
        if ($mail === '' || $motDePasse === '') {
            return 'Renseignez votre e-mail et votre mot de passe.';
        }
        if (tente_connexion($mail, $motDePasse, $seSouvenir)) {
            return null;
        }
        journalise('CONNEXION_ECHEC', $mail);
        return 'E-mail ou mot de passe incorrect.';
    }

    private function urlRetour(): string
    {
        $retour = (string) ($_GET['retour'] ?? '');
        return $retour !== '' ? ltrim($retour, '/') : page_accueil_role();
    }

    /** Liste des comptes de démonstration : [e-mail, rôle, mot de passe]. */
    private function comptesDemo(): array
    {
        $pdo = db();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS compte_demo (
                NumUsers INT PRIMARY KEY,
                FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );

        $recherche = $pdo->prepare('SELECT NumUsers, passwordUsers FROM utilisateur WHERE mailUsers = ?');
        $ajout = $pdo->prepare('INSERT IGNORE INTO compte_demo (NumUsers) VALUES (?)');
        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM compte_demo');
        foreach (self::EMAILS_DEMO as $email) {
            $recherche->execute([$email]);
            $compte = $recherche->fetch();
            if ($compte && MotDePasse::verifier(self::MOT_DE_PASSE_DEMO, $compte['passwordUsers'])) {
                $ajout->execute([(int) $compte['NumUsers']]);
            }
        }
        $pdo->commit();

        $pdo->exec(
            'DELETE cd FROM compte_demo cd
             LEFT JOIN utilisateur u ON u.NumUsers = cd.NumUsers
             LEFT JOIN admin a ON a.NumUsers = u.NumUsers
             LEFT JOIN employe e ON e.NumUsers = u.NumUsers
             LEFT JOIN adherent d ON d.NumUsers = u.NumUsers
             WHERE u.NumUsers IS NULL
                OR (a.NumUsers IS NULL AND e.NumUsers IS NULL AND d.NumUsers IS NULL)'
        );

        $comptes = $pdo->query(
            'SELECT u.mailUsers,
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
        )->fetchAll();

        $resultat = [];
        foreach ($comptes as $compte) {
            $role = $this->libelleRole($compte);
            if ($role !== null) {
                $resultat[] = [$compte['mailUsers'], $role, self::MOT_DE_PASSE_DEMO];
            }
        }
        return $resultat;
    }

    private function libelleRole(array $compte): ?string
    {
        $roles = [];
        if ($compte['est_admin']) {
            $roles[] = 'administratrice';
        }
        if ($compte['est_employe'] && $compte['est_adherent']) {
            $roles[] = 'employé + adhérent';
        } elseif ($compte['est_employe']) {
            $roles[] = str_contains(strtolower((string) $compte['libellefonc']), 'maintenance')
                ? 'employé (maintenance)'
                : 'employé';
        } elseif ($compte['est_adherent']) {
            $roles[] = 'adhérent';
        }
        return $roles ? implode(' et ', $roles) : null;
    }
}