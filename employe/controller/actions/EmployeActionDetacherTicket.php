<?php

if ($action === 'detacher_ticket') {
        $nummaint = (int) ($_POST['maintenance'] ?? 0);
        try {
            $pdo->beginTransaction();
            $ticket = $pdo->prepare(
                'SELECT NumUsers_technicien, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande || (int) $demande['NumUsers_technicien'] !== (int) $moi) {
                throw new RuntimeException('Ce ticket ne vous est pas attribué.');
            }
            $termineQuery = $pdo->prepare(
                "SELECT libelle_statut FROM statut WHERE Numstatut = ?"
            );
            $termineQuery->execute([(int) $demande['Numstatut']]);
            if (in_array($termineQuery->fetchColumn(), ['Terminee', 'À valider'], true)) {
                throw new RuntimeException('Un ticket exécuté ou fermé ne peut pas être détaché.');
            }

            $statutOuverteQuery = $pdo->prepare(
                "SELECT Numstatut FROM statut
                 WHERE type_statut = 'MAINTENANCE' AND libelle_statut = 'Ouverte'"
            );
            $statutOuverteQuery->execute();
            $statutOuverte = (int) $statutOuverteQuery->fetchColumn();
            if (!$statutOuverte) {
                throw new RuntimeException('Le statut « Ouverte » n’est pas configuré.');
            }

            $detachement = $pdo->prepare(
                'UPDATE demande_maintenance
                 SET NumUsers_technicien = NULL, Numstatut = ?
                 WHERE Nummaint = ? AND NumUsers_technicien = ?'
            );
            $detachement->execute([$statutOuverte, $nummaint, $moi]);
            if ($detachement->rowCount() !== 1) {
                throw new RuntimeException('Le ticket n’a pas pu être remis dans la file commune.');
            }
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?)'
            )->execute(['Ticket remis dans la file commune pour prise en charge par un autre technicien.', $nummaint, $moi, $statutOuverte]);
            $pdo->commit();
            journalise('DETACHEMENT_TICKET_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Ticket remis dans la file commune.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Le ticket n’a pas pu être remis dans la file commune.');
        }
        redirige('employe/espace.php?onglet=maintenance');
    }
