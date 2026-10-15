<?php
$donnees = $donnees ?? ['nom' => '', 'mail' => '', 'sujet' => '', 'message' => ''];
$erreurs = $erreurs ?? [];
require_once __DIR__ . '/../../public/includes/public_icons.php';
require __DIR__ . '/../../layout/header.php';
$infos = [
    ['map-pin', 'Adresse', ['12 Rue des Sports', '75000 Paris']],
    ['phone', 'Téléphone', ['+33 1 23 45 67 89', 'Lun - Ven : 8h - 20h']],
    ['mail', 'Email', ['contact@athletix.fr', 'Réponse sous 24h']],
    ['clock', 'Horaires', ['Lun - Ven : 6h - 22h', 'Sam - Dim : 8h - 20h']],
];
$sujets = ['Abonnements', 'Activités & cours', 'Coaching personnalisé', 'Laisser un avis', 'Autre demande'];
?>

<section class="hero" style="--hero-img:url('https://images.unsplash.com/photo-1548690312-e3b507d8c110?auto=format&fit=crop&w=1600&q=70')">
    <div class="container hero-content reveal"><p class="eyebrow">Contact</p><h1 class="hero-title">Une question ?<br><span class="accent">On est là pour vous.</span></h1><p class="hero-lead">Notre équipe est disponible pour répondre à vos questions. Écrivez-nous, nous vous répondrons au plus vite.</p></div>
</section>

<section class="section section-tight"><div class="container">
    <div class="contact-grid">
        <div class="info-list">
            <?php foreach ($infos as $i => [$ic, $label, $lines]): ?><article class="card info-card reveal" style="--delay:<?= (int) $i * 60 ?>ms"><span class="icon-circle"><?= publicIcon($ic, 'icon') ?></span><div><p class="info-label"><?= e($label) ?></p><?php foreach ($lines as $line): ?><p class="muted small"><?= e($line) ?></p><?php endforeach; ?></div></article><?php endforeach; ?>
        </div>
        <form class="card contact-form reveal" id="contact-form" method="post" novalidate>
            <?= champ_csrf() ?><h2 class="form-title">Envoyez-nous un message</h2>
            <?php foreach (['nom' => 'Nom complet', 'mail' => 'Email'] as $field => $label): ?>
                <div class="field<?= isset($erreurs[$field]) ? ' has-error' : '' ?>"><label for="<?= e($field) ?>"><?= e($label) ?> *</label><input type="<?= $field === 'mail' ? 'email' : 'text' ?>" id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e($donnees[$field]) ?>" required autocomplete="<?= $field === 'nom' ? 'name' : 'email' ?>"><?php if (isset($erreurs[$field])): ?><small class="field-error"><?= e($erreurs[$field]) ?></small><?php endif; ?></div>
            <?php endforeach; ?>
            <div class="field<?= isset($erreurs['sujet']) ? ' has-error' : '' ?>"><label for="sujet">Sujet *</label><div class="select-wrap"><select id="sujet" name="sujet" required><option value="">Sélectionner un sujet</option><?php foreach ($sujets as $sujet): ?><option value="<?= e($sujet) ?>" <?= $donnees['sujet'] === $sujet ? 'selected' : '' ?>><?= e($sujet) ?></option><?php endforeach; ?><?php if ($donnees['sujet'] !== '' && !in_array($donnees['sujet'], $sujets, true)): ?><option value="<?= e($donnees['sujet']) ?>" selected><?= e($donnees['sujet']) ?></option><?php endif; ?></select><?= publicIcon('chevron-down', 'icon') ?></div><?php if (isset($erreurs['sujet'])): ?><small class="field-error"><?= e($erreurs['sujet']) ?></small><?php endif; ?></div>
            <div class="field<?= isset($erreurs['message']) ? ' has-error' : '' ?>"><label for="message">Votre message *</label><textarea id="message" name="message" rows="4" maxlength="1500" required><?= e($donnees['message']) ?></textarea><?php if (isset($erreurs['message'])): ?><small class="field-error"><?= e($erreurs['message']) ?></small><?php endif; ?></div>
            <button type="submit" class="btn btn-primary btn-block">Envoyer le message <?= publicIcon('arrow-right', 'icon') ?></button>
        </form>
    </div>
    <div class="card map-card reveal"><div class="map-frame"><iframe title="Carte ATHLETIX" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=2.3300%2C48.8500%2C2.3600%2C48.8650&amp;layer=mapnik"></iframe><span class="map-pin"><?= publicIcon('map-pin', 'icon') ?></span></div><div class="map-info"><p class="map-title">Notre salle</p><p class="muted">12 Rue des Sports<br>75000 Paris</p><a href="https://www.google.com/maps/search/?api=1&amp;query=12+Rue+des+Sports+75000+Paris" target="_blank" rel="noopener" class="btn btn-primary">Voir sur Google Maps <?= publicIcon('arrow-right', 'icon') ?></a></div></div>
    <div class="card perks reveal"><?php foreach ([['message', 'Réponse rapide', 'Sous 24h maximum'], ['user', 'Équipe à l’écoute', 'Des coachs passionnés'], ['map-pin', 'Accès facile', 'Proche des transports'], ['clock', 'Nous rendre visite', 'Venez nous rencontrer']] as [$ic, $titre, $detail]): ?><div class="perk"><span class="perk-icon"><?= publicIcon($ic, 'icon') ?></span><div><p class="perk-title"><?= e($titre) ?></p><p class="muted small"><?= e($detail) ?></p></div></div><?php endforeach; ?></div>
</div></section>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
