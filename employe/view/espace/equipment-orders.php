      <?php if ($commandesEmploye): ?>
        <section class="inventory-my-orders" aria-label="Mes demandes de commande">
          <h3>Mes demandes de commande</h3>
          <div class="inventory-orders-list">
            <?php foreach ($commandesEmploye as $commande): ?>
              <article><span><strong><?= e($commande['nomequip']) ?> · <?= (int) $commande['quantite'] ?> unité<?= (int) $commande['quantite'] === 1 ? '' : 's' ?></strong><small>Demande du <?= dateFr($commande['date_demande']) ?><?= $commande['message'] ? ' · ' . e($commande['message']) : '' ?></small></span><b class="is-order-<?= e(match ($commande['statut_commande']) { 'En attente' => 'pending', 'Approuvée' => 'approved', 'Reçue' => 'received', default => 'rejected' }) ?>"><?= e($commande['statut_commande']) ?></b></article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
