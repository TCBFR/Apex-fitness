<?php

declare(strict_types=1);

require_once __DIR__ . '/../../interaction_adherent/model/interaction_adherent.php';
require_once __DIR__ . '/FaqPersistence.php';
require_once __DIR__ . '/FaqContent.php';
require_once __DIR__ . '/FaqModeration.php';

final class Faq extends InteractionAdherent
{
    protected const TABLE = 'faq';
    private string $question;
    private ?string $reponse;
    private string $statut;
    private string $categorie;
    use FaqPersistence;
    use FaqContent;
    use FaqModeration;

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
}
