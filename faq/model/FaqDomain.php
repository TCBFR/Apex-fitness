<?php

declare(strict_types=1);

require_once __DIR__ . '/../../interaction_adherent/model/interaction_adherent.php';

final class Faq extends InteractionAdherent
{
    protected const TABLE = 'faq';

    private string $question;
    private ?string $reponse;
    private string $statut;
    private string $categorie;

    public function __construct(
        int $numUtilisateur = 0,
        string $question = '',
        ?PDO $pdo = null,
        ?string $reponse = null,
        string $statut = 'En attente',
        string $categorie = 'General'
    ) {
        parent::__construct($numUtilisateur, 'FAQ', $pdo);
        $this->question = $question;
        $this->reponse = $reponse;
        $this->statut = $statut;
        $this->categorie = $categorie;
    }

    public function createFaq(PDO $pdo): int
    {
        if (trim($this->question) === '') {
            throw new InvalidArgumentException('La question est obligatoire.');
        }
        $pdo->beginTransaction();
        try {
            $id = parent::createInteraction($pdo);
            $requete = $pdo->prepare(
                'INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $requete->execute([$id, trim($this->question), $this->reponse, $this->statut, $this->categorie]);
            $pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public function updateFaq(PDO $pdo, int $id): void
    {
        $pdo->beginTransaction();
        try {
            parent::updateInteraction($pdo, $id);
            $requete = $pdo->prepare(
                'UPDATE faq SET question = ?, reponse = ?, statut_faq = ?, categorie = ?
                 WHERE NumInteraction = ?'
            );
            $requete->execute([$this->question, $this->reponse, $this->statut, $this->categorie, $id]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
    }

    public static function findFaqById(PDO $pdo, int $id): ?array
    {
        $requete = $pdo->prepare(
            'SELECT i.NumInteraction, i.date_interaction, i.type_interaction, i.NumUsers,
                    f.question, f.reponse, f.statut_faq, f.categorie
             FROM interaction_adherent i JOIN faq f ON f.NumInteraction = i.NumInteraction
             WHERE i.NumInteraction = ?'
        );
        $requete->execute([$id]);
        return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findFaqs(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT i.NumInteraction, i.date_interaction, i.type_interaction, i.NumUsers,
                    f.question, f.reponse, f.statut_faq, f.categorie
             FROM interaction_adherent i JOIN faq f ON f.NumInteraction = i.NumInteraction
             ORDER BY i.date_interaction DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteFaq(PDO $pdo, int $id): void
    {
        InteractionAdherent::deleteInteraction($pdo, $id);
    }

    public function publiees(): array
    {
        return $this->pdo->query(
            "SELECT f.*, i.date_interaction
             FROM faq f
             JOIN interaction_adherent i ON i.NumInteraction = f.NumInteraction
             WHERE f.statut_faq = 'Publiee'
             ORDER BY f.categorie, f.NumInteraction"
        )->fetchAll();
    }

    public function toutes(): array
    {
        return $this->pdo->query(
            'SELECT f.*, i.date_interaction, u.nomUsers, u.prenomUsers
             FROM faq f
             JOIN interaction_adherent i ON i.NumInteraction = f.NumInteraction
             LEFT JOIN utilisateur u ON u.NumUsers = i.NumUsers
             ORDER BY (f.statut_faq = \'En attente\') DESC, i.date_interaction DESC'
        )->fetchAll();
    }

    public function creer(int $numUtilisateur, string $question): int
    {
        $faq = new self($numUtilisateur, $question, $this->pdo);
        return $faq->createFaq($this->pdo);
    }

    public function repondre(int $numInteraction, string $reponse, string $categorie): void
    {
        $reponse = trim($reponse);
        $statut = $reponse === '' ? 'En attente' : 'Publiee';
        $requete = $this->pdo->prepare(
            'UPDATE faq SET reponse = ?, statut_faq = ?, categorie = ? WHERE NumInteraction = ?'
        );
        $requete->execute([$reponse === '' ? null : $reponse, $statut, $categorie ?: 'General', $numInteraction]);
    }

    public function supprimer(int $numInteraction): void
    {
        InteractionAdherent::deleteInteraction($this->pdo, $numInteraction);
    }
}
