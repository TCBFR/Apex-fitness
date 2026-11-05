<?php

if ($maintenance && $action === 'commander_materiel') {
    $idMateriel = filter_var($_POST['materiel'] ?? null, FILTER_VALIDATE_INT);
    $quantite = filter_var($_POST['quantite'] ?? null, FILTER_VALIDATE_INT);
    $messageCommande = trim((string) ($_POST['message'] ?? ''));
    try {
      if (!$idMateriel || $idMateriel < 1 || !$quantite || $quantite < 1 || $quantite > 10000) {
        throw new RuntimeException('Indiquez un matériel et une quantité entre 1 et 10 000.');
      }
      if (mb_strlen($messageCommande) > 500) {
        throw new RuntimeException('Le message ne doit pas dépasser 500 caractères.');
      }
      $materielQuery = $pdo->prepare('SELECT 1 FROM equipement WHERE Numequip = ?');
      $materielQuery->execute([$idMateriel]);
      if (!$materielQuery->fetchColumn()) {
        throw new RuntimeException('Ce matériel n’existe plus dans l’inventaire.');
      }
      $pdo->prepare(
        "INSERT INTO commande_materiel (Numequip, NumUsers, quantite, message)
         VALUES (?, ?, ?, ?)"
      )->execute([$idMateriel, (int) $moi, $quantite, $messageCommande ?: null]);
      $idCommande = (int) $pdo->lastInsertId();
      journalise('DEMANDE_COMMANDE_MATERIEL', 'Numcommande=' . $idCommande);
      flash('succes', 'La demande de commande a été envoyée à l’administrateur.');
    } catch (RuntimeException $exception) {
      flash('erreur', $exception->getMessage());
    } catch (Throwable $exception) {
      error_log('Création de la demande de commande impossible : ' . $exception->getMessage());
      flash('erreur', 'La demande de commande n’a pas pu être envoyée. Réessayez.');
    }
    redirige('employe/espace.php?onglet=materiel&commande=1');
  }
