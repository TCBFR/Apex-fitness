<?php
$d = $planningDonneesAdmin;
$edition = $creneauAModifier;
$debutSemainePlanning = new DateTimeImmutable($d['debut_semaine']);
$finSemainePlanning = $debutSemainePlanning->modify('+6 days');
$debutForm = $edition ? date('Y-m-d\TH:i', strtotime($edition['date_heure_debut'])) : $debutSemainePlanning->format('Y-m-d') . 'T09:00';
$dureeForm = $edition ? max(15, (int) round((strtotime($edition['date_heure_fin']) - strtotime($edition['date_heure_debut'])) / 60)) : 60;
$semainePrecedente = $debutSemainePlanning->modify('-7 days')->format('Y-m-d');
$semaineSuivante = $debutSemainePlanning->modify('+7 days')->format('Y-m-d');
$joursPlanning = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$libellesMois = [1=>'janvier',2=>'février',3=>'mars',4=>'avril',5=>'mai',6=>'juin',7=>'juillet',8=>'août',9=>'septembre',10=>'octobre',11=>'novembre',12=>'décembre'];
$heureMinPlanning = 8;
$heureMaxPlanning = 21;
$totalPlacesSemaine = array_sum(array_map(static fn(array $cours): int => (int) $cours['places_prises'], $d['creneaux_semaine']));
$capaciteMoyenneSemaine = count($d['creneaux_semaine']) ? (int) round($totalPlacesSemaine / count($d['creneaux_semaine'])) : 0;
?>
<section class="admin-planning-page" id="planning-calendrier">
  <header class="admin-planning-heading">
    <div class="admin-planning-heading-copy"><span class="admin-planning-icon" aria-hidden="true">▦</span><div><h2>Planning des cours</h2><p>Consultez et gérez le planning des activités. Vous pouvez ajouter, modifier ou supprimer des cours.</p></div></div>
    <a class="admin-planning-add-link" href="#formulaire-planning">＋ <span>Ajouter un cours</span></a>
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
            <a class="admin-week-course-edit" href="<?= e(url('admin/espace.php?onglet=planning&semaine=' . $d['debut_semaine'] . '&modifier=' . (int) $cours['Numactivite'] . '#formulaire-planning')) ?>" aria-label="Modifier <?= e($cours['titre']) ?>">···</a>
            <strong><?= e($cours['titre']) ?></strong><span><?= $debutCours->format('H:i') ?> – <?= $finCours->format('H:i') ?></span><small><?= e($cours['nomsalle']) ?> · <?= (int) $cours['places_prises'] ?>/<?= (int) $cours['capacite_max'] ?></small><small class="admin-week-coach">♙ <?= e($nomCoachCours) ?></small>
            <form method="post" class="admin-week-delete-form" data-confirmation="Supprimer ce cours et ses réservations ?"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer_creneau"><input type="hidden" name="creneau" value="<?= (int) $cours['Numactivite'] ?>"><input type="hidden" name="portee" value="un"><input type="hidden" name="semaine" value="<?= e($d['debut_semaine']) ?>"><button type="submit" aria-label="Supprimer ce cours">×</button></form>
          </article>
        <?php endforeach; ?>
        <p class="admin-week-empty" id="planning-filtre-vide" hidden>Aucun cours ne correspond aux filtres pour cette semaine.</p>
      </div>
      <footer class="admin-planning-summary">
        <div class="admin-planning-legend"><strong>Légende des activités</strong><div><?php foreach (PlanningController::TYPES as $cleType => $nomType): ?><span class="legend-<?= e($cleType) ?>"><i></i><?= e($nomType) ?></span><?php endforeach; ?></div></div>
        <div class="admin-planning-metric"><span aria-hidden="true">▦</span><div><strong><?= count($d['creneaux_semaine']) ?></strong><small>Cours cette semaine</small></div></div>
        <div class="admin-planning-metric"><span aria-hidden="true">♙</span><div><strong><?= $totalPlacesSemaine ?></strong><small>Inscrits au total</small></div></div>
        <div class="admin-planning-metric"><span aria-hidden="true">♟</span><div><strong><?= $capaciteMoyenneSemaine ?></strong><small>Moyenne par cours</small></div></div>
      </footer>
    </div>

    <aside class="admin-planning-editor" id="formulaire-planning">
      <header><span aria-hidden="true">＋</span><h3><?= $edition ? 'Modifier un cours' : 'Ajouter un cours' ?></h3></header>
      <form method="post">
        <?= champ_csrf() ?><input type="hidden" name="action" value="<?= $edition ? 'modifier_creneau' : 'creer_creneau' ?>"><input type="hidden" name="semaine" value="<?= e($d['debut_semaine']) ?>">
        <?php if ($edition): ?><input type="hidden" name="creneau" value="<?= (int) $edition['Numactivite'] ?>"><?php endif; ?>
        <label>Activité <b>*</b><input name="titre" list="admin-planning-titres" maxlength="100" placeholder="Sélectionner une activité" value="<?= e($edition['titre'] ?? '') ?>" required><datalist id="admin-planning-titres"><?php foreach ($d['titres'] as $titre): ?><option value="<?= e($titre) ?>"><?php endforeach; ?></datalist></label>
        <label>Type <b>*</b><select name="type" required><?php foreach (PlanningController::TYPES as $cle => $libelle): ?><option value="<?= e($cle) ?>" <?= ($edition['type_activite'] ?? 'cardio') === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></label>
        <label>Salle <b>*</b><select name="salle" required><?php foreach ($d['salles'] as $salle): $salleIndisponible = ($salle['etatsalle'] ?? 'Ouverte') !== 'Ouverte' && (int) ($edition['Numsalle'] ?? 0) !== (int) $salle['Numsalle']; ?><option value="<?= (int) $salle['Numsalle'] ?>" <?= (int) ($edition['Numsalle'] ?? 0) === (int) $salle['Numsalle'] ? 'selected' : '' ?> <?= $salleIndisponible ? 'disabled' : '' ?>><?= e($salle['nomsalle']) ?> (<?= (int) $salle['capacitesalle'] ?> places<?= $salleIndisponible ? ' · indisponible' : '' ?>)</option><?php endforeach; ?></select></label>
        <label>Coach <b>*</b><select name="coach" required><?php foreach ($d['coachs'] as $coach): ?><option value="<?= (int) $coach['NumUsers'] ?>" <?= (int) ($edition['NumUsers'] ?? 0) === (int) $coach['NumUsers'] ? 'selected' : '' ?>><?= e($coach['prenomUsers'] . ' ' . $coach['nomUsers']) ?></option><?php endforeach; ?></select></label>
        <label>Date <b>*</b><input type="date" name="date" value="<?= e($edition ? date('Y-m-d', strtotime($edition['date_heure_debut'])) : $debutSemainePlanning->format('Y-m-d')) ?>" required></label>
        <label>Heure de début <b>*</b><input type="time" name="heure" value="<?= e($edition ? date('H:i', strtotime($edition['date_heure_debut'])) : '09:00') ?>" step="900" required></label>
        <label>Durée <b>*</b><select name="duree" required><?php foreach ([30,45,60,75,90,120,180] as $duree): ?><option value="<?= $duree ?>" <?= $dureeForm === $duree ? 'selected' : '' ?>><?= $duree >= 60 ? ($duree % 60 ? intdiv($duree,60) . ' h ' . $duree % 60 . ' min' : intdiv($duree,60) . ' heure' . ($duree > 60 ? 's' : '')) : $duree . ' min' ?></option><?php endforeach; ?></select></label>
        <label>Capacité maximale <b>*</b><input type="number" name="capacite" value="<?= (int) ($edition['capacite_max'] ?? 15) ?>" min="1" required></label>
        <?php if (!$edition): ?><details class="admin-planning-repeat"><summary>Répéter ce cours chaque semaine</summary><label>Jusqu’au<input type="date" name="jusqu_au" min="<?= e($debutSemainePlanning->format('Y-m-d')) ?>"><input type="checkbox" name="repeter" value="1"> Activer la répétition hebdomadaire</label></details><?php endif; ?>
        <div class="admin-planning-form-actions"><a href="<?= e(url('admin/espace.php?onglet=planning&semaine=' . $d['debut_semaine'] . '#planning-calendrier')) ?>">Annuler</a><button type="submit"><?= $edition ? 'Enregistrer' : 'Enregistrer le cours' ?></button></div>
      </form>
    </aside>
  </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
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
  const repeatToggle = document.querySelector('input[name="repeter"]'); const until = document.querySelector('input[name="jusqu_au"]');
  if (repeatToggle && until) { const sync = () => { until.required = repeatToggle.checked; until.disabled = !repeatToggle.checked; }; repeatToggle.addEventListener('change', sync); sync(); }
});
</script>
