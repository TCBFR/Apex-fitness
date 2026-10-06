<section class="carte" style="max-width:760px;margin:auto">
  <p class="mono">COMPTE N° <?= (int) $compteConsulte['NumUsers'] ?></p>
  <h2><?= e($compteConsulte['prenomUsers'] . ' ' . $compteConsulte['nomUsers']) ?></h2>

  <div class="table-scroll">
    <table class="tableau">
      <tbody>
        <tr><th>E-mail</th><td><?= e($compteConsulte['mailUsers']) ?></td></tr>
        <tr><th>Téléphone</th><td><?= e($compteConsulte['telUsers'] ?: 'Non renseigné') ?></td></tr>
        <tr><th>Créé le</th><td><?= e(dateFr($compteConsulte['Date_CreationUsers'], true)) ?></td></tr>
        <tr>
          <th>Rôles</th>
          <td>
            <?php if ($compteConsulte['est_admin']): ?><span class="tag tag-bad">Administrateur</span><?php endif; ?>
            <?php if ($compteConsulte['est_employe']): ?><span class="tag tag-warn">Employé</span><?php endif; ?>
            <?php if ($compteConsulte['est_adherent']): ?><span class="tag tag-ok">Adhérent</span><?php endif; ?>
          </td>
        </tr>
        <?php if ($compteConsulte['est_employe'] && $compteConsulte['libellefonc']): ?>
          <tr><th>Fonction</th><td><?= e($compteConsulte['libellefonc']) ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
    <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=utilisateurs&compte=modifier&id=' . (int) $compteConsulte['NumUsers'])) ?>">Modifier ce compte</a>
    <a class="btn btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs')) ?>">Retour à la liste</a>
  </p>
</section>
