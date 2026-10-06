<?php if (!$messages): ?>
  <div class="vide">Aucun message reçu.</div>
<?php else: foreach ($messages as $thread): $last = end($thread); ?>
  <?php if ((int) $thread[0]['NumUsers_destinataire'] !== $moi) continue; ?>
  <article class="carte message-conversation">
    <h3>Historique de la conversation</h3>
    <div class="messages-fil">
      <?php foreach ($thread as $message): $estMoi = (int) $message['NumUsers_emetteur'] === $moi; ?>
        <div class="message-bulle <?= $estMoi ? 'message-droite' : 'message-gauche' ?>">
          <strong><?= $estMoi ? 'Vous' : e($message['prenomUsers'] . ' ' . $message['nomUsers']) ?></strong>
          <p><?= nl2br(e($message['message'])) ?></p><small><?= dateFr($message['date_envoi'], true) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ((int) $last['NumUsers_destinataire'] === $moi): ?><form method="post" class="message-reponse">
      <?= champ_csrf() ?><input type="hidden" name="action" value="repondre_message"><input type="hidden" name="message_parent" value="<?= (int) $last['IdMessage'] ?>">
      <textarea name="message" rows="2" placeholder="Répondre à ce message..." required></textarea><button class="btn btn-vert btn-sm" type="submit">Répondre</button>
    </form><?php endif; ?>
    <form method="post" class="message-suppression"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer_message"><input type="hidden" name="message_parent" value="<?= (int) $thread[0]['IdMessage'] ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirme="Supprimer tout l’historique ?">Supprimer l’historique</button></form>
  </article>
<?php endforeach; endif; ?>
