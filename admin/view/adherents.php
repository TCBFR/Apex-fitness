<?php
$adherentEnEdition = $vueAdherent === 'modifier' && $adherentSelectionne;
?>
<section class="adherents-page">
  <header class="adherents-intro">
    <div>
      <h3>Répertoire des adhérents</h3>
      <p>Consultez et gérez les informations des membres.</p>
    </div>
  </header>

  <div class="adherents-layout <?= $adherentSelectionne ? 'has-detail' : 'is-full' ?>">
    <div class="adherents-list-wrap">
      <label class="adherents-search">
        <span aria-hidden="true">⌕</span>
        <input type="search" id="recherche-adherent" placeholder="Rechercher un nom, un e-mail ou une commune" autocomplete="off">
      </label>
      <p class="adherents-hint">Cliquez sur un adhérent pour consulter sa fiche.</p>

      <?php if (!$adherents): ?>
        <div class="vide">Aucun adhérent enregistré.</div>
      <?php else: ?>
        <div class="table-scroll adherents-table-scroll">
          <table class="tableau adherents-table" id="table-adherents">
            <thead><tr><th>Adhérent</th><th>Commune</th><th>Formule</th><th>Inscription</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($adherents as $adherent):
              $nomComplet = trim($adherent['prenomUsers'] . ' ' . $adherent['nomUsers']);
              $selectionne = $adherentSelectionne && (int) $adherentSelectionne['NumUsers'] === (int) $adherent['NumUsers'];
            ?>
              <tr class="<?= $selectionne ? 'is-selected' : '' ?>" data-adherent-row data-search="<?= e(mb_strtolower($nomComplet . ' ' . $adherent['mailUsers'] . ' ' . ($adherent['nom_commune'] ?? ''))) ?>">
                <td>
                  <a class="adherent-person" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherent['NumUsers'] . '&vue=voir')) ?>">
                    <?php if (!empty($adherent['photo_profil'])): ?>
                      <img src="<?= e(url($adherent['photo_profil'])) ?>" alt="">
                    <?php else: ?>
                      <span class="adherent-avatar"><?= e(mb_strtoupper(mb_substr($adherent['prenomUsers'], 0, 1) . mb_substr($adherent['nomUsers'], 0, 1))) ?></span>
                    <?php endif; ?>
                    <span><strong><?= e($nomComplet) ?></strong><small><?= e($adherent['mailUsers']) ?></small></span>
                  </a>
                </td>
                <td><?= e($adherent['nom_commune'] ?: 'Non renseignée') ?><small class="adherents-cell-sub"><?= e($adherent['code_postal'] ?? '') ?></small></td>
                <td><?= e($adherent['abonnement'] ?: 'Aucune formule') ?></td>
                <td><?= e(dateFr($adherent['Date_inscriptionadh'])) ?></td>
                <td>
                  <div class="adherents-actions">
                    <a class="btn btn-sm btn-ghost" aria-label="Lire la fiche de <?= e($nomComplet) ?>" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherent['NumUsers'] . '&vue=voir')) ?>">Lire</a>
                    <a class="btn btn-sm btn-ghost" aria-label="Modifier <?= e($nomComplet) ?>" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherent['NumUsers'] . '&vue=modifier')) ?>">Modifier</a>
                    <form method="post">
                      <?= champ_csrf() ?>
                      <input type="hidden" name="action" value="supprimer">
                      <input type="hidden" name="utilisateur" value="<?= (int) $adherent['NumUsers'] ?>">
                      <input type="hidden" name="retour" value="adherents">
                      <button class="btn btn-sm btn-danger" type="submit" data-confirme="<?= !empty($adherent['est_employe']) ? 'Supprimer le rôle adhérent, ses réservations, abonnements et son historique financier ? Le compte employé et ses activités seront conservés.' : 'Supprimer définitivement cet adhérent, son compte et son historique de paiements ?' ?>">Supprimer</button>
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

    <?php if ($adherentSelectionne): ?>
    <aside class="adherent-detail" aria-live="polite">
      <?php if ($adherentEnEdition): ?>
        <div class="adherent-detail-heading">
          <div><p class="admin-eyebrow">MODIFICATION</p><h3><?= e($adherentSelectionne['prenomUsers'] . ' ' . $adherentSelectionne['nomUsers']) ?></h3></div>
          <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherentSelectionne['NumUsers'] . '&vue=voir')) ?>" aria-label="Fermer">×</a>
        </div>
        <form class="adherent-edit-form" method="post">
          <?= champ_csrf() ?>
          <input type="hidden" name="action" value="modifier_adherent">
          <input type="hidden" name="utilisateur" value="<?= (int) $adherentSelectionne['NumUsers'] ?>">
          <label>Prénom<input name="prenom" value="<?= e($adherentSelectionne['prenomUsers']) ?>" required maxlength="60"></label>
          <label>Nom<input name="nom" value="<?= e($adherentSelectionne['nomUsers']) ?>" required maxlength="60"></label>
          <label>E-mail<input type="email" name="mail" value="<?= e($adherentSelectionne['mailUsers']) ?>" required></label>
          <label>Téléphone<input type="tel" name="tel" value="<?= e($adherentSelectionne['telUsers'] ?? '') ?>" maxlength="20"></label>
          <label>Date de naissance<input type="date" name="date_naissance" value="<?= e($adherentSelectionne['date_naissance'] ?? '') ?>"></label>
          <label>Sexe
            <select name="sexe" required>
              <option value="">Choisir une option</option>
              <?php foreach (['Femme', 'Homme', 'Autre', 'Préfère ne pas répondre'] as $sexe): ?>
                <option value="<?= e($sexe) ?>" <?= ($adherentSelectionne['sexe'] ?? '') === $sexe ? 'selected' : '' ?>><?= e($sexe) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Adresse postale<input name="adresse_postale" value="<?= e($adherentSelectionne['adresse_postale'] ?? '') ?>" maxlength="255"></label>
          <label>Commune
            <select name="id_commune" required>
              <option value="">Choisir une commune</option>
              <?php foreach ($communes as $commune): ?>
                <option value="<?= (int) $commune['id_commune'] ?>" <?= (int) $commune['id_commune'] === (int) $adherentSelectionne['id_commune'] ? 'selected' : '' ?>><?= e($commune['nom_commune'] . ' (' . $commune['code_postal'] . ')') ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="adherent-edit-dates">
            <label>Inscription<input type="date" name="Date_inscriptionadh" value="<?= e($adherentSelectionne['Date_inscriptionadh']) ?>" required></label>
            <label>Fin d’adhésion<input type="date" name="Date_finadh" value="<?= e($adherentSelectionne['Date_finadh'] ?? '') ?>"></label>
          </div>
          <div class="adherent-detail-actions">
            <button class="btn btn-vert" type="submit">Enregistrer</button>
            <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherentSelectionne['NumUsers'] . '&vue=voir')) ?>">Annuler</a>
          </div>
        </form>
      <?php else: ?>
        <div class="adherent-detail-heading">
          <div><p class="admin-eyebrow">FICHE ADHÉRENT · <?= e($adherentSelectionne['matriculeadh']) ?></p><h3><?= e($adherentSelectionne['prenomUsers'] . ' ' . $adherentSelectionne['nomUsers']) ?></h3></div>
          <a class="adherent-close" href="<?= e(url('admin/espace.php?onglet=adherents')) ?>" aria-label="Fermer la fiche">×</a>
        </div>
        <dl class="adherent-info-list">
          <div><dt>E-mail</dt><dd><a href="mailto:<?= e($adherentSelectionne['mailUsers']) ?>"><?= e($adherentSelectionne['mailUsers']) ?></a></dd></div>
          <div><dt>Téléphone</dt><dd><?= e($adherentSelectionne['telUsers'] ?: 'Non renseigné') ?></dd></div>
          <div><dt>Date de naissance</dt><dd><?= e($adherentSelectionne['date_naissance'] ? dateFr($adherentSelectionne['date_naissance']) : 'Non renseignée') ?></dd></div>
          <div><dt>Sexe</dt><dd><?= e($adherentSelectionne['sexe'] ?: 'Non renseigné') ?></dd></div>
          <div><dt>Adresse</dt><dd><?= e($adherentSelectionne['adresse_postale'] ?: 'Non renseignée') ?></dd></div>
          <div><dt>Commune</dt><dd><?= e($adherentSelectionne['nom_commune'] ?: 'Non renseignée') ?><?= $adherentSelectionne['code_postal'] ? ' · ' . e($adherentSelectionne['code_postal']) : '' ?></dd></div>
          <div><dt>Compte créé le</dt><dd><?= e(dateFr($adherentSelectionne['Date_CreationUsers'], true)) ?></dd></div>
          <div><dt>Inscription adhérent</dt><dd><?= e(dateFr($adherentSelectionne['Date_inscriptionadh'])) ?></dd></div>
          <div><dt>Fin d’adhésion</dt><dd><?= e($adherentSelectionne['Date_finadh'] ? dateFr($adherentSelectionne['Date_finadh']) : 'Sans date de fin') ?></dd></div>
          <div><dt>Formule actuelle</dt><dd><?= e($adherentSelectionne['abonnement'] ?: 'Aucune formule en cours') ?></dd></div>
        </dl>
        <div class="adherent-detail-actions">
          <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $adherentSelectionne['NumUsers'] . '&vue=modifier')) ?>">Modifier la fiche</a>
        </div>
      <?php endif; ?>
    </aside>
    <?php endif; ?>
  </div>
</section>
<script>
document.getElementById('recherche-adherent')?.addEventListener('input', function () {
  const terme = this.value.trim().toLocaleLowerCase('fr');
  document.querySelectorAll('[data-adherent-row]').forEach((ligne) => {
    ligne.hidden = !ligne.dataset.search.includes(terme);
  });
});
</script>
