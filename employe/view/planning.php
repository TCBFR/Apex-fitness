<?php
/**
 * ATHLETIX — Planning, vue EMPLOYÉ  (planning/view/planning_employe.php)
 *
 * Même gabarit que planning.php (barre d'outils, calendrier FullCalendar, filtres,
 * légende, modale de séance) mais orienté gestion de la salle.
 * Branchée sur PlanningController :
 *   - séances       : flux JSON planning/events.php (celui de FullCalendar)
 *   - création      : action « creer_creneau »    (titre, type, salle, coach, debut, duree,
 *                     capacite, repeter, jusqu_au)
 *   - suppression   : action « supprimer_creneau » (creneau, portee = un | serie)
 *   - listes du formulaire : $planningDonnees['salles' | 'coachs' | 'titres']
 *
 * Intégration dans employe/espace.php :
 *   1. ligne « $mode = 'employe'; require … planning/view/planning.php »
 *      → require __DIR__ . '/../planning/view/planning_employe.php';
 *   2. onglet « creneaux », en bas de page : supprimer le <script> assets/js/planning.js
 *      (cette vue embarque son script) ; garder FC_SCRIPTS (FullCalendar + locale fr).
 */

$PE = [
    'events_url'       => url('planning/events.php'),
    'action_creer'     => 'creer_creneau',
    'action_supprimer' => 'supprimer_creneau',
];

/* ---- Types d'activité (PlanningController::TYPES) + couleurs ------------- */
$palette = ['#CCFF00', '#4DA3FF', '#FF7A59', '#B07CFF', '#2EC4B6', '#FFC857', '#F06292', '#8BC34A'];
$types = [];
$n = 0;
foreach (PlanningController::TYPES as $cle => $libelle) {
    $types[$cle] = ['label' => $libelle, 'color' => $palette[$n++ % count($palette)]];
}

/* ---- Listes du formulaire (tolérant sur les noms de colonnes) ------------ */
$pick = static function (array $row, array $keys, $default = null) {
    foreach ($keys as $key) {
        if (isset($row[$key]) && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return $default;
};
$premierTexte = static function (array $row): string {
    foreach ($row as $v) {
        if (is_string($v) && $v !== '' && !is_numeric($v)) {
            return $v;
        }
    }
    return '';
};

$salles = [];
foreach ((array) ($planningDonnees['salles'] ?? []) as $row) {
    if (!is_array($row) || !$row) {
        continue;
    }
    $id  = (int) $pick($row, ['Numsalle', 'num_salle', 'id'], reset($row));
    $nom = (string) $pick($row, ['nomsalle', 'nom', 'libelle', 'libellesalle'], $premierTexte($row));
    $cap = (int) $pick($row, ['capacite', 'capacite_max', 'capacite_salle'], 0);
    if ($id > 0 && $nom !== '') {
        $salles[] = ['id' => $id, 'nom' => $nom, 'capacite' => $cap];
    }
}

$coachs = [];
foreach ((array) ($planningDonnees['coachs'] ?? []) as $row) {
    if (!is_array($row) || !$row) {
        continue;
    }
    $id     = (int) $pick($row, ['NumUsers', 'Numcoach', 'id'], reset($row));
    $prenom = (string) $pick($row, ['prenomUsers', 'prenom'], '');
    $nom    = (string) $pick($row, ['nomUsers', 'nom'], '');
    $label  = trim($prenom . ' ' . $nom);
    if ($label === '') {
        $label = (string) $pick($row, ['nom_complet', 'libelle'], $premierTexte($row));
    }
    if ($id > 0 && $label !== '') {
        $coachs[] = ['id' => $id, 'prenom' => $prenom !== '' ? $prenom : $label, 'label' => $label];
    }
}

$titres = [];
foreach ((array) ($planningDonnees['titres'] ?? []) as $t) {
    $v = is_array($t) ? (string) (reset($t) ?: '') : (string) $t;
    if ($v !== '') {
        $titres[] = $v;
    }
}
$titres = array_values(array_unique($titres));

$cfg = ['eventsUrl' => $PE['events_url'], 'types' => $types];
?>
<div class="pe" id="pe-root" data-testid="planning-employe">
  <style>
    .pe [hidden]{display:none !important}
    .pe-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px}
    .pe-stat{padding:14px 16px;border-radius:12px;border:1px solid rgba(128,128,128,.3)}
    .pe-stat b{display:block;font-size:26px;line-height:1.15}
    .pe-stat span{font-size:13px;opacity:.7}
    .pe-stat.is-warn b{color:#c2410c}
    .pe-stat.is-bad b{color:#b91c1c}
    .pe .date-nav{flex-wrap:wrap;gap:10px}
    .pe-hint{margin:10px 2px 0;font-size:13px;opacity:.7}
    .pe-ev-count{display:block;margin-top:2px;font-size:11px;font-weight:600;opacity:.9}
    .atx-ev.is-low .pe-ev-count{opacity:.75}
    .atx-ev.is-full .pe-ev-count{text-decoration:underline}
    .pe-row{display:flex;gap:10px;align-items:flex-start;width:100%;padding:9px 10px;margin-top:8px;text-align:left;
      border:1px solid rgba(128,128,128,.3);border-left:4px solid var(--c,#CCFF00);border-radius:8px;background:transparent;color:inherit;font:inherit;cursor:pointer}
    .pe-row:hover{background:rgba(128,128,128,.1)}
    .pe-row b{min-width:44px}
    .pe-row span{display:grid;gap:3px;flex:1}
    .pe-row small{opacity:.7}
    .pe-mini{display:block;height:5px;border-radius:3px;background:rgba(128,128,128,.25);overflow:hidden}
    .pe-mini u{display:block;height:100%;background:var(--c,#CCFF00);text-decoration:none}
    .pe-actions{display:flex;flex-wrap:wrap;align-items:flex-end;gap:8px;margin-top:16px}
    .pe-actions form{margin:0 0 0 auto;display:grid;gap:6px;justify-items:end}
    .pe-actions select{padding:7px 9px;border:1px solid rgba(128,128,128,.4);border-radius:8px;background:transparent;color:inherit;font:inherit;font-size:13px}
    .pe-form{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
    .pe-field{display:grid;gap:5px;font-size:13px}
    .pe-field.full{grid-column:1/-1}
    .pe-field input,.pe-field select{width:100%;padding:9px 11px;border:1px solid rgba(128,128,128,.4);border-radius:8px;background:transparent;color:inherit;font:inherit}
    .pe-check{grid-column:1/-1;display:flex;align-items:center;gap:8px;font-size:14px}
    .pe-warn{grid-column:1/-1;margin:0;padding:9px 11px;border-radius:8px;font-size:13px;background:rgba(234,88,12,.12);color:#c2410c}
    .pe-info{grid-column:1/-1;margin:0;font-size:13px;opacity:.8}
    .pe-form-actions{grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px}
    @media (max-width:560px){.pe-form{grid-template-columns:1fr}}
  </style>

  <section class="pe-stats" data-testid="planning-stats">
    <div class="pe-stat"><b id="pe-stat-count" data-testid="stat-sessions">0</b><span>séances sur la période</span></div>
    <div class="pe-stat"><b id="pe-stat-fill" data-testid="stat-fill">—</b><span>taux de remplissage moyen</span></div>
    <div class="pe-stat is-warn"><b id="pe-stat-full" data-testid="stat-full">0</b><span>séances complètes</span></div>
    <div class="pe-stat is-bad"><b id="pe-stat-low" data-testid="stat-low">0</b><span>séances peu remplies (&lt; 30&nbsp;%)</span></div>
  </section>

  <div class="layout-2">
    <div class="col-main">
      <section class="panel toolbar" data-testid="planning-toolbar">
        <div class="view-tabs" role="tablist">
          <button type="button" class="vt is-active" data-pe-view="timeGridWeek" data-testid="view-week-button">Semaine</button>
          <button type="button" class="vt" data-pe-view="dayGridMonth" data-testid="view-month-button">Mois</button>
          <button type="button" class="vt" data-pe-view="timeGridDay" data-testid="view-day-button">Jour</button>
        </div>
        <div class="date-nav">
          <button type="button" class="btn btn-ghost btn-sm" data-pe-nav="today" data-testid="calendar-today-button">Aujourd’hui</button>
          <div class="date-box">
            <button type="button" class="icon-btn" data-pe-nav="prev" data-testid="calendar-prev-button" aria-label="Précédent">‹</button>
            <span class="date-label"><span id="pe-cal-title" data-testid="calendar-title"></span></span>
            <button type="button" class="icon-btn" data-pe-nav="next" data-testid="calendar-next-button" aria-label="Suivant">›</button>
          </div>
          <button type="button" class="btn btn-primary btn-vert btn-sm" id="pe-create-btn" data-testid="create-session-button"><?= !empty($accueil) ? '+ Ajouter un cours' : '+ Nouveau créneau' ?></button>
        </div>
      </section>

      <section class="panel cal-wrap">
        <div id="pe-calendar" data-testid="planning-calendar"></div>
        <div class="event-tooltip" id="pe-tooltip" role="tooltip" hidden></div>
        <p class="cal-empty" id="pe-empty" hidden data-testid="calendar-empty-state">Aucune séance ne correspond à vos filtres sur cette période.</p>
      </section>
      <p class="pe-hint">Astuce : cliquez sur une plage libre du calendrier pour préparer un nouveau créneau à cette date et à cette heure.</p>
    </div>

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
  </div>

  <!-- Modale : détail d'une séance -->
  <div class="modal" id="pe-session-modal" hidden data-testid="session-modal">
    <div class="modal-backdrop" data-pe-close></div>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="pe-modal-title">
      <button type="button" class="icon-btn modal-close" data-pe-close data-testid="session-modal-close" aria-label="Fermer">×</button>
      <div class="modal-img" id="pe-modal-img"></div>
      <div class="modal-body">
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
          <button type="button" class="btn btn-outline btn-sm" id="pe-dup" data-testid="session-duplicate-button">Dupliquer (+ 7 jours)</button>
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

  <!-- Modale : nouveau créneau -->
  <div class="modal" id="pe-create-modal" hidden data-testid="create-modal">
    <div class="modal-backdrop" data-pe-close></div>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="pe-create-title">
      <button type="button" class="icon-btn modal-close" data-pe-close data-testid="create-modal-close" aria-label="Fermer">×</button>
      <div class="modal-body">
        <h2 id="pe-create-title">Nouveau créneau</h2>
        <form method="post" class="pe-form" id="pe-create-form" data-testid="create-form">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="<?= e($PE['action_creer']) ?>">
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
          <label class="pe-check">
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
            <button type="submit" class="btn btn-primary btn-vert btn-sm" data-testid="create-submit">Créer le créneau</button>
          </div>
        </form>
        <datalist id="pe-dl-titres"><?php foreach ($titres as $v): ?><option value="<?= e($v) ?>"><?php endforeach; ?></datalist>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="pe-config"><?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?></script>
<script>
/* ATHLETIX — Planning employé (FullCalendar v6, flux planning/events.php) */
(function () {
  function init() {
    const root = document.getElementById('pe-root');
    const $ = (id) => document.getElementById(id);
    const EMPTY_TXT = $('pe-empty').textContent;
    if (!window.FullCalendar) {
      $('pe-empty').hidden = false;
      $('pe-empty').textContent = 'Le calendrier n’a pas pu être chargé. Vérifiez votre connexion puis rechargez la page.';
      return;
    }
    const CFG = JSON.parse($('pe-config').textContent);
    const TYPES = CFG.types;
    const FIRST_TYPE = Object.keys(TYPES)[0];

    /* ---------- Helpers ---------- */
    const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pad = (n) => String(n).padStart(2, '0');
    const D = (v) => (v instanceof Date ? v : new Date(v));
    const hm = (v) => pad(D(v).getHours()) + ':' + pad(D(v).getMinutes());
    const ymd = (v) => D(v).getFullYear() + '-' + pad(D(v).getMonth() + 1) + '-' + pad(D(v).getDate());
    const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);
    const frDate = (v, o) => cap(D(v).toLocaleDateString('fr-FR', o));
    const addMin = (t, m) => { const p = t.split(':').map(Number); const tot = Math.min(23 * 60 + 59, p[0] * 60 + p[1] + m); return pad(Math.floor(tot / 60)) + ':' + pad(tot % 60); };
    const plural = (n, w) => n + ' ' + w + (n > 1 ? 's' : '');

    /* ---------- Séances (flux planning/events.php) ---------- */
    const known = new Map(); // toutes les séances déjà chargées, par id (pour la détection de conflits)

    function toSession(ev) {
      const p = ev.extendedProps || {};
      const type = TYPES[p.type] ? p.type : FIRST_TYPE;
      const capacite = Number(p.capacite) || 0;
      const prises = Number(p.prises) || 0;
      const s = {
        id: ev.id, title: ev.title, start: ev.start, end: ev.end,
        type: type, typeLabel: p.typeLibelle || TYPES[type].label, color: TYPES[type].color,
        salle: p.salle || '', coach: p.coach || '',
        capacite: capacite, prises: prises,
        restantes: p.restantes != null ? Number(p.restantes) : Math.max(0, capacite - prises),
        serie: !!p.serie, passe: !!p.passe,
      };
      known.set(String(s.id), s);
      return s;
    }
    function fetchSessions(startIso, endIso) {
      const sep = CFG.eventsUrl.indexOf('?') === -1 ? '?' : '&';
      const url = CFG.eventsUrl + sep + 'start=' + encodeURIComponent(startIso) + '&end=' + encodeURIComponent(endIso);
      return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then((r) => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then((list) => { if (!Array.isArray(list)) throw new Error('Réponse inattendue'); return list.map(toSession); });
    }

    const pct = (s) => (s.capacite > 0 ? Math.min(100, Math.round((s.prises / s.capacite) * 100)) : 0);
    const isFull = (s) => s.capacite > 0 && s.restantes === 0;
    const isLow = (s) => !s.passe && s.capacite > 0 && pct(s) < 30;

    /* ---------- Filtres ---------- */
    const state = { type: 'all', room: 'all', coach: 'all', fill: 'all', hidePast: false };
    const fillOk = {
      all: () => true,
      free: (s) => s.restantes > 0 && !s.passe,
      almost: (s) => s.restantes > 0 && pct(s) >= 80 && !s.passe,
      full: isFull,
      low: isLow,
    };
    const matches = (s) =>
      (state.type === 'all' || s.type === state.type) &&
      (state.room === 'all' || s.salle === state.room) &&
      (state.coach === 'all' || s.coach === state.coach) &&
      (!state.hidePast || !s.passe) &&
      fillOk[state.fill](s);

    const coachSel = $('pe-filter-coach');
    const seenCoachs = new Set();
    function syncCoachOptions(list) {
      let added = false;
      list.forEach((s) => { if (s.coach && !seenCoachs.has(s.coach)) { seenCoachs.add(s.coach); added = true; } });
      if (!added) return;
      const keep = coachSel.value;
      coachSel.length = 1;
      Array.from(seenCoachs).sort((a, b) => a.localeCompare(b, 'fr')).forEach((n) => {
        const o = document.createElement('option');
        o.value = n;
        o.textContent = 'Coach ' + n;
        coachSel.append(o);
      });
      coachSel.value = keep;
    }

    /* ---------- Indicateurs de la période affichée ---------- */
    function updateStats(list) {
      const cp = list.reduce((a, s) => a + s.capacite, 0);
      const bk = list.reduce((a, s) => a + Math.min(s.prises, s.capacite || s.prises), 0);
      $('pe-stat-count').textContent = list.length;
      $('pe-stat-fill').textContent = cp > 0 ? Math.round((bk / cp) * 100) + ' %' : '—';
      $('pe-stat-full').textContent = list.filter(isFull).length;
      $('pe-stat-low').textContent = list.filter(isLow).length;
    }

    /* ---------- Info-bulle ---------- */
    const tip = $('pe-tooltip');
    function showTip(info) {
      const s = info.event.extendedProps.s;
      tip.style.setProperty('--c', s.color);
      const lines = [
        frDate(s.start, { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' + hm(s.start) + ' – ' + hm(s.end),
        s.coach ? 'Coach ' + s.coach : '',
        s.salle,
        s.prises + ' / ' + s.capacite + ' inscrits (' + pct(s) + ' %)',
      ].filter(Boolean);
      const h = document.createElement('strong');
      h.textContent = s.title;
      tip.replaceChildren(h);
      lines.forEach((t) => { const l = document.createElement('span'); l.textContent = t; tip.append(l); });
      const b = document.createElement('b');
      b.textContent = s.passe ? 'Séance passée' : isFull(s) ? 'Séance complète' : 'Cliquez pour gérer';
      tip.append(b);
      tip.hidden = false;
      const r = info.el.getBoundingClientRect();
      tip.style.left = Math.max(12, Math.min(r.left, window.innerWidth - tip.offsetWidth - 12)) + 'px';
      tip.style.top = Math.max(12, Math.min(r.top, window.innerHeight - tip.offsetHeight - 12)) + 'px';
    }
    const hideTip = () => { tip.hidden = true; };

    /* ---------- Modales ---------- */
    function openModal(m) {
      clearTimeout(m._t);
      m.hidden = false;
      requestAnimationFrame(() => m.classList.add('is-open'));
    }
    function closeModal(m) {
      m.classList.remove('is-open');
      m._t = setTimeout(() => { m.hidden = true; }, 200);
    }
    const sessionModal = $('pe-session-modal');
    const createModal = $('pe-create-modal');
    root.querySelectorAll('[data-pe-close]').forEach((el) => el.addEventListener('click', () => closeModal(el.closest('.modal'))));
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      [sessionModal, createModal].forEach((m) => { if (!m.hidden) closeModal(m); });
    });

    /* ---------- Modale séance ---------- */
    let cur = null;
    const scope = $('pe-scope');
    function openSession(s) {
      cur = s;
      sessionModal.style.setProperty('--c', s.color);
      $('pe-modal-img').style.backgroundImage = 'linear-gradient(135deg,' + s.color + ',#111)';
      $('pe-modal-cat').textContent = s.typeLabel;
      $('pe-modal-title').textContent = s.title;
      $('pe-modal-date').textContent = frDate(s.start, { weekday: 'long', day: 'numeric', month: 'long' }) + ' · ' + hm(s.start) + ' – ' + hm(s.end);
      $('pe-modal-coach').textContent = s.coach ? 'Coach ' + s.coach : 'Coach non renseigné';
      $('pe-modal-room').textContent = s.salle || 'Salle non renseignée';
      $('pe-modal-serie').textContent = s.serie ? 'Série hebdomadaire' : 'Créneau unique';
      $('pe-modal-places').textContent =
        s.prises + ' / ' + s.capacite + ' · ' + s.restantes + ' place' + (s.restantes > 1 ? 's' : '') + ' restante' + (s.restantes > 1 ? 's' : '');
      $('pe-modal-bar').style.width = pct(s) + '%';

      $('pe-delete-id').value = s.id;
      scope.value = 'un';
      scope.hidden = !s.serie;
      const del = $('pe-delete-btn');
      del.disabled = s.passe;
      del.textContent = s.passe ? 'Séance passée' : 'Supprimer';
      openModal(sessionModal);
    }

    $('pe-delete-form').addEventListener('submit', (e) => {
      if (!cur) { e.preventDefault(); return; }
      const serie = scope.value === 'serie';
      let msg = serie
        ? 'Supprimer toutes les occurrences à venir de « ' + cur.title + ' » ?'
        : 'Supprimer le créneau « ' + cur.title + ' » du ' + frDate(cur.start, { day: 'numeric', month: 'long' }) + ' à ' + hm(cur.start) + ' ?';
      if (serie || cur.prises > 0) {
        msg += '\n\nLes réservations associées seront supprimées' + (!serie && cur.prises > 0 ? ' (' + plural(cur.prises, 'inscrit') + ').' : '.');
      }
      if (!confirm(msg)) e.preventDefault();
    });

    $('pe-dup').addEventListener('click', () => {
      if (!cur) return;
      const s = cur;
      const next = new Date(s.start);
      next.setDate(next.getDate() + 7);
      closeModal(sessionModal);
      openCreate({
        title: s.title, type: s.type, salleNom: s.salle, coachPrenom: s.coach, capacite: s.capacite,
        debut: ymd(next) + 'T' + hm(s.start),
        duree: Math.max(15, Math.round((D(s.end) - D(s.start)) / 60000)),
      });
    });

    /* ---------- Modale création ---------- */
    const form = $('pe-create-form');
    const el = form.elements;
    const conflictBox = $('pe-conflict');
    const errBox = $('pe-form-error');
    const previewBox = $('pe-preview');
    const untilWrap = $('pe-until-wrap');

    function selectByData(sel, key, val) {
      sel.value = '';
      if (!val) return;
      for (const o of sel.options) { if (o.dataset[key] === val) { sel.value = o.value; return; } }
    }

    function occurrenceCount() {
      if (!el.repeter.checked) return 1;
      const v = el.debut.value, u = el.jusqu_au.value;
      if (!v || !u) return 0;
      const first = new Date(v), limit = new Date(u + 'T23:59:59');
      if (isNaN(first) || limit < first) return 0;
      let n = 0;
      for (const d = new Date(first); d <= limit && n < 200; d.setDate(d.getDate() + 7)) n++;
      return n;
    }
    function refreshPreview() {
      const on = el.repeter.checked;
      untilWrap.hidden = !on;
      el.jusqu_au.required = on;
      previewBox.hidden = !on;
      if (!on) return;
      const n = occurrenceCount();
      previewBox.textContent = n === 0
        ? 'Indiquez une date de fin postérieure au début.'
        : n > 52 ? n + ' créneaux : la répétition est limitée à 52 semaines.' : (n > 1 ? n + ' créneaux seront créés' : '1 créneau sera créé') + ' (un par semaine).';
    }

    function conflictsFor() {
      const v = el.debut.value;
      const dur = parseInt(el.duree.value, 10);
      if (!v || !(dur > 0)) return [];
      const a = new Date(v);
      if (isNaN(a)) return [];
      const b = new Date(a.getTime() + dur * 60000);
      const so = el.salle.selectedOptions[0], co = el.coach.selectedOptions[0];
      const salle = (so && so.dataset.nom) || '', coach = (co && co.dataset.prenom) || '';
      return Array.from(known.values()).filter((s) =>
        D(s.start) < b && D(s.end) > a && ((salle && s.salle === salle) || (coach && s.coach === coach))
      );
    }
    function refreshConflicts() {
      const list = conflictsFor();
      conflictBox.hidden = list.length === 0;
      conflictBox.textContent = list.length
        ? 'Salle ou coach déjà pris sur ce créneau : ' + list.map((s) => '« ' + s.title + ' » (' + hm(s.start) + '–' + hm(s.end) + ')').join(' ; ')
        : '';
    }
    function refreshAll() { refreshPreview(); refreshConflicts(); }

    ['debut', 'duree', 'salle', 'coach', 'jusqu_au'].forEach((n) => el[n].addEventListener('input', refreshAll));
    el.repeter.addEventListener('change', () => {
      if (el.repeter.checked && !el.jusqu_au.value && el.debut.value) {
        const d = new Date(el.debut.value);
        d.setDate(d.getDate() + 28);
        el.jusqu_au.value = ymd(d);
      }
      refreshAll();
    });
    el.salle.addEventListener('change', () => {
      const o = el.salle.selectedOptions[0];
      const max = o ? parseInt(o.dataset.capacite, 10) : 0;
      if (max > 0) {
        el.capacite.max = max;
        if (Number(el.capacite.value) > max) el.capacite.value = max;
      } else {
        el.capacite.removeAttribute('max');
      }
    });

    function openCreate(p) {
      p = p || {};
      form.reset();
      errBox.hidden = true;
      el.titre.value = p.title || '';
      if (p.type && TYPES[p.type]) el.type.value = p.type;
      selectByData(el.salle, 'nom', p.salleNom);
      selectByData(el.coach, 'prenom', p.coachPrenom);
      el.salle.dispatchEvent(new Event('change'));
      el.capacite.value = p.capacite || 15;
      if (el.capacite.max && Number(el.capacite.value) > Number(el.capacite.max)) el.capacite.value = el.capacite.max;
      el.debut.value = p.debut || ymd(new Date()) + 'T18:00';
      el.duree.value = p.duree || 60;
      refreshAll();
      openModal(createModal);
      el.titre.focus();
    }
    $('pe-create-btn').addEventListener('click', () => openCreate());

    form.addEventListener('submit', (e) => {
      const fail = (msg) => { e.preventDefault(); errBox.textContent = msg; errBox.hidden = false; };
      errBox.hidden = true;
      const debut = new Date(el.debut.value);
      if (isNaN(debut) || debut < new Date()) return fail('La date de début doit être dans le futur.');
      if (el.repeter.checked) {
        const n = occurrenceCount();
        if (n === 0) return fail('Indiquez une date de fin de répétition postérieure au début.');
        if (n > 52) return fail('Répétition limitée à 52 semaines.');
      }
    });

    /* ---------- Séances du jour ---------- */
    function renderToday() {
      const box = $('pe-today');
      const t0 = new Date();
      const t1 = new Date(t0.getFullYear(), t0.getMonth(), t0.getDate() + 1);
      fetchSessions(ymd(t0) + 'T00:00:00', ymd(t1) + 'T00:00:00')
        .then((list) => {
          list = list.filter((s) => ymd(s.start) === ymd(t0)).sort((a, b) => a.start.localeCompare(b.start));
          syncCoachOptions(list);
          if (!list.length) { box.innerHTML = '<p class="muted">Aucune séance aujourd’hui.</p>'; return; }
          box.replaceChildren();
          list.forEach((s) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'pe-row';
            b.style.setProperty('--c', s.color);
            b.setAttribute('data-testid', 'today-session-' + s.id);
            b.innerHTML = '<b>' + hm(s.start) + '</b><span><strong>' + esc(s.title) + '</strong><small>' + (s.salle ? 'Salle ' + esc(s.salle) + ' · ' : '') + s.prises + ' / ' + s.capacite +
              ' inscrits</small><i class="pe-mini"><u style="width:' + pct(s) + '%"></u></i></span>';
            b.addEventListener('click', () => openSession(s));
            box.append(b);
          });
        })
        .catch(() => { box.innerHTML = '<p class="muted">Séances du jour indisponibles.</p>'; });
    }

    /* ---------- Calendrier ---------- */
    const calendar = new FullCalendar.Calendar($('pe-calendar'), {
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
        fetchSessions(info.startStr, info.endStr)
          .then((all) => {
            const list = all.filter(matches);
            syncCoachOptions(all);
            $('pe-empty').textContent = EMPTY_TXT;
            $('pe-empty').hidden = list.length > 0;
            updateStats(list);
            success(list.map((s) => ({ id: String(s.id), title: s.title, start: s.start, end: s.end, extendedProps: { s: s } })));
          })
          .catch((err) => {
            $('pe-empty').hidden = false;
            $('pe-empty').textContent = 'Impossible de charger le planning (' + (err && err.message ? err.message : 'erreur réseau') + ').';
            updateStats([]);
            failure(err);
          });
      },
      dayHeaderContent: (arg) => {
        const dow = cap(arg.date.toLocaleDateString('fr-FR', { weekday: 'short' }));
        if (arg.view.type === 'dayGridMonth') return { html: '<span class="dh-dow">' + dow + '</span>' };
        const date = arg.date.getDate() + ' ' + arg.date.toLocaleDateString('fr-FR', { month: 'long' });
        return { html: '<span class="dh-dow">' + dow + '</span><span class="dh-date">' + date + '</span>' };
      },
      eventClassNames: (arg) => {
        const s = arg.event.extendedProps.s;
        return ['atx-ev', isFull(s) && 'is-full', isLow(s) && 'is-low', s.passe && 'is-past'].filter(Boolean);
      },
      eventContent: (arg) => {
        const s = arg.event.extendedProps.s;
        const count = s.prises + '/' + s.capacite;
        if (arg.view.type === 'dayGridMonth') {
          return { html: '<div class="ev-m"><i></i><b>' + hm(s.start) + '</b><span>' + esc(s.title) + (s.salle ? ' · ' + esc(s.salle) : '') + ' · ' + count + '</span></div>' };
        }
        return {
          html: '<div class="ev"><strong>' + (s.serie ? '↻ ' : '') + esc(s.title) + '</strong><span>' + hm(s.start) + ' – ' + hm(s.end) + '</span>' +
            (s.coach ? '<span class="ev-coach">Coach ' + esc(s.coach) + '</span>' : '') +
            (s.salle ? '<span class="ev-room">Salle ' + esc(s.salle) + '</span>' : '') +
            '<span class="pe-ev-count">' + count + ' inscrits</span></div>',
        };
      },
      eventDidMount: (arg) => {
        arg.el.style.setProperty('--c', arg.event.extendedProps.s.color);
        arg.el.setAttribute('data-testid', 'calendar-event-' + arg.event.id);
      },
      eventMouseEnter: showTip,
      eventMouseLeave: hideTip,
      eventClick: (info) => {
        info.jsEvent.preventDefault();
        hideTip();
        openSession(info.event.extendedProps.s);
      },
      dateClick: (info) => {
        const debut = info.allDay ? '18:00' : hm(info.date);
        openCreate({ debut: ymd(info.date) + 'T' + debut });
      },
      datesSet: (arg) => {
        $('pe-cal-title').textContent = arg.view.title;
        root.querySelectorAll('[data-pe-view]').forEach((b) => b.classList.toggle('is-active', b.dataset.peView === arg.view.type));
      },
    });
    calendar.render();
    renderToday();

    root.querySelectorAll('[data-pe-view]').forEach((b) => b.addEventListener('click', () => calendar.changeView(b.dataset.peView)));
    root.querySelectorAll('[data-pe-nav]').forEach((b) => b.addEventListener('click', () => calendar[b.dataset.peNav]()));

    /* ---------- Filtres ---------- */
    root.querySelectorAll('[data-pe-type]').forEach((b) =>
      b.addEventListener('click', () => {
        state.type = b.dataset.peType;
        root.querySelectorAll('[data-pe-type]').forEach((x) => x.classList.toggle('is-active', x === b));
        calendar.refetchEvents();
      })
    );
    coachSel.addEventListener('change', (e) => { state.coach = e.target.value; calendar.refetchEvents(); });
    const roomFilter = $('pe-filter-room');
    if (roomFilter) roomFilter.addEventListener('change', (e) => { state.room = e.target.value; calendar.refetchEvents(); });
    $('pe-filter-fill').addEventListener('change', (e) => { state.fill = e.target.value; calendar.refetchEvents(); });
    $('pe-filter-past').addEventListener('change', (e) => { state.hidePast = e.target.checked; calendar.refetchEvents(); });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>
