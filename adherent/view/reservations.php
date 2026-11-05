<?php
require_once dirname(__DIR__) . '/includes/planning/layout.php';

page_head('reservations', 'Réservations');
hero('calweek', 'Mes séances', 'Retrouvez vos réservations à venir et les séances déjà terminées.', 'reservations-hero');
?>
      <div class="layout-2">
        <div class="col-main">
          <section class="panel list-panel">
            <div class="list-head">
              <span class="list-icon"><?= icon('calendar', 26) ?></span>
              <div>
                <h2>Mes prochaines réservations</h2>
                <p>Annulez jusqu’à deux heures avant le début de la séance.</p>
              </div>
              <span class="count-pill" id="my-count" data-testid="my-bookings-count">0</span>
            </div>
            <div class="my-list" id="my-list" data-testid="my-bookings-list"><p class="empty">Chargement…</p></div>
          </section>

          <section class="panel list-panel" data-testid="session-history-panel">
            <div class="list-head">
              <span class="list-icon"><?= icon('calcheck', 26) ?></span><div><h2>Historique des séances terminées</h2><p>Toutes vos séances réservées et terminées.</p></div>
            </div>
            <div class="history-filters"><label>Période <select id="history-period"><option value="all">Depuis toujours</option><option value="30">30 derniers jours</option><option value="90">3 derniers mois</option><option value="365">12 derniers mois</option></select></label><label>Activité <select id="history-category"><option value="all">Toutes les activités</option><?php foreach (CATEGORIES as $key => $category): ?><option value="<?= e($key) ?>"><?= e($category['label']) ?></option><?php endforeach; ?></select></label></div>
            <div class="my-list" id="session-history" data-testid="session-history-list"><p class="empty">Chargement…</p></div>
          </section>

        </div>

        <aside class="col-side">
          <section class="panel side-card">
            <h2 class="side-title"><?= icon('calendar', 22) ?>Réserver une séance</h2>
            <p class="muted">Parcourez le calendrier pour choisir un cours, vérifier les places et confirmer votre réservation.</p>
            <a class="btn btn-primary btn-block" href="<?= e(url('adherent/planning')) ?>">Ouvrir le planning <?= icon('right', 16) ?></a>          </section>

        </aside>
      </div>
<script>
  /* ATHLETIX — Réservations */
(function () {
  const ATX = window.ATX;
  const myList = document.getElementById('my-list');
  const myCount = document.getElementById('my-count');
  const historyList = document.getElementById('session-history');
  let pastSessions = [];
  const renderUpcoming = (items) => {
    myCount.textContent = items.length;
    if (!items.length) { myList.innerHTML = '<p class="empty">Aucune réservation à venir. Réservez une séance depuis le planning.</p>'; return; }
    myList.innerHTML = items.map(s => '<div class="my-row"><div class="my-main"><strong>' + ATX.esc(s.title) + '</strong><small>' + ATX.frDate(s.start, {weekday:"short",day:"numeric",month:"short"}) + ' · ' + ATX.hm(s.start) + ' – ' + ATX.hm(s.end) + ' · Coach ' + ATX.esc(s.coach) + '</small></div>' + (s.can_cancel ? '<button class="btn btn-danger btn-sm" data-cancel="' + s.id + '">Annuler</button>' : '<span class="btn btn-muted btn-sm">Annulation close</span>') + '</div>').join('');
  };
  const renderHistory = () => {
    const days = document.getElementById('history-period').value;
    const category = document.getElementById('history-category').value;
    const cutoff = days === 'all' ? 0 : Date.now() - Number(days) * 86400000;
    const items = pastSessions.filter(item => (category === 'all' || item.type_activite === category) && new Date(item.date_heure_debut.replace(' ', 'T')).getTime() >= cutoff);
    historyList.innerHTML = items.length ? items.map(item => '<div class="my-row"><div class="my-main"><strong>' + ATX.esc(item.titre) + '</strong><small>' + new Date(item.date_heure_debut.replace(' ', 'T')).toLocaleDateString('fr-FR', {weekday:'short', day:'numeric', month:'short', year:'numeric'}) + ' · Coach ' + ATX.esc(item.coach) + ' · ' + ATX.esc(item.nomsalle) + '</small></div><span class="tag tag-neon">Terminée</span></div>').join('') : '<p class="empty">Aucune séance terminée pour ces filtres.</p>';
  };
  myList.addEventListener('click', async event => {
    const button = event.target.closest('[data-cancel]'); if (!button) return;
    button.disabled = true;
    try { await ATX.api('cancel', {id:button.dataset.cancel}, 'POST'); load(); }
    catch (error) { alert(error.message); button.disabled = false; }
  });
  document.getElementById('history-period').addEventListener('change', renderHistory);
  document.getElementById('history-category').addEventListener('change', renderHistory);
  async function load() {
    try {
      const [upcoming, history] = await Promise.all([ATX.api('my'), ATX.api('reservation_history')]);
      renderUpcoming(upcoming); pastSessions = history; renderHistory();
    } catch (error) { myList.innerHTML = '<p class="empty">Vos réservations sont indisponibles pour le moment.</p>'; }
  }
  load();

})();

</script>
<?php page_foot(); ?>
