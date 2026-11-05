<?php

if ($action === 'avancer_maintenance') {
        $nummaint  = (int) $_POST['maintenance'];
        $nouveau   = (int) $_POST['statut'];
        $commentaire = trim($_POST['commentaire'] ?? '') ?: null;

        try {
            $pdo->beginTransaction();

            $ticket = $pdo->prepare(
                'SELECT NumUsers_technicien, Numstatut
                 FROM demande_maintenance WHERE Nummaint = ? FOR UPDATE'
            );
            $ticket->execute([$nummaint]);
            $demande = $ticket->fetch();
            if (!$demande || (int) $demande['NumUsers_technicien'] !== (int) $moi) {
                throw new RuntimeException('Seul le technicien chargé du ticket peut le mettre à jour.');
            }

            $nouveauStatutQuery = $pdo->prepare(
                "SELECT s.libelle_statut
                 FROM statut s
                 JOIN statut_maintenance sm ON sm.Numstatut = s.Numstatut
                 WHERE s.Numstatut = ?"
            );
            $nouveauStatutQuery->execute([$nouveau]);
            $statut = $nouveauStatutQuery->fetch();
            $ancienStatutQuery = $pdo->prepare('SELECT libelle_statut FROM statut WHERE Numstatut = ?');
            $ancienStatutQuery->execute([(int) $demande['Numstatut']]);
            $ancienStatut = (string) $ancienStatutQuery->fetchColumn();
            if (
                !$statut
                || !in_array($statut['libelle_statut'], $transitionsMaintenance[$ancienStatut] ?? [], true)
            ) {
                throw new RuntimeException('Cette transition de statut n’est pas autorisée.');
            }

            // L'historique est conserve : une ligne par changement d'etat
            $pdo->prepare('UPDATE demande_maintenance SET Numstatut = ? WHERE Nummaint = ?')
                ->execute([$nouveau, $nummaint]);
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)')
                ->execute([$commentaire, $nummaint, $moi, $nouveau]);

            $libelle = $statut['libelle_statut'];

            $pdo->commit();
            journalise('MAJ_MAINTENANCE', 'Nummaint=' . $nummaint . ' -> ' . $libelle);
            flash('succes', $libelle === 'À valider'
                ? 'Intervention déclarée exécutée. L’employé déclarant peut maintenant fermer le ticket.'
                : 'Demande mise à jour.');
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', 'Mise à jour impossible.');
        }
        redirige('employe/espace.php?onglet=maintenance');
    }
