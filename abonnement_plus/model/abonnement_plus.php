<?php
require_once __DIR__ . '/../../offre/model/offre.php';

class AbonnementPlus extends Offre
{
    protected const TABLE = 'abonnement_plus';

    private float $prixMensuel;
    private float $supplementMensuel;
    private int $seancesCoachIncluses;
    private bool $accesIllimite;

    public function __construct(
        string $libelle,
        ?string $description,
        float $prixMensuel,
        float $supplementMensuel,
        int $seancesCoachIncluses,
        bool $accesIllimite = true
    ) {
        parent::__construct($libelle, $description, 'PLUS');
        $this->prixMensuel = $prixMensuel;
        $this->supplementMensuel = $supplementMensuel;
        $this->seancesCoachIncluses = $seancesCoachIncluses;
        $this->accesIllimite = $accesIllimite;
    }

    public function createPlusOffer(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createOffer($pdo);
            $pdo->prepare(
                'INSERT INTO abonnement_plus
                 (Numoffre, prix_mensuel_plus, supplement_mensuel, seances_coach_incluses, acces_illimite)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$id, $this->prixMensuel, $this->supplementMensuel, $this->seancesCoachIncluses, (int) $this->accesIllimite]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updatePlusOffer(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateOffer($pdo, $id);
            $pdo->prepare(
                'UPDATE abonnement_plus SET prix_mensuel_plus = ?, supplement_mensuel = ?,
                 seances_coach_incluses = ?, acces_illimite = ? WHERE Numoffre = ?'
            )->execute([$this->prixMensuel, $this->supplementMensuel, $this->seancesCoachIncluses, (int) $this->accesIllimite, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findPlusOfferById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT o.*, p.prix_mensuel_plus, p.supplement_mensuel, p.seances_coach_incluses, p.acces_illimite
             FROM offre o JOIN abonnement_plus p ON p.Numoffre = o.Numoffre WHERE o.Numoffre = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findPlusOffers(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT o.*, p.prix_mensuel_plus, p.supplement_mensuel, p.seances_coach_incluses, p.acces_illimite
             FROM offre o JOIN abonnement_plus p ON p.Numoffre = o.Numoffre ORDER BY o.libelle_offre'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deletePlusOffer(PDO $pdo, int $id): void
    {
        Offre::deleteOffer($pdo, $id);
    }
}