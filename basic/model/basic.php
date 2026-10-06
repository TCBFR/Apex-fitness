<?php
require_once __DIR__ . '/../../offre/model/offre.php';

class Basic extends Offre
{
    protected const TABLE = 'basic';

    private float $prixAnnuel;
    private int $dureeEngagementMois;
    private bool $reconductionTacite;

    public function __construct(
        string $libelle,
        ?string $description,
        float $prixAnnuel,
        int $dureeEngagementMois = 12,
        bool $reconductionTacite = true
    ) {
        parent::__construct($libelle, $description, 'BASIC');
        $this->prixAnnuel = $prixAnnuel;
        $this->dureeEngagementMois = $dureeEngagementMois;
        $this->reconductionTacite = $reconductionTacite;
    }

    public function createBasicOffer(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createOffer($pdo);
            $pdo->prepare(
                'INSERT INTO basic (Numoffre, prix_annuel, duree_engagement_mois, reconduction_tacite)
                 VALUES (?, ?, ?, ?)'
            )->execute([$id, $this->prixAnnuel, $this->dureeEngagementMois, (int) $this->reconductionTacite]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateBasicOffer(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateOffer($pdo, $id);
            $pdo->prepare(
                'UPDATE basic SET prix_annuel = ?, duree_engagement_mois = ?, reconduction_tacite = ?
                 WHERE Numoffre = ?'
            )->execute([$this->prixAnnuel, $this->dureeEngagementMois, (int) $this->reconductionTacite, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findBasicOfferById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT o.*, b.prix_annuel, b.duree_engagement_mois, b.reconduction_tacite
             FROM offre o JOIN basic b ON b.Numoffre = o.Numoffre WHERE o.Numoffre = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findBasicOffers(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT o.*, b.prix_annuel, b.duree_engagement_mois, b.reconduction_tacite
             FROM offre o JOIN basic b ON b.Numoffre = o.Numoffre ORDER BY o.libelle_offre'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteBasicOffer(PDO $pdo, int $id): void
    {
        Offre::deleteOffer($pdo, $id);
    }
}