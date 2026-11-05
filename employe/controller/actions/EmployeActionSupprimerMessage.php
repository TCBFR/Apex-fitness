<?php

if (!$maintenance && $action === 'supprimer_message') {
    try {
      $messageController->delete($moi, (int) ($_POST['message_parent'] ?? 0));
      flash('succes', 'Conversation supprimée.');
    } catch (Throwable $exception) {
      flash('erreur', $exception->getMessage());
    }
    redirige('employe/espace.php?onglet=messages');
  }
