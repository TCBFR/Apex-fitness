<?php $modeCreationEmploye = ($_GET['employe'] ?? '') === 'creer'; ?>
<section class="adherents-page employes-page">
  <header class="adherents-intro">
    <div>
      <h3>Répertoire des employés</h3>
      <p>Gérez les équipes Maintenance et Accueil.</p>
    </div>
    <div class="adherents-header-actions">
      <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=utilisateurs&employe=creer')) ?>">Ajouter un employé</a>
    </div>
  </header>

  <div class="adherents-layout <?= ($employeSelectionne || $modeCreationEmploye) ? 'has-detail' : 'is-full' ?>">
    <div class="adherents-list-wrap">
      <label class="adherents-search">
        <span aria-hidden="true">⌕</span>
        <input type="search" id="recherche-employe" placeholder="Rechercher un nom, un e-mail ou un service" autocomplete="off">
      </label>
      <p class="adherents-hint">Cliquez sur un employé pour consulter sa fiche.</p>

      <?php if (!$employesGestion): ?>
        <div class="vide">Aucun employé de Maintenance ou d’Accueil n’est enregistré.</div>
      <?php else: ?>
        <div class="table-scroll adherents-table-scroll">
          <table class="tableau adherents-table coachs-table" id="table-employes">
            <thead><tr><th>Employé</th><th>Service</th><th>Commune</th><th>Matricule</th><th>Embauche</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($employesGestion as $employeGestion):
              $nomComplet = trim($employeGestion['prenomUsers'] . ' ' . $employeGestion['nomUsers']);
              $selectionne = $employeSelectionne && (int) $employeSelectionne['NumUsers'] === (int) $employeGestion['NumUsers'];
              $lienFiche = url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeGestion['NumUsers'] . '&vue=voir');
            ?>
              <tr class="<?= $selectionne ? 'is-selected' : '' ?>" data-employe-row data-search="<?= e(mb_strtolower($nomComplet . ' ' . $employeGestion['mailUsers'] . ' ' . ($employeGestion['libellefonc'] ?? ''))) ?>">
                <td>
                  <a class="adherent-person" href="<?= e($lienFiche) ?>">
                    <span class="adherent-avatar"><?= e(mb_strtoupper(mb_substr($employeGestion['prenomUsers'], 0, 1) . mb_substr($employeGestion['nomUsers'], 0, 1))) ?></span>
                    <span><strong><?= e($nomComplet) ?></strong><small><?= e($employeGestion['mailUsers']) ?></small></span>
                  </a>
                </td>
                <td><?= e($employeGestion['libellefonc']) ?></td>
                <td><?= e($employeGestion['nom_commune'] ?: 'Non renseignée') ?></td>
                <td><?= e($employeGestion['matriculeemp'] ?? '') ?></td>
                <td><?= e(dateFr($employeGestion['Date_embaucheemp'] ?? '')) ?></td>
                <td>
                  <div class="adherents-actions">
                    <a class="btn btn-sm btn-ghost" href="<?= e($lienFiche) ?>">Lire</a>
                    <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeGestion['NumUsers'] . '&vue=modifier')) ?>">Modifier</a>
                    <form method="post">
                      <?= champ_csrf() ?>
                      <input type="hidden" name="action" value="supprimer">
                      <input type="hidden" name="utilisateur" value="<?= (int) $employeGestion['NumUsers'] ?>">
                      <input type="hidden" name="retour" value="employes">
                      <button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer définitivement le compte de cet employé ?">Supprimer</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($employeSelectionne || $modeCreationEmploye): ?>
      <aside class="adherent-detail" aria-live="polite">
        <?php if ($modeCreationEmploye): ?>
          <div class="adherent-detail-heading">
            <div><p class="admin-eyebrow">NOUVEAU MEMBRE DE L’ÉQUIPE</p><h3>Ajouter un employé</h3></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>" aria-label="Fermer">×</a>
          </div>
          <form class="adherent-edit-form" method="post">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="creer_employe">
            <label>Prénom<input name="prenom" required maxlength="60" autocomplete="given-name"></label>
            <label>Nom<input name="nom" required maxlength="60" autocomplete="family-name"></label>
            <label>E-mail<input type="email" name="mail" required maxlength="150" autocomplete="email"></label>
            <label>Téléphone<input type="tel" name="tel" maxlength="20" autocomplete="tel"></label>
            <div class="employe-commune-field">
              <label for="commune">Commune</label>
              <div class="commune-recherche">
                <input type="text" id="commune" value="" data-url="<?= e(url('commune/recherche.php')) ?>" autocomplete="off" placeholder="Saisir au moins 2 caractères" required>
                <div class="commune-resultats" id="commune-resultats" hidden></div>
                <input type="hidden" name="id_commune" id="id_commune" value="">
                <p id="commune-message" aria-live="polite"></p>
              </div>
              <label for="code_postal">Code postal</label>
              <input type="text" id="code_postal" value="" readonly tabindex="-1">
            </div>
            <label>Service
              <select name="fonction" required>
                <option value="">Choisir un service</option>
                <?php foreach ($fonctionsEmployeNoms as $fonction): ?>
                  <option value="<?= (int) $fonction['Numfonc'] ?>"><?= e($fonction['libellefonc']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Mot de passe<input type="password" name="motdepasse" required minlength="8" autocomplete="new-password"></label>
            <label>Confirmer le mot de passe<input type="password" name="confirmation" required minlength="8" autocomplete="new-password"></label>
            <div class="adherent-detail-actions">
              <button class="btn btn-vert" type="submit">Créer le compte employé</button>
              <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>">Annuler</a>
            </div>
          </form>
        <?php elseif (($_GET['vue'] ?? 'voir') === 'modifier'): ?>
          <div class="adherent-detail-heading">
            <div><p class="admin-eyebrow">MODIFICATION DE L’EMPLOYÉ</p><h3><?= e($employeSelectionne['prenomUsers'] . ' ' . $employeSelectionne['nomUsers']) ?></h3></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeSelectionne['NumUsers'] . '&vue=voir')) ?>" aria-label="Fermer">×</a>
          </div>
          <form class="adherent-edit-form" method="post">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="modifier_employe">
            <input type="hidden" name="utilisateur" value="<?= (int) $employeSelectionne['NumUsers'] ?>">
            <label>Prénom<input name="prenom" value="<?= e($employeSelectionne['prenomUsers']) ?>" required maxlength="60"></label>
            <label>Nom<input name="nom" value="<?= e($employeSelectionne['nomUsers']) ?>" required maxlength="60"></label>
            <label>E-mail<input type="email" name="mail" value="<?= e($employeSelectionne['mailUsers']) ?>" required></label>
            <label>Téléphone<input type="tel" name="tel" value="<?= e($employeSelectionne['telUsers'] ?? '') ?>" maxlength="20"></label>
            <div class="employe-commune-field">
              <label for="commune">Commune</label>
              <div class="commune-recherche">
                <input type="text" id="commune" value="<?= e($employeSelectionne['nom_commune'] ?? '') ?>" data-url="<?= e(url('commune/recherche.php')) ?>" autocomplete="off" placeholder="Saisir au moins 2 caractères" required>
                <div class="commune-resultats" id="commune-resultats" hidden></div>
                <input type="hidden" name="id_commune" id="id_commune" value="<?= e((string) ($employeSelectionne['id_commune'] ?? '')) ?>">
                <p id="commune-message" aria-live="polite"></p>
              </div>
              <label for="code_postal">Code postal</label>
              <input type="text" id="code_postal" value="<?= e($employeSelectionne['code_postal'] ?? '') ?>" readonly tabindex="-1">
            </div>
            <label>Service
              <select name="Numfonc" required>
                <?php foreach ($fonctionsEmployeNoms as $fonction): ?>
                  <option value="<?= (int) $fonction['Numfonc'] ?>" <?= (int) $fonction['Numfonc'] === (int) $employeSelectionne['Numfonc'] ? 'selected' : '' ?>><?= e($fonction['libellefonc']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Matricule<input name="matriculeemp" value="<?= e($employeSelectionne['matriculeemp'] ?? '') ?>" required maxlength="20"></label>
            <label>Date d’embauche<input type="date" name="Date_embaucheemp" value="<?= e($employeSelectionne['Date_embaucheemp'] ?? '') ?>" required></label>
            <div class="adherent-detail-actions">
              <button class="btn btn-vert" type="submit">Enregistrer</button>
              <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeSelectionne['NumUsers'] . '&vue=voir')) ?>">Annuler</a>
            </div>
          </form>
        <?php else: ?>
          <div class="adherent-detail-heading">
            <div><p class="admin-eyebrow">FICHE EMPLOYÉ · <?= e($employeSelectionne['matriculeemp'] ?? '') ?></p><h3><?= e($employeSelectionne['prenomUsers'] . ' ' . $employeSelectionne['nomUsers']) ?></h3></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>" aria-label="Fermer la fiche">×</a>
          </div>
          <dl class="adherent-info-list">
            <div><dt>E-mail</dt><dd><a href="mailto:<?= e($employeSelectionne['mailUsers']) ?>"><?= e($employeSelectionne['mailUsers']) ?></a></dd></div>
            <div><dt>Téléphone</dt><dd><?= e($employeSelectionne['telUsers'] ?: 'Non renseigné') ?></dd></div>
            <div><dt>Service</dt><dd><?= e($employeSelectionne['libellefonc']) ?></dd></div>
            <div><dt>Commune</dt><dd><?= e($employeSelectionne['nom_commune'] ?: 'Non renseignée') ?><?= !empty($employeSelectionne['code_postal']) ? ' · ' . e($employeSelectionne['code_postal']) : '' ?></dd></div>
            <div><dt>Matricule</dt><dd><?= e($employeSelectionne['matriculeemp'] ?? '') ?></dd></div>
            <div><dt>Date d’embauche</dt><dd><?= e(dateFr($employeSelectionne['Date_embaucheemp'] ?? '')) ?></dd></div>
            <div><dt>Compte créé le</dt><dd><?= e(dateFr($employeSelectionne['Date_CreationUsers'] ?? null, true)) ?></dd></div>
          </dl>
          <div class="adherent-detail-actions">
            <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=utilisateurs&id=' . (int) $employeSelectionne['NumUsers'] . '&vue=modifier')) ?>">Modifier la fiche</a>
          </div>
        <?php endif; ?>
      </aside>
    <?php endif; ?>
  </div>
</section>
<script>
document.getElementById('recherche-employe')?.addEventListener('input', function () {
  const terme = this.value.trim().toLocaleLowerCase('fr');
  document.querySelectorAll('[data-employe-row]').forEach((ligne) => {
    ligne.hidden = !ligne.dataset.search.includes(terme);
  });
});
</script>
