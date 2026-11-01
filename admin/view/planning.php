<?php
$d = $planningDonneesAdmin;
$debutSemainePlanning = new DateTimeImmutable($d['debut_semaine']);
$finSemainePlanning = $debutSemainePlanning->modify('+6 days');
$semainePrecedente = $debutSemainePlanning->modify('-7 days')->format('Y-m-d');
$semaineSuivante = $debutSemainePlanning->modify('+7 days')->format('Y-m-d');
$joursPlanning = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$libellesMois = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
$heureMinPlanning = 8;
$heureMaxPlanning = 21;
?>
<section class="admin-planning-page" id="planning-calendrier">
  <header class="admin-planning-heading">
    <div class="admin-planning-heading-copy"><span class="admin-planning-icon" aria-hidden="true">▦</span><div><h2>Planning des cours</h2><p>Consultez et gérez le planning des activités. Vous pouvez ajouter, modifier ou supprimer des cours.</p></div></div>
    <a class="admin-planning-add-link" href="<?= e(url('admin/espace.php?onglet=planning&vue=ajouter&semaine=' . $d['debut_semaine'])) ?>">＋ <span>Ajouter un cours</span></a>
  </header>

  <div class="admin-planning-toolbar">
    <div class="admin-week-navigation"><a href="<?= e(url('admin/espace.php?onglet=planning&semaine=' . $semainePrecedente . '#planning-calendrier')) ?>" aria-label="Semaine précédente">‹</a><span aria-hidden="true">▦</span><strong><?= (int) $debutSemainePlanning->format('j') ?> – <?= (int) $finSemainePlanning->format('j') ?> <?= e($libellesMois[(int) $finSemainePlanning->format('n')]) ?> <?= $finSemainePlanning->format('Y') ?></strong><a href="<?= e(url('admin/espace.php?onglet=planning&semaine=' . $semaineSuivante . '#planning-calendrier')) ?>" aria-label="Semaine suivante">›</a></div>
    <a class="admin-week-today" href="<?= e(url('admin/espace.php?onglet=planning#planning-calendrier')) ?>">Cette semaine</a>
    <select id="filtre-planning-salle" aria-label="Filtrer les salles"><option value="">Toutes les salles</option><?php foreach ($d['salles'] as $salle): ?><option value="<?= e(mb_strtolower($salle['nomsalle'])) ?>"><?= e($salle['nomsalle']) ?></option><?php endforeach; ?></select>
    <select id="filtre-planning-activite" aria-label="Filtrer les activités"><option value="">Toutes les activités</option><?php foreach (PlanningController::TYPES as $type => $libelle): ?><option value="<?= e($type) ?>"><?= e($libelle) ?></option><?php endforeach; ?></select>
    <select id="filtre-planning-coach" aria-label="Filtrer les coachs"><option value="">Tous les coachs</option><?php foreach ($d['coachs'] as $coach): ?><option value="<?= e(mb_strtolower($coach['prenomUsers'] . ' ' . $coach['nomUsers'])) ?>"><?= e($coach['prenomUsers'] . ' ' . $coach['nomUsers']) ?></option><?php endforeach; ?></select>
    <label class="admin-planning-search"><span aria-hidden="true">⌕</span><input id="recherche-planning" type="search" placeholder="Rechercher un cours…"></label>
  </div>

  <div class="admin-planning-main">
    <div class="admin-planning-calendar-wrap">
      <div class="admin-week-grid" id="grille-planning" style="--planning-columns:7">
        <div class="admin-week-corner"></div>
        <?php foreach ($joursPlanning as $indexJour => $jour): $dateJour = $debutSemainePlanning->modify('+' . $indexJour . ' days'); ?>
          <div class="admin-week-day"><strong><?= e($jour) ?></strong><span><?= $dateJour->format('d/m') ?></span></div>
        <?php endforeach; ?>
        <?php for ($heure = $heureMinPlanning; $heure < $heureMaxPlanning; $heure++): ?>
          <div class="admin-week-hour" style="grid-column:1;grid-row:<?= 2 + ($heure - $heureMinPlanning) * 2 ?>"><?= sprintf('%02d:00', $heure) ?></div>
        <?php endfor; ?>
        <?php foreach ($joursPlanning as $indexJour => $_jour): ?>
          <div class="admin-week-day-track" style="grid-column:<?= $indexJour + 2 ?>;grid-row:2 / span <?= ($heureMaxPlanning - $heureMinPlanning) * 2 ?>"></div>
        <?php endforeach; ?>
        <?php foreach ($d['creneaux_semaine'] as $cours):
          $debutCours = new DateTimeImmutable($cours['date_heure_debut']);
          $finCours = new DateTimeImmutable($cours['date_heure_fin']);
          $jourCours = (int) $debutCours->format('N');
          $minuteDebut = ((int) $debutCours->format('G') - $heureMinPlanning) * 60 + (int) $debutCours->format('i');
          $minuteFin = ((int) $finCours->format('G') - $heureMinPlanning) * 60 + (int) $finCours->format('i');
          $decalageLigne = max(0, (int) floor($minuteDebut / 30));
          $lignesCours = max(1, (int) ceil((min(($heureMaxPlanning - $heureMinPlanning) * 60, $minuteFin) - max(0, $minuteDebut)) / 30));
          $typeCours = isset(PlanningController::TYPES[$cours['type_activite']]) ? $cours['type_activite'] : 'cardio';
          $nomCoachCours = trim($cours['prenomUsers'] . ' ' . $cours['nomUsers']);
          $termeRechercheCours = mb_strtolower($cours['titre'] . ' ' . $cours['nomsalle'] . ' ' . $nomCoachCours);
          if ($jourCours < 1 || $jourCours > 7 || $minuteFin <= 0 || $minuteDebut >= ($heureMaxPlanning - $heureMinPlanning) * 60) continue;
        ?>
          <article class="admin-week-course act-<?= e($typeCours) ?>" data-planning-course data-room="<?= e(mb_strtolower($cours['nomsalle'])) ?>" data-type="<?= e($typeCours) ?>" data-coach="<?= e(mb_strtolower($nomCoachCours)) ?>" data-search="<?= e($termeRechercheCours) ?>" style="grid-column:<?= $jourCours + 1 ?>;grid-row:<?= 2 + $decalageLigne ?> / span <?= $lignesCours ?>">
            <button type="button" class="admin-week-course-details" data-course-details
              data-title="<?= e($cours['titre']) ?>"
              data-type-label="<?= e(PlanningController::TYPES[$typeCours]) ?>"
              data-date="<?= e($debutCours->format('d/m/Y')) ?>"
              data-start="<?= e($debutCours->format('H:i')) ?>"
              data-end="<?= e($finCours->format('H:i')) ?>"
              data-room-name="<?= e($cours['nomsalle']) ?>"
              data-coach-name="<?= e($nomCoachCours) ?>"
              data-spots="<?= (int) $cours['places_prises'] ?>"
              data-capacity="<?= (int) $cours['capacite_max'] ?>"
              data-description="<?= e($cours['description'] ?? '') ?>"
              aria-label="Afficher le détail du cours <?= e($cours['titre']) ?>"></button>
            <a class="admin-week-course-edit" href="<?= e(url('admin/espace.php?onglet=planning&vue=modifier&semaine=' . $d['debut_semaine'] . '&modifier=' . (int) $cours['Numactivite'])) ?>" aria-label="Modifier <?= e($cours['titre']) ?>">···</a>
            <strong><?= e($cours['titre']) ?></strong><span><?= $debutCours->format('H:i') ?> – <?= $finCours->format('H:i') ?></span><small><?= e($cours['nomsalle']) ?> · <?= (int) $cours['places_prises'] ?>/<?= (int) $cours['capacite_max'] ?></small><small class="admin-week-coach">♙ <?= e($nomCoachCours) ?></small>
            <form method="post" class="admin-week-delete-form" data-confirmation="Supprimer ce cours et ses réservations ?"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer_creneau"><input type="hidden" name="creneau" value="<?= (int) $cours['Numactivite'] ?>"><input type="hidden" name="portee" value="un"><input type="hidden" name="semaine" value="<?= e($d['debut_semaine']) ?>"><button type="submit" aria-label="Supprimer ce cours">×</button></form>
          </article>
        <?php endforeach; ?>
        <p class="admin-week-empty" id="planning-filtre-vide" hidden>Aucun cours ne correspond aux filtres pour cette semaine.</p>
      </div>
    </div>
  </div>
</section>
<dialog class="admin-course-details-dialog" id="admin-course-details" aria-labelledby="admin-course-details-title">
  <header>
    <div><p>Détail du cours</p><h2 id="admin-course-details-title" data-course-title></h2></div>
    <button type="button" class="admin-course-details-close" data-course-details-close aria-label="Fermer">×</button>
  </header>
  <dl>
    <div><dt>Activité</dt><dd data-course-type></dd></div>
    <div><dt>Date</dt><dd data-course-date></dd></div>
    <div><dt>Horaires</dt><dd><span data-course-start></span> – <span data-course-end></span></dd></div>
    <div><dt>Salle</dt><dd data-course-room></dd></div>
    <div><dt>Coach</dt><dd data-course-coach></dd></div>
    <div><dt>Places réservées</dt><dd><span data-course-spots></span> / <span data-course-capacity></span></dd></div>
    <div class="admin-course-details-description"><dt>Description</dt><dd data-course-description></dd></div>
  </dl>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const detailsDialog = document.getElementById('admin-course-details');
  document.querySelectorAll('[data-course-details]').forEach(button => button.addEventListener('click', () => {
    if (!detailsDialog) return;
    const fields = {
      '[data-course-title]': button.dataset.title,
      '[data-course-type]': button.dataset.typeLabel,
      '[data-course-date]': button.dataset.date,
      '[data-course-start]': button.dataset.start,
      '[data-course-end]': button.dataset.end,
      '[data-course-room]': button.dataset.roomName,
      '[data-course-coach]': button.dataset.coachName,
      '[data-course-spots]': button.dataset.spots,
      '[data-course-capacity]': button.dataset.capacity,
      '[data-course-description]': button.dataset.description || 'Aucune description renseignée.',
    };
    Object.entries(fields).forEach(([selector, value]) => {
      const field = detailsDialog.querySelector(selector);
      if (field) field.textContent = value || '';
    });
    detailsDialog.showModal();
  }));
  document.querySelector('[data-course-details-close]')?.addEventListener('click', () => detailsDialog?.close());
  detailsDialog?.addEventListener('click', event => {
    if (event.target === detailsDialog) detailsDialog.close();
  });

  const cards = [...document.querySelectorAll('[data-planning-course]')];
  const filters = [document.getElementById('filtre-planning-salle'), document.getElementById('filtre-planning-activite'), document.getElementById('filtre-planning-coach')];
  const search = document.getElementById('recherche-planning');
  const empty = document.getElementById('planning-filtre-vide');
  const filterCourses = () => {
    const values = filters.map(filter => filter?.value || '');
    const term = (search?.value || '').trim().toLocaleLowerCase('fr'); let visible = 0;
    cards.forEach(card => { const match = (!values[0] || card.dataset.room === values[0]) && (!values[1] || card.dataset.type === values[1]) && (!values[2] || card.dataset.coach === values[2]) && card.dataset.search.includes(term); card.hidden = !match; if (match) visible++; });
    if (empty) empty.hidden = visible > 0;
  };
  filters.forEach(filter => filter?.addEventListener('change', filterCourses)); search?.addEventListener('input', filterCourses);
  document.querySelectorAll('.admin-week-delete-form').forEach(form => form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirmation)) event.preventDefault(); }));
});
</script>
