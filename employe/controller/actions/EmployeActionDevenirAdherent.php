<?php

if ($action === 'devenir_adherent') {
    if (!$maintenance && !$accueil) {
      http_response_code(403);
      flash('erreur', 'Cette option est réservée aux employés d’accueil et de maintenance.');
      redirige('employe/espace.php?onglet=tableau');
    }

    try {
      $adherentQuery = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
      $adherentQuery->execute([(int) $moi]);
      if (!$adherentQuery->fetchColumn()) {
        Utilisateur::ajouterAdherent((int) $moi);
      }

      $_SESSION['user']['roles'] = roles_de((int) $moi);
      journalise('ACTIVATION_ESPACE_ADHERENT', 'Espace membre');
      flash('succes', 'Votre espace membre est activé. Votre compte employé et ses accès sont conservés.');
      redirige('adherent/espace.php');
    } catch (Throwable $exception) {
      error_log('[Activation espace adhérent] ' . $exception->getMessage());
      flash('erreur', 'L’activation de votre espace membre a échoué. Réessayez dans quelques instants.');
      redirige('employe/espace.php?onglet=tableau');
    }
  }
