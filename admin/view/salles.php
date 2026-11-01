<?php
$salleEnEdition = $vueSalleAdmin === 'modifier' ? $salleSelectionneeAdmin : null;
$nombreOuvertes = count(array_filter($sallesAdmin, static fn(array $salle): bool => $salle['etatsalle'] === 'Ouverte'));
$nombreMaintenance = count(array_filter($sallesAdmin, static fn(array $salle): bool => $salle['etatsalle'] === 'Maintenance'));
$nombreHorsService = count(array_filter($sallesAdmin, static fn(array $salle): bool => $salle['etatsalle'] === 'Hors service'));
$imagesSalles = [
    'cardio' => 'cross.png', 'zen' => 'yogas.png', 'yoga' => 'yogas.png', 'pilates' => 'pilate.png',
    'cross' => 'cross.png', 'muscu' => 'header.png', 'collectif' => 'danse.png', 'polyvalente' => 'header.png',
];
$descriptionsSalles = [
    'cardio' => 'Zone cardio équipée pour travailler l’endurance et le souffle.',
    'zen' => 'Espace calme consacré au yoga, au Pilates et à la mobilité.',
    'yoga' => 'Espace calme consacré au yoga, au Pilates et à la mobilité.',
    'pilates' => 'Une salle lumineuse pour les séances de renforcement doux et de mobilité.',
    'cross' => 'Espace fonctionnel adapté aux entraînements intensifs et au travail en circuit.',
    'muscu' => 'Plateau dédié au renforcement musculaire avec les équipements de la salle.',
    'collectif' => 'Espace prévu pour les séances collectives et les cours en groupe.',
    'polyvalente' => 'Salle modulable pour différents entraînements et événements sportifs.',
];
$limitePhotoSalle = ActiviteCatalogue::limiteUploadImage();
$limitePhotoSalleMo = number_format($limitePhotoSalle / 1048576, 1, ',', ' ');
?>
<section class="admin-rooms">
  <header class="admin-rooms-heading">
    <div class="admin-rooms-brandline"><span class="admin-rooms-mark" aria-hidden="true">◆</span><span>GESTION DES ESPACES SPORTIFS</span></div>
    <div class="admin-room-title-row"><div><h2>Gestion des salles</h2><p>Consultez l’état des espaces et gérez les salles de votre centre.</p></div><a class="admin-room-add-button" href="#salle-formulaire"><span aria-hidden="true">＋</span> Ajouter une salle</a></div>
  </header>

  <div class="admin-room-stats">
    <article><span class="room-stat-icon" aria-hidden="true">⌂</span><div><strong><?= count($sallesAdmin) ?></strong><span>Salles au total</span></div></article>
    <article><span class="room-stat-icon" aria-hidden="true">✓</span><div><strong><?= $nombreOuvertes ?></strong><span>Salles disponibles</span></div></article>
    <article><span class="room-stat-icon" aria-hidden="true">◷</span><div><strong><?= $nombreMaintenance ?></strong><span>Salles en maintenance</span></div></article>
    <article><span class="room-stat-icon" aria-hidden="true">⊘</span><div><strong><?= $nombreHorsService ?></strong><span>Salles hors service</span></div></article>
  </div>

  <div class="admin-room-list-heading">
    <h3>Liste des salles <span><?= count($sallesAdmin) ?></span></h3>
    <div class="admin-room-controls">
      <label class="admin-room-search"><span aria-hidden="true">⌕</span><input id="recherche-salle" type="search" placeholder="Rechercher une salle…" autocomplete="off"></label>
      <select id="filtre-statut-salle" aria-label="Filtrer par statut"><option value="">Tous les statuts</option><?php foreach (SalleAdmin::ETATS as $etat => $libelle): ?><option value="<?= e($etat) ?>"><?= e($libelle) ?></option><?php endforeach; ?></select>
      <select id="tri-salle" aria-label="Trier les salles"><option value="nom">Nom (A–Z)</option><option value="capacite">Capacité (décroissante)</option></select>
      <div class="admin-room-view-buttons" aria-label="Présentation des salles"><button type="button" class="is-active" data-room-view="grid" aria-label="Affichage en grille">▦</button><button type="button" data-room-view="list" aria-label="Affichage en liste">☷</button></div>
    </div>
  </div>

  <?php if (!$sallesAdmin): ?>
    <div class="admin-room-empty"><h3>Aucune salle enregistrée</h3><p>Ajoutez la première salle de votre centre depuis le formulaire ci-dessous.</p></div>
  <?php else: ?>
    <div class="admin-room-cards" id="cartes-salles">
      <?php foreach ($sallesAdmin as $salle):
        $nomSalleNormalise = mb_strtolower($salle['nomsalle']);
        $imageDefautSalle = 'header.png';
        foreach ($imagesSalles as $motCleSalle => $imageSalle) {
            if (str_contains($nomSalleNormalise, $motCleSalle)) { $imageDefautSalle = $imageSalle; break; }
        }
        $descriptionDefautSalle = 'Espace sportif ' . ($salle['localisationsalle'] ? 'situé à ' . $salle['localisationsalle'] : 'du centre') . ', aménagé pour accueillir vos entraînements.';
        foreach ($descriptionsSalles as $motCleSalle => $descriptionSalle) {
            if (str_contains($nomSalleNormalise, $motCleSalle)) { $descriptionDefautSalle = $descriptionSalle; break; }
        }
        $descriptionAffichee = trim((string) ($salle['description_salle'] ?? '')) ?: $descriptionDefautSalle;
        $etatSalle = isset(SalleAdmin::ETATS[$salle['etatsalle']]) ? $salle['etatsalle'] : 'Ouverte';
        $nomEtatSalle = SalleAdmin::ETATS[$etatSalle];
        $rechercheSalle = mb_strtolower($salle['nomsalle'] . ' ' . $salle['localisationsalle'] . ' ' . $descriptionAffichee);
      ?>
        <article class="admin-room-card" data-room-card data-room-name="<?= e($nomSalleNormalise) ?>" data-room-capacity="<?= (int) $salle['capacitesalle'] ?>" data-room-status="<?= e($etatSalle) ?>" data-search="<?= e($rechercheSalle) ?>">
          <img class="admin-room-photo" src="<?= e(url($salle['image_salle'] ?: 'assets/img/' . $imageDefautSalle)) ?>" alt="Salle <?= e($salle['nomsalle']) ?>" loading="lazy">
          <div class="admin-room-card-body">
            <div class="admin-room-card-title"><span class="admin-room-type-icon" aria-hidden="true">⌂</span><div><h4><?= e($salle['nomsalle']) ?></h4><p><?= (int) $salle['capacitesalle'] ?> places<?= $salle['superficiesalle'] ? ' · ' . e((string) $salle['superficiesalle']) . ' m²' : '' ?></p></div></div>
            <span class="admin-room-status <?= $etatSalle === 'Ouverte' ? 'is-available' : ($etatSalle === 'Maintenance' ? 'is-maintenance' : 'is-out') ?>"><i></i><?= e($nomEtatSalle) ?></span>
            <p class="admin-room-description"><?= e($descriptionAffichee) ?></p>
            <div class="admin-room-card-actions"><a class="admin-room-details-button" href="#details-salle-<?= (int) $salle['Numsalle'] ?>" data-room-open="details-salle-<?= (int) $salle['Numsalle'] ?>">Voir les détails</a><a class="admin-room-icon-button" href="<?= e(url('admin/espace.php?onglet=salles&vue=modifier&id=' . (int) $salle['Numsalle'] . '#salle-formulaire')) ?>" aria-label="Modifier <?= e($salle['nomsalle']) ?>">✎</a><form method="post" class="admin-room-delete-form"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer_salle"><input type="hidden" name="salle" value="<?= (int) $salle['Numsalle'] ?>"><button type="submit" class="admin-room-icon-button" aria-label="Supprimer <?= e($salle['nomsalle']) ?>" data-confirme="Supprimer cette salle ? Les salles utilisées dans le planning ne peuvent pas être supprimées.">⋮</button></form></div>
            <div class="admin-room-detail-overlay" id="details-salle-<?= (int) $salle['Numsalle'] ?>" hidden><button type="button" class="admin-room-detail-close" aria-label="Fermer">×</button><div class="admin-room-detail-panel"><img src="<?= e(url($salle['image_salle'] ?: 'assets/img/' . $imageDefautSalle)) ?>" alt=""><p class="admin-eyebrow">FICHE SALLE</p><h4><?= e($salle['nomsalle']) ?></h4><p><?= e($descriptionAffichee) ?></p><dl><div><dt>Statut</dt><dd><?= e($nomEtatSalle) ?></dd></div><div><dt>Capacité</dt><dd><?= (int) $salle['capacitesalle'] ?> personnes</dd></div><div><dt>Localisation</dt><dd><?= e($salle['localisationsalle'] ?: 'Non renseignée') ?></dd></div><?php if ($salle['superficiesalle']): ?><div><dt>Superficie</dt><dd><?= e((string) $salle['superficiesalle']) ?> m²</dd></div><?php endif; ?></dl></div></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="admin-room-filter-empty" id="salles-filtre-vide" hidden>Aucune salle ne correspond aux filtres.</p>
  <?php endif; ?>

  <section class="admin-room-editor" id="salle-formulaire">
    <header><p class="admin-eyebrow"><?= $salleEnEdition ? 'MODIFIER' : 'NOUVELLE SALLE' ?></p><h3><?= $salleEnEdition ? 'Modifier une salle' : 'Ajouter une salle' ?></h3><p>Renseignez les informations qui apparaîtront dans la gestion des espaces.</p></header>
    <form method="post" enctype="multipart/form-data" class="admin-room-form">
      <?= champ_csrf() ?><input type="hidden" name="action" value="<?= $salleEnEdition ? 'modifier_salle' : 'creer_salle' ?>">
      <?php if ($salleEnEdition): ?><input type="hidden" name="salle" value="<?= (int) $salleEnEdition['Numsalle'] ?>"><?php endif; ?>
      <div class="admin-room-form-image">
        <?php if (!empty($salleEnEdition['image_salle'])): ?><img src="<?= e(url($salleEnEdition['image_salle'])) ?>" alt="Photo actuelle de la salle">
        <?php else: ?><div class="admin-room-image-placeholder" aria-hidden="true">▧</div><?php endif; ?>
        <label class="admin-room-image-upload"><input type="file" name="image" id="image-salle-upload" accept="image/jpeg,image/png,image/webp" data-max-bytes="<?= $limitePhotoSalle ?>"><span>Choisir une image</span><small>JPEG, PNG ou WebP · <?= e($limitePhotoSalleMo) ?> Mo max après compression</small><span id="image-salle-status" aria-live="polite"></span></label>
        <?php if (!empty($salleEnEdition['image_salle'])): ?><label class="admin-room-remove-image"><input type="checkbox" name="retirer_image" value="1"> Retirer l’image actuelle</label><?php endif; ?>
      </div>
      <div class="admin-room-form-fields">
        <label>Nom de la salle <b>*</b><input type="text" name="nom" maxlength="60" placeholder="Ex. : Studio cardio" value="<?= e($salleEnEdition['nomsalle'] ?? '') ?>" required></label>
        <label>Capacité d’accueil <b>*</b><input type="number" name="capacite" min="1" max="1000" placeholder="Ex. : 20" value="<?= e(isset($salleEnEdition['capacitesalle']) ? (string) $salleEnEdition['capacitesalle'] : '') ?>" required></label>
        <label>Statut <b>*</b><select name="etat" required><?php foreach (SalleAdmin::ETATS as $etat => $libelle): ?><option value="<?= e($etat) ?>" <?= ($salleEnEdition['etatsalle'] ?? 'Ouverte') === $etat ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></label>
        <label>Localisation<input type="text" name="localisation" maxlength="80" placeholder="Ex. : Rez-de-chaussée" value="<?= e($salleEnEdition['localisationsalle'] ?? '') ?>"></label>
        <label>Superficie (m²)<input type="number" name="superficie" min="0.1" max="9999.99" step="0.01" placeholder="Ex. : 85" value="<?= e(isset($salleEnEdition['superficiesalle']) ? (string) $salleEnEdition['superficiesalle'] : '') ?>"></label>
        <label class="admin-room-description-field">Description<textarea name="description" rows="3" maxlength="2000" placeholder="Décrivez la salle, ses équipements et ses spécificités…"><?= e($salleEnEdition['description_salle'] ?? '') ?></textarea></label>
        <div class="admin-room-form-actions"><button type="submit" class="admin-room-save-button"><?= $salleEnEdition ? 'Enregistrer les modifications' : 'Enregistrer la salle' ?></button><?php if ($salleEnEdition): ?><a class="admin-room-cancel-button" href="<?= e(url('admin/espace.php?onglet=salles')) ?>">Annuler</a><?php endif; ?></div>
      </div>
    </form>
  </section>
</section>

<script>
(() => {
  const cards = document.getElementById('cartes-salles');
  const search = document.getElementById('recherche-salle');
  const statusFilter = document.getElementById('filtre-statut-salle');
  const sortSelect = document.getElementById('tri-salle');
  const empty = document.getElementById('salles-filtre-vide');
  const applyFilters = () => {
    if (!cards) return;
    const term = (search?.value || '').trim().toLocaleLowerCase('fr');
    const status = statusFilter?.value || '';
    let visible = 0;
    cards.querySelectorAll('[data-room-card]').forEach(card => {
      card.hidden = !(card.dataset.search.includes(term) && (!status || card.dataset.roomStatus === status));
      if (!card.hidden) visible++;
    });
    if (empty) empty.hidden = visible > 0;
  };
  search?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);
  sortSelect?.addEventListener('change', () => {
    const sorted = [...cards.querySelectorAll('[data-room-card]')].sort((a,b) => sortSelect.value === 'capacite' ? Number(b.dataset.roomCapacity)-Number(a.dataset.roomCapacity) : a.dataset.roomName.localeCompare(b.dataset.roomName,'fr'));
    sorted.forEach(card => cards.append(card));
  });
  document.querySelectorAll('[data-room-view]').forEach(button => button.addEventListener('click', () => {
    cards?.classList.toggle('is-list', button.dataset.roomView === 'list');
    document.querySelectorAll('[data-room-view]').forEach(item => item.classList.toggle('is-active', item === button));
  }));
  document.querySelectorAll('[data-room-open]').forEach(button => button.addEventListener('click', event => {
    event.preventDefault(); const panel = document.getElementById(button.dataset.roomOpen); if (panel) panel.hidden = false;
  }));
  document.querySelectorAll('.admin-room-detail-close').forEach(button => button.addEventListener('click', () => { const panel = button.closest('.admin-room-detail-overlay'); if (panel) panel.hidden = true; }));

  const input = document.getElementById('image-salle-upload');
  const status = document.getElementById('image-salle-status');
  if (!input || !status) return;
  const limit = Number(input.dataset.maxBytes || 1900000);
  input.addEventListener('change', async () => {
    const original = input.files?.[0]; status.textContent = '';
    if (!original || original.size <= limit) return;
    if (!original.type.startsWith('image/')) { status.textContent = 'Choisissez une image valide.'; input.value = ''; return; }
    if (original.size > 25 * 1048576) { status.textContent = 'Le fichier dépasse 25 Mo avant compression.'; input.value = ''; return; }
    status.textContent = 'Compression de l’image…';
    const source = URL.createObjectURL(original);
    try {
      const image = new Image();
      await new Promise((resolve,reject) => { image.onload=resolve; image.onerror=reject; image.src=source; });
      let scale = Math.min(1,2200/Math.max(image.naturalWidth,image.naturalHeight));
      const canvas = document.createElement('canvas'); const context = canvas.getContext('2d'); let blob = null;
      for (let i=0;i<10;i++) {
        canvas.width=Math.max(1,Math.round(image.naturalWidth*scale)); canvas.height=Math.max(1,Math.round(image.naturalHeight*scale));
        context.fillStyle='#fff'; context.fillRect(0,0,canvas.width,canvas.height); context.drawImage(image,0,0,canvas.width,canvas.height);
        blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',Math.max(.52,.88-i*.06)));
        if (blob && blob.size<=limit) break; scale*=.82;
      }
      if (!blob || blob.size>limit) throw new Error('compression');
      const file=new File([blob],original.name.replace(/\.[^.]+$/,'')+'.jpg',{type:'image/jpeg',lastModified:Date.now()});
      const transfer=new DataTransfer(); transfer.items.add(file); input.files=transfer.files; status.textContent='Image optimisée pour le transfert.';
    } catch (error) { status.textContent='Compression impossible. Choisissez une image plus petite.'; input.value=''; }
    finally { URL.revokeObjectURL(source); }
  });
})();
</script>
