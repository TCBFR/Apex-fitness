<?php

if ($action === 'declarer_panne') {
        $numequip = (int) $_POST['equipement'];
        $desc     = trim($_POST['description'] ?? '');
        $priorite = in_array($_POST['priorite'] ?? '', ['Basse', 'Moyenne', 'Haute'], true)
                    ? $_POST['priorite'] : 'Moyenne';
        $horsService = isset($_POST['hors_service']);

        if (mb_strlen($desc) < 5) {
            flash('erreur', 'Décrivez le problème en quelques mots.');
            redirige('employe/espace.php?onglet=creer-ticket');
        }

        $ticketCree = false;
        try {
            $pdo->beginTransaction();

            $equipement = $pdo->prepare(
                "SELECT eq.Numstatut, s.libelle_statut
                 FROM equipement eq
                 JOIN statut s ON s.Numstatut = eq.Numstatut
                 WHERE eq.Numequip = ? FOR UPDATE"
            );
            $equipement->execute([$numequip]);
            $etatEquipement = $equipement->fetch();
            if (!$etatEquipement) {
                throw new RuntimeException('Cet équipement n’existe pas dans l’inventaire.');
            }
            $ticketActif = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM demande_maintenance d
                 JOIN statut s ON s.Numstatut = d.Numstatut
                 WHERE d.Numequip = ? AND s.libelle_statut <> 'Terminee'"
            );
            $ticketActif->execute([$numequip]);
            if ((int) $ticketActif->fetchColumn() > 0) {
                throw new RuntimeException('Un ticket de maintenance est déjà en cours pour cet équipement.');
            }

            // statut "Ouverte" de la maintenance, etat cible de l'equipement
            $statutOuverteQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'Ouverte'"
            );
            $statutOuverteQuery->execute();
            $statutOuverte = (int) $statutOuverteQuery->fetchColumn();
            $etatCibleQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut WHERE type_statut = 'EQUIPEMENT' AND libelle_statut = ?"
            );
            $etatCibleQuery->execute([$horsService ? 'Hors service' : 'Maintenance']);
            $etatCible = (int) $etatCibleQuery->fetchColumn();
            if (!$statutOuverte || !$etatCible) {
                throw new RuntimeException('Les statuts nécessaires à la création du ticket ne sont pas configurés.');
            }

            $pdo->prepare(
                'INSERT INTO demande_maintenance (description_problememaint, priorite, Numequip, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?, ?)')
                ->execute([$desc, $priorite, $numequip, $moi, $statutOuverte]);
            $nummaint = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)')
                ->execute(['Demande ouverte', $nummaint, $moi, $statutOuverte]);

            $pdo->prepare('UPDATE equipement SET Numstatut = ? WHERE Numequip = ?')
                ->execute([$etatCible, $numequip]);

            $pdo->commit();
            $ticketCree = true;
            journalise('CREATION_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket créé et transmis à la file des techniciens de maintenance.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être créé. Réessayez.');
        }
        redirige('employe/espace.php?onglet=' . ($ticketCree && $accueil ? 'suivi-tickets' : ($ticketCree ? 'materiel' : 'creer-ticket')));
    }
