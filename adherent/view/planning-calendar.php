<div class="layout-2">
        <div class="col-main">
          <section class="panel toolbar" data-testid="planning-toolbar">
            <div class="view-tabs" role="tablist">
              <button class="vt is-active" data-view="timeGridWeek" data-testid="view-week-button"><?= icon('calweek', 20) ?>Semaine</button>
              <button class="vt" data-view="dayGridMonth" data-testid="view-month-button"><?= icon('calendar', 20) ?>Mois</button>
              <button class="vt" data-view="timeGridDay" data-testid="view-day-button"><?= icon('clock', 20) ?>Jour</button>
            </div>
            <div class="date-nav">
              <button class="btn btn-ghost btn-sm" data-nav="today" data-testid="calendar-today-button">Aujourd’hui</button>
              <div class="date-box">
                <button class="icon-btn" data-nav="prev" data-testid="calendar-prev-button" aria-label="Précédent"><?= icon('left', 18) ?></button>
                <span class="date-label"><?= icon('calendar', 18) ?><span id="cal-title" data-testid="calendar-title"></span></span>
                <button class="icon-btn" data-nav="next" data-testid="calendar-next-button" aria-label="Suivant"><?= icon('right', 18) ?></button>
              </div>
            </div>
          </section>

          <section class="panel cal-wrap">
            <div id="calendar" data-testid="planning-calendar"></div>
            <div class="event-tooltip" id="event-tooltip" role="tooltip" hidden></div>
            <p class="cal-empty" id="cal-empty" hidden data-testid="calendar-empty-state">Aucune séance n’est programmée sur cette période.</p>
          </section>

        </div>

        <aside class="col-side">
          <section class="panel side-card" data-testid="next-session-card">
            <div class="side-head">
              <h2 class="side-title"><?= icon('calendar', 22) ?>Ma prochaine réservation</h2>
              <a href="<?= e(url('adherent/reservations')) ?>" class="link" data-testid="next-session-see-all">Mes séances</a>            </div>
            <div id="next-session"><div class="skeleton" style="height:150px"></div></div>
          </section>

          <section class="panel side-card booking-guidance">
            <h2 class="side-title"><?= icon('check', 22) ?>À retenir</h2>
            <p>Vous pouvez annuler une réservation jusqu’à 2 heures avant le début du cours.</p>
            <a class="btn btn-outline btn-block" href="<?= e(url('adherent/reservations')) ?>">Gérer mes séances <?= icon('right', 16) ?></a>          </section>
        </aside>
      </div>
