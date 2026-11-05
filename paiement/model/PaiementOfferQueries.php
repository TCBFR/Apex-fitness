<?php

declare(strict_types=1);

trait PaiementOfferQueries
{
    public function offres(): array
    {
        return $this->pdo->query(
            'SELECT ab.Numabo, ab.libelle_abo, ab.description_abo,
                    o.type_offre AS type_abo, f.prix_mensuel_reduit,
                    f.taux_reduction, f.justificatif_requis, f.nb_reservations_max_mois,
                    b.prix_annuel, b.duree_engagement_mois, b.reconduction_tacite,
                    p.prix_mensuel_plus, p.seances_coach_incluses, p.acces_illimite
             FROM abonnement ab
             JOIN offre o ON o.Numoffre = ab.Numoffre
             LEFT JOIN freemium f ON f.Numoffre = ab.Numoffre
             LEFT JOIN basic b ON b.Numoffre = ab.Numoffre
             LEFT JOIN abonnement_plus p ON p.Numoffre = ab.Numoffre
             WHERE ab.actif_abo = 1 ORDER BY ab.Numabo'
        )->fetchAll();
    }

    private function offre(int $numAbonnement): ?array
    {
        $requete = $this->pdo->prepare(
            'SELECT ab.Numabo, ab.libelle_abo, ab.description_abo,
                    o.type_offre AS type_abo, f.prix_mensuel_reduit,
                    f.taux_reduction, f.justificatif_requis, f.nb_reservations_max_mois,
                    b.prix_annuel, b.duree_engagement_mois, b.reconduction_tacite,
                    p.prix_mensuel_plus, p.seances_coach_incluses, p.acces_illimite
             FROM abonnement ab
             JOIN offre o ON o.Numoffre = ab.Numoffre
             LEFT JOIN freemium f ON f.Numoffre = ab.Numoffre
             LEFT JOIN basic b ON b.Numoffre = ab.Numoffre
             LEFT JOIN abonnement_plus p ON p.Numoffre = ab.Numoffre
             WHERE ab.Numabo = ? AND ab.actif_abo = 1'
        );
        $requete->execute([$numAbonnement]);
        return $requete->fetch() ?: null;
    }

    private function montant(array $offre): float
    {
        return match ($offre['type_abo']) {
            'FREEMIUM' => (float) $offre['prix_mensuel_reduit'],
            'BASIC' => (float) $offre['prix_annuel'],
            'PLUS' => (float) $offre['prix_mensuel_plus'],
            default => throw new RuntimeException('Tarif de formule introuvable.'),
        };
    }
}
