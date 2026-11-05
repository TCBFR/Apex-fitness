<?php

$materielDisponible = count(array_filter($equipements, static fn(array $item): bool => $item['libelle_statut'] === 'Disponible'));
    $materielEnMaintenance = count(array_filter($equipements, static fn(array $item): bool => $item['libelle_statut'] === 'Maintenance'));
    $materielHorsService = count(array_filter($equipements, static fn(array $item): bool => $item['libelle_statut'] === 'Hors service'));
    $nombreMaterielsAlerte = count(array_filter(
      $equipements,
      static fn(array $item): bool => (int) $item['stock_actuel'] <= (int) $item['seuil_alerte']
    ));
    $unitesEnStock = array_sum(array_map(static fn(array $item): int => (int) $item['stock_actuel'], $equipements));
    $materielSelectionneId = filter_var($_GET['materiel'] ?? null, FILTER_VALIDATE_INT);
    $materielSelectionne = null;
    foreach ($equipements as $equipement) {
      if ($materielSelectionneId && (int) $equipement['Numequip'] === $materielSelectionneId) {
        $materielSelectionne = $equipement;
        break;
      }
    }
    $typesEquipement = $pdo->query(
      'SELECT Numtypeequip, libelletypeequip FROM type_equipement ORDER BY libelletypeequip'
    )->fetchAll(PDO::FETCH_ASSOC);
    $statutsEquipement = $pdo->query(
      "SELECT Numstatut, libelle_statut FROM statut WHERE type_statut = 'EQUIPEMENT' ORDER BY libelle_statut"
    )->fetchAll(PDO::FETCH_ASSOC);
    $modeEditionMateriel = isset($_GET['modifier']) && $_GET['modifier'] === '1' && $materielSelectionne !== null;
    $modeCommandeMateriel = isset($_GET['commander']) && $_GET['commander'] === '1';
    $historiqueMaterielComplet = isset($_GET['historique']) && $_GET['historique'] === '1';
    $commandesEmploye = $pdo->prepare(
      'SELECT c.Numcommande, c.Numequip, c.quantite, c.message, c.statut_commande, c.date_demande,
              eq.nomequip
         FROM commande_materiel c
         JOIN equipement eq ON eq.Numequip = c.Numequip
        WHERE c.NumUsers = ?
        ORDER BY c.date_demande DESC LIMIT 10'
    );
    $commandesEmploye->execute([(int) $moi]);
    $commandesEmploye = $commandesEmploye->fetchAll(PDO::FETCH_ASSOC);
    $mouvementsMateriel = [];
    $nombreMouvementsMateriel = 0;
    $derniereEntreeMateriel = null;
    if ($materielSelectionne) {
      $compteMouvements = $pdo->prepare('SELECT COUNT(*) FROM mouvement_stock WHERE Numequip = ?');
      $compteMouvements->execute([(int) $materielSelectionne['Numequip']]);
      $nombreMouvementsMateriel = (int) $compteMouvements->fetchColumn();
      $mouvementEntree = $pdo->prepare(
        "SELECT date_mouvement FROM mouvement_stock
         WHERE Numequip = ? AND type_mouvement = 'ENTREE'
         ORDER BY date_mouvement DESC, Nummouvement DESC LIMIT 1"
      );
      $mouvementEntree->execute([(int) $materielSelectionne['Numequip']]);
      $derniereEntreeMateriel = $mouvementEntree->fetchColumn() ?: null;
      $sqlMouvements = 'SELECT m.type_mouvement, m.quantite, m.date_mouvement, m.commentaire,
                               u.prenomUsers, u.nomUsers
                          FROM mouvement_stock m
                          JOIN utilisateur u ON u.NumUsers = m.NumUsers
                         WHERE m.Numequip = ?
                         ORDER BY m.date_mouvement DESC, m.Nummouvement DESC';
      if (!$historiqueMaterielComplet) $sqlMouvements .= ' LIMIT 5';
      $queryMouvements = $pdo->prepare($sqlMouvements);
      $queryMouvements->execute([(int) $materielSelectionne['Numequip']]);
      $mouvementsMateriel = $queryMouvements->fetchAll(PDO::FETCH_ASSOC);
    }

$ticketsEquipement = array_values(array_filter(
                $maintenances,
                static fn(array $ticket): bool => (int) $ticket['Numequip'] === (int) $materielSelectionne['Numequip']
              ));
