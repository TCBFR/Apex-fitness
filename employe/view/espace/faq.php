  <?php if (!$questionsFaq): ?>
    <div class="vide">Aucune question posée pour le moment.</div>
  <?php else: foreach ($questionsFaq as $q): ?>
    <article class="carte" style="margin-bottom:14px">
      <p class="mono">
        <?= dateFr($q['date_interaction'], true) ?> &middot;
        <?= $q['nomUsers'] ? e($q['prenomUsers'] . ' ' . $q['nomUsers']) : 'Rédaction interne' ?> &middot;
        <span class="tag <?= $q['statut_faq'] === 'Publiee' ? 'tag-ok' : 'tag-warn' ?>"><?= e($q['statut_faq']) ?></span>
      </p>
      <h3><?= e($q['question']) ?></h3>
      <form method="post" style="display:grid;gap:10px;margin-top:10px">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="repondre_faq">
        <input type="hidden" name="interaction" value="<?= (int) $q['NumInteraction'] ?>">
        <textarea name="reponse" placeholder="Rédigez la réponse publiée sur la page FAQ"
                  style="min-height:90px;padding:9px 11px;border:1px solid #DCDAD2;border-radius:3px"><?= e($q['reponse'] ?? '') ?></textarea>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <input type="text" name="categorie" value="<?= e($q['categorie']) ?>" placeholder="Catégorie"
                 style="padding:8px 11px;border:1px solid #DCDAD2;border-radius:3px">
          <button class="btn btn-sm btn-vert" type="submit">Publier la réponse</button>
        </div>
      </form>
      <form method="post" style="margin-top:8px">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="supprimer_faq">
        <input type="hidden" name="interaction" value="<?= (int) $q['NumInteraction'] ?>">
        <button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer cette question ?">Supprimer</button>
      </form>
    </article>
  <?php endforeach; endif; ?>
