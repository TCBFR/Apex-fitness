<section class="carte" style="max-width:760px;margin:auto">
  <p class="mono">TECHNICIEN N° <?= (int) $technicienSelectionne['NumUsers'] ?></p>
  <h3><?= e($technicienSelectionne['prenomUsers'] . ' ' . $technicienSelectionne['nomUsers']) ?></h3>
  <div class="table-scroll">
    <table class="tableau">
      <tbody>
        <tr><th>E-mail</th><td><?= e($technicienSelectionne['mailUsers']) ?></td></tr>
        <tr><th>Téléphone</th><td><?= e($technicienSelectionne['telUsers'] ?: 'Non renseigné') ?></td></tr>
        <tr><th>Fonction</th><td><?= e($technicienSelectionne['libellefonc']) ?></td></tr>
        <tr><th>Matricule</th><td><?= e($technicienSelectionne['matriculeemp'] ?? '') ?></td></tr>
        <tr><th>Date d’embauche</th><td><?= e(dateFr($technicienSelectionne['Date_embaucheemp'] ?? '')) ?></td></tr>
      </tbody>
    </table>
  </div>
  <p style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
    <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=maintenance&maintenance=modifier&id=' . (int) $technicienSelectionne['NumUsers'])) ?>">Modifier ce compte</a>
    <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=maintenance')) ?>">Retour à la liste</a>
  </p>
</section>
