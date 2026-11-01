<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';
require_once dirname(__DIR__) . '/model/AdherentEspace.php';
require_once dirname(__DIR__) . '/controller/AdherentController.php';

$memberId = (int) utilisateur()['NumUsers'];
$space = new AdherentEspace();
$member = $space->profil($memberId);
$pastReservations = $space->historiqueReservations($memberId);
$upcomingReservations = $space->reservations($memberId);
$objectives = (new AdherentController())->objectifs($memberId, 12);
page_head('performances', 'Mon activité');
?>
      <section class="hero hero-xl" data-testid="performances-hero">
        <div class="hero-body col">
          <p class="hello" data-testid="hero-greeting">Bonjour <?= e($member['prenom']) ?>,</p>
          <h1 data-testid="page-title">Mon activité</h1>
          <p>Consultez vos réservations et les objectifs enregistrés dans votre compte.</p>
        </div>
      </section>

      <div class="kpis">
        <article class="panel kpi">
          <span class="kpi-icon"><?= icon('dumbbell', 26) ?></span>
          <div><p class="kpi-label">Réservations passées</p><p class="kpi-value"><?= count($pastReservations) ?></p></div>
        </article>
        <article class="panel kpi">
          <span class="kpi-icon"><?= icon('calendar', 26) ?></span>
          <div><p class="kpi-label">Réservations à venir</p><p class="kpi-value"><?= count($upcomingReservations) ?></p></div>
        </article>
        <article class="panel kpi">
          <span class="kpi-icon"><?= icon('target', 26) ?></span>
          <div><p class="kpi-label">Objectifs enregistrés</p><p class="kpi-value"><?= count($objectives['history']) ?></p></div>
        </article>
      </div>

      <div class="layout-2 perf">
        <div class="col-main">
          <section class="panel chart-card" data-testid="reservation-history">
            <div class="side-head">
              <h2 class="side-title"><?= icon('clock', 24) ?>Historique des réservations</h2>
            </div>
            <?php if (!$pastReservations): ?>
              <p class="muted">Aucune réservation passée enregistrée.</p>
            <?php else: ?>
              <div class="table-scroll">
                <table class="tableau">
                  <thead><tr><th>Séance</th><th>Date</th><th>Salle</th><th>Coach</th></tr></thead>
                  <tbody>
                  <?php foreach ($pastReservations as $reservation): ?>
                    <tr>
                      <td><?= e($reservation['titre']) ?></td>
                      <td><?= e(fr_date($reservation['date_heure_debut'], 'short')) ?> · <?= e(hm($reservation['date_heure_debut'])) ?></td>
                      <td><?= e($reservation['nomsalle']) ?></td>
                      <td><?= e($reservation['coach']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </section>
        </div>

        <aside class="col-side">
          <section class="panel side-card" data-testid="perf-next-session">
            <div class="side-head">
              <h2 class="side-title"><?= icon('calendar', 22) ?>Prochaine réservation</h2>
              <a href="<?= e(url('adherent/view/planning.php')) ?>" class="link" data-testid="perf-see-planning">Voir le planning <?= icon('right', 14) ?></a>
            </div>
            <?php if ($upcomingReservations): $next = $upcomingReservations[0]; ?>
              <div>
                <h3 data-testid="perf-next-title"><?= e($next['titre']) ?></h3>
                <p class="muted"><?= icon('clock', 14) ?><?= e(fr_date($next['date_heure_debut'], 'short')) ?> · <?= e(hm($next['date_heure_debut'])) ?> – <?= e(hm($next['date_heure_fin'])) ?></p>
                <p class="muted"><?= icon('pin', 14) ?><?= e($next['nomsalle']) ?> · Coach <?= e($next['coach']) ?></p>
              </div>
            <?php else: ?>
              <p class="muted">Aucune réservation à venir.</p>
            <?php endif; ?>
          </section>

          <section class="panel side-card" data-testid="objectives-card">
            <div class="side-head"><h2 class="side-title"><?= icon('target', 22) ?>Objectifs quotidiens</h2></div>
            <p class="muted">Les objectifs cochés sont enregistrés dans votre compte.</p>
            <ul class="daily-objectives" id="daily-objectives">
              <?php foreach ($objectives['available'] as $objective): ?>
                <li><label><input type="checkbox" data-objective="<?= e($objective) ?>" <?= in_array($objective, $objectives['done'], true) ? 'checked' : '' ?>><span><?= e($objective) ?></span></label></li>
              <?php endforeach; ?>
            </ul>
            <div class="objective-history-head"><strong>Historique enregistré</strong>
              <label class="sr-only" for="objective-period">Période de l’historique</label>
              <select id="objective-period"><option value="1">30 derniers jours</option><option value="3" selected>3 derniers mois</option><option value="12">12 derniers mois</option></select>
            </div>
            <div id="objective-history" class="objective-history" aria-live="polite"></div>
          </section>
        </aside>
      </div>

<script>window.ATX_OBJECTIVES = <?= json_encode(['today' => $objectives['today'], 'history' => $objectives['history']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script>
(() => {
  const checks = document.querySelectorAll('[data-objective]');
  checks.forEach(input => input.addEventListener('change', async () => {
    input.disabled = true;
    try {
      const result = await window.ADH_API('objectives', {
        date: window.ATX_OBJECTIVES.today,
        objectif: input.dataset.objective,
        atteint: input.checked
      });
      window.ATX_OBJECTIVES.history = result.history;
      renderObjectiveHistory(Number(document.getElementById('objective-period').value));
    } catch (error) {
      input.checked = !input.checked;
      alert(error.message);
    } finally {
      input.disabled = false;
    }
  }));

  function renderObjectiveHistory(months) {
    const container = document.getElementById('objective-history');
    const since = new Date();
    since.setMonth(since.getMonth() - months);
    const rows = window.ATX_OBJECTIVES.history.filter(row => new Date(row.date_objectif) >= since);
    container.replaceChildren();
    if (!rows.length) {
      container.textContent = 'Aucun objectif enregistré pour cette période.';
      return;
    }
    rows.forEach(row => {
      const item = document.createElement('p');
      item.textContent = new Date(row.date_objectif + 'T12:00:00').toLocaleDateString('fr-FR') + ' · ' + row.objectif;
      container.append(item);
    });
  }

  const period = document.getElementById('objective-period');
  period.addEventListener('change', () => renderObjectiveHistory(Number(period.value)));
  renderObjectiveHistory(Number(period.value));
})();
</script>
<?php page_foot(); ?>
