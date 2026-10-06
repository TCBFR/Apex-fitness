<?php
require_once __DIR__ . '/../../offre/model/offre.php';

class Freemium extends Offre
{
    protected const TABLE = 'freemium';

    private float $prixMensuelReduit;
    private float $tauxReduction;
    private string $justificatifRequis;
    private int $reservationsMaxMois;

    public function __construct(
        string $libelle,
        ?string $description,
        float $prixMensuelReduit,
        float $tauxReduction,
        string $justificatifRequis,
        int $reservationsMaxMois
    ) {
        parent::__construct($libelle, $description, 'FREEMIUM');
        $this->prixMensuelReduit = $prixMensuelReduit;
        $this->tauxReduction = $tauxReduction;
        $this->justificatifRequis = $justificatifRequis;
        $this->reservationsMaxMois = $reservationsMaxMois;
    }

    public function createFreemiumOffer(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createOffer($pdo);
            $pdo->prepare(
                'INSERT INTO freemium
                 (Numoffre, prix_mensuel_reduit, taux_reduction, justificatif_requis, nb_reservations_max_mois)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$id, $this->prixMensuelReduit, $this->tauxReduction, $this->justificatifRequis, $this->reservationsMaxMois]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateFreemiumOffer(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateOffer($pdo, $id);
            $pdo->prepare(
                'UPDATE freemium SET prix_mensuel_reduit = ?, taux_reduction = ?,
                 justificatif_requis = ?, nb_reservations_max_mois = ? WHERE Numoffre = ?'
            )->execute([$this->prixMensuelReduit, $this->tauxReduction, $this->justificatifRequis, $this->reservationsMaxMois, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findFreemiumOfferById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT o.*, f.prix_mensuel_reduit, f.taux_reduction, f.justificatif_requis, f.nb_reservations_max_mois
             FROM offre o JOIN freemium f ON f.Numoffre = o.Numoffre WHERE o.Numoffre = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findFreemiumOffers(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT o.*, f.prix_mensuel_reduit, f.taux_reduction, f.justificatif_requis, f.nb_reservations_max_mois
             FROM offre o JOIN freemium f ON f.Numoffre = o.Numoffre ORDER BY o.libelle_offre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteFreemiumOffer(PDO $pdo, int $id): void
    {
        Offre::deleteOffer($pdo, $id);
    }
}