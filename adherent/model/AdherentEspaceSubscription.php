<?php

declare(strict_types=1);

trait AdherentEspaceSubscription
{
    public function abonnement(int $id): ?array
    {
        $q = $this->pdo->prepare(
            "SELECT s.date_debut, s.date_fin, s.statut_souscription,
                    a.libelle_abo, a.description_abo, o.type_offre,
                    CASE o.type_offre
                      WHEN 'FREEMIUM' THEN f.prix_mensuel_reduit
                      WHEN 'BASIC' THEN b.prix_annuel
                      WHEN 'PLUS' THEN p.prix_mensuel_plus
                    END AS prix,
                    CASE o.type_offre
                      WHEN 'FREEMIUM' THEN 'mensuel'
                      WHEN 'BASIC' THEN 'annuel'
                      WHEN 'PLUS' THEN 'mensuel'
                    END AS periodicite
             FROM souscrire s JOIN abonnement a ON a.Numabo = s.Numabo
             JOIN offre o ON o.Numoffre = a.Numoffre
             LEFT JOIN freemium f ON f.Numoffre = o.Numoffre
             LEFT JOIN basic b ON b.Numoffre = o.Numoffre
             LEFT JOIN abonnement_plus p ON p.Numoffre = o.Numoffre
             WHERE s.NumUsers = ? AND s.statut_souscription = 'En cours'
               AND s.date_debut <= CURRENT_DATE
               AND (s.date_fin IS NULL OR s.date_fin >= CURRENT_DATE)
             ORDER BY s.date_debut DESC LIMIT 1"
        );
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }
}
