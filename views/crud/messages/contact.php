<?php if (!$messages): ?>
  <div class="vide">Aucun message reçu.</div>
<?php else: ?>
  <div class="table-scroll"><table class="tableau">
    <thead><tr><th>Date</th><th>Expéditeur</th><th>Sujet</th><th>Message</th></tr></thead>
    <tbody><?php foreach ($messages as $message): ?><tr>
      <td class="mono"><?= dateFr($message['date_envoi'], true) ?></td>
      <td><?= e($message['nom']) ?><br><span class="mono"><?= e($message['mail']) ?></span></td>
      <td><?= e($message['sujet']) ?></td><td><?= nl2br(e($message['message'])) ?></td>
    </tr><?php endforeach; ?></tbody>
  </table></div>
<?php endif; ?>
