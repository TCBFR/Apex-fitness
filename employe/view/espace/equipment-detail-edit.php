
              <header class="inventory-detail-heading">
                <h3><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Modifier le matériel</h3>
                <a href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $materielSelectionne['Numequip'])) ?>" aria-label="Annuler la modification"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a>
              </header>
              <form class="inventory-edit-form" method="post" enctype="multipart/form-data">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="modifier_materiel">
                <input type="hidden" name="materiel" value="<?= (int) $materielSelectionne['Numequip'] ?>">
                <label>Nom du matériel<input type="text" name="nom" maxlength="80" required value="<?= e($materielSelectionne['nomequip']) ?>"></label>
                <label>Marque<input type="text" name="marque" maxlength="60" value="<?= e($materielSelectionne['marque'] ?? '') ?>"></label>
                <label>Modèle<input type="text" name="modele" maxlength="60" value="<?= e($materielSelectionne['modele'] ?? '') ?>"></label>
                <label>Catégorie<select name="categorie" required><?php foreach ($typesEquipement as $type): ?><option value="<?= (int) $type['Numtypeequip'] ?>"<?= (int) $type['Numtypeequip'] === (int) $materielSelectionne['Numtypeequip'] ? ' selected' : '' ?>><?= e($type['libelletypeequip']) ?></option><?php endforeach; ?></select></label>
                <label>Statut<select name="statut" required><?php foreach ($statutsEquipement as $statut): ?><option value="<?= (int) $statut['Numstatut'] ?>"<?= (int) $statut['Numstatut'] === (int) $materielSelectionne['Numstatut'] ? ' selected' : '' ?>><?= e($statut['libelle_statut']) ?></option><?php endforeach; ?></select></label>
                <label>Seuil d’alerte<input type="number" name="seuil_alerte" min="0" max="10000" required value="<?= (int) $materielSelectionne['seuil_alerte'] ?>"></label>
                <label class="inventory-image-field">Image du matériel
                  <?php if (!empty($materielSelectionne['image_equipement'])): ?><img src="<?= e(url($materielSelectionne['image_equipement'])) ?>" alt="Image actuelle du matériel"><?php endif; ?>
                  <input type="file" name="image_equipement" accept="image/jpeg,image/png,image/webp">
                  <small>JPEG, PNG ou WebP · 5 Mo maximum. Laissez vide pour conserver l’image actuelle.</small>
                </label>
                <div class="inventory-edit-actions">
                  <a class="inventory-cancel-button" href="<?= e(url('employe/espace.php?onglet=materiel&materiel=' . (int) $materielSelectionne['Numequip'])) ?>">Annuler</a>
                  <button type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Enregistrer</button>
                </div>
              </form>
