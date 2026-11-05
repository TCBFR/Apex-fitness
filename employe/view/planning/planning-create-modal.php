  <!-- Modale : nouveau créneau -->
  <div class="modal" id="pe-create-modal" hidden data-testid="create-modal">
    <div class="modal-backdrop" data-pe-close></div>
    <div class="modal-box" role="<?= !empty($accueil) ? 'region' : 'dialog' ?>"<?= empty($accueil) ? ' aria-modal="true"' : '' ?> aria-labelledby="pe-create-title">
      <button type="button" class="icon-btn modal-close" data-pe-close data-testid="create-modal-close" aria-label="Fermer">×</button>
      <div class="modal-body">
        <?php if (!empty($accueil)): ?>
          <div class="pe-create-view-heading">
            <button type="button" data-pe-close>← Retour au planning</button>
            <div><span>PLANNING DES COURS</span><h2 id="pe-create-title">Nouveau créneau</h2><p>Renseignez les informations pour organiser cette séance.</p></div>
          </div>
        <?php else: ?>
          <h2 id="pe-create-title">Nouveau créneau</h2>
        <?php endif; ?>
        <form method="post" class="pe-form" id="pe-create-form" data-testid="create-form">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" id="pe-create-action" value="<?= e($PE['action_creer']) ?>">
          <input type="hidden" name="creneau" id="pe-edit-id" value="">
          <?php if (!$salles || !$coachs): ?>
            <p class="pe-warn">
              <?= !$salles ? 'La liste des salles est vide ou illisible. ' : '' ?>
              <?= !$coachs ? 'La liste des coachs est vide ou illisible. ' : '' ?>
              Un créneau ne peut pas être créé sans salle ni coach.
            </p>
          <?php endif; ?>
          <label class="pe-field full">Titre *
            <input type="text" name="titre" required maxlength="100" list="pe-dl-titres" data-testid="create-title">
          </label>
          <label class="pe-field">Type d’activité *
            <select name="type" required data-testid="create-type">
              <?php foreach ($types as $cle => $t): ?><option value="<?= e($cle) ?>"><?= e($t['label']) ?></option><?php endforeach; ?>
            </select>
          </label>
          <label class="pe-field">Capacité (places) *
            <input type="number" name="capacite" min="1" value="15" required data-testid="create-capacity">
          </label>
          <label class="pe-field">Salle *
            <select name="salle" required data-testid="create-room">
              <option value="">Choisir…</option>
              <?php foreach ($salles as $s): ?>
                <option value="<?= (int) $s['id'] ?>" data-nom="<?= e($s['nom']) ?>" data-capacite="<?= (int) $s['capacite'] ?>"><?= e($s['nom']) ?><?= $s['capacite'] ? ' (' . (int) $s['capacite'] . ' places)' : '' ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="pe-field">Coach *
            <select name="coach" required data-testid="create-coach">
              <option value="">Choisir…</option>
              <?php foreach ($coachs as $c): ?>
                <option value="<?= (int) $c['id'] ?>" data-prenom="<?= e($c['prenom']) ?>"><?= e($c['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="pe-field">Début *
            <input type="datetime-local" name="debut" required data-testid="create-start">
          </label>
          <label class="pe-field">Durée (minutes) *
            <input type="number" name="duree" min="15" max="480" step="5" value="60" required data-testid="create-duration">
          </label>
          <label class="pe-check" id="pe-repeat-field">
            <input type="checkbox" name="repeter" value="1" id="pe-repeter" data-testid="create-repeat">
            Répéter chaque semaine
          </label>
          <label class="pe-field full" id="pe-until-wrap" hidden>Jusqu’au (inclus) *
            <input type="date" name="jusqu_au" id="pe-until" data-testid="create-until">
          </label>
          <p class="pe-info" id="pe-preview" hidden data-testid="create-preview"></p>
          <p class="pe-warn" id="pe-conflict" hidden role="alert" data-testid="create-conflict"></p>
          <p class="pe-warn" id="pe-form-error" hidden role="alert" data-testid="create-error"></p>
          <div class="pe-form-actions">
            <button type="button" class="btn btn-ghost btn-sm" data-pe-close>Annuler</button>
            <button type="submit" class="btn btn-primary btn-vert btn-sm" id="pe-create-submit" data-testid="create-submit">Créer le créneau</button>
          </div>
        </form>
        <datalist id="pe-dl-titres"><?php foreach ($titres as $v): ?><option value="<?= e($v) ?>"><?php endforeach; ?></datalist>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="pe-config"><?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?></script>
<script src="<?= e(url('assets/js/planning-employe.js')) ?>"></script>
