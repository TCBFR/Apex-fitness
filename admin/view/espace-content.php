<?php if ($onglet === 'dashboard'): ?>
  <?php require __DIR__ . '/dashboard.php'; ?>

<?php elseif ($onglet === 'avis'): ?>
  <?php if (!$avisAdmin): ?>
    <div class="vide">Aucun avis publié pour le moment.</div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="tableau">
        <thead><tr><th>Adhérent</th><th>Note</th><th>Avis</th><th>Réponse</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($avisAdmin as $avis): ?>
          <tr>
            <td>
              <?= e($avis['prenomUsers'] . ' ' . $avis['nomUsers']) ?><br>
              <span class="mono"><?= dateFr($avis['date_interaction']) ?></span>
            </td>
            <td class="mono"><?= (int) $avis['noteavis'] ?> / 5</td>
            <td><?= nl2br(e($avis['commentaire'])) ?></td>
            <td>
              <form method="post" style="margin:0">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="repondre_avis">
                <input type="hidden" name="num_interaction" value="<?= (int) $avis['NumInteraction'] ?>">
                <textarea name="reponse" rows="4" placeholder="Réponse de l’équipe..." required><?= e($avis['reponse'] ?? '') ?></textarea>
                <button class="btn btn-sm btn-vert" type="submit" style="margin-top:8px">Enregistrer</button>
              </form>
            </td>
            <td>
              <?php if (!empty($avis['reponse'])): ?><span class="tag tag-ok">Répondu</span><?php else: ?><span class="tag tag-mute">À répondre</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

<?php elseif ($onglet === 'utilisateurs'): ?>
  <?php require __DIR__ . '/employes.php'; ?>

<?php elseif ($onglet === 'adherents'): ?>
  <?php require __DIR__ . '/adherents.php'; ?>

<?php elseif ($onglet === 'coachs'): ?>
  <?php require __DIR__ . '/coachs.php'; ?>

<?php elseif ($onglet === 'paiements'): ?>
  <?php require __DIR__ . '/paiements.php'; ?>

<?php elseif ($onglet === 'abonnements'): ?>
  <?php require __DIR__ . '/abonnements.php'; ?>

<?php elseif ($onglet === 'planning'): ?>
  <?php require __DIR__ . ($vuePlanning === 'ajouter' || $vuePlanning === 'modifier' ? '/planning-form.php' : '/planning.php'); ?>

<?php elseif ($onglet === 'reservations'): ?>
  <?php require __DIR__ . '/reservations.php'; ?>

<?php elseif ($onglet === 'activites'): ?>
  <?php require __DIR__ . '/activites.php'; ?>

<?php elseif ($onglet === 'equipements'): ?>
  <?php require __DIR__ . '/equipements.php'; ?>

<?php elseif ($onglet === 'salles'): ?>
  <?php require __DIR__ . ($vueSalleAdmin === 'creer' || $vueSalleAdmin === 'modifier' ? '/salles-form.php' : '/salles.php'); ?>

<?php elseif ($onglet === 'maintenance'): ?>
  <?php if ($vueMaintenance === 'creer'): ?>
    <?php require __DIR__ . '/creer-technicien.php'; ?>
  <?php elseif ($vueMaintenance === 'modifier' && $technicienSelectionne): ?>
    <?php require __DIR__ . '/modifier-technicien.php'; ?>
  <?php elseif ($vueMaintenance === 'voir' && $technicienSelectionne): ?>
    <?php require __DIR__ . '/voir-technicien.php'; ?>
  <?php else: ?>
    <?php require __DIR__ . '/techniciens-maintenance.php'; ?>
  <?php endif; ?>

<?php elseif ($onglet === 'messages'): ?>
  <?php require __DIR__ . '/../message_contact/view/admin-contact.php'; ?>

<?php else: ?>
  <div class="dashboard-box" style="background:#fff; padding:20px; border-radius:12px;">
    <h3>Module en cours de développement</h3>
    <p>La section <strong><?= e($onglet) ?></strong> est en cours de configuration et sera bientôt disponible.</p>
  </div>
<?php endif; ?>
