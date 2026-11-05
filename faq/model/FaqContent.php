<?php

declare(strict_types=1);

trait FaqContent
{
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

    public function assurerQuestionsDemo(): int
    {
        $auteur = $this->pdo->query(
            'SELECT NumUsers FROM adherent ORDER BY NumUsers LIMIT 1'
        )->fetchColumn();
        if ($auteur === false) {
            throw new RuntimeException('Ajoutez au moins un adhérent avant de charger les FAQ de démonstration.');
        }

        $questions = [
            [
                'Comment puis-je consulter les cours proposés ?',
                'Consultez le planning depuis le site ou votre espace adhérent. Vous y trouverez les activités et leurs créneaux.',
                'Planning',
            ],
            [
                'Comment réserver une place pour un cours ?',
                'Connectez-vous à votre espace adhérent, ouvrez le planning puis choisissez un créneau disponible pour réserver.',
                'Réservations',
            ],
            [
                'Où puis-je retrouver mes réservations ?',
                'Vos réservations sont consultables dans la rubrique « Réservations » de votre espace adhérent.',
                'Réservations',
            ],
            [
                'Comment consulter ou changer mon abonnement ?',
                'Retrouvez votre formule et son statut dans la rubrique « Abonnement » de votre espace adhérent. Les offres disponibles sont présentées dans le catalogue.',
                'Abonnements',
            ],
            [
                'Comment contacter l’équipe Apex Fitness ?',
                'Utilisez le formulaire de contact du site pour envoyer votre demande à l’équipe.',
                'Contact',
            ],
            [
                'Comment laisser un avis sur mon expérience ?',
                'Depuis votre espace adhérent, ouvrez la rubrique « Avis », attribuez une note et partagez votre commentaire.',
                'Avis',
            ],
        ];
        $existe = $this->pdo->prepare('SELECT 1 FROM faq WHERE question = ? LIMIT 1');
        $ajoute = 0;

        foreach ($questions as [$question, $reponse, $categorie]) {
            $existe->execute([$question]);
            if ($existe->fetchColumn() !== false) {
                continue;
            }

            $faqDemo = new self((int) $auteur, $question, $this->pdo, $reponse, 'Publiee', $categorie);
            $faqDemo->createFaq($this->pdo);
            $ajoute++;
        }

        return $ajoute;
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
}
