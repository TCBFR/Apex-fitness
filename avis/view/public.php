<?php
$avis = $avis ?? [];
$moyenne = $moyenne ?? 0;
$noteFiltre = $noteFiltre ?? null;
$compteurs = $compteurs ?? array_fill(1, 5, 0);
$maxCompteur = $maxCompteur ?? 1;
$avisAffiches = $avisAffiches ?? [];
$erreur = $erreur ?? null;
require_once __DIR__ . '/../../public/includes/public_icons.php';
require __DIR__ . '/../../layout/header.php';
?>

<section class="hero" style="--hero-img:url('https://images.unsplash.com/photo-1550345332-09e3ac987658?auto=format&fit=crop&w=1600&q=70')">
    <div class="container hero-content reveal">
        <p class="eyebrow">Nos membres</p><h1 class="hero-title">Ils progressent.<br><span class="accent">Ils en parlent.</span></h1>
        <p class="hero-lead">Découvrez les témoignages de notre communauté et pourquoi ses membres ont choisi ATHLETIX.</p>
        <?php if ($avis): ?>
            <div class="rating-row">
                <div class="card rating-card"><div class="rating-score"><strong><?= number_format($moyenne, 1, ',', ' ') ?></strong><span>/5</span></div><div><?= publicStars((int) round($moyenne)) ?><p class="muted small">Sur <?= count($avis) ?> avis publiés</p></div></div>
                <div class="chips" data-filter-group="reviews" role="tablist" aria-label="Filtrer les avis par note">
                    <button class="chip<?= $noteFiltre === null ? ' is-active' : '' ?>" type="button" data-filter="all">Tous</button>
                    <?php for ($n = 5; $n >= 1; $n--): ?><button class="chip<?= $noteFiltre === $n ? ' is-active' : '' ?>" type="button" data-filter="note-<?= $n ?>"><?= $n ?> étoiles</button><?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-tight"><div class="container">
    <?php if (!$avis): ?><div class="empty-state"><h2>Aucun avis publié pour le moment.</h2><p>Les avis partagés par les adhérents apparaîtront ici.</p></div><?php else: ?>
        <div class="review-list-wrap"><div class="grid grid-3" data-filter-target="reviews">
            <?php foreach ($avisAffiches as $item):
                $prenom = (string) ($item['prenomUsers'] ?? '');
                $nom = (string) ($item['nomUsers'] ?? '');
                $fullName = trim($prenom . ' ' . $nom);
                $initials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
            ?>
                <article class="card review-card reveal" data-category="note-<?= (int) $item['noteavis'] ?>">
                    <header class="review-head"><span class="avatar"><?= e($initials ?: 'A') ?></span><div class="review-meta"><p class="review-name"><?= e($fullName ?: 'Adhérent') ?></p><?= publicStars((int) $item['noteavis']) ?></div><time class="review-date"><?= e(dateFr($item['date_interaction'])) ?></time></header>
                    <blockquote class="review-text">&ldquo;<?= nl2br(e($item['commentaire'])) ?>&rdquo;</blockquote>
                    <?php if (!empty($item['reponse'])): ?><div class="reponse-admin"><strong>Réponse de l’équipe ATHLETIX</strong><p><?= nl2br(e($item['reponse'])) ?></p></div><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div><p class="empty-state" <?= $avisAffiches ? 'hidden' : '' ?>>Aucun avis dans cette catégorie pour le moment.</p></div>
    <?php endif; ?>

    <?php if ($erreur): ?><p class="flash flash-erreur"><?= e($erreur) ?></p><?php endif; ?>
    <?php if (connecte() && a_role('ADHERENT') && !a_role('EMPLOYE')): ?>
        <section class="form public-review-form"><h2>Donner votre avis</h2><p class="muted">Votre expérience aide les futurs adhérents à découvrir le club.</p><form method="post"><?= champ_csrf() ?><div class="field"><label for="note">Votre note</label><select id="note" name="note" required><?php for ($n = 5; $n > 0; $n--): ?><option value="<?= $n ?>"><?= $n ?> / 5</option><?php endfor; ?></select></div><div class="field"><label for="commentaire">Votre commentaire</label><textarea id="commentaire" name="commentaire" minlength="10" maxlength="1000" required></textarea></div><button class="btn btn-primary" type="submit">Publier mon avis <?= publicIcon('arrow-right', 'icon') ?></button></form></section>
    <?php elseif (!connecte()): ?><div class="center-row"><a href="<?= e(url('utilisateur/connexion.php')) ?>" class="btn btn-outline btn-lg">Connectez-vous pour laisser un avis <?= publicIcon('arrow-right', 'icon') ?></a></div><?php endif; ?>
</div></section>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
