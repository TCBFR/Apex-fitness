<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/employe.php';

/** Relie les routes CRUD des employés à leur modèle métier et prépare l'espace employé. */
final class EmployeController extends ControleurGenerique
{
    /** Actions accessibles par URL (/employe/espace.php). */
    public const ACTIONS = ['index', 'espace'];

    public function __construct()
    {
        parent::__construct(Employe::class);
    }

    /**
     * Prépare les données de l'espace employé (ancien employe/espace.php).
     * Le rôle EMPLOYE est déjà exigé par le routeur (ROLES_REQUIS).
     */
    public function espace(): array
    {
        require_once __DIR__ . '/../../faq/controller/FaqController.php';
        require_once __DIR__ . '/../../message_contact/controller/MessageController.php';
        require_once __DIR__ . '/../../planning/controller/planning.php';
        require_once __DIR__ . '/../service/SchemaInventaireEquipement.php';
        require_once __DIR__ . '/../../utilisateur/model/utilisateur.php';

        require __DIR__ . '/EmployeRequest.php';
        require __DIR__ . '/EmployePostActions.php';
        require __DIR__ . '/EmployeData.php';
        if ($maintenance && $onglet === 'materiel') {
            require __DIR__ . '/data/EmployeEquipmentViewData.php';
        }
        if ($onglet === 'maintenance' || ($accueil && $onglet === 'suivi-tickets')) {
            require __DIR__ . '/data/EmployeTicketViewData.php';
        }

        // Onglet « creneaux » : mêmes données que le planning de l'administration
        if ($onglet === 'creneaux') {
            $controleurPlanning = new PlanningController();
            $planningDonneesAdmin = $controleurPlanning->donneesAdmin((string) ($_GET['semaine'] ?? ''));

            $vuePlanning = (string) ($_GET['vue'] ?? '');
            $planningVue = in_array($vuePlanning, ['ajouter', 'modifier'], true) ? 'formulaire' : 'grille';

            // Créneau à modifier : on le retrouve parmi ceux de la semaine affichée
            $creneauAModifier = null;
            if ($vuePlanning === 'modifier') {
                $idModifier = (int) ($_GET['modifier'] ?? 0);
                foreach ($planningDonneesAdmin['creneaux_semaine'] as $creneauSemaine) {
                    if ((int) $creneauSemaine['Numactivite'] === $idModifier) {
                        $creneauAModifier = $creneauSemaine;
                        break;
                    }
                }
            }
        }

        // Toutes les variables préparées par ces fichiers partent vers la vue.
        // 'layout' => 'page' : les vues espace-* ont déjà leur propre en-tête et pied de page.
        return get_defined_vars() + ['layout' => 'page'];
    }
}