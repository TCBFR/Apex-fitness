<?php

if ($maintenance && $action === 'modifier_materiel') {
    require_once __DIR__ . '/service/StockageImageEquipement.php';
    $imageStorage = new StockageImageEquipement();
    $imageEnregistree = null;
    $ancienneImage = null;
    $idMateriel = filter_var($_POST['materiel'] ?? null, FILTER_VALIDATE_INT);
    try {
      if (!$idMateriel || $idMateriel < 1) {
        throw new RuntimeException('Le matériel sélectionné est invalide.');
      }
      $nom = trim((string) ($_POST['nom'] ?? ''));
      $marque = trim((string) ($_POST['marque'] ?? ''));
      $modele = trim((string) ($_POST['modele'] ?? ''));
      $idType = filter_var($_POST['categorie'] ?? null, FILTER_VALIDATE_INT);
      $idStatut = filter_var($_POST['statut'] ?? null, FILTER_VALIDATE_INT);
      $seuilAlerte = filter_var($_POST['seuil_alerte'] ?? null, FILTER_VALIDATE_INT);
      if ($nom === '' || mb_strlen($nom) > 80) {
        throw new RuntimeException('Le nom est obligatoire (80 caractères maximum).');
      }
      if (mb_strlen($marque) > 60 || mb_strlen($modele) > 60) {
        throw new RuntimeException('La marque et le modèle ne doivent pas dépasser 60 caractères.');
      }
      if (!$idType || !$idStatut || $seuilAlerte === false || $seuilAlerte < 0 || $seuilAlerte > 10000) {
        throw new RuntimeException('Choisissez une catégorie, un statut et un seuil d’alerte valides.');
      }
      $pdo->beginTransaction();
      $materielQuery = $pdo->prepare('SELECT image_equipement FROM equipement WHERE Numequip = ? FOR UPDATE');
      $materielQuery->execute([$idMateriel]);
      $materielActuel = $materielQuery->fetch(PDO::FETCH_ASSOC);
      if (!$materielActuel) {
        throw new RuntimeException('Ce matériel n’existe plus dans l’inventaire.');
      }
      $ancienneImage = $materielActuel['image_equipement'];
      $categorieQuery = $pdo->prepare('SELECT 1 FROM type_equipement WHERE Numtypeequip = ?');
      $categorieQuery->execute([$idType]);
      $statutQuery = $pdo->prepare("SELECT libelle_statut FROM statut WHERE Numstatut = ? AND type_statut = 'EQUIPEMENT'");
      $statutQuery->execute([$idStatut]);
      $libelleStatut = $statutQuery->fetchColumn();
      if (!$categorieQuery->fetchColumn() || $libelleStatut === false) {
        throw new RuntimeException('La catégorie ou le statut sélectionné n’est pas valide.');
      }
      if ($libelleStatut === 'Disponible') {
        $ticketActif = $pdo->prepare(
          "SELECT COUNT(*)
           FROM demande_maintenance d
           JOIN statut s ON s.Numstatut = d.Numstatut AND s.type_statut = 'MAINTENANCE'
           WHERE d.Numequip = ? AND s.libelle_statut <> 'Terminee'"
        );
        $ticketActif->execute([$idMateriel]);
        if ((int) $ticketActif->fetchColumn() > 0) {
          throw new RuntimeException('Un matériel avec un ticket de maintenance actif ne peut pas être marqué disponible.');
        }
      }
      if (isset($_FILES['image_equipement'])) {
        $imageEnregistree = $imageStorage->store($_FILES['image_equipement']);
      }
      $imageFinale = $imageEnregistree ?? $ancienneImage;
      $updateMateriel = $pdo->prepare(
        'UPDATE equipement
         SET nomequip = ?, marque = ?, modele = ?, image_equipement = ?, Numtypeequip = ?, Numstatut = ?, seuil_alerte = ?
         WHERE Numequip = ?'
      );
      $updateMateriel->execute([
        $nom,
        $marque !== '' ? $marque : null,
        $modele !== '' ? $modele : null,
        $imageFinale,
        $idType,
        $idStatut,
        $seuilAlerte,
        $idMateriel,
      ]);
      $pdo->commit();
      if ($imageEnregistree !== null && $ancienneImage !== $imageEnregistree) {
        $imageStorage->remove($ancienneImage);
      }
      journalise('MODIFICATION_EQUIPEMENT', 'Numequip=' . $idMateriel);
      flash('succes', 'Le matériel a été modifié.');
    } catch (RuntimeException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $imageStorage->remove($imageEnregistree);
      flash('erreur', $exception->getMessage());
    } catch (PDOException $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $imageStorage->remove($imageEnregistree);
      error_log('Modification du matériel impossible : ' . $exception->getMessage());
      flash('erreur', 'Le matériel n’a pas pu être modifié. Réessayez.');
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $imageStorage->remove($imageEnregistree);
      error_log('Erreur inattendue lors de la modification du matériel : ' . $exception->getMessage());
      flash('erreur', 'Une erreur inattendue a empêché la modification du matériel.');
    }
    redirige('employe/espace.php?onglet=materiel&materiel=' . (int) $idMateriel);
  }
