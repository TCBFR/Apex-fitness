<?php

declare(strict_types=1);

trait AdherentEspacePayments
{
    public function paiements(int $id): array
    {
        $q = $this->pdo->prepare(
            'SELECT p.Numpaiement, p.date_paiement, p.montant, p.moyen_paiement,
                    a.libelle_abo FROM paiement p JOIN abonnement a ON a.Numabo=p.Numabo
             WHERE p.NumUsers=? ORDER BY p.date_paiement DESC'
        );
        $q->execute([$id]);
        return $q->fetchAll();
    }

    public function paiement(int $id, int $paymentId): ?array
    {
        // Les installations anciennes peuvent ne pas encore avoir les champs
        // d'adresse ajoutés par la migration d'inscription.
        $adherentColumns = $this->pdo->query('SHOW COLUMNS FROM adherent')->fetchAll(PDO::FETCH_COLUMN);
        $address = in_array('adresse_postale', $adherentColumns, true) ? 'ad.adresse_postale' : "''";
        $city = in_array('ville', $adherentColumns, true) ? 'ad.ville' : "''";
        $postalCode = in_array('code_postal', $adherentColumns, true) ? 'ad.code_postal' : "''";
        $q = $this->pdo->prepare(
            "SELECT p.Numpaiement, p.date_paiement,
                    CASE o.type_offre
                        WHEN 'FREEMIUM' THEN 20.00
                        WHEN 'BASIC' THEN COALESCE(b.prix_annuel, p.montant)
                        WHEN 'PLUS' THEN COALESCE(ap.prix_mensuel_plus, p.montant)
                        ELSE p.montant
                    END AS montant,
                    p.moyen_paiement, o.type_offre,
                    CASE WHEN o.type_offre = 'BASIC' THEN 'annuelle' ELSE 'mensuelle' END AS periodicite,
                    a.libelle_abo, a.description_abo, u.prenomUsers, u.nomUsers, u.mailUsers, u.telUsers AS telephone,
                    {$address} AS adresse_postale, {$city} AS ville, {$postalCode} AS code_postal
             FROM paiement p JOIN abonnement a ON a.Numabo=p.Numabo
             JOIN offre o ON o.Numoffre=a.Numoffre
             LEFT JOIN basic b ON b.Numoffre=o.Numoffre
             LEFT JOIN abonnement_plus ap ON ap.Numoffre=o.Numoffre
             JOIN utilisateur u ON u.NumUsers=p.NumUsers
             JOIN adherent ad ON ad.NumUsers=p.NumUsers
             WHERE p.NumUsers=? AND p.Numpaiement=?"
        );
        $q->execute([$id, $paymentId]);
        return $q->fetch() ?: null;
    }
}
