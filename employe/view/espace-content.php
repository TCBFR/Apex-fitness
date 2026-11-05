
      <?php if ((!$maintenance || !in_array($onglet, ['tableau', 'maintenance'], true)) && !($accueil && in_array($onglet, ['suivi-tickets', 'creneaux', 'reservations'], true))): ?>
      <div class="admin-heading">
        <div>
          <p class="admin-eyebrow"><?= $maintenance ? 'SUIVI DES INTERVENTIONS' : 'GESTION DE LA SALLE' ?></p>
          <h2><?= e($titresOnglets[$onglet] ?? 'Espace employé') ?></h2>
          <p class="employee-page-description"><?= e([
            'tableau' => 'Retrouvez en un coup d’œil les indicateurs et l’activité du club aujourd’hui.',
            'creneaux' => $accueil ? 'Consultez le planning des activités et organisez les séances dans les salles du club.' : 'Planifiez les séances et consultez leur occupation.',
            'adherents' => 'Consultez les informations et le statut des adhérents.',
            'creer-ticket' => 'Signalez un problème matériel à l’équipe de maintenance.',
            'materiel' => 'Consultez l’inventaire et suivez les tickets que vous avez signalés.',
            'maintenance' => 'Suivez les demandes et consignez chaque intervention.',
            'faq' => 'Répondez aux questions et gérez les contenus de la FAQ.',
            'messages' => 'Consultez les échanges et répondez aux adhérents.',
          ][$onglet] ?? '') ?></p>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($maintenance && !in_array($onglet, ['tableau', 'maintenance', 'materiel'], true)): ?>
      <div class="compteurs">
        <div class="compteur"><div class="v"><?= count($maintenances) ?></div><div class="k">tickets au total</div></div>
        <div class="compteur warn"><div class="v"><?= count(array_filter($maintenances, static fn(array $ticket): bool => $ticket['NumUsers_technicien'] === null && $ticket['libelle_statut'] !== 'Terminee')) ?></div><div class="k">dans la file commune</div></div>
        <div class="compteur"><div class="v"><?= $statutsTickets['Terminee'] ?? 0 ?></div><div class="k">tickets fermés</div></div>
      </div>
      <?php endif; ?>

<?php if ($maintenance && $onglet === 'tableau'): ?>
<?php require __DIR__ . '/espace/maintenance-dashboard.php'; ?>
<?php elseif ($onglet === 'tableau'): ?>
<?php require __DIR__ . '/espace/dashboard.php'; ?>
<?php elseif ($onglet === 'adherents'): ?>
<?php require __DIR__ . '/espace/members.php'; ?>
<?php elseif ($onglet === 'reservations' && $accueil): ?>
<?php require __DIR__ . '/espace/reservations.php'; ?>
<?php elseif ($onglet === 'creneaux'): ?>
<?php require __DIR__ . '/espace/planning.php'; ?>
<?php elseif ($onglet === 'creer-ticket'): ?>
<?php require __DIR__ . '/espace/create-ticket.php'; ?>
<?php elseif ($onglet === 'materiel'): ?>
<?php require __DIR__ . '/espace/equipment.php'; ?>
<?php elseif ($onglet === 'maintenance' || ($accueil && $onglet === 'suivi-tickets')): ?>
<?php require __DIR__ . '/espace/tickets.php'; ?>
<?php elseif ($onglet === 'messages'): ?>
<?php require __DIR__ . '/espace/messages.php'; ?>
<?php else: ?>
<?php require __DIR__ . '/espace/faq.php'; ?>
<?php endif; ?>

    </main>