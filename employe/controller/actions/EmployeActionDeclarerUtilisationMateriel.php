<?php

if ($maintenance && $action === 'declarer_utilisation_materiel') {
    $idMateriel = filter_var($_POST['materiel'] ?? null, FILTER_VALIDATE_INT);
    $quantite = filter_var($_POST['quantite'] ?? null, FILTER_VALIDATE_INT);
    $commentaire = trim((string) ($_POST['commentaire'] ?? ''));
    try {
      if (!$idMateriel || $idMateriel < 1 || !$quantite || $quantite < 1) {
        throw new RuntimeException('Indiquez un matériel et une quantité valides.');
      }
      if (mb_strlen($commentaire) > 255) {
        throw new RuntimeException('Le commentaire ne doit pas dépasser 255 caractères.');
      }
      $pdo->beginTransaction();
      $materielQuery = $pdo->prepare('SELECT stock_actuel FROM equipement WHERE Numequip = ? FOR UPDATE');
      $materielQuery->execute([$idMateriel]);
      $materiel = $materielQuery->fetch(PDO::FETCH_ASSOC);
      if (!$materiel) {
        throw new RuntimeException('Ce matériel n’existe plus dans l’inventaire.');
      }
      if ((int) $materiel['stock_actuel'] < $quantite) {
        throw new RuntimeException('Le stock disponible est insuffisant pour cette utilisation.');
      }
      $pdo->prepare('UPDATE equipement SET stock_actuel = stock_actuel - ? WHERE Numequip = ?')
        ->execute([$quantite, $idMateriel]);
      $pdo->prepare(
        "INSERT INTO mouvement_stock (Numequip, NumUsers, type_mouvement, quantite, commentaire)
         VALUES (?, ?, 'SORTIE', ?, ?)"
      )->execute([$idMateriel, (int) $moi, $quantite, $commentaire ?: null]);
      $pdo->commit();
      journalise('UTILISATION_MATERIEL', 'Numequip=' . $idMateriel . ', quantite=' . $quantite);
      flash('succes', 'L’utilisation du matériel a été enregistrée.');
    } catch (RuntimeException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      flash('erreur', $exception->getMessage());
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      error_log('Enregistrement de l’utilisation du matériel impossible : ' . $exception->getMessage());
      flash('erreur', 'L’utilisation n’a pas pu être enregistrée. Réessayez.');
    }
    redirige('employe/espace.php?onglet=materiel&materiel=' . (int) $idMateriel);
  }
