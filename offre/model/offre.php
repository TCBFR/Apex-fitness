<?php
require_once __DIR__ . '/../../config/model/OperationCrudModele.php';
require_once __DIR__ . '/../../config/model/CleCrudModele.php';

final class Offre
{
    use OperationCrudModele;
    use CleCrudModele;

    protected const TABLE = 'offre';

    protected string $libelle;
    protected ?string $description;
    protected string $type;

    public function __construct(string $libelle, ?string $description, string $type)
    {
        if (!in_array($type, ['FREEMIUM', 'BASIC', 'PLUS'], true)) {
            throw new InvalidArgumentException('Type d’offre invalide.');
        }
        $this->libelle = $libelle;
        $this->description = $description;
        $this->type = $type;
    }

    public function createOffer(PDO $pdo): int
    {
        $requete = $pdo->prepare(
            'INSERT INTO offre (libelle_offre, description_offre, type_offre) VALUES (?, ?, ?)'
        );
        $requete->execute([$this->libelle, $this->description, $this->type]);
        return (int) $pdo->lastInsertId();
    }

    public function updateOffer(PDO $pdo, int $id): void
    {
        $requete = $pdo->prepare(
            'UPDATE offre SET libelle_offre = ?, description_offre = ?, type_offre = ? WHERE Numoffre = ?'
        );
        $requete->execute([$this->libelle, $this->description, $this->type, $id]);
    }

    public static function findOfferById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare('SELECT * FROM offre WHERE Numoffre = ?');
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findOffers(PDO $pdo): array
    {
        return $pdo->query('SELECT * FROM offre ORDER BY libelle_offre')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteOffer(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM offre WHERE Numoffre = ?')->execute([$id]);
    }
}