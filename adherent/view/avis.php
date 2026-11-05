<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';
require_once dirname(__DIR__, 2) . '/avis/controller/AvisController.php';
require_once dirname(__DIR__, 2) . '/config/function.php';

exige_role('ADHERENT');
$controller = new AvisController();
$memberId = (int) utilisateur()['NumUsers'];
$error = null;
$submittedComment = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $submittedComment = (string) ($_POST['commentaire'] ?? '');
    try {
        $controller->creer($memberId, (int) ($_POST['note'] ?? 0), $submittedComment);
        journalise('AVIS_SALLE', 'ATHLETIX');
        flash('succes', 'Merci pour votre avis.');
        redirige('adherent/view/avis.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$review = $controller->avisDeAdherent($memberId);
page_head('avis', 'Mon avis');
?>
<?php include __DIR__ . '/avis-styles.php'; ?>

<?php hero('star', 'Votre avis compte', 'Partagez votre expérience chez ATHLETIX en quelques mots.', 'review-hero'); ?>

<div class="review-layout">
  <section class="panel">
    <?php if ($review): ?>
      <div class="side-head">
        <h2 class="side-title"><?= icon('check', 22) ?>Avis envoyé</h2>
        <span class="tag tag-neon">Publié</span>
      </div>
      <p class="muted">Vous avez déjà transmis votre avis le <?= e(date('d/m/Y', strtotime((string) $review['date_interaction']))) ?>. Un seul avis est autorisé par adhérent.</p>
      <p class="review-stars" aria-label="Note : <?= (int) $review['noteavis'] ?> sur 5"><?= str_repeat('★', (int) $review['noteavis']) . str_repeat('☆', 5 - (int) $review['noteavis']) ?></p>
      <p class="review-comment"><?= e((string) $review['commentaire']) ?></p>
      <?php if (!empty($review['reponse'])): ?>
        <div class="review-reply"><strong>Réponse de l’équipe ATHLETIX</strong><p><?= e((string) $review['reponse']) ?></p></div>
      <?php else: ?>
        <div class="review-reply"><strong>Merci pour votre retour</strong><p>L’équipe pourra vous répondre ici après consultation de votre avis.</p></div>
      <?php endif; ?>
    <?php else: ?>
      <div class="side-head">
        <h2 class="side-title"><?= icon('star', 22) ?>Donner mon avis</h2>
        <span class="tag tag-neon">1 avis par adhérent</span>
      </div>
      <p class="muted">Votre retour aide l’équipe à améliorer l’accueil et les entraînements.</p>
      <?php if ($error): ?><p class="review-error" role="alert"><?= e($error) ?></p><?php endif; ?>
      <form method="post" class="review-form">
        <?= champ_csrf() ?>
        <label class="review-label" for="review-rating">Votre note
          <select class="review-select" id="review-rating" name="note" required>
            <option value="">Choisir une note</option>
            <?php for ($rating = 5; $rating >= 1; $rating--): ?>
              <option value="<?= $rating ?>" <?= (int) ($_POST['note'] ?? 0) === $rating ? 'selected' : '' ?>><?= $rating ?> / 5 — <?= $rating === 5 ? 'Excellent' : ($rating === 4 ? 'Très bien' : ($rating === 3 ? 'Bien' : ($rating === 2 ? 'À améliorer' : 'Décevant'))) ?></option>
            <?php endfor; ?>
          </select>
        </label>
        <label class="review-label" for="review-comment">Votre expérience
          <textarea class="review-textarea" id="review-comment" name="commentaire" minlength="10" maxlength="100" required placeholder="Décrivez votre expérience en 10 à 100 caractères."><?= e($submittedComment) ?></textarea>
        </label>
        <div class="review-meta"><span>Entre 10 et 100 caractères</span><span id="review-count" aria-live="polite">0 / 100</span></div>
        <button class="btn btn-primary" type="submit">Publier mon avis <?= icon('right', 16) ?></button>
      </form>
    <?php endif; ?>
  </section>

  <aside class="panel side-card">
    <h2 class="side-title"><?= icon('heart', 22) ?>Un retour utile</h2>
    <p class="muted">Restez respectueux et précis. Votre commentaire doit contenir entre 10 et 100 caractères.</p>
    <p class="muted">Une fois publié, l’avis ne peut pas être remplacé par un second avis.</p>
  </aside>
</div>
<script>
(() => {
  const field = document.getElementById('review-comment');
  const counter = document.getElementById('review-count');
  if (!field || !counter) return;
  const update = () => { counter.textContent = `${Array.from(field.value).length} / 100`; };
  field.addEventListener('input', update);
  update();
})();
</script>
<?php page_foot(); ?>
