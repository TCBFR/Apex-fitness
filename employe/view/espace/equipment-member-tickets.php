
  <section class="carte" style="margin-bottom:22px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
      <div>
        <h3 style="margin:0 0 6px">Mes tickets signalés</h3>
        <p style="margin:0">Suivez l’avancement des demandes envoyées à l’équipe de maintenance.</p>
      </div>
      <a class="btn btn-vert" href="<?= e(url('employe/espace.php?onglet=creer-ticket')) ?>">Créer un ticket</a>
    </div>
    <?php if (!$ticketsEmploye): ?>
      <p class="vide" style="margin-top:16px">Vous n’avez pas encore créé de ticket.</p>
    <?php else: ?>
      <div class="table-scroll" style="margin-top:16px">
        <table class="tableau">
          <thead><tr><th>N°</th><th>Équipement</th><th>Problème</th><th>Priorité</th><th>Statut</th><th>Technicien</th><th>Signalé le</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($ticketsEmploye as $ticket): ?>
            <tr>
              <td class="mono"><?= (int) $ticket['Nummaint'] ?></td>
              <td><?= e($ticket['nomequip']) ?><br><span class="mono"><?= e($ticket['num_inventaire']) ?></span></td>
              <td><?= e($ticket['description_problememaint']) ?></td>
              <td><?= e($ticket['priorite']) ?></td>
              <td><span class="tag <?= in_array($ticket['libelle_statut'], ['À valider', 'Terminee'], true) ? 'tag-ok' : 'tag-warn' ?>"><?= $ticket['libelle_statut'] === 'Terminee' ? 'Fermé' : e($ticket['libelle_statut']) ?></span></td>
              <td><?= $ticket['technicien_nom'] ? e($ticket['technicien_prenom'] . ' ' . $ticket['technicien_nom']) : 'File commune' ?></td>
              <td class="mono"><?= dateFr($ticket['date_demandemaint']) ?></td>
              <td>
                <?php if ($ticket['libelle_statut'] === 'À valider'): ?>
                  <form method="post">
                    <?= champ_csrf() ?>
                    <input type="hidden" name="action" value="fermer_ticket">
                    <input type="hidden" name="maintenance" value="<?= (int) $ticket['Nummaint'] ?>">
                    <button class="btn btn-sm btn-vert" type="submit" data-confirme="Confirmer que l’intervention est satisfaisante et fermer ce ticket ?">Confirmer et fermer</button>
                  </form>
                <?php elseif ($ticket['libelle_statut'] === 'Terminee'): ?>
                  <span class="tag tag-ok">Fermé</span>
                <?php else: ?>
                  <span class="tag tag-mute">En attente du technicien</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
