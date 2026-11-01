<?php
$selection = $activiteCatalogueSelectionnee;
$creation = $vueActiviteCatalogue === 'creer';
$edition = $selection && $vueActiviteCatalogue === 'modifier';
$formulaireActif = $creation || $edition;
$limiteImageUpload = ActiviteCatalogue::limiteUploadImage();
$limiteImageUploadMo = number_format($limiteImageUpload / 1048576, 1, ',', ' ');
?>
<section class="admin-activities">
  <header class="admin-activities-heading">
    <div><p class="admin-eyebrow">CATALOGUE PUBLIC</p><h3>Activités</h3><p>Les fiches du catalogue sont publiées sur la page d’accueil dès leur enregistrement.</p></div>
  </header>

  <?php if ($formulaireActif): ?>
    <a class="admin-activity-back" href="<?= e(url('admin/espace.php?onglet=activites')) ?>">← Retour à la liste</a>
    <section class="admin-activity-editor">
      <header><p class="admin-eyebrow"><?= $edition ? 'MODIFICATION' : 'NOUVELLE FICHE' ?></p><h4><?= $edition ? 'Modifier l’activité' : 'Ajouter une activité' ?></h4><p>Cette fiche sera publiée dans la section Activités de la page d’accueil.</p></header>
    <form method="post" enctype="multipart/form-data" class="admin-activity-form">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="<?= $edition ? 'modifier_activite_catalogue' : 'creer_activite_catalogue' ?>">
      <?php if ($edition): ?><input type="hidden" name="activite" value="<?= (int) $selection['Numcatalogue'] ?>"><?php endif; ?>
      <label>Titre de l’activité<input type="text" name="titre" maxlength="100" value="<?= e($selection['titre'] ?? '') ?>" required></label>
      <label>Discipline / image<select name="type_activite" required><?php foreach (ActiviteCatalogue::TYPES as $type => $libelle): ?><option value="<?= e($type) ?>" <?= ($selection['type_activite'] ?? '') === $type ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></label>
      <label class="admin-activity-description">Image de l’activité<input id="image-activite-upload" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-max-bytes="<?= $limiteImageUpload ?>"><small>JPEG, PNG ou WebP · jusqu’à <?= e($limiteImageUploadMo) ?> Mo après compression<?= !empty($selection['image_path']) ? ' · Laissez vide pour conserver l’image actuelle' : '' ?></small><span class="admin-activity-upload-status" id="image-activite-status" aria-live="polite"></span></label>
      <?php if (!empty($selection['image_path'])): ?><div class="admin-activity-image-current"><img src="<?= e(url($selection['image_path'])) ?>" alt="Image actuelle de <?= e($selection['titre']) ?>"><label><input type="checkbox" name="retirer_image" value="1"> Retirer l’image actuelle</label></div><?php endif; ?>
      <label class="admin-activity-description">Description<textarea name="description" rows="4" maxlength="2000" required><?= e($selection['description'] ?? '') ?></textarea></label>
      <div class="admin-activity-form-actions"><button class="btn btn-vert" type="submit"><?= $edition ? 'Enregistrer les modifications' : 'Ajouter à la page publique' ?></button>
        <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=activites' . ($selection ? '&activite_id=' . (int) $selection['Numcatalogue'] . '&vue=voir' : ''))) ?>">Annuler</a></div>
    </form>
    </section>
  <?php else: ?>
    <a class="admin-activity-create-trigger" href="<?= e(url('admin/espace.php?onglet=activites&vue=creer')) ?>">
      <span class="admin-activity-create-icon" aria-hidden="true">+</span>
      <span class="admin-activity-create-copy"><strong>Ajouter une activité</strong><small>Créer une nouvelle fiche pour la page d’accueil</small></span>
      <span class="admin-activity-create-chevron" aria-hidden="true">→</span>
    </a>

  <div class="admin-activities-layout <?= $selection && !$edition ? 'has-detail' : 'is-full' ?>">
    <section class="admin-activities-list">
      <div class="admin-activities-list-heading"><div><h4>Liste des activités</h4><p><?= count($activitesCatalogueAdmin) ?> activité<?= count($activitesCatalogueAdmin) > 1 ? 's' : '' ?></p></div>
        <label><span aria-hidden="true">⌕</span><input id="recherche-activite-admin" type="search" placeholder="Rechercher une activité" autocomplete="off"></label></div>
      <?php if (!$activitesCatalogueAdmin): ?><div class="vide">Aucune activité au catalogue.</div><?php else: ?>
      <div class="table-scroll"><table class="tableau admin-activities-table"><thead><tr><th>Activité</th><th>Discipline</th><th>Description</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($activitesCatalogueAdmin as $activite):
          $urlBase = 'admin/espace.php?onglet=activites&activite_id=' . (int) $activite['Numcatalogue'];
        ?>
        <tr data-activite-row data-search="<?= e(mb_strtolower($activite['titre'] . ' ' . (ActiviteCatalogue::TYPES[$activite['type_activite']] ?? $activite['type_activite']) . ' ' . $activite['description'])) ?>">
          <td><strong><?= e($activite['titre']) ?></strong></td>
          <td><?= e(ActiviteCatalogue::TYPES[$activite['type_activite']] ?? $activite['type_activite']) ?></td>
          <td class="admin-activity-excerpt"><?= e($activite['description']) ?></td>
          <td class="admin-activity-actions"><a class="btn btn-sm btn-ghost" href="<?= e(url($urlBase . '&vue=voir')) ?>">Lire</a><a class="btn btn-sm btn-ghost" href="<?= e(url($urlBase . '&vue=modifier')) ?>">Modifier</a>
            <form method="post" style="margin:0"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer_activite_catalogue"><input type="hidden" name="activite" value="<?= (int) $activite['Numcatalogue'] ?>"><button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer cette activité de la page publique ?">Supprimer</button></form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody></table></div>
      <p class="admin-activity-search-empty" id="activites-admin-vide" hidden>Aucune activité ne correspond à la recherche.</p>
      <?php endif; ?>
    </section>

    <?php if ($selection && !$edition): ?>
      <?php $imagesCatalogue = ['cross'=>'cross.png','pilates'=>'pilate.png','abdos'=>'abdos.png','boxe'=>'boxe.png','danse'=>'danse.png','yoga'=>'yogas.png','cardio'=>'cross.png','musculation'=>'cross.png']; ?>
      <aside class="admin-activity-detail">
        <a class="admin-activity-detail-close" href="<?= e(url('admin/espace.php?onglet=activites')) ?>" aria-label="Fermer">×</a>
        <p class="admin-eyebrow">FICHE ACTIVITÉ</p>
        <img src="<?= e(url($selection['image_path'] ?: 'assets/img/' . ($imagesCatalogue[$selection['type_activite']] ?? 'cross.png'))) ?>" alt="<?= e($selection['titre']) ?>">
        <span class="admin-activity-type"><?= e(ActiviteCatalogue::TYPES[$selection['type_activite']] ?? $selection['type_activite']) ?></span>
        <h4><?= e($selection['titre']) ?></h4>
        <p><?= nl2br(e($selection['description'])) ?></p>
        <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=activites&activite_id=' . (int) $selection['Numcatalogue'] . '&vue=modifier')) ?>">Modifier cette activité</a>
      </aside>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<script>
document.getElementById('recherche-activite-admin')?.addEventListener('input', function () {
  const terme = this.value.trim().toLocaleLowerCase('fr'); let visibles = 0;
  document.querySelectorAll('[data-activite-row]').forEach(ligne => { ligne.hidden = !ligne.dataset.search.includes(terme); if (!ligne.hidden) visibles++; });
  const message = document.getElementById('activites-admin-vide'); if (message) message.hidden = visibles > 0;
});
</script>
<script>
(() => {
  const input = document.getElementById('image-activite-upload');
  const status = document.getElementById('image-activite-status');
  if (!input || !status) return;
  const limite = Number(input.dataset.maxBytes || 1900000);

  input.addEventListener('change', async () => {
    const original = input.files?.[0];
    status.textContent = '';
    if (!original || original.size <= limite) return;
    if (!original.type.startsWith('image/')) { status.textContent = 'Choisissez un fichier image.'; input.value = ''; return; }
    if (original.size > 25 * 1048576) { status.textContent = 'Le fichier dépasse 25 Mo avant compression.'; input.value = ''; return; }

    status.textContent = 'Compression de l’image…';
    try {
      const image = new Image();
      const source = URL.createObjectURL(original);
      await new Promise((resolve, reject) => { image.onload = resolve; image.onerror = reject; image.src = source; });
      let echelle = Math.min(1, 2200 / Math.max(image.naturalWidth, image.naturalHeight));
      const canvas = document.createElement('canvas');
      const contexte = canvas.getContext('2d');
      let fichierCompresse = null;
      for (let tentative = 0; tentative < 10; tentative++) {
        canvas.width = Math.max(1, Math.round(image.naturalWidth * echelle));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * echelle));
        contexte.fillStyle = '#fff';
        contexte.fillRect(0, 0, canvas.width, canvas.height);
        contexte.drawImage(image, 0, 0, canvas.width, canvas.height);
        fichierCompresse = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', Math.max(.52, .88 - tentative * .06)));
        if (fichierCompresse && fichierCompresse.size <= limite) break;
        echelle *= .82;
      }
      URL.revokeObjectURL(source);
      if (!fichierCompresse || fichierCompresse.size > limite) throw new Error('compression');
      const resultat = new File([fichierCompresse], original.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
      const transfert = new DataTransfer();
      transfert.items.add(resultat);
      input.files = transfert.files;
      status.textContent = 'Image optimisée pour le transfert.';
    } catch (erreur) {
      status.textContent = 'La compression a échoué. Choisissez une image plus petite.';
      input.value = '';
    }
  });
})();
</script>
