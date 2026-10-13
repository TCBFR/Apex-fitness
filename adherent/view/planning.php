<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';
page_head('planning', 'Planning');
hero('calweek', 'Planning', 'Découvrez et réservez vos séances en quelques clics.<br>Cours collectifs, musculation, cardio et bien plus encore.', 'planning-hero');
?>
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

          <div class="pills" data-testid="planning-category-pills">
            <button class="pill is-active" data-filter="cat" data-value="all" data-testid="filter-cat-all">Tous</button>
            <?php foreach (MAIN_FILTERS as $k): ?>
              <button class="pill" data-filter="cat" data-value="<?= e($k) ?>" data-testid="filter-cat-<?= e($k) ?>"><?= icon(CATEGORIES[$k]['icon'], 18) ?><?= e(CATEGORIES[$k]['label']) ?></button>
            <?php endforeach; ?>
          </div>

          <section class="panel cal-wrap">
            <div id="calendar" data-testid="planning-calendar"></div>
            <p class="cal-empty" id="cal-empty" hidden data-testid="calendar-empty-state">Aucune séance ne correspond à vos filtres sur cette période.</p>
          </section>

          <?php help_banner(); ?>
        </div>

        <aside class="col-side">
          <section class="panel side-card" data-testid="filters-panel">
            <h2 class="side-title"><?= icon('sliders', 22) ?>Filtres</h2>
            <h3 class="label">Activité</h3>
            <div class="pills pills-sm">
              <button class="pill is-active" data-filter="cat" data-value="all" data-testid="side-filter-cat-all">Tous</button>
              <?php foreach (CATEGORIES as $k => $c): ?>
                <button class="pill" data-filter="cat" data-value="<?= e($k) ?>" data-testid="side-filter-cat-<?= e($k) ?>"><?= icon($c['icon'], 16) ?><?= e($c['label']) ?></button>
              <?php endforeach; ?>
            </div>
            <h3 class="label">Coach</h3>
            <div class="select">
              <select id="filter-coach" data-testid="filter-coach-select">
                <option value="all">Tous les coachs</option>
                <?php foreach (COACHES as $k => $full): ?><option value="<?= e($k) ?>">Coach <?= e($k) ?></option><?php endforeach; ?>
              </select><?= icon('down', 18) ?>
            </div>
            <h3 class="label">Disponibilité</h3>
            <label class="switch-row">
              <span>Places disponibles uniquement</span>
              <input type="checkbox" id="filter-available" class="switch" data-testid="filter-available-toggle">
            </label>
          </section>

          <section class="panel side-card" data-testid="next-session-card">
            <div class="side-head">
              <h2 class="side-title"><?= icon('calendar', 22) ?>Prochaine séance</h2>
              <a href="reservations.php" class="link" data-testid="next-session-see-all">Voir tout</a>
            </div>
            <div id="next-session"><div class="skeleton" style="height:150px"></div></div>
          </section>

          <section class="panel side-card" data-testid="legend-card">
            <h2 class="side-title plain">Légende</h2>
            <ul class="legend">
              <?php foreach (CATEGORIES as $k => $c): ?>
                <li data-testid="legend-<?= e($k) ?>"><i style="--c:<?= e($c['color']) ?>"></i><?= e($c['label']) ?></li>
              <?php endforeach; ?>
            </ul>
          </section>
        </aside>
      </div>

      <div class="modal" id="session-modal" hidden data-testid="session-modal">
        <div class="modal-backdrop" data-close-modal></div>
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modal-title">
          <button class="icon-btn modal-close" data-close-modal data-testid="session-modal-close"><?= icon('x', 20) ?></button>
          <div class="modal-img" id="modal-img"></div>
          <div class="modal-body">
            <span class="tag" id="modal-cat" data-testid="session-modal-category"></span>
            <h2 id="modal-title" data-testid="session-modal-title"></h2>
            <ul class="meta-list">
              <li><?= icon('calendar', 16) ?><span id="modal-date" data-testid="session-modal-date"></span></li>
              <li><?= icon('user', 16) ?><span id="modal-coach" data-testid="session-modal-coach"></span></li>
              <li><?= icon('pin', 16) ?><span id="modal-room"></span></li>
              <li><?= icon('signal', 16) ?><span id="modal-level"></span></li>
            </ul>
            <div class="places">
              <div class="places-top"><span>Places</span><strong id="modal-places" data-testid="session-modal-places"></strong></div>
              <div class="bar"><i id="modal-bar"></i></div>
            </div>
            <button class="btn btn-primary btn-block" id="modal-action" data-testid="session-modal-action-button"></button>
          </div>
        </div>
      </div>

<script src="assets/vendor/fullcalendar.min.js"></script>
<script src="assets/vendor/fullcalendar-fr.js"></script>
<?php page_foot(['assets/js/planning.js']); ?>

<script>
  /* ATHLETIX — Planning (FullCalendar v6) */
(function () {
  const ATX = window.ATX;
  const state = { cat: 'all', coach: 'all', available: false };
  const $ = (id) => document.getElementById(id);

  const matches = (p) =>
    (state.cat === 'all' || p.category === state.cat) &&
    (state.coach === 'all' || p.coach === state.coach) &&
    (!state.available || (p.remaining > 0 && !p.is_past));

  /* ---------- Calendrier ---------- */
  const calendar = new FullCalendar.Calendar($('calendar'), {
    locale: 'fr',
    initialView: 'timeGridWeek',
    headerToolbar: false,
    firstDay: 1,
    allDaySlot: false,
    slotMinTime: '07:00:00',
    slotMaxTime: '21:00:00',
    slotDuration: '00:30:00',
    slotLabelInterval: '01:00',
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
    slotEventOverlap: false,
    nowIndicator: true,
    height: 'auto',
    dayMaxEvents: 3,
    events: (info, success, failure) => {
      ATX.api('events', { start: info.startStr, end: info.endStr })
        .then((list) => {
          const filtered = list.filter((ev) => matches(ev.extendedProps));
          $('cal-empty').hidden = filtered.length > 0;
          success(filtered);
        })
        .catch((e) => {
          $('cal-empty').hidden = false;
          $('cal-empty').textContent = 'Impossible de charger le planning pour le moment.';
          failure(e);
        });
    },
    dayHeaderContent: (arg) => {
      const dow = ATX.cap(arg.date.toLocaleDateString('fr-FR', { weekday: 'short' }));
      if (arg.view.type === 'dayGridMonth') return { html: '<span class="dh-dow">' + dow + '</span>' };
      const date = arg.date.getDate() + ' ' + arg.date.toLocaleDateString('fr-FR', { month: 'long' });
      return { html: '<span class="dh-dow">' + dow + '</span><span class="dh-date">' + date + '</span>' };
    },
    eventClassNames: (arg) => {
      const p = arg.event.extendedProps;
      return [p.is_booked && 'is-booked', p.remaining <= 0 && 'is-full', p.is_past && 'is-past'].filter(Boolean);
    },
    eventContent: (arg) => {
      const p = arg.event.extendedProps;
      const c = ATX.categories[p.category];
      const t = ATX.esc(arg.event.title);
      if (arg.view.type === 'dayGridMonth') {
        return { html: '<div class="ev-m"><i></i><b>' + ATX.hm(p.start) + '</b><span>' + t + '</span></div>' };
      }
      return {
        html:
          '<div class="ev">' +
          '<div class="ev-ic">' + c.svg + (p.is_booked ? '<span class="ev-ok">' + ATX.icons.check + 'Réservée</span>' : '') + '</div>' +
          '<strong>' + t + '</strong>' +
          '<span>' + ATX.hm(p.start) + ' – ' + ATX.hm(p.end) + '</span>' +
          '<span class="ev-coach">Coach ' + ATX.esc(p.coach) + '</span>' +
          '<span class="ev-places">' + ATX.icons.user + p.booked + ' / ' + p.capacity + (p.remaining <= 0 ? ' · Complet' : '') + '</span>' +
          '</div>',
      };
    },
    eventDidMount: (arg) => {
      arg.el.style.setProperty('--c', arg.event.extendedProps.color);
      arg.el.setAttribute('data-testid', 'calendar-event-' + arg.event.id);
      arg.el.setAttribute('title', arg.event.title + ' — Coach ' + arg.event.extendedProps.coach);
    },
    eventClick: (info) => {
      info.jsEvent.preventDefault();
      openModal(info.event.extendedProps);
    },
    datesSet: (arg) => {
      $('cal-title').textContent = arg.view.title;
      document.querySelectorAll('.vt').forEach((b) => b.classList.toggle('is-active', b.dataset.view === arg.view.type));
    },
  });
  calendar.render();

  document.querySelectorAll('.vt').forEach((b) => b.addEventListener('click', () => calendar.changeView(b.dataset.view)));
  document.querySelectorAll('[data-nav]').forEach((b) =>
    b.addEventListener('click', () => calendar[b.dataset.nav]())
  );

  /* ---------- Filtres ---------- */
  document.querySelectorAll('[data-filter="cat"]').forEach((b) =>
    b.addEventListener('click', () => {
      state.cat = b.dataset.value;
      ATX.syncFilter('cat', state.cat);
      calendar.refetchEvents();
    })
  );
  $('filter-coach').addEventListener('change', (e) => {
    state.coach = e.target.value;
    calendar.refetchEvents();
  });
  $('filter-available').addEventListener('change', (e) => {
    state.available = e.target.checked;
    calendar.refetchEvents();
  });

  /* ---------- Modale de réservation ---------- */
  const modal = $('session-modal');
  let current = null;

  function actionState(s) {
    if (s.is_past) return { label: 'Séance terminée', cls: 'btn-muted', action: null };
    if (s.is_booked) return { label: 'Annuler ma réservation', cls: 'btn-danger', action: 'cancel' };
    if (s.remaining <= 0) return { label: 'Complet', cls: 'btn-muted', action: null };
    return { label: 'Réserver cette séance', cls: 'btn-primary', action: 'book' };
  }

  function fillModal(s) {
    current = s;
    const c = ATX.categories[s.category];
    modal.style.setProperty('--c', s.color);
    $('modal-img').style.backgroundImage = "url('" + s.image + "')";
    $('modal-cat').innerHTML = c.svg + ATX.esc(c.label);
    $('modal-title').textContent = s.title;
    $('modal-date').textContent =
      ATX.frDate(s.start, { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' + ATX.hm(s.start) + ' – ' + ATX.hm(s.end);
    $('modal-coach').textContent = 'Coach ' + s.coach_full;
    $('modal-room').textContent = s.room;
    $('modal-level').textContent = s.level;
    $('modal-places').textContent = s.booked + ' / ' + s.capacity + ' inscrits · ' + s.remaining + ' place' + (s.remaining > 1 ? 's' : '') + ' restante' + (s.remaining > 1 ? 's' : '');
    $('modal-bar').style.width = Math.round((s.booked / s.capacity) * 100) + '%';
    const a = actionState(s);
    const btn = $('modal-action');
    btn.className = 'btn btn-block ' + a.cls;
    btn.textContent = a.label;
    btn.disabled = !a.action;
    btn.dataset.action = a.action || '';
  }

  function openModal(s) {
    fillModal(s);
    modal.hidden = false;
    requestAnimationFrame(() => modal.classList.add('is-open'));
  }
  function closeModal() {
    modal.classList.remove('is-open');
    setTimeout(() => (modal.hidden = true), 200);
  }
  modal.querySelectorAll('[data-close-modal]').forEach((el) => el.addEventListener('click', closeModal));
  document.addEventListener('keydown', (e) => e.key === 'Escape' && !modal.hidden && closeModal());

  $('modal-action').addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    if (!current || !btn.dataset.action) return;
    btn.disabled = true;
    const updated = await ATX.bookAction(btn.dataset.action, current.id);
    if (updated) {
      fillModal(updated);
      calendar.refetchEvents();
      loadNext();
    } else {
      btn.disabled = false;
    }
  });

  /* ---------- Prochaine séance ---------- */
  const nextBox = $('next-session');
  async function loadNext() {
    try {
      const { session: s } = await ATX.api('next');
      if (!s) {
        nextBox.innerHTML = '<p class="muted">Aucune séance à venir.</p>';
        return;
      }
      const c = ATX.categories[s.category];
      nextBox.innerHTML =
        '<div class="next-big" style="--c:' + s.color + '">' +
        '<span class="next-img" style="background-image:url(\'' + s.image + '\')"></span>' +
        '<div>' +
        '<h3 data-testid="next-session-title"><span class="cat-ic">' + c.svg + '</span>' + ATX.esc(s.title) + '</h3>' +
        '<p class="next-date" data-testid="next-session-date">' + ATX.frDate(s.start, { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' }) + '</p>' +
        '<p class="next-time">' + ATX.hm(s.start) + ' – ' + ATX.hm(s.end) + '</p>' +
        '<p class="muted">' + ATX.icons.user + 'Coach ' + ATX.esc(s.coach) + '</p>' +
        '<p class="muted">' + ATX.icons.user + s.remaining + ' / ' + s.capacity + ' places restantes</p>' +
        '</div></div>' +
        (s.is_booked
          ? '<div class="next-booked"><span class="tag tag-neon">' + ATX.icons.check + 'Réservée</span><a class="btn btn-outline btn-sm" href="reservations.php" data-testid="next-session-manage">Gérer ' + ATX.icons.right + '</a></div>'
          : '<button class="btn btn-primary btn-block" data-testid="next-session-book-button" data-id="' + s.id + '">Réserver ' + ATX.icons.right + '</button>');
      const bookBtn = nextBox.querySelector('[data-testid="next-session-book-button"]');
      if (bookBtn) bookBtn.addEventListener('click', () => openModal(s));
    } catch (e) {
      nextBox.innerHTML = '<p class="muted">Prochaine séance indisponible pour le moment.</p>';
    }
  }
  loadNext();
})();

</script>