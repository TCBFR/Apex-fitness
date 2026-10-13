<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';
$perf = atx_performance_data();
$next = atx_next_session();
page_head('performances', 'Mes performances');
$goalPct = round($perf['goal']['done'] / $perf['goal']['target'] * 100);
?>
      <section class="hero hero-xl" data-testid="performances-hero">
        <div class="hero-img" style="background-image:url('<?= e(CAT_IMAGES['cardio']) ?>')"></div>
        <div class="hero-body col">
          <p class="hello" data-testid="hero-greeting">Bonjour <?= e(ATX_USER['firstname']) ?>,</p>
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
              <p class="kpi-trend"><b>↑ <?= e($k['trend']) ?></b><br>vs. mois dernier</p>
            </div>
          </article>
        <?php endforeach; ?>
        <article class="panel kpi" data-testid="kpi-goal">
          <span class="kpi-icon"><?= icon('target', 26) ?></span>
          <div class="grow">
            <p class="kpi-label">Objectif du mois</p>
            <p class="kpi-value"><?= (int)$perf['goal']['done'] ?> <small>/ <?= (int)$perf['goal']['target'] ?></small></p>
            <div class="bar"><i style="width:<?= $goalPct ?>%"></i></div>
            <p class="kpi-trend">Séances par semaine</p>
          </div>
        </article>
      </div>

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

          <section class="panel" data-testid="history-card">
            <div class="side-head">
              <h2 class="side-title"><?= icon('dumbbell', 24) ?>Historique des entraînements</h2>
              <a href="planning.php" class="link" data-testid="history-see-all">Voir tout <?= icon('right', 14) ?></a>
            </div>
            <div class="history">
              <?php foreach ($perf['history'] as $i => $h): ?>
                <div class="hist" data-testid="history-item-<?= $i ?>">
                  <span class="hist-img" style="background-image:url('<?= e(CAT_IMAGES[$h['cat']]) ?>')"></span>
                  <div><strong><?= e($h['title']) ?></strong><small><?= e(fr_date((new DateTimeImmutable($h['meta']))->format('Y-m-d'), 'day')) ?> · <?= e($h['duration']) ?></small></div>
                  <span class="hist-check"><?= icon('check', 16) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        </div>

        <aside class="col-side">
          <section class="panel side-card" data-testid="perf-next-session">
            <div class="side-head">
              <h2 class="side-title"><?= icon('calendar', 22) ?>Prochaine séance</h2>
              <a href="planning.php" class="link" data-testid="perf-see-planning">Voir le planning <?= icon('right', 14) ?></a>
            </div>
            <?php if ($next): ?>
              <div class="next-row">
                <span class="next-img" style="background-image:url('<?= e($next['image']) ?>')"></span>
                <div>
                  <h3 data-testid="perf-next-title"><?= e($next['title']) ?>
                    <?php if ($next['is_booked']): ?><span class="tag tag-neon">Réservée</span><?php endif; ?></h3>
                  <p class="muted"><?= icon('clock', 14) ?><?= e(fr_date($next['start'], 'short')) ?> · <?= e(hm($next['start'])) ?> – <?= e(hm($next['end'])) ?></p>
                  <p class="muted"><?= icon('pin', 14) ?><?= e($next['room']) ?> · Coach <?= e($next['coach']) ?></p>
                  <a href="reservations.php?date=<?= e($next['date']) ?>" class="btn btn-outline btn-sm" data-testid="perf-next-detail">Voir le détail <?= icon('right', 16) ?></a>
                </div>
              </div>
            <?php else: ?>
              <p class="muted">Aucune séance prévue.</p>
            <?php endif; ?>
          </section>

          <section class="panel side-card" data-testid="objectives-card">
            <div class="side-head">
              <h2 class="side-title"><?= icon('target', 22) ?>Objectifs</h2>
              <a href="#" class="link" data-soon="Objectifs" data-testid="objectives-see-all">Voir tous <?= icon('right', 14) ?></a>
            </div>
            <div class="objectives">
              <div class="ring" style="--p:<?= $goalPct ?>" data-testid="objectives-ring">
                <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="50" class="ring-bg"/><circle cx="60" cy="60" r="50" class="ring-fg" pathLength="100"/></svg>
                <div><strong><?= $goalPct ?>%</strong><small>Objectif mensuel</small></div>
              </div>
              <ul class="obj-list">
                <?php foreach ($perf['objectives'] as $i => $o): ?>
                  <li data-testid="objective-<?= $i ?>">
                    <span class="obj-icon" style="color:<?= e($o['color']) ?>"><?= icon($o['icon'], 20) ?></span>
                    <div><p><span><?= e($o['label']) ?></span><b><?= e($o['text']) ?></b></p>
                      <div class="bar"><i style="width:<?= (int)$o['pct'] ?>%;background:<?= e($o['color']) ?>"></i></div></div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </section>

          <section class="panel quote" style="background-image:url('<?= e(CAT_IMAGES['musculation']) ?>')" data-testid="quote-card">
            <p>Le progrès n’est pas <em>une chance</em>, c’est <em>une décision.</em></p><i></i>
          </section>
        </aside>
      </div>
<script>window.ATX_PERF = <?= json_encode($perf['series'], JSON_UNESCAPED_UNICODE) ?>;</script>
<?php page_foot(['assets/js/performances.js']); ?>
