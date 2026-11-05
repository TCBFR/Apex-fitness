  <!-- Modale : détail d'une séance -->
  <div class="modal" id="pe-session-modal" hidden data-testid="session-modal">
    <div class="modal-backdrop" data-pe-close></div>
    <div class="modal-box" role="<?= !empty($accueil) ? 'region' : 'dialog' ?>"<?= empty($accueil) ? ' aria-modal="true"' : '' ?> aria-labelledby="pe-modal-title">
      <button type="button" class="icon-btn modal-close" data-pe-close data-testid="session-modal-close" aria-label="Fermer">×</button>
      <div class="modal-img" id="pe-modal-img"></div>
      <div class="modal-body">
        <?php if (!empty($accueil)): ?>
          <div class="pe-session-view-heading">
            <button type="button" data-pe-close>← Retour au planning</button>
            <div><span>PLANNING DES COURS</span><h2>Détail du cours</h2><p>Consultez les informations et gérez cette séance.</p></div>
          </div>
        <?php endif; ?>
        <span class="tag" id="pe-modal-cat" data-testid="session-modal-category"></span>
        <h2 id="pe-modal-title" data-testid="session-modal-title"></h2>
        <ul class="meta-list">
          <li><span id="pe-modal-date" data-testid="session-modal-date"></span></li>
          <li><span id="pe-modal-coach" data-testid="session-modal-coach"></span></li>
          <li><span id="pe-modal-room"></span></li>
          <li><span id="pe-modal-serie"></span></li>
        </ul>
        <div class="places">
          <div class="places-top"><span>Inscrits</span><strong id="pe-modal-places" data-testid="session-modal-places"></strong></div>
          <div class="bar"><i id="pe-modal-bar"></i></div>
        </div>
        <div class="pe-actions">
          <?php if (!empty($accueil)): ?><button type="button" class="btn btn-outline btn-sm" id="pe-edit-btn" data-testid="session-edit-button">Modifier</button><?php else: ?><button type="button" class="btn btn-outline btn-sm" id="pe-dup" data-testid="session-duplicate-button">Dupliquer (+ 7 jours)</button><?php endif; ?>
          <form method="post" id="pe-delete-form">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="<?= e($PE['action_supprimer']) ?>">
            <input type="hidden" name="creneau" id="pe-delete-id">
            <select name="portee" id="pe-scope" aria-label="Portée de la suppression" data-testid="session-delete-scope" hidden>
              <option value="un">Ce créneau uniquement</option>
              <option value="serie">Toutes les occurrences à venir de la série</option>
            </select>
            <button class="btn btn-danger btn-sm" type="submit" id="pe-delete-btn" data-testid="session-delete-button">Supprimer</button>
          </form>
        </div>
      </div>
    </div>
  </div>

