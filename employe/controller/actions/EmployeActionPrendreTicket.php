<?php

if ($action === 'prendre_ticket') {
        $nummaint = (int) ($_POST['maintenance'] ?? 0);
        try {
            $pdo->beginTransaction();
            $ticket = $pdo->prepare(
                'SELECT NumUsers_technicien, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande) {
                throw new RuntimeException('Ticket introuvable.');
            }
            if ($demande['NumUsers_technicien'] !== null) {
                throw new RuntimeException('Ce ticket est déjà pris par un technicien.');
            }
            $statutTicketQuery = $pdo->prepare(
                "SELECT libelle_statut FROM statut WHERE Numstatut = ? AND type_statut = 'MAINTENANCE'"
            );
            $statutTicketQuery->execute([(int) $demande['Numstatut']]);
            if ($statutTicketQuery->fetchColumn() !== 'Ouverte') {
                throw new RuntimeException('Seuls les tickets disponibles dans la file commune peuvent être pris.');
            }

            $statutAttenteQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'En attente'"
            );
            $statutAttenteQuery->execute();
            $statutAttente = (int) $statutAttenteQuery->fetchColumn();
            if (!$statutAttente) {
                throw new RuntimeException('Le statut « En attente » n’est pas configuré.');
            }

            $attribution = $pdo->prepare(
                'UPDATE demande_maintenance
                 SET NumUsers_technicien = ?, Numstatut = ?
                 WHERE Nummaint = ? AND NumUsers_technicien IS NULL'
            );
            $attribution->execute([$moi, $statutAttente, $nummaint]);
            if ($attribution->rowCount() !== 1) {
                throw new RuntimeException('Le ticket n’a pas pu être attribué.');
            }
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?)'
            )->execute(['Ticket attribué au technicien et mis en attente de prise en charge.', $nummaint, $moi, $statutAttente]);
            $pdo->commit();
            journalise('PRISE_TICKET_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket pris en charge.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être pris en charge.');
        }
        redirige('employe/espace.php?onglet=maintenance');
    }
