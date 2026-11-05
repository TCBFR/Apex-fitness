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
    const TYPE_ICONS = {
      cross: 'fa-dumbbell', pilates: 'fa-spa', abdos: 'fa-heart-pulse',
      boxe: 'fa-hand-fist', danse: 'fa-music', yoga: 'fa-leaf',
      cardio: 'fa-heart-pulse', musculation: 'fa-dumbbell',
    };

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
        salle: p.salle || '', roomId: Number(p.roomId) || 0,
        coach: p.coach || '', coachFirstName: p.coachFirstName || p.coach || '',
        coachId: Number(p.coachId) || 0,
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
      if (!$('pe-stat-count')) return;
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
      if (root.classList.contains('pe-reception')) {
        root.classList.toggle('is-creating', m === createModal);
        root.classList.toggle('is-viewing', m === sessionModal);
      }
      requestAnimationFrame(() => m.classList.add('is-open'));
    }
    function closeModal(m) {
      m.classList.remove('is-open');
      m._t = setTimeout(() => {
        m.hidden = true;
        if (m === createModal) root.classList.remove('is-creating');
        if (m === sessionModal) root.classList.remove('is-viewing');
      }, 200);
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
      del.textContent = s.passe ? 'Séance passée' : (root.classList.contains('pe-reception') ? 'Annuler le créneau' : 'Supprimer');
      const editButton = $('pe-edit-btn');
      if (editButton) editButton.hidden = s.passe;
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

    $('pe-dup')?.addEventListener('click', () => {
      if (!cur) return;
      const s = cur;
      const next = new Date(s.start);
      next.setDate(next.getDate() + 7);
      closeModal(sessionModal);
      openCreate({
        title: s.title, type: s.type, salleNom: s.salle, coachPrenom: s.coachFirstName, capacite: s.capacite,
        debut: ymd(next) + 'T' + hm(s.start),
        duree: Math.max(15, Math.round((D(s.end) - D(s.start)) / 60000)),
      });
    });

    const editButton = $('pe-edit-btn');
    if (editButton) {
      editButton.addEventListener('click', () => {
        if (!cur || cur.passe) return;
        const s = cur;
        closeModal(sessionModal);
        openCreate({
          edit: true, id: s.id, title: s.title, type: s.type,
          salleId: s.roomId, coachId: s.coachId, capacite: s.capacite, prises: s.prises,
          debut: ymd(s.start) + 'T' + hm(s.start),
          duree: Math.max(15, Math.round((D(s.end) - D(s.start)) / 60000)),
        });
      });
    }

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
      const so = el.salle.selectedOptions[0];
      const salle = (so && so.dataset.nom) || '', coach = el.coach.value;
      const currentId = $('pe-edit-id').value;
      return Array.from(known.values()).filter((s) =>
        String(s.id) !== currentId
        && D(s.start) < b && D(s.end) > a
        && ((salle && s.salle === salle) || (coach && String(s.coachId) === coach))
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
      const editing = p.edit === true;
      $('pe-create-action').value = editing ? 'modifier_creneau' : CFG.createAction;
      $('pe-edit-id').value = editing ? String(p.id) : '';
      $('pe-create-title').textContent = editing ? 'Modifier le créneau' : 'Nouveau créneau';
      $('pe-create-submit').textContent = editing ? 'Enregistrer les modifications' : 'Créer le créneau';
      $('pe-repeat-field').hidden = editing;
      el.repeter.disabled = editing;
      el.capacite.min = editing ? String(Math.max(1, Number(p.prises) || 0)) : '1';
      el.titre.value = p.title || '';
      if (p.type && TYPES[p.type]) el.type.value = p.type;
      if (editing) {
        el.salle.value = String(p.salleId || '');
        el.coach.value = String(p.coachId || '');
      } else {
        selectByData(el.salle, 'nom', p.salleNom);
        selectByData(el.coach, 'prenom', p.coachPrenom);
      }
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
      if (!box) return;
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
      slotMinTime: CFG.slotMinTime,
      slotMaxTime: CFG.slotMaxTime,
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
          html: '<div class="ev"><strong><i class="fa-solid ' + (TYPE_ICONS[s.type] || 'fa-dumbbell') + '" aria-hidden="true"></i>' + (s.serie ? '↻ ' : '') + esc(s.title) + '</strong><span>' + hm(s.start) + ' – ' + hm(s.end) + '</span>' +
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
    const typeFilter = $('pe-filter-type');
    if (typeFilter) typeFilter.addEventListener('change', (e) => { state.type = e.target.value; calendar.refetchEvents(); });
    const fillFilter = $('pe-filter-fill');
    if (fillFilter) fillFilter.addEventListener('change', (e) => { state.fill = e.target.value; calendar.refetchEvents(); });
    const pastFilter = $('pe-filter-past');
    if (pastFilter) pastFilter.addEventListener('change', (e) => { state.hidePast = e.target.checked; calendar.refetchEvents(); });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
