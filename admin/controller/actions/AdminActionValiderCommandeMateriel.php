<?php

if ($onglet === 'equipements' && in_array($action, [
        'valider_commande_materiel', 'refuser_commande_materiel', 'recevoir_commande_materiel',
    ], true)) {
        $idCommande = filter_var($_POST['commande'] ?? null, FILTER_VALIDATE_INT);
        try {
            if (!$idCommande || $idCommande < 1) throw new RuntimeException('Cette commande est invalide.');
            $pdo->beginTransaction();
            $commandeQuery = $pdo->prepare(
                'SELECT Numcommande, Numequip, quantite, statut_commande
                   FROM commande_materiel WHERE Numcommande = ? FOR UPDATE'
            );
            $commandeQuery->execute([$idCommande]);
            $commande = $commandeQuery->fetch(PDO::FETCH_ASSOC);
            if (!$commande) throw new RuntimeException('Cette commande est introuvable.');

            if ($action === 'valider_commande_materiel' || $action === 'refuser_commande_materiel') {
                if ($commande['statut_commande'] !== 'En attente') {
                    throw new RuntimeException('Cette commande a déjà été traitée.');
                }
                $nouveauStatut = $action === 'valider_commande_materiel' ? 'Approuvée' : 'Refusée';
                $pdo->prepare(
                    'UPDATE commande_materiel
                     SET statut_commande = ?, date_decision = NOW(), NumUsers_admin = ?
                     WHERE Numcommande = ?'
                )->execute([$nouveauStatut, (int) utilisateur()['NumUsers'], $idCommande]);
            } else {
                if ($commande['statut_commande'] !== 'Approuvée') {
                    throw new RuntimeException('Seule une commande approuvée peut être réceptionnée.');
                }
                $materielQuery = $pdo->prepare('SELECT Numequip FROM equipement WHERE Numequip = ? FOR UPDATE');
                $materielQuery->execute([(int) $commande['Numequip']]);
                if (!$materielQuery->fetchColumn()) {
                    throw new RuntimeException('Le matériel de cette commande est introuvable.');
                }
                $pdo->prepare('UPDATE equipement SET stock_actuel = stock_actuel + ? WHERE Numequip = ?')
                    ->execute([(int) $commande['quantite'], (int) $commande['Numequip']]);
                $pdo->prepare(
                    "INSERT INTO mouvement_stock (Numequip, NumUsers, type_mouvement, quantite, commentaire)
                     VALUES (?, ?, 'ENTREE', ?, ?)"
                )->execute([
                    (int) $commande['Numequip'],
                    (int) utilisateur()['NumUsers'],
                    (int) $commande['quantite'],
                    'Réception de la commande #' . (int) $idCommande,
                ]);
                $pdo->prepare(
                    "UPDATE commande_materiel
                     SET statut_commande = 'Reçue', date_reception = NOW()
                     WHERE Numcommande = ?"
                )->execute([$idCommande]);
            }
            $pdo->commit();
            journalise(strtoupper($action), 'Numcommande=' . $idCommande);
            flash('succes', match ($action) {
                'valider_commande_materiel' => 'La commande a été approuvée. Le stock sera mis à jour à sa réception.',
                'refuser_commande_materiel' => 'La commande a été refusée.',
                default => 'La réception a été enregistrée et le stock mis à jour.',
            });
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'La commande n’a pas pu être mise à jour.');
            if (!$exception instanceof RuntimeException) {
                error_log('Traitement de commande matériel impossible : ' . $exception->getMessage());
            }
        }
        redirige('admin/espace.php?onglet=equipements');
    }
