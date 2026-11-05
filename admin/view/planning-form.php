<?php
// Préfixe des liens du planning : admin par défaut, l'espace employé le redéfinit avant l'inclusion
$planningBase = $planningBase ?? 'admin/espace.php?onglet=planning';
$d = $planningDonneesAdmin;
$edition = $creneauAModifier;
$debutSemainePlanning = new DateTimeImmutable($d['debut_semaine']);
$dureeForm = $edition
    ? max(15, (int) round((strtotime($edition['date_heure_fin']) - strtotime($edition['date_heure_debut'])) / 60))
    : 60;
$semaineRetour = $d['debut_semaine'];
$urlRetourPlanning = url($planningBase . '&semaine=' . $semaineRetour);
?>
<section class="admin-planning-page admin-planning-form-page">
  <header class="admin-planning-heading">
    <div class="admin-planning-heading-copy">
      <span class="admin-planning-icon" aria-hidden="true"><?= $edition ? '✎' : '＋' ?></span>
      <div>
        <h2><?= $edition ? 'Modifier un cours' : 'Ajouter un cours' ?></h2>
        <p><?= $edition ? 'Mettez à jour les informations de cette séance.' : 'Renseignez les informations pour ajouter une séance au planning.' ?></p>
      </div>
    </div>
    <a class="admin-planning-back-link" href="<?= e($urlRetourPlanning) ?>">Retour au planning</a>
  </header>

  <section class="admin-planning-editor admin-planning-editor-page">
    <header><span aria-hidden="true">▦</span><h3>Informations du cours</h3></header>
    <form method="post">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="<?= $edition ? 'modifier_creneau' : 'creer_creneau' ?>">
      <input type="hidden" name="semaine" value="<?= e($d['debut_semaine']) ?>">
      <?php if ($edition): ?><input type="hidden" name="creneau" value="<?= (int) $edition['Numactivite'] ?>"><?php endif; ?>
      <label>Activité <b>*</b><input name="titre" list="admin-planning-titres" maxlength="100" placeholder="Sélectionner une activité" value="<?= e($edition['titre'] ?? '') ?>" required><datalist id="admin-planning-titres"><?php foreach ($d['titres'] as $titre): ?><option value="<?= e($titre) ?>"><?php endforeach; ?></datalist></label>
      <label>Type <b>*</b><select name="type" required><?php foreach (PlanningController::TYPES as $cle => $libelle): ?><option value="<?= e($cle) ?>" <?= ($edition['type_activite'] ?? 'cardio') === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></label>
      <label>Salle <b>*</b><select name="salle" required><?php foreach ($d['salles'] as $salle): $salleIndisponible = ($salle['etatsalle'] ?? 'Ouverte') !== 'Ouverte' && (int) ($edition['Numsalle'] ?? 0) !== (int) $salle['Numsalle']; ?><option value="<?= (int) $salle['Numsalle'] ?>" <?= (int) ($edition['Numsalle'] ?? 0) === (int) $salle['Numsalle'] ? 'selected' : '' ?> <?= $salleIndisponible ? 'disabled' : '' ?>><?= e($salle['nomsalle']) ?> (<?= (int) $salle['capacitesalle'] ?> places<?= $salleIndisponible ? ' · indisponible' : '' ?>)</option><?php endforeach; ?></select></label>
      <label>Coach <b>*</b><select name="coach" required><?php foreach ($d['coachs'] as $coach): ?><option value="<?= (int) $coach['NumUsers'] ?>" <?= (int) ($edition['NumUsers'] ?? 0) === (int) $coach['NumUsers'] ? 'selected' : '' ?>><?= e($coach['prenomUsers'] . ' ' . $coach['nomUsers']) ?></option><?php endforeach; ?></select></label>
      <label>Date <b>*</b><input type="date" name="date" value="<?= e($edition ? date('Y-m-d', strtotime($edition['date_heure_debut'])) : $debutSemainePlanning->format('Y-m-d')) ?>" required></label>
      <label>Heure de début <b>*</b><input type="time" name="heure" value="<?= e($edition ? date('H:i', strtotime($edition['date_heure_debut'])) : '09:00') ?>" step="900" required></label>
      <label>Durée <b>*</b><select name="duree" required><?php foreach ([30,45,60,75,90,120,180] as $duree): ?><option value="<?= $duree ?>" <?= $dureeForm === $duree ? 'selected' : '' ?>><?= $duree >= 60 ? ($duree % 60 ? intdiv($duree,60) . ' h ' . $duree % 60 . ' min' : intdiv($duree,60) . ' heure' . ($duree > 60 ? 's' : '')) : $duree . ' min' ?></option><?php endforeach; ?></select></label>
      <label>Capacité maximale <b>*</b><input type="number" name="capacite" value="<?= (int) ($edition['capacite_max'] ?? 15) ?>" min="1" required></label>
      <?php if (!$edition): ?>
        <details class="admin-planning-repeat">
          <summary>Répéter ce cours chaque semaine</summary>
          <label>Jusqu’au<input type="date" name="jusqu_au" min="<?= e($debutSemainePlanning->format('Y-m-d')) ?>"><span><input type="checkbox" name="repeter" value="1"> Activer la répétition hebdomadaire</span></label>
        </details>
      <?php endif; ?>
      <div class="admin-planning-form-actions">
        <a href="<?= e($urlRetourPlanning) ?>">Annuler</a>
        <button type="submit"><?= $edition ? 'Enregistrer les modifications' : 'Enregistrer le cours' ?></button>
      </div>
    </form>
  </section>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const repeatToggle = document.querySelector('input[name="repeter"]');
  const until = document.querySelector('input[name="jusqu_au"]');
  if (repeatToggle && until) {
    const sync = () => {
      until.required = repeatToggle.checked;
      until.disabled = !repeatToggle.checked;
    };
    repeatToggle.addEventListener('change', sync);
    sync();
  }
});
</script>