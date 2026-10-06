<?php
require_once __DIR__ . '/../../statut/model/statut.php';

class EtatEquipement extends Statut
{
    protected const TABLE = 'etat_equipement';

    public function __construct(string $libelle)
    {
        parent::__construct($libelle, 'EQUIPEMENT');
    }

    public function createEquipmentStatus(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createStatut($pdo);
            $pdo->prepare('INSERT INTO etat_equipement (Numstatut) VALUES (?)')->execute([$id]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateEquipmentStatus(PDO $pdo, int $id): void
    {
        parent::updateStatut($pdo, $id);
    }

    public static function findEquipmentStatusById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            "SELECT s.* FROM statut s
             JOIN etat_equipement e ON e.Numstatut = s.Numstatut
             WHERE s.Numstatut = ? AND s.type_statut = 'EQUIPEMENT'"
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findEquipmentStatuses(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT s.* FROM statut s
             JOIN etat_equipement e ON e.Numstatut = s.Numstatut
             WHERE s.type_statut = 'EQUIPEMENT' ORDER BY s.libelle_statut"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteEquipmentStatus(PDO $pdo, int $id): void
    {
        Statut::deleteStatut($pdo, $id);
    }
}