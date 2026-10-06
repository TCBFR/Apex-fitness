<?php
require_once __DIR__ . '/../../statut/model/statut.php';

class StatutMaintenance extends Statut
{
    protected const TABLE = 'statut_maintenance';

    public function __construct(string $libelle)
    {
        parent::__construct($libelle, 'MAINTENANCE');
    }

    public function createMaintenanceStatus(PDO $pdo): int
    {
        $pdo->beginTransaction();
        try {
            $id = parent::createStatut($pdo);
            $pdo->prepare('INSERT INTO statut_maintenance (Numstatut) VALUES (?)')->execute([$id]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateMaintenanceStatus(PDO $pdo, int $id): void
    {
        parent::updateStatut($pdo, $id);
    }

    public static function findMaintenanceStatusById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            "SELECT s.* FROM statut s
             JOIN statut_maintenance m ON m.Numstatut = s.Numstatut
             WHERE s.Numstatut = ? AND s.type_statut = 'MAINTENANCE'"
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findMaintenanceStatuses(PDO $pdo): array
    {
        return $pdo->query(
            "SELECT s.* FROM statut s
             JOIN statut_maintenance m ON m.Numstatut = s.Numstatut
             WHERE s.type_statut = 'MAINTENANCE' ORDER BY s.libelle_statut"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteMaintenanceStatus(PDO $pdo, int $id): void
    {
        Statut::deleteStatut($pdo, $id);
    }
}