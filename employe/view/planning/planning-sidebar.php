    <?php if (empty($accueil)): ?><p class="pe-hint">Astuce : cliquez sur une plage libre du calendrier pour préparer un nouveau créneau à cette date et à cette heure.</p><?php endif; ?>
    </div>

    <?php if (empty($accueil)): ?>
    <aside class="col-side">
      <section class="panel side-card" data-testid="filters-panel">
        <h2 class="side-title">Filtres</h2>
        <?php if (!empty($accueil)): ?>
        <h3 class="label">Salle</h3>
        <div class="select">
          <select id="pe-filter-room" data-testid="filter-room-select">
            <option value="all">Toutes les salles</option>
            <?php foreach ($salles as $s): ?>
              <option value="<?= e($s['nom']) ?>"><?= e($s['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <h3 class="label">Activité</h3>
        <div class="pills pills-sm" id="pe-type-pills">
          <button type="button" class="pill is-active" data-pe-type="all" data-testid="side-filter-cat-all">Tous</button>
          <?php foreach ($types as $cle => $t): ?>
            <button type="button" class="pill" data-pe-type="<?= e($cle) ?>" data-testid="side-filter-cat-<?= e($cle) ?>"><?= e($t['label']) ?></button>
          <?php endforeach; ?>
        </div>
        <h3 class="label">Coach</h3>
        <div class="select">
          <select id="pe-filter-coach" data-testid="filter-coach-select">
            <option value="all">Tous les coachs</option>
          </select>
        </div>
        <h3 class="label">Remplissage</h3>
        <div class="select">
          <select id="pe-filter-fill" data-testid="filter-fill-select">
            <option value="all">Toutes les séances</option>
            <option value="free">Places disponibles</option>
            <option value="almost">Presque complètes (≥ 80 %)</option>
            <option value="full">Complètes</option>
            <option value="low">Peu remplies (&lt; 30 %)</option>
          </select>
        </div>
        <label class="switch-row">
          <span>Masquer les séances passées</span>
          <input type="checkbox" id="pe-filter-past" class="switch" data-testid="filter-hide-past-toggle">
        </label>
      </section>

      <section class="panel side-card" data-testid="today-card">
        <h2 class="side-title">Aujourd’hui</h2>
        <div id="pe-today"><p class="muted">Chargement…</p></div>
      </section>

      <section class="panel side-card" data-testid="legend-card">
        <h2 class="side-title plain">Légende</h2>
        <ul class="legend">
          <?php foreach ($types as $cle => $t): ?>
            <li data-testid="legend-<?= e($cle) ?>"><i style="--c:<?= e($t['color']) ?>"></i><?= e($t['label']) ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    </aside>
    <?php endif; ?>
  </div>

