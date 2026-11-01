<?php
$compteurTickets = [
    'ouverts' => count(array_filter($ticketsEquipementsAdmin, static fn(array $ticket): bool => !in_array($ticket['libelle_statut'], ['Terminee', 'À valider'], true))),
    'validation' => count(array_filter($ticketsEquipementsAdmin, static fn(array $ticket): bool => $ticket['libelle_statut'] === 'À valider')),
    'termines' => count(array_filter($ticketsEquipementsAdmin, static fn(array $ticket): bool => $ticket['libelle_statut'] === 'Terminee')),
];
$commandesEnAttente = count(array_filter(
    $commandesMaterielAdmin,
    static fn(array $commande): bool => $commande['statut_commande'] === 'En attente'
));
?>
<section class="admin-material-orders" aria-labelledby="admin-material-orders-title">
  <header>
    <div>
      <p class="admin-eyebrow">APPROBATION DES BESOINS</p>
      <h2 id="admin-material-orders-title">Commandes de matériel</h2>
      <p>Les demandes envoyées par la maintenance apparaissent ici. Le stock est mis à jour à l’enregistrement de la réception.</p>
    </div>
    <strong class="admin-material-orders-count"><?= $commandesEnAttente ?> demande<?= $commandesEnAttente === 1 ? '' : 's' ?> à valider</strong>
  </header>
  <?php if (!$commandesMaterielAdmin): ?>
    <p class="admin-material-orders-empty">Aucune commande de matériel n’a été demandée.</p>
  <?php else: ?>
    <div class="admin-material-orders-list">
      <?php foreach ($commandesMaterielAdmin as $commande):
        $commandeEnAttente = $commande['statut_commande'] === 'En attente';
        $commandeApprouvee = $commande['statut_commande'] === 'Approuvée';
        $statutCommandeClass = match ($commande['statut_commande']) {
            'En attente' => 'pending',
            'Approuvée' => 'approved',
            'Reçue' => 'received',
            default => 'rejected',
        };
      ?>
        <article class="admin-material-order">
          <div class="admin-material-order-main">
            <div><small>Commande #<?= (int) $commande['Numcommande'] ?> · <?= dateFr($commande['date_demande']) ?></small><h3><?= e($commande['nomequip']) ?></h3><span><?= e($commande['libelletypeequip']) ?> · <?= (int) $commande['quantite'] ?> unité<?= (int) $commande['quantite'] === 1 ? '' : 's' ?></span></div>
            <b class="is-order-<?= $statutCommandeClass ?>"><?= e($commande['statut_commande']) ?></b>
          </div>
          <p>Demandée par <?= e($commande['demandeur_prenom'] . ' ' . $commande['demandeur_nom']) ?> · Stock actuel : <?= (int) $commande['stock_actuel'] ?> (seuil : <?= (int) $commande['seuil_alerte'] ?>)</p>
          <?php if ($commande['message']): ?><blockquote><?= nl2br(e($commande['message'])) ?></blockquote><?php endif; ?>
          <?php if ($commande['statut_commande'] === 'Approuvée' && $commande['validateur_prenom']): ?><small>Approuvée par <?= e($commande['validateur_prenom'] . ' ' . $commande['validateur_nom']) ?></small><?php endif; ?>
          <div class="admin-material-order-actions">
            <?php if ($commandeEnAttente): ?>
              <form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="valider_commande_materiel"><input type="hidden" name="commande" value="<?= (int) $commande['Numcommande'] ?>"><button type="submit" class="is-approve"><i class="fa-solid fa-check" aria-hidden="true"></i> Valider</button></form>
              <form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="refuser_commande_materiel"><input type="hidden" name="commande" value="<?= (int) $commande['Numcommande'] ?>"><button type="submit" class="is-reject"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Refuser</button></form>
            <?php elseif ($commandeApprouvee): ?>
              <form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="recevoir_commande_materiel"><input type="hidden" name="commande" value="<?= (int) $commande['Numcommande'] ?>"><button type="submit" class="is-approve"><i class="fa-solid fa-box-open" aria-hidden="true"></i> Enregistrer la réception</button></form>
            <?php elseif ($commande['statut_commande'] === 'Reçue'): ?>
              <small>Réception enregistrée le <?= dateFr($commande['date_reception']) ?>.</small>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="admin-equipment-tickets">
  <header class="admin-equipment-tickets-heading">
    <div>
      <p class="admin-eyebrow">PARCOURS ACCUEIL → MAINTENANCE</p>
      <h2>Tickets des équipements</h2>
      <p>Suivez les demandes signalées à l’accueil et leur prise en charge par l’équipe de maintenance.</p>
    </div>
  </header>

  <div class="admin-equipment-ticket-stats" aria-label="Résumé des tickets">
    <div><span class="ticket-stat-dot is-open"></span><span>À traiter</span><strong><?= $compteurTickets['ouverts'] ?></strong></div>
    <div><span class="ticket-stat-dot is-validation"></span><span>À valider</span><strong><?= $compteurTickets['validation'] ?></strong></div>
    <div><span class="ticket-stat-dot is-done"></span><span>Terminés</span><strong><?= $compteurTickets['termines'] ?></strong></div>
    <label class="admin-equipment-ticket-search"><span aria-hidden="true">⌕</span><input type="search" id="recherche-ticket-equipement" placeholder="Rechercher un équipement, un employé…" autocomplete="off"></label>
  </div>

  <?php if (!$ticketsEquipementsAdmin): ?>
    <div class="admin-equipment-ticket-empty"><span aria-hidden="true">✓</span><h3>Aucun ticket pour le moment</h3><p>Les demandes envoyées depuis l’espace Accueil apparaîtront ici.</p></div>
  <?php else: ?>
    <div class="admin-equipment-ticket-list">
      <?php foreach ($ticketsEquipementsAdmin as $ticket):
        $idTicket = (int) $ticket['Nummaint'];
        $statutTicket = (string) $ticket['libelle_statut'];
        $termine = $statutTicket === 'Terminee';
        $attenteValidation = $statutTicket === 'À valider';
        $interventionsTicket = $interventionsParTicketAdmin[$idTicket] ?? [];
        $rechercheTicket = implode(' ', [
            $ticket['nomequip'], $ticket['libelletypeequip'], $ticket['description_problememaint'],
            $ticket['demandeur_prenom'], $ticket['demandeur_nom'], $ticket['technicien_prenom'] ?? '', $ticket['technicien_nom'] ?? '',
            $statutTicket, $ticket['priorite'],
        ]);
      ?>
        <article class="admin-maintenance-ticket" data-ticket-row data-search="<?= e(mb_strtolower($rechercheTicket)) ?>">
          <header class="maintenance-ticket-header">
            <div class="maintenance-ticket-id"><span>Ticket</span><strong>#<?= str_pad((string) $idTicket, 4, '0', STR_PAD_LEFT) ?></strong><time datetime="<?= e(date('c', strtotime($ticket['date_demandemaint']))) ?>"><?= e(dateFr($ticket['date_demandemaint'])) ?></time></div>
            <div class="maintenance-ticket-badges">
              <span class="maintenance-ticket-priority <?= $ticket['priorite'] === 'Haute' ? 'is-high' : '' ?>"><?= e($ticket['priorite']) ?> priorité</span>
              <span class="maintenance-ticket-status <?= $termine ? 'is-done' : ($attenteValidation ? 'is-validation' : 'is-open') ?>"><?= e($statutTicket === 'Terminee' ? 'Terminé' : $statutTicket) ?></span>
            </div>
          </header>

          <div class="maintenance-ticket-content">
            <div class="maintenance-ticket-equipment"><span class="maintenance-equipment-icon" aria-hidden="true">⚙</span><div><small><?= e($ticket['libelletypeequip']) ?> · Équipement #<?= (int) $ticket['Numequip'] ?></small><h3><?= e($ticket['nomequip']) ?></h3></div></div>
            <p class="maintenance-ticket-description"><?= nl2br(e($ticket['description_problememaint'])) ?></p>
          </div>

          <div class="maintenance-ticket-flow">
            <div class="maintenance-flow-step is-complete"><span class="maintenance-flow-marker">✓</span><div><small>Demande transmise</small><strong><?= e($ticket['demandeur_prenom'] . ' ' . $ticket['demandeur_nom']) ?></strong><span><?= e($ticket['demandeur_fonction']) ?></span></div></div>
            <div class="maintenance-flow-step <?= $ticket['NumUsers_technicien'] ? 'is-complete' : '' ?>"><span class="maintenance-flow-marker"><?= $ticket['NumUsers_technicien'] ? '✓' : '2' ?></span><div><small>Pris en charge par</small><?php if ($ticket['NumUsers_technicien']): ?><strong><?= e($ticket['technicien_prenom'] . ' ' . $ticket['technicien_nom']) ?></strong><span><?= e($ticket['technicien_fonction'] ?? 'Maintenance') ?></span><?php else: ?><strong class="maintenance-flow-pending">En attente d’un technicien</strong><span>File de maintenance</span><?php endif; ?></div></div>
            <div class="maintenance-flow-step <?= $termine ? 'is-complete' : '' ?>"><span class="maintenance-flow-marker"><?= $termine ? '✓' : '3' ?></span><div><small>État de l’intervention</small><strong><?= e($statutTicket === 'Terminee' ? 'Intervention terminée' : ($attenteValidation ? 'En attente de validation' : 'Suivi en cours')) ?></strong><span><?= count($interventionsTicket) ?> étape<?= count($interventionsTicket) === 1 ? '' : 's' ?> enregistrée<?= count($interventionsTicket) === 1 ? '' : 's' ?></span></div></div>
          </div>

          <details class="maintenance-ticket-history">
            <summary>Historique des interventions <span><?= count($interventionsTicket) ?></span></summary>
            <?php if (!$interventionsTicket): ?><p>Aucune intervention enregistrée.</p><?php else: ?>
              <ol>
                <?php foreach ($interventionsTicket as $intervention): ?>
                  <li><div class="maintenance-history-dot"></div><div class="maintenance-history-body"><div><strong><?= e($intervention['prenomUsers'] . ' ' . $intervention['nomUsers']) ?></strong><span><?= e($intervention['libellefonc']) ?></span></div><time datetime="<?= e(date('c', strtotime($intervention['date_intervention']))) ?>"><?= e(dateFr($intervention['date_intervention'])) ?></time><p><?= e($intervention['commentaire'] ?: 'Mise à jour du ticket : ' . $intervention['libelle_statut']) ?></p><small><?= e($intervention['libelle_statut']) ?></small></div></li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>
          </details>
        </article>
      <?php endforeach; ?>
    </div>
    <p class="admin-ticket-search-empty" id="tickets-equipement-vides" hidden>Aucun ticket ne correspond à la recherche.</p>
  <?php endif; ?>
</section>
<script>
document.getElementById('recherche-ticket-equipement')?.addEventListener('input', function () {
  const terme = this.value.trim().toLocaleLowerCase('fr'); let visibles = 0;
  document.querySelectorAll('[data-ticket-row]').forEach(ticket => { ticket.hidden = !ticket.dataset.search.includes(terme); if (!ticket.hidden) visibles++; });
  const message = document.getElementById('tickets-equipement-vides'); if (message) message.hidden = visibles > 0;
});
</script>
