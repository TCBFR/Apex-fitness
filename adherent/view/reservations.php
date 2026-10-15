<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';

$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) $date = date('Y-m-d');
$d = new DateTimeImmutable($date);
$prev = $d->modify('-1 day')->format('Y-m-d');
$next = $d->modify('+1 day')->format('Y-m-d');
$sessions = atx_sessions_view(fn($s) => $s['date'] === $date);

function action_button(array $s): string
{
    if ($s['is_past']) return '<button class="btn btn-muted" disabled data-testid="session-action-' . e($s['id']) . '">Terminée</button>';
    if ($s['is_booked']) return $s['can_cancel'] ? '<button class="btn btn-danger" data-action="cancel" data-id="' . e($s['id']) . '" data-testid="session-action-' . e($s['id']) . '">Annuler ' . icon('x', 16) . '</button>' : '<span class="btn btn-muted" data-testid="session-action-' . e($s['id']) . '">Annulation close (moins de 2 h)</span>';
    if ($s['is_waitlisted']) return '<button class="btn btn-outline" data-action="waitlist_leave" data-id="' . e($s['id']) . '" data-testid="session-action-' . e($s['id']) . '">Quitter la liste d’attente</button>';
    if ($s['remaining'] <= 0) return '<button class="btn btn-outline" data-action="waitlist_join" data-id="' . e($s['id']) . '" data-testid="session-action-' . e($s['id']) . '">Liste d’attente</button>';
    return '<button class="btn btn-primary" data-action="book" data-id="' . e($s['id']) . '" data-testid="session-action-' . e($s['id']) . '">Réserver ' . icon('right', 16) . '</button>';
}

page_head('reservations', 'Réservations');
hero('calweek', 'Réservations', 'Réservez vos séances en quelques clics et accédez à toutes nos activités.', 'reservations-hero');
?>
      <div class="layout-2">
        <div class="col-main">
          <section class="panel list-panel">
            <div class="list-head">
              <span class="list-icon"><?= icon('calendar', 26) ?></span>
              <div>
                <h2>Prochaines séances</h2>
                <p>Découvrez et réservez les cours disponibles dans votre salle.</p>
              </div>
              <div class="date-box">
                <a class="icon-btn" href="<?= e(url('adherent/view/reservations.php?date='.$prev)) ?>" data-testid="day-prev-button" aria-label="Jour précédent"><?= icon('left', 18) ?></a>
                <span class="date-label" data-testid="day-label"><?= e(fr_date($date, 'short') . ' ' . $d->format('Y')) ?></span>
                <a class="icon-btn" href="<?= e(url('adherent/view/reservations.php?date='.$next)) ?>" data-testid="day-next-button" aria-label="Jour suivant"><?= icon('right', 18) ?></a>
              </div>
            </div>

            <div class="sess-list" id="sess-list" data-testid="sessions-list">
              <?php foreach ($sessions as $s): ?>
                <article class="sess-card<?= $s['is_booked'] ? ' is-booked' : '' ?>" style="--c:<?= e($s['color']) ?>"
                         data-id="<?= e($s['id']) ?>" data-cat="<?= e($s['category']) ?>" data-coach="<?= e($s['coach']) ?>"
                         data-level="<?= e($s['level']) ?>" data-remaining="<?= (int)$s['remaining'] ?>" data-waitlisted="<?= $s['is_waitlisted'] ? '1' : '0' ?>" data-testid="session-card-<?= e($s['id']) ?>">
                  <div class="sess-img" style="background-image:url('<?= e($s['image']) ?>')"></div>
                  <div class="sess-time"><strong><?= e(hm($s['start'])) ?></strong><span>- <?= e(hm($s['end'])) ?></span></div>
                  <div class="sess-info">
                    <h3><?= e($s['title']) ?> <span class="booked-badge"><?= icon('check', 12) ?>Réservée</span></h3>
                    <p><?= icon('user', 14) ?>Coach <?= e($s['coach_full']) ?></p>
                    <p class="lvl"><?= icon('signal', 14) ?><?= e($s['level']) ?> · <?= e($s['room']) ?></p>
                  </div>
                  <div class="sess-places">
                    <strong data-role="count"><?= icon('user', 15) ?><span><?= (int)$s['remaining'] ?> / <?= (int)$s['capacity'] ?></span></strong>
                    <span>places restantes</span>
                  </div>
                  <div class="sess-action"><?= action_button($s) ?></div>
                </article>
              <?php endforeach; ?>
            </div>
            <p class="empty" id="sess-empty" <?= $sessions ? 'hidden' : '' ?> data-testid="sessions-empty-state">Aucune séance ne correspond à vos filtres ce jour-là.</p>
          </section>

          <section class="panel list-panel" data-testid="my-bookings-panel">
            <div class="list-head">
              <span class="list-icon"><?= icon('calcheck', 26) ?></span>
              <div>
                <h2>Mes réservations</h2>
                <p>Annulez jusqu’à 2 heures avant le début du cours. Les places libérées sont proposées à la liste d’attente.</p>
              </div>
              <span class="count-pill" id="my-count" data-testid="my-bookings-count">0</span>
            </div>
            <div class="my-list" id="my-list" data-testid="my-bookings-list"><div class="skeleton" style="height:64px"></div></div>
          </section>

        </div>

        <aside class="col-side">
          <section class="panel side-card" data-testid="filters-panel">
            <h2 class="side-title"><?= icon('sliders', 22) ?>Filtres</h2>
            <h3 class="label">Activité</h3>
            <div class="pills pills-sm">
              <button class="pill is-active" data-filter="cat" data-value="all" data-testid="side-filter-cat-all"><?= icon('grid', 16) ?>Toutes</button>
              <?php foreach (CATEGORIES as $k => $c): ?>
                <button class="pill" data-filter="cat" data-value="<?= e($k) ?>" data-testid="side-filter-cat-<?= e($k) ?>"><?= icon($c['icon'], 16) ?><?= e($c['label']) ?></button>
              <?php endforeach; ?>
            </div>
            <h3 class="label">Niveau</h3>
            <div class="pills pills-sm">
              <button class="pill is-active" data-filter="level" data-value="all" data-testid="filter-level-all">Tous</button>
              <?php foreach (LEVELS as $lv): ?>
                <button class="pill" data-filter="level" data-value="<?= e($lv) ?>" data-testid="filter-level-<?= e(strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $lv))) ?>"><?= e($lv) ?></button>
              <?php endforeach; ?>
            </div>
            <h3 class="label">Coach</h3>
            <div class="select">
              <select id="filter-coach" data-testid="filter-coach-select">
                <option value="all">Tous les coachs</option>
                <?php foreach (atx_coaches() as $k => $full): ?><option value="<?= e($k) ?>">Coach <?= e($full) ?></option><?php endforeach; ?>
              </select><?= icon('down', 18) ?>
            </div>
            <h3 class="label">Disponibilité</h3>
            <label class="switch-row">
              <span>Places disponibles uniquement</span>
              <input type="checkbox" id="filter-available" class="switch" data-testid="filter-available-toggle">
            </label>
          </section>

          <section class="panel promo" data-testid="promo-card" style="background-image:url('<?= e(CAT_IMAGES['abdos']) ?>')">
            <svg width="34" height="34" viewBox="0 0 48 48" aria-hidden="true"><path d="M24 3 44 44H34L24 22 14 44H4Z" fill="#CCFF00"/></svg>
            <h2>Ton objectif,<br>notre priorité</h2>
            <p>Un accompagnement sur mesure pour aller plus loin.</p>
            <a href="<?= e(url('adherent/view/performance.php')) ?>" class="btn btn-primary btn-sm" data-testid="promo-cta">Voir mes performances <?= icon('right', 16) ?></a>
          </section>

          <section class="panel help-mini">
            <span class="help-icon"><?= icon('headset', 24) ?></span>
            <div><strong>Une question ?</strong><p>Notre équipe est disponible pour vous aider.</p>
              <a href="<?= e(url('adherent/view/messagerie.php')) ?>" class="link" data-testid="help-mini-contact">Contacter l’accueil <?= icon('right', 14) ?></a></div>
          </section>
        </aside>
      </div>
<script>
  /* ATHLETIX — Réservations */
(function () {
  const ATX = window.ATX;
  const state = { cat: 'all', level: 'all', coach: 'all', available: false };
  const list = document.getElementById('sess-list');
  const empty = document.getElementById('sess-empty');
  const myList = document.getElementById('my-list');
  const myCount = document.getElementById('my-count');

  /* ---------- Filtres ---------- */
  function apply() {
    let visible = 0;
    list.querySelectorAll('.sess-card').forEach((card) => {
      const d = card.dataset;
      const ok =
        (state.cat === 'all' || d.cat === state.cat) &&
        (state.level === 'all' || d.level === state.level || d.level === 'Tous niveaux') &&
        (state.coach === 'all' || d.coach === state.coach) &&
        (!state.available || (Number(d.remaining) > 0 && !card.querySelector('[disabled]')));
      card.hidden = !ok;
      if (ok) visible++;
    });
    empty.hidden = visible > 0;
  }

  document.querySelectorAll('[data-filter]').forEach((b) =>
    b.addEventListener('click', () => {
      state[b.dataset.filter] = b.dataset.value;
      ATX.syncFilter(b.dataset.filter, b.dataset.value);
      apply();
    })
  );
  document.getElementById('filter-coach').addEventListener('change', (e) => {
    state.coach = e.target.value;
    apply();
  });
  document.getElementById('filter-available').addEventListener('change', (e) => {
    state.available = e.target.checked;
    apply();
  });

  /* ---------- Boutons ---------- */
  function buttonHtml(s) {
    const tid = ' data-testid="session-action-' + s.id + '"';
    if (s.is_past) return '<button class="btn btn-muted" disabled' + tid + '>Terminée</button>';
    if (s.is_booked) return s.can_cancel ? '<button class="btn btn-danger" data-action="cancel" data-id="' + s.id + '"' + tid + '>Annuler ' + ATX.icons.right.replace('m9 18 6-6-6-6', 'M18 6 6 18M6 6l12 12') + '</button>' : '<span class="btn btn-muted"' + tid + '>Annulation close (moins de 2 h)</span>';
    if (s.is_waitlisted) return '<button class="btn btn-outline" data-action="waitlist_leave" data-id="' + s.id + '"' + tid + '>Quitter la liste d’attente</button>';
    if (s.remaining <= 0) return '<button class="btn btn-outline" data-action="waitlist_join" data-id="' + s.id + '"' + tid + '>Liste d’attente</button>';
    return '<button class="btn btn-primary" data-action="book" data-id="' + s.id + '"' + tid + '>Réserver ' + ATX.icons.right + '</button>';
  }

  function updateCard(s) {
    const card = list.querySelector('.sess-card[data-id="' + s.id + '"]');
    if (!card) return;
    card.dataset.remaining = s.remaining;
    card.classList.toggle('is-booked', s.is_booked);
    card.querySelector('[data-role="count"] span').textContent = s.remaining + ' / ' + s.capacity;
    card.querySelector('.sess-action').innerHTML = buttonHtml(s);
    card.classList.add('flash');
    setTimeout(() => card.classList.remove('flash'), 700);
    apply();
  }

  async function handle(ev) {
    const btn = ev.target.closest('[data-action]');
    if (!btn) return;
    btn.disabled = true;
    if (btn.dataset.action === 'waitlist_join' || btn.dataset.action === 'waitlist_leave') {
      try {
        await ATX.api(btn.dataset.action, {id: btn.dataset.id}, 'POST');
        const card = btn.closest('.sess-card');
        if (card) {
          card.dataset.waitlisted = btn.dataset.action === 'waitlist_join' ? '1' : '0';
          btn.outerHTML = buttonHtml({id:btn.dataset.id, is_waitlisted:card.dataset.waitlisted === '1', is_booked:false, remaining:Number(card.dataset.remaining), is_past:false});
        }
      } catch (error) { alert(error.message); btn.disabled = false; }
      return;
    }
    const s = await ATX.bookAction(btn.dataset.action, btn.dataset.id);
    if (s) {
      updateCard(s);
      loadMy();
    } else {
      btn.disabled = false;
    }
  }
  list.addEventListener('click', handle);
  myList.addEventListener('click', handle);

  /* ---------- Mes réservations ---------- */
  async function loadMy() {
    try {
      const items = await ATX.api('my');
      myCount.textContent = items.length;
      if (!items.length) {
        myList.innerHTML = '<p class="empty" data-testid="my-bookings-empty">Aucune réservation à venir. Choisissez une séance ci-dessus !</p>';
        return;
      }
      myList.innerHTML = items
        .map((s) => {
          const c = ATX.categories[s.category];
          return (
            '<div class="my-row" style="--c:' + s.color + '" data-testid="my-booking-' + s.id + '">' +
            '<span class="my-ic">' + c.svg + '</span>' +
            '<div class="my-main"><strong>' + ATX.esc(s.title) + '</strong>' +
            '<small>' + ATX.frDate(s.start, { weekday: 'short', day: 'numeric', month: 'short' }) + ' · ' + ATX.hm(s.start) + ' – ' + ATX.hm(s.end) + ' · Coach ' + ATX.esc(s.coach) + '</small></div>' +
            '<a class="btn btn-ghost btn-sm" href="' + ATX.reservationsUrl + '&date=' + encodeURIComponent(s.date) + '" data-testid="my-booking-goto-' + s.id + '">Voir</a>' +
            (s.can_cancel ? '<button class="btn btn-danger btn-sm" data-action="cancel" data-id="' + s.id + '" data-testid="my-booking-cancel-' + s.id + '">Annuler</button>' : '<span class="btn btn-muted btn-sm">Annulation close</span>') +
            '</div>'
          );
        })
        .join('');
    } catch (e) {
      myList.innerHTML = '<p class="empty">Vos réservations sont indisponibles pour le moment.</p>';
    }
  }

  apply();
  loadMy();

})();

</script>
<?php page_foot(); ?>
