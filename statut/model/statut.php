<?php
require_once __DIR__ . '/../../config/model/OperationCrudModele.php';
require_once __DIR__ . '/../../config/model/CleCrudModele.php';

final class Statut
{
    use OperationCrudModele;
    use CleCrudModele;

    protected const TABLE = 'statut';

    protected string $libelle;
    protected string $type;

    public function __construct(string $libelle, string $type)
    {
        if (!in_array($type, ['EQUIPEMENT', 'MAINTENANCE'], true)) {
            throw new InvalidArgumentException('Type de statut invalide.');
        }
        $this->libelle = $libelle;
        $this->type = $type;
    }

    public function createStatut(PDO $pdo): int
    {
        $requete = $pdo->prepare('INSERT INTO statut (libelle_statut, type_statut) VALUES (?, ?)');
        $requete->execute([$this->libelle, $this->type]);
        return (int) $pdo->lastInsertId();
    }

    public function updateStatut(PDO $pdo, int $id): void
    {
        $requete = $pdo->prepare('UPDATE statut SET libelle_statut = ?, type_statut = ? WHERE Numstatut = ?');
        $requete->execute([$this->libelle, $this->type, $id]);
    }

    public static function findStatutById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare('SELECT * FROM statut WHERE Numstatut = ?');
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findStatuts(PDO $pdo): array
    {
        return $pdo->query('SELECT * FROM statut ORDER BY libelle_statut')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteStatut(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM statut WHERE Numstatut = ?')->execute([$id]);
    }
}