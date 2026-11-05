<?php

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/../../avis/controller/AvisController.php';
require_once __DIR__ . '/../../utilisateur/controller/UtilisateurController.php';
require_once __DIR__ . '/../../activite/model/ActiviteCatalogue.php';
require_once __DIR__ . '/../../employe/service/SchemaInventaireEquipement.php';
require_once __DIR__ . '/../../planning/controller/planning.php';
// À AJOUTER : le fichier qui définit PlanningController (voir la commande plus bas)

/** Espace administrateur (tableau de bord à onglets). */
final class AdminController
{
    public function __construct()
    {
        exige_role('ADMIN');
    }

    public function index(): array
    {
        return $this->tableauDeBord();
    }

    /** Page d'arrivée après la connexion (/admin/espace). */
    public function espace(): array
    {
        return $this->tableauDeBord();
    }

    /** Prépare toutes les données du tableau de bord pour la vue. */
    private function tableauDeBord(): array
    {
        $dossier = __DIR__;

        require $dossier . '/AdminRequest.php';      // définit $onglet, $vue...
        require $dossier . '/AdminSetup.php';        // tables, colonnes, $fonctions
        require $dossier . '/AdminPostActions.php';  // traite les POST (redirige)
        require $dossier . '/AdminData.php';         // données de chaque onglet
        require $dossier . '/AdminNavigation.php';   // $sidebarItems, $topbar...

        $vars = get_defined_vars();
        unset($vars['dossier']);

        return $vars + [
            'titrePage'  => 'Administration',
            'pageActive' => 'admin',
            'layout'     => 'autonome',
        ];
    }
}