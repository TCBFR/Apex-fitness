<?php

if (!$maintenance && $action === 'fermer_ticket') {
        $nummaint = (int) ($_POST['maintenance'] ?? 0);
        try {
            $pdo->beginTransaction();
            $ticket = $pdo->prepare(
                'SELECT NumUsers, Numequip, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande || (int) $demande['NumUsers'] !== (int) $moi) {
                throw new RuntimeException('Vous ne pouvez fermer que vos propres tickets.');
            }

            $statutActuel = $pdo->prepare('SELECT libelle_statut FROM statut WHERE Numstatut = ?');
            $statutActuel->execute([(int) $demande['Numstatut']]);
            if ($statutActuel->fetchColumn() !== 'À valider') {
                throw new RuntimeException('Le ticket doit d’abord être déclaré exécuté par le technicien.');
            }

            $statutTermineQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'Terminee'"
            );
            $statutTermineQuery->execute();
            $statutTermine = (int) $statutTermineQuery->fetchColumn();
            $statutDisponibleQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'EQUIPEMENT' AND libelle_statut = 'Disponible'"
            );
            $statutDisponibleQuery->execute();
            $statutDisponible = (int) $statutDisponibleQuery->fetchColumn();
            if (!$statutTermine || !$statutDisponible) {
                throw new RuntimeException('Les statuts nécessaires à la fermeture ne sont pas configurés.');
            }

            $pdo->prepare('UPDATE demande_maintenance SET Numstatut = ? WHERE Nummaint = ?')
                ->execute([$statutTermine, $nummaint]);
            $pdo->prepare(
                'UPDATE equipement
                 SET Numstatut = ?, date_derniere_maintenance = CURRENT_DATE
                 WHERE Numequip = ?'
            )
                ->execute([$statutDisponible, (int) $demande['Numequip']]);
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?)'
            )->execute(['Travaux vérifiés et ticket fermé par l’employé déclarant.', $nummaint, $moi, $statutTermine]);
            $pdo->commit();
            journalise('FERMETURE_TICKET_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket fermé. L’équipement est de nouveau disponible.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être fermé.');
        }
        redirige('employe/espace.php?onglet=' . ($accueil ? 'suivi-tickets&ticket=' . $nummaint : 'materiel'));
    }
