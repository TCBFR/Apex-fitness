<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';
require_once dirname(__DIR__) . '/model/AdherentEspace.php';
require_once dirname(__DIR__) . '/controller/AdherentController.php';
$perf = atx_performance_data();
$next = atx_next_session();
$member = (new AdherentEspace())->profil((int)utilisateur()['NumUsers']);
$objectives = (new AdherentController())->objectifs((int)utilisateur()['NumUsers'], 12);
page_head('performances', 'Mes performances');
$goalPct = $perf['goal']['target'] > 0 ? round($perf['goal']['done'] / $perf['goal']['target'] * 100) : 0;
?>
      <section class="hero hero-xl" data-testid="performances-hero">
        <div class="hero-img" style="background-image:url('<?= e(CAT_IMAGES['cardio']) ?>')"></div>
        <div class="hero-body col">
          <p class="hello" data-testid="hero-greeting">Bonjour <?= e($member['prenom']) ?>,</p>
          <h1 data-testid="page-title">Tu <em>progresses</em> !</h1>
          <p>Chaque effort compte. Voici un aperçu de ta progression.</p>
        </div>
        <div class="hero-motto"><span>Plus</span><span>fort</span><span>chaque</span><span>jour</span></div>
        <span class="hero-slash"></span>
      </section>

      <div class="kpis">
        <?php foreach ($perf['kpis'] as $i => $k): ?>
          <article class="panel kpi" data-testid="kpi-<?= $i ?>">
            <span class="kpi-icon"><?= icon($k['icon'], 26) ?></span>
            <div>
              <p class="kpi-label"><?= e($k['label']) ?></p>
              <p class="kpi-value" data-testid="kpi-value-<?= $i ?>"><?= e($k['value']) ?><?php if ($k['unit']): ?> <small><?= e($k['unit']) ?></small><?php endif; ?></p>
              <?php if ($k['trend'] !== ''): ?><p class="kpi-trend"><b><?= e($k['trend']) ?></b></p><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
        <article class="panel kpi" data-testid="kpi-goal">
          <span class="kpi-icon"><?= icon('target', 26) ?></span>
          <div class="grow">
            <p class="kpi-label">Objectif du mois</p>
            <p class="kpi-value"><?= (int)$perf['goal']['done'] ?> <small>/ <?= (int)$perf['goal']['target'] ?></small></p>
            <div class="bar"><i style="width:<?= $goalPct ?>%"></i></div>
            <p class="kpi-trend"><?= $perf['goal']['target'] ? 'Objectif hebdomadaire' : 'Aucun objectif enregistré' ?></p>
          </div>
        </article>
      </div>

      <p class="demo-note" role="note">Données fictives de démonstration — elles ne sont pas enregistrées dans ton compte.</p>

      <div class="layout-2 perf">
        <div class="col-main">
          <section class="panel chart-card" data-testid="progress-chart-card">
            <div class="side-head">
              <h2 class="side-title"><?= icon('chart', 24) ?>Évolution de ta forme</h2>
              <div class="seg" role="tablist">
                <button class="seg-btn" data-range="7" data-testid="chart-range-7">7J</button>
                <button class="seg-btn is-active" data-range="30" data-testid="chart-range-30">30J</button>
                <button class="seg-btn" data-range="90" data-testid="chart-range-90">90J</button>
              </div>
            </div>
            <div class="chart" id="chart" data-testid="progress-chart">
              <div class="chart-badge" data-testid="chart-progress-badge"><strong id="chart-delta">+12%</strong><?= icon('trend', 16) ?><span>Progression globale</span></div>
              <div class="chart-tip" id="chart-tip" hidden></div>
            </div>
            <div class="body-stats">
              <?php foreach ($perf['body'] as $i => $b): ?>
                <div class="body-stat" data-testid="body-stat-<?= $i ?>">
                  <span class="kpi-icon sm"><?= icon($b['icon'], 20) ?></span>
                  <div><p class="kpi-label"><?= e($b['label']) ?></p>
                    <p class="body-value"><?= e($b['value']) ?> <small><?= e($b['unit']) ?></small></p>
                    <p class="kpi-trend"><b>↓ <?= e($b['delta']) ?></b></p></div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

        </div>

        <aside class="col-side">
          <section class="panel side-card" data-testid="perf-next-session">
            <div class="side-head">
              <h2 class="side-title"><?= icon('calendar', 22) ?>Prochaine séance</h2>
              <a href="<?= e(url('adherent/view/planning.php')) ?>" class="link" data-testid="perf-see-planning">Voir le planning <?= icon('right', 14) ?></a>
            </div>
            <?php if ($next): ?>
              <div class="next-row">
                <span class="next-img" style="background-image:url('<?= e($next['image']) ?>')"></span>
                <div>
                  <h3 data-testid="perf-next-title"><?= e($next['title']) ?>
                    <?php if ($next['is_booked']): ?><span class="tag tag-neon">Réservée</span><?php endif; ?></h3>
                  <p class="muted"><?= icon('clock', 14) ?><?= e(fr_date($next['start'], 'short')) ?> · <?= e(hm($next['start'])) ?> – <?= e(hm($next['end'])) ?></p>
                  <p class="muted"><?= icon('pin', 14) ?><?= e($next['room']) ?> · Coach <?= e($next['coach']) ?></p>
                  <a href="<?= e(url('adherent/view/reservations.php?date='.$next['date'])) ?>" class="btn btn-outline btn-sm" data-testid="perf-next-detail">Voir le détail <?= icon('right', 16) ?></a>
                </div>
              </div>
            <?php else: ?>
              <p class="muted">Aucune séance prévue.</p>
            <?php endif; ?>
          </section>

          <section class="panel side-card" data-testid="objectives-card">
            <div class="side-head">
              <h2 class="side-title"><?= icon('target', 22) ?>Objectifs</h2>
              <div class="seg objective-views" role="tablist" aria-label="Vues des objectifs">
                <button type="button" id="objective-tab-overview" class="seg-btn is-active" data-objective-view="overview" role="tab" aria-controls="objective-panel-overview" aria-selected="true">Vue globale</button>
                <button type="button" id="objective-tab-daily" class="seg-btn" data-objective-view="daily" role="tab" aria-controls="objective-panel-daily" aria-selected="false">Quotidien</button>
              </div>
            </div>
            <div id="objective-panel-overview" class="objective-panel" data-objective-panel="overview" role="tabpanel" aria-labelledby="objective-tab-overview">
              <div class="objectives">
                <div class="ring" style="--p:<?= $goalPct ?>" data-testid="objectives-ring">
                  <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="50" class="ring-bg"/><circle cx="60" cy="60" r="50" class="ring-fg" pathLength="100"/></svg>
                  <div><strong><?= $goalPct ?>%</strong><small>Objectif mensuel</small></div>
                </div>
                <ul class="obj-list">
                  <?php foreach (($perf['objectives'] ?? []) as $i => $objective): ?>
                    <li data-testid="objective-<?= $i ?>">
                      <span class="obj-icon" style="color:<?= e($objective['color']) ?>"><?= icon($objective['icon'], 20) ?></span>
                      <div><p><span><?= e($objective['label']) ?></span><b><?= e($objective['text']) ?></b></p>
                        <div class="bar"><i style="width:<?= (int)$objective['pct'] ?>%;background:<?= e($objective['color']) ?>"></i></div></div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            <div id="objective-panel-daily" class="objective-panel" data-objective-panel="daily" role="tabpanel" aria-labelledby="objective-tab-daily" hidden>
              <p class="muted">Coche les objectifs atteints aujourd’hui.</p>
              <ul class="daily-objectives" id="daily-objectives">
                <?php foreach ($objectives['available'] as $objective): ?>
                  <li><label><input type="checkbox" data-objective="<?= e($objective) ?>" <?= in_array($objective, $objectives['done'], true) ? 'checked' : '' ?>><span><?= e($objective) ?></span></label></li>
                <?php endforeach; ?>
              </ul>
              <div class="objective-history-head"><strong>Historique</strong>
                <label class="sr-only" for="objective-period">Période de l’historique</label>
                <select id="objective-period"><option value="1">30 derniers jours</option><option value="3" selected>3 derniers mois</option><option value="12">12 derniers mois</option></select>
              </div>
              <div id="objective-history" class="objective-history" aria-live="polite"></div>
            </div>
          </section>

          <section class="panel quote" style="background-image:url('<?= e(CAT_IMAGES['musculation']) ?>')" data-testid="quote-card">
            <p>Le progrès n’est pas <em>une chance</em>, c’est <em>une décision.</em></p><i></i>
          </section>
        </aside>
      </div>
<script>window.ATX_PERF = <?= json_encode($perf['series'], JSON_UNESCAPED_UNICODE) ?>;</script>
<script>window.ATX_OBJECTIVES = <?= json_encode(['today'=>$objectives['today'], 'history'=>$objectives['history']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<script>
(() => {
  const objectiveViews = document.querySelectorAll('[data-objective-view]');
  objectiveViews.forEach(button => button.addEventListener('click', () => {
    objectiveViews.forEach(view => {
      const active = view === button;
      view.classList.toggle('is-active', active);
      view.setAttribute('aria-selected', String(active));
    });
    document.querySelectorAll('[data-objective-panel]').forEach(panel => {
      panel.hidden = panel.dataset.objectivePanel !== button.dataset.objectiveView;
    });
  }));

  const api = (action, data) => window.ADH_API(action, data);
  const checks = document.querySelectorAll('[data-objective]');
  checks.forEach(input => input.addEventListener('change', async () => {
    input.disabled = true;
    try {
      const result = await api('objectives', {date: window.ATX_OBJECTIVES.today, objectif: input.dataset.objective, atteint: input.checked});
      window.ATX_OBJECTIVES.history = result.history;
      renderObjectiveHistory(Number(document.getElementById('objective-period').value));
    } catch (error) { input.checked = !input.checked; alert(error.message); }
    finally { input.disabled = false; }
  }));
  function renderObjectiveHistory(months) {
    const container = document.getElementById('objective-history');
    const since = new Date(); since.setMonth(since.getMonth() - months);
    const rows = window.ATX_OBJECTIVES.history.filter(row => new Date(row.date_objectif) >= since);
    container.replaceChildren();
    if (!rows.length) { container.textContent = 'Aucun objectif enregistré pour cette période.'; return; }
    rows.forEach(row => {
      const item = document.createElement('p');
      item.textContent = new Date(row.date_objectif + 'T12:00:00').toLocaleDateString('fr-FR') + ' · ' + row.objectif;
      container.append(item);
    });
  }
  const period = document.getElementById('objective-period');
  period.addEventListener('change', () => renderObjectiveHistory(Number(period.value)));
  renderObjectiveHistory(Number(period.value));
  const chart = document.getElementById('chart');
  const data = window.ATX_PERF;
  if (!chart || !data) return;
  function draw(days) {
    const values = data[days].values;
    const width = Math.max(chart.clientWidth, 320), height = 220, max = 100;
    const points = values.map((value, index) => `${index * width / Math.max(1, values.length - 1)},${height - value * (height - 20) / max}`).join(' ');
    chart.querySelector('svg.performance-line')?.remove();
    chart.insertAdjacentHTML('beforeend', `<svg class="performance-line" viewBox="0 0 ${width} ${height}" preserveAspectRatio="none" role="img" aria-label="Courbe fictive de progression"><g class="grid">${[0,25,50,75,100].map(v => `<line x1="0" y1="${height-v*(height-20)/max}" x2="${width}" y2="${height-v*(height-20)/max}"/>`).join('')}</g><polyline class="line" points="${points}"/>${values.map((v,i) => `<circle class="point" cx="${i*width/Math.max(1,values.length-1)}" cy="${height-v*(height-20)/max}" r="${i===values.length-1?4:2.5}"/>`).join('')}</svg>`);
  }
  document.querySelectorAll('[data-range]').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('[data-range]').forEach(item => item.classList.toggle('is-active', item === button));
    draw(Number(button.dataset.range));
  }));
  draw(30);
})();
</script>
<?php page_foot(); ?>
