<?php
$salleEnEdition = $vueSalleAdmin === 'modifier' ? $salleSelectionneeAdmin : null;
$limitePhotoSalle = ActiviteCatalogue::limiteUploadImage();
$limitePhotoSalleMo = number_format($limitePhotoSalle / 1048576, 1, ',', ' ');
$urlRetourSalles = url('admin/espace.php?onglet=salles');
?>
<section class="admin-rooms admin-rooms-form-page">
  <header class="admin-rooms-heading">
    <div class="admin-rooms-brandline"><span class="admin-rooms-mark" aria-hidden="true">◆</span><span>GESTION DES ESPACES SPORTIFS</span></div>
    <div class="admin-room-title-row">
      <div>
        <h2><?= $salleEnEdition ? 'Modifier une salle' : 'Ajouter une salle' ?></h2>
        <p><?= $salleEnEdition ? 'Mettez à jour les informations de cet espace sportif.' : 'Renseignez les informations du nouvel espace sportif.' ?></p>
      </div>
      <a class="admin-room-back-button" href="<?= e($urlRetourSalles) ?>">Retour aux salles</a>
    </div>
  </header>

  <section class="admin-room-editor">
    <header>
      <p class="admin-eyebrow"><?= $salleEnEdition ? 'MODIFICATION' : 'NOUVEL ESPACE' ?></p>
      <h3>Informations de la salle</h3>
      <p>Les champs marqués d’un astérisque sont obligatoires.</p>
    </header>
    <form method="post" enctype="multipart/form-data" class="admin-room-form">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="<?= $salleEnEdition ? 'modifier_salle' : 'creer_salle' ?>">
      <?php if ($salleEnEdition): ?><input type="hidden" name="salle" value="<?= (int) $salleEnEdition['Numsalle'] ?>"><?php endif; ?>
      <div class="admin-room-form-image">
        <?php if (!empty($salleEnEdition['image_salle'])): ?>
          <img src="<?= e(url($salleEnEdition['image_salle'])) ?>" alt="Photo actuelle de la salle">
        <?php else: ?>
          <div class="admin-room-image-placeholder" aria-hidden="true">▧</div>
        <?php endif; ?>
        <label class="admin-room-image-upload">
          <input type="file" name="image" id="image-salle-upload" accept="image/jpeg,image/png,image/webp" data-max-bytes="<?= $limitePhotoSalle ?>">
          <span>Choisir une image</span>
          <small>JPEG, PNG ou WebP · <?= e($limitePhotoSalleMo) ?> Mo max après compression</small>
          <span id="image-salle-status" aria-live="polite"></span>
        </label>
        <?php if (!empty($salleEnEdition['image_salle'])): ?>
          <label class="admin-room-remove-image"><input type="checkbox" name="retirer_image" value="1"> Retirer l’image actuelle</label>
        <?php endif; ?>
      </div>
      <div class="admin-room-form-fields">
        <label>Nom de la salle <b>*</b><input type="text" name="nom" maxlength="60" placeholder="Ex. : Studio cardio" value="<?= e($salleEnEdition['nomsalle'] ?? '') ?>" required></label>
        <label>Capacité d’accueil <b>*</b><input type="number" name="capacite" min="1" max="1000" placeholder="Ex. : 20" value="<?= e(isset($salleEnEdition['capacitesalle']) ? (string) $salleEnEdition['capacitesalle'] : '') ?>" required></label>
        <label>Statut <b>*</b><select name="etat" required><?php foreach (SalleAdmin::ETATS as $etat => $libelle): ?><option value="<?= e($etat) ?>" <?= ($salleEnEdition['etatsalle'] ?? 'Ouverte') === $etat ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></label>
        <label>Localisation<input type="text" name="localisation" maxlength="80" placeholder="Ex. : Rez-de-chaussée" value="<?= e($salleEnEdition['localisationsalle'] ?? '') ?>"></label>
        <label>Superficie (m²)<input type="number" name="superficie" min="0.1" max="9999.99" step="0.01" placeholder="Ex. : 85" value="<?= e(isset($salleEnEdition['superficiesalle']) ? (string) $salleEnEdition['superficiesalle'] : '') ?>"></label>
        <label class="admin-room-description-field">Description<textarea name="description" rows="3" maxlength="2000" placeholder="Décrivez la salle, ses équipements et ses spécificités…"><?= e($salleEnEdition['description_salle'] ?? '') ?></textarea></label>
        <div class="admin-room-form-actions">
          <a class="admin-room-cancel-button" href="<?= e($urlRetourSalles) ?>">Annuler</a>
          <button type="submit" class="admin-room-save-button"><?= $salleEnEdition ? 'Enregistrer les modifications' : 'Enregistrer la salle' ?></button>
        </div>
      </div>
    </form>
  </section>
</section>

<script>
(() => {
  const input = document.getElementById('image-salle-upload');
  const status = document.getElementById('image-salle-status');
  if (!input || !status) return;
  const limit = Number(input.dataset.maxBytes || 1900000);
  input.addEventListener('change', async () => {
    const original = input.files?.[0];
    status.textContent = '';
    if (!original || original.size <= limit) return;
    if (!original.type.startsWith('image/')) {
      status.textContent = 'Choisissez une image valide.';
      input.value = '';
      return;
    }
    if (original.size > 25 * 1048576) {
      status.textContent = 'Le fichier dépasse 25 Mo avant compression.';
      input.value = '';
      return;
    }

    status.textContent = 'Compression de l’image…';
    const source = URL.createObjectURL(original);
    try {
      const image = new Image();
      await new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = reject;
        image.src = source;
      });
      let scale = Math.min(1, 2200 / Math.max(image.naturalWidth, image.naturalHeight));
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d');
      let blob = null;
      for (let attempt = 0; attempt < 10; attempt++) {
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', Math.max(.52, .88 - attempt * .06)));
        if (blob && blob.size <= limit) break;
        scale *= .82;
      }
      if (!blob || blob.size > limit) throw new Error('compression');
      const file = new File([blob], original.name.replace(/\.[^.]+$/, '') + '.jpg', {
        type: 'image/jpeg',
        lastModified: Date.now(),
      });
      const transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
      status.textContent = 'Image optimisée pour le transfert.';
    } catch (error) {
      status.textContent = 'Compression impossible. Choisissez une image plus petite.';
      input.value = '';
    } finally {
      URL.revokeObjectURL(source);
    }
  });
})();
</script>
