<?php
require_once __DIR__ . '/../../config/model/OperationCrudModele.php';
require_once __DIR__ . '/../../config/model/CleCrudModele.php';

require_once __DIR__ . '/../../config/function.php';
class InteractionAdherent
{
    use OperationCrudModele;
    use CleCrudModele;

    protected const TABLE = 'interaction_adherent';

    protected int $numUtilisateur;
    protected string $typeInteraction;
    protected PDO $pdo;

    public function __construct(int $numUtilisateur, string $typeInteraction, ?PDO $pdo = null)
    {
        if (!in_array($typeInteraction, ['AVIS', 'FAQ'], true)) {
            throw new InvalidArgumentException('Type d’interaction invalide.');
        }
        $this->pdo = $pdo ?? db();
        $this->numUtilisateur = $numUtilisateur;
        $this->typeInteraction = $typeInteraction;
    }

    public function createInteraction(PDO $pdo): int
    {
        $requete = $pdo->prepare(
            'INSERT INTO interaction_adherent (NumUsers, type_interaction) VALUES (?, ?)'
        );
        $requete->execute([$this->numUtilisateur, $this->typeInteraction]);
        return (int) $pdo->lastInsertId();
    }

    public function updateInteraction(PDO $pdo, int $id): void
    {
        $requete = $pdo->prepare(
            'UPDATE interaction_adherent SET NumUsers = ?, type_interaction = ? WHERE NumInteraction = ?'
        );
        $requete->execute([$this->numUtilisateur, $this->typeInteraction, $id]);
    }

    public static function findInteractionById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare('SELECT * FROM interaction_adherent WHERE NumInteraction = ?');
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findInteractions(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT * FROM interaction_adherent ORDER BY date_interaction DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteInteraction(PDO $pdo, int $id): void
    {
        $pdo->prepare('DELETE FROM interaction_adherent WHERE NumInteraction = ?')->execute([$id]);
    }
}