  <div class="layout-2<?= !empty($accueil) ? ' pe-layout-reception' : '' ?>">
    <div class="col-main">
      <section class="panel toolbar" data-testid="planning-toolbar">
        <?php if (empty($accueil)): ?>
        <div class="view-tabs" role="tablist">
          <button type="button" class="vt is-active" data-pe-view="timeGridWeek" data-testid="view-week-button">Semaine</button>
          <button type="button" class="vt" data-pe-view="dayGridMonth" data-testid="view-month-button">Mois</button>
          <button type="button" class="vt" data-pe-view="timeGridDay" data-testid="view-day-button">Jour</button>
        </div>
        <?php endif; ?>
        <div class="date-nav">
          <?php if (empty($accueil)): ?><button type="button" class="btn btn-ghost btn-sm" data-pe-nav="today" data-testid="calendar-today-button">Aujourd’hui</button><?php endif; ?>
          <div class="date-box">
            <button type="button" class="icon-btn" data-pe-nav="prev" data-testid="calendar-prev-button" aria-label="<?= !empty($accueil) ? 'Semaine précédente' : 'Précédent' ?>">‹</button>
            <span class="date-label"><?php if (!empty($accueil)): ?><i class="fa-regular fa-calendar" aria-hidden="true"></i><?php endif; ?><span id="pe-cal-title" data-testid="calendar-title"></span></span>
            <button type="button" class="icon-btn" data-pe-nav="next" data-testid="calendar-next-button" aria-label="<?= !empty($accueil) ? 'Semaine suivante' : 'Suivant' ?>">›</button>
          </div>
          <?php if (!empty($accueil)): ?>
          <button type="button" class="btn btn-primary btn-vert btn-sm" data-pe-nav="today"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Cette semaine</button>
          <?php endif; ?>
          <button type="button" class="btn btn-primary btn-vert btn-sm" id="pe-create-btn" data-testid="create-session-button"><?= !empty($accueil) ? '+ Ajouter un créneau' : '+ Nouveau créneau' ?></button>
          <?php if (!empty($accueil)): ?>
          <div class="pe-reception-filters">
            <label class="select"><span class="sr-only">Filtrer les salles</span><select id="pe-filter-room" data-testid="filter-room-select"><option value="all">Toutes les salles</option><?php foreach ($salles as $s): ?><option value="<?= e($s['nom']) ?>"><?= e($s['nom']) ?></option><?php endforeach; ?></select></label>
            <label class="select"><span class="sr-only">Filtrer les activités</span><select id="pe-filter-type" data-testid="filter-type-select"><option value="all">Toutes les activités</option><?php foreach ($types as $cle => $t): ?><option value="<?= e($cle) ?>"><?= e($t['label']) ?></option><?php endforeach; ?></select></label>
            <label class="select"><span class="sr-only">Filtrer les coachs</span><select id="pe-filter-coach" data-testid="filter-coach-select"><option value="all">Tous les coachs</option></select></label>
          </div>
          <?php endif; ?>
        </div>
      </section>

