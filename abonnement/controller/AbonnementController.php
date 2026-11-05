<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/abonnement.php';

/** Fournit les offres actives et l’abonnement courant d’un adhérent. */
final class AbonnementController
{
    /**
     * Point d'entrée appelé par le routeur (public/index.php).
     * Les données retournées sont disponibles dans la vue via $viewData.
     */
    public function index(): array
    {
        // Même logique que l'ancien abonnement/index.php
        $aboCourant = a_role('ADHERENT')
            ? $this->get((int) utilisateur()['NumUsers'])
            : null;

        // Clés attendues par view/catalogue.php
        return [
            'titrePage' => 'Abonnements',
            'pageActive' => 'abonnements',
            'offres' => $this->getAll(),
            'aboCourant' => $aboCourant,
        ];
    }
    public const ACTIONS = ['index', 'catalogue'];

public function catalogue(): array
{
    return $this->index();
}
    public function getAll(): array
    {
        return db()->query(
            'SELECT ab.*, o.type_offre AS type_abo,
                    f.prix_mensuel_reduit,
                    f.taux_reduction,
                    f.justificatif_requis,
                    f.nb_reservations_max_mois,
                    b.prix_annuel,
                    b.duree_engagement_mois,
                    b.reconduction_tacite,
                    p.prix_mensuel_plus,
                    p.supplement_mensuel,
                    p.seances_coach_incluses,
                    p.acces_illimite
             FROM abonnement ab
             INNER JOIN offre o ON o.Numoffre = ab.Numoffre
             LEFT JOIN freemium f ON f.Numoffre = ab.Numoffre
             LEFT JOIN basic b ON b.Numoffre = ab.Numoffre
             LEFT JOIN abonnement_plus p ON p.Numoffre = ab.Numoffre
             WHERE ab.actif_abo = 1 ORDER BY ab.Numabo'
        )->fetchAll();
    }

    public function get(int $userId): ?int
    {
        $query = db()->prepare(
            "SELECT Numabo FROM souscrire
             WHERE NumUsers = ? AND statut_souscription = 'En cours'
               AND date_debut <= CURRENT_DATE
               AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)
             ORDER BY date_debut DESC LIMIT 1"
        );
        $query->execute([$userId]);
        return ($value = $query->fetchColumn()) === false ? null : (int) $value;
    }

    public function traiterCrud(): array
    {
        return (new ControleurGenerique(Abonnement::class))->traiter();
    }
}