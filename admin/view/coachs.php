<?php
$coachEnEdition = $vueCoach === 'modifier' && $coachSelectionne;
$modeCreationCoach = ($_GET['coach'] ?? '') === 'creer';
?>
<section class="adherents-page coachs-page">
  <header class="adherents-intro">
    <div>
      <h3>Répertoire des coachs</h3>
      <p>Consultez et gérez les informations de l’équipe coaching.</p>
    </div>
    <div class="adherents-header-actions">
      <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=coachs&coach=creer')) ?>">Ajouter un coach</a>
    </div>
  </header>

  <div class="adherents-layout <?= ($coachSelectionne || $modeCreationCoach) ? 'has-detail' : 'is-full' ?>">
    <div class="adherents-list-wrap">
      <label class="adherents-search">
        <span aria-hidden="true">⌕</span>
        <input type="search" id="recherche-coach" placeholder="Rechercher un nom, un e-mail ou une spécialité" autocomplete="off">
      </label>
      <p class="adherents-hint">Cliquez sur un coach pour consulter sa fiche.</p>

      <?php if (!$coachs): ?>
        <div class="vide">Aucun coach enregistré. Les employés dont la fonction contient « coach » apparaîtront ici.</div>
      <?php else: ?>
        <div class="table-scroll adherents-table-scroll">
          <table class="tableau adherents-table coachs-table" id="table-coachs">
            <thead><tr><th>Coach</th><th>Spécialisation</th><th>Commune</th><th>Matricule</th><th>Embauche</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($coachs as $coach):
              $nomComplet = trim($coach['prenomUsers'] . ' ' . $coach['nomUsers']);
              $selectionne = $coachSelectionne && (int) $coachSelectionne['NumUsers'] === (int) $coach['NumUsers'];
              $lienFiche = url('admin/espace.php?onglet=coachs&id=' . (int) $coach['NumUsers'] . '&vue=voir');
            ?>
              <tr class="<?= $selectionne ? 'is-selected' : '' ?>" data-coach-row data-search="<?= e(mb_strtolower($nomComplet . ' ' . $coach['mailUsers'] . ' ' . ($coach['libellefonc'] ?? ''))) ?>">
                <td>
                  <a class="adherent-person" href="<?= e($lienFiche) ?>">
                    <span class="adherent-avatar"><?= e(mb_strtoupper(mb_substr($coach['prenomUsers'], 0, 1) . mb_substr($coach['nomUsers'], 0, 1))) ?></span>
                    <span><strong><?= e($nomComplet) ?></strong><small><?= e($coach['mailUsers']) ?></small></span>
                  </a>
                </td>
                <td><?= e($coach['libellefonc']) ?></td>
                <td><?= e($coach['nom_commune'] ?: 'Non renseignée') ?></td>
                <td><?= e($coach['matriculeemp'] ?? '') ?></td>
                <td><?= e(dateFr($coach['Date_embaucheemp'] ?? '')) ?></td>
                <td>
                  <div class="adherents-actions">
                    <a class="btn btn-sm btn-ghost" href="<?= e($lienFiche) ?>">Lire</a>
                    <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/espace.php?onglet=coachs&id=' . (int) $coach['NumUsers'] . '&vue=modifier')) ?>">Modifier</a>
                    <form method="post">
                      <?= champ_csrf() ?>
                      <input type="hidden" name="action" value="supprimer">
                      <input type="hidden" name="utilisateur" value="<?= (int) $coach['NumUsers'] ?>">
                      <input type="hidden" name="retour" value="coachs">
                      <button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer définitivement le compte de ce coach ?">Supprimer</button>
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

    <?php if ($coachSelectionne || $modeCreationCoach): ?>
      <aside class="adherent-detail" aria-live="polite">
        <?php if ($modeCreationCoach): ?>
          <div class="adherent-detail-heading">
            <div><p class="admin-eyebrow">NOUVEAU MEMBRE DE L’ÉQUIPE</p><h3>Ajouter un coach</h3></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=coachs')) ?>" aria-label="Fermer">×</a>
          </div>
          <form class="adherent-edit-form" method="post">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="creer_coach">
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
            <label>Spécialisation
              <select name="fonction" required>
                <option value="">Choisir une spécialisation</option>
                <?php foreach ($fonctionsCoach as $fonction): ?>
                  <?php if (in_array(mb_strtolower($fonction['libellefonc']), array_map('mb_strtolower', $specialisationsCoachNoms), true)): ?>
                    <option value="<?= (int) $fonction['Numfonc'] ?>"><?= e($fonction['libellefonc']) ?></option>
                  <?php endif; ?>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Mot de passe<input type="password" name="motdepasse" required minlength="8" autocomplete="new-password"></label>
            <label>Confirmer le mot de passe<input type="password" name="confirmation" required minlength="8" autocomplete="new-password"></label>
            <div class="adherent-detail-actions">
              <button class="btn btn-vert" type="submit">Créer le compte coach</button>
              <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=coachs')) ?>">Annuler</a>
            </div>
          </form>
        <?php elseif ($coachEnEdition): ?>
          <div class="adherent-detail-heading">
            <div><p class="admin-eyebrow">MODIFICATION DU COACH</p><h3><?= e($coachSelectionne['prenomUsers'] . ' ' . $coachSelectionne['nomUsers']) ?></h3></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=coachs&id=' . (int) $coachSelectionne['NumUsers'] . '&vue=voir')) ?>" aria-label="Fermer">×</a>
          </div>
          <form class="adherent-edit-form" method="post">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="modifier_coach">
            <input type="hidden" name="utilisateur" value="<?= (int) $coachSelectionne['NumUsers'] ?>">
            <label>Prénom<input name="prenom" value="<?= e($coachSelectionne['prenomUsers']) ?>" required maxlength="60"></label>
            <label>Nom<input name="nom" value="<?= e($coachSelectionne['nomUsers']) ?>" required maxlength="60"></label>
            <label>E-mail<input type="email" name="mail" value="<?= e($coachSelectionne['mailUsers']) ?>" required></label>
            <label>Téléphone<input type="tel" name="tel" value="<?= e($coachSelectionne['telUsers'] ?? '') ?>" maxlength="20"></label>
            <div class="employe-commune-field">
              <label for="commune">Commune</label>
              <div class="commune-recherche">
                <input type="text" id="commune" value="<?= e($coachSelectionne['nom_commune'] ?? '') ?>" data-url="<?= e(url('commune/recherche.php')) ?>" autocomplete="off" placeholder="Saisir au moins 2 caractères" required>
                <div class="commune-resultats" id="commune-resultats" hidden></div>
                <input type="hidden" name="id_commune" id="id_commune" value="<?= e((string) ($coachSelectionne['id_commune'] ?? '')) ?>">
                <p id="commune-message" aria-live="polite"></p>
              </div>
              <label for="code_postal">Code postal</label>
              <input type="text" id="code_postal" value="<?= e($coachSelectionne['code_postal'] ?? '') ?>" readonly tabindex="-1">
            </div>
            <label>Spécialisation
              <select name="Numfonc" required>
                <?php foreach ($fonctionsCoach as $fonction): ?>
                  <option value="<?= (int) $fonction['Numfonc'] ?>" <?= (int) $fonction['Numfonc'] === (int) $coachSelectionne['Numfonc'] ? 'selected' : '' ?>><?= e($fonction['libellefonc']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Matricule<input name="matriculeemp" value="<?= e($coachSelectionne['matriculeemp'] ?? '') ?>" required maxlength="20"></label>
            <label>Date d’embauche<input type="date" name="Date_embaucheemp" value="<?= e($coachSelectionne['Date_embaucheemp'] ?? '') ?>" required></label>
            <div class="adherent-detail-actions">
              <button class="btn btn-vert" type="submit">Enregistrer</button>
              <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=coachs&id=' . (int) $coachSelectionne['NumUsers'] . '&vue=voir')) ?>">Annuler</a>
            </div>
          </form>
        <?php else: ?>
          <div class="adherent-detail-heading">
            <div><p class="admin-eyebrow">FICHE COACH · <?= e($coachSelectionne['matriculeemp'] ?? '') ?></p><h3><?= e($coachSelectionne['prenomUsers'] . ' ' . $coachSelectionne['nomUsers']) ?></h3></div>
            <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=coachs')) ?>" aria-label="Fermer la fiche">×</a>
          </div>
          <dl class="adherent-info-list">
            <div><dt>E-mail</dt><dd><a href="mailto:<?= e($coachSelectionne['mailUsers']) ?>"><?= e($coachSelectionne['mailUsers']) ?></a></dd></div>
            <div><dt>Téléphone</dt><dd><?= e($coachSelectionne['telUsers'] ?: 'Non renseigné') ?></dd></div>
            <div><dt>Spécialisation</dt><dd><?= e($coachSelectionne['libellefonc']) ?></dd></div>
            <div><dt>Commune</dt><dd><?= e($coachSelectionne['nom_commune'] ?: 'Non renseignée') ?><?= !empty($coachSelectionne['code_postal']) ? ' · ' . e($coachSelectionne['code_postal']) : '' ?></dd></div>
            <div><dt>Matricule</dt><dd><?= e($coachSelectionne['matriculeemp'] ?? '') ?></dd></div>
            <div><dt>Date d’embauche</dt><dd><?= e(dateFr($coachSelectionne['Date_embaucheemp'] ?? '')) ?></dd></div>
            <div><dt>Compte créé le</dt><dd><?= e(dateFr($coachSelectionne['Date_CreationUsers'] ?? null, true)) ?></dd></div>
          </dl>
          <div class="adherent-detail-actions">
            <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=coachs&id=' . (int) $coachSelectionne['NumUsers'] . '&vue=modifier')) ?>">Modifier la fiche</a>
          </div>
        <?php endif; ?>
      </aside>
    <?php endif; ?>
  </div>
</section>
<script>
document.getElementById('recherche-coach')?.addEventListener('input', function () {
  const terme = this.value.trim().toLocaleLowerCase('fr');
  document.querySelectorAll('[data-coach-row]').forEach((ligne) => {
    ligne.hidden = !ligne.dataset.search.includes(terme);
  });
});
</script>
