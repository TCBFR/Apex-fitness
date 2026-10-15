<?php
$parCategorie = $parCategorie ?? [];
$erreur = $erreur ?? null;
require_once __DIR__ . '/../../public/includes/public_icons.php';
$categories = [
    ['Général', 'info', [
        ['Qu\'est-ce qu\'ATHLETIX ?', 'ATHLETIX est une salle de sport de 1 500 m² au cœur de Paris, avec plateau musculation, cardio, box de cross training et studios de cours collectifs.'],
        ['Faut-il être sportif pour s\'inscrire ?', 'Pas du tout. Nos coachs accompagnent tous les niveaux, du grand débutant à l\'athlète confirmé.'],
        ['Puis-je faire une séance d\'essai ?', 'Oui, une séance découverte gratuite est offerte. Réservez-la à l\'accueil ou via la page contact.'],
        ['Y a-t-il un âge minimum ?', 'L\'accès est ouvert dès 16 ans, avec une autorisation parentale pour les mineurs.'],
        ['Les vestiaires sont-ils équipés ?', 'Oui : casiers sécurisés, douches individuelles, sèche-cheveux et produits d\'hygiène.'],
        ['Puis-je venir accompagné ?', 'Les membres Performance et Illimité peuvent inviter un ami une fois par mois.'],
    ]],
    ['Abonnements', 'lock', [
        ['Puis-je changer de formule en cours d\'abonnement ?', 'Oui, vous pouvez changer de formule à tout moment. Le changement prendra effet à la fin de la période en cours.'],
        ['Y a-t-il un engagement de durée ?', 'Non, toutes nos formules sont sans engagement.'],
        ['Comment fonctionne la résiliation ?', 'Depuis votre espace membre ou à l\'accueil, avec un préavis d\'un mois, sans frais.'],
        ['Quels moyens de paiement acceptez-vous ?', 'Prélèvement SEPA mensuel, carte bancaire et paiement annuel en une fois.'],
        ['Existe-t-il des tarifs réduits ?', 'Oui : -20 % pour les étudiants et demandeurs d\'emploi sur présentation d\'un justificatif.'],
        ['Puis-je suspendre mon abonnement ?', 'Une suspension de 1 à 3 mois est possible une fois par an (voyage, blessure, grossesse…).'],
        ['Que comprennent les frais d\'inscription ?', 'Le badge d\'accès, le bilan forme initial et votre programme de démarrage.'],
        ['Puis-je offrir un abonnement ?', 'Oui, des cartes cadeaux de 1, 3 ou 6 mois sont disponibles à l\'accueil.'],
    ]],
    ['Activités', 'users', [
        ['Combien de cours collectifs par semaine ?', 'Plus de 40 cours hebdomadaires : HIIT, cross training, yoga, pilates, cycling…'],
        ['Comment réserver un cours ?', 'Via l\'application ATHLETIX jusqu\'à 7 jours à l\'avance.'],
        ['Les cours sont-ils adaptés aux débutants ?', 'Oui, chaque cours propose des variantes selon votre niveau.'],
        ['Que se passe-t-il si j\'annule tard ?', 'Toute annulation moins de 2h avant le cours compte comme une séance consommée.'],
        ['Y a-t-il des cours le week-end ?', 'Oui, le samedi et le dimanche de 9h à 18h.'],
        ['Le cross training est-il inclus ?', 'Il est inclus dans les formules Performance et Illimité.'],
        ['Proposez-vous des cours en extérieur ?', 'Des sessions running et bootcamp sont organisées aux beaux jours.'],
    ]],
    ['Accès & horaires', 'clock', [
        ['Quels sont les horaires d\'ouverture ?', 'Du lundi au vendredi de 6h à 22h, le week-end de 8h à 20h.'],
        ['Comment accéder à la salle ?', 'Avec votre badge ou le QR code de l\'application aux portiques d\'entrée.'],
        ['La salle est-elle ouverte les jours fériés ?', 'Oui, avec les horaires du week-end, sauf le 25 décembre et le 1er janvier.'],
        ['Y a-t-il un parking ?', 'Un parking public se trouve à 100 m, et un local vélos sécurisé est à votre disposition.'],
        ['Quels transports desservent la salle ?', 'Métro et bus à moins de 3 minutes à pied.'],
    ]],
    ['Équipement', 'wrench', [
        ['Quel matériel est disponible ?', 'Racks, haltères jusqu\'à 50 kg, machines guidées, cardio connecté et zone fonctionnelle.'],
        ['Dois-je apporter une serviette ?', 'Oui, une serviette est obligatoire sur les machines. Location possible à l\'accueil.'],
        ['Le matériel est-il désinfecté ?', 'Des stations de nettoyage sont à disposition et l\'équipe désinfecte la salle plusieurs fois par jour.'],
        ['Puis-je utiliser mon propre matériel ?', 'Ceintures, gants et sangles personnels sont bien sûr autorisés.'],
    ]],
    ['Coaching', 'target', [
        ['Comment se déroule le bilan forme ?', 'Un entretien de 45 min : objectifs, composition corporelle et tests physiques simples.'],
        ['Les coachs sont-ils diplômés ?', 'Tous nos coachs sont titulaires d\'un diplôme d\'État (BPJEPS ou STAPS).'],
        ['Puis-je choisir mon coach ?', 'Oui, selon les disponibilités et votre objectif.'],
        ['Le coaching est-il inclus ?', 'Il est inclus dans la formule Illimité, et disponible en option pour les autres formules.'],
        ['Proposez-vous un suivi nutritionnel ?', 'Oui, en option, avec une diététicienne partenaire.'],
        ['À quelle fréquence est mis à jour mon programme ?', 'Tous les 2 mois, ou plus tôt si vos objectifs évoluent.'],
    ]],
    ['Annulation & remboursement', 'rotate', [
        ['Puis-je être remboursé ?', 'Les mensualités déjà commencées ne sont pas remboursées, sauf cas médical justifié.'],
        ['Que faire en cas de blessure ?', 'Envoyez un certificat médical : votre abonnement sera suspendu sans frais.'],
        ['Délai de rétractation ?', 'Vous disposez de 14 jours après une souscription en ligne pour vous rétracter.'],
    ]],
];

foreach ($parCategorie as $categorie => $questionsDb) {
    $indexCategorie = array_search($categorie, array_column($categories, 0), true);
    if ($indexCategorie === false) {
        $categories[] = [$categorie, 'message', []];
        $indexCategorie = array_key_last($categories);
    }
    $existantes = array_map(static fn(array $item): string => mb_strtolower(trim($item[0])), $categories[$indexCategorie][2]);
    foreach ($questionsDb as $questionDb) {
        if (!in_array(mb_strtolower(trim((string) $questionDb['question'])), $existantes, true)) {
            $categories[$indexCategorie][2][] = [(string) $questionDb['question'], (string) ($questionDb['reponse'] ?? '')];
        }
    }
}

$valeurs = [['headphones', 'Une équipe passionnée', 'à votre écoute'], ['clock', 'Des réponses rapides', 'et précises'], ['target', 'Un accompagnement', 'personnalisé']];
require __DIR__ . '/../../layout/header.php';
?>

<section class="hero" style="--hero-img:url('https://images.unsplash.com/photo-1576678927484-cc907957088c?auto=format&fit=crop&w=1600&q=70')">
    <div class="container hero-content reveal"><p class="eyebrow">FAQ</p><h1 class="hero-title">Vos questions,<br><span class="accent">nos réponses.</span></h1><p class="hero-lead">Trouvez rapidement les réponses aux questions fréquentes sur nos services, nos abonnements et le fonctionnement du club.</p></div>
</section>
<section class="section section-tight"><div class="container">
    <label class="search-box reveal"><?= publicIcon('search', 'icon') ?><input type="search" id="faq-search" placeholder="Rechercher une question..." aria-label="Rechercher une question"></label>
    <div class="faq-cats" data-accordion data-single>
        <?php foreach ($categories as $i => [$cat, $ic, $questions]): ?>
            <div class="acc-item faq-cat reveal" style="--delay:<?= (int) $i * 40 ?>ms">
                <button class="acc-trigger cat-trigger" aria-expanded="false"><span class="icon-circle"><?= publicIcon($ic, 'icon') ?></span><span class="cat-text"><span class="cat-name"><?= e($cat) ?></span><span class="muted small"><span class="cat-count"><?= count($questions) ?></span> questions</span></span><span class="acc-chevron"><?= publicIcon('chevron-down', 'icon') ?></span></button>
                <div class="acc-panel"><div class="acc-panel-inner"><div class="accordion nested" data-accordion>
                    <?php foreach ($questions as [$question, $reponse]): ?><div class="acc-item faq-q"><button class="acc-trigger" aria-expanded="false"><span><?= e($question) ?></span><span class="acc-chevron"><?= publicIcon('chevron-down', 'icon') ?></span></button><div class="acc-panel"><div class="acc-panel-inner"><p><?= nl2br(e($reponse)) ?></p></div></div></div><?php endforeach; ?>
                </div></div></div>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="empty-state" id="faq-empty" hidden>Aucune question ne correspond à votre recherche.</p>
    <div class="card help-banner reveal"><span class="icon-circle icon-circle-lg"><?= publicIcon('message', 'icon') ?></span><div class="help-text"><p class="banner-title">Vous ne trouvez pas la réponse ?</p><p class="muted small">Notre équipe est disponible pour vous aider.</p></div><a href="<?= e(url('message_contact/index.php')) ?>" class="btn btn-primary">Nous contacter <?= publicIcon('arrow-right', 'icon') ?></a></div>
    <div class="card perks perks-3 reveal"><?php foreach ($valeurs as [$ic, $titre, $detail]): ?><div class="perk"><span class="perk-icon perk-icon-ring"><?= publicIcon($ic, 'icon') ?></span><div><p class="perk-title"><?= e($titre) ?></p><p class="muted small"><?= e($detail) ?></p></div></div><?php endforeach; ?></div>
    <?php if (a_role('ADHERENT')): ?><section class="form public-question-form"><h2>Poser une question</h2><?php if ($erreur): ?><p class="flash flash-erreur"><?= e($erreur) ?></p><?php endif; ?><form method="post"><?= champ_csrf() ?><div class="field"><label for="question">Votre question</label><textarea id="question" name="question" minlength="10" maxlength="500" required></textarea></div><button class="btn btn-primary" type="submit">Envoyer la question <?= publicIcon('arrow-right', 'icon') ?></button></form></section><?php else: ?><p class="note">Les adhérents connectés peuvent poser une question. Sinon, utilisez le <a href="<?= e(url('message_contact/index.php')) ?>">formulaire de contact</a>.</p><?php endif; ?>
</div></section>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
