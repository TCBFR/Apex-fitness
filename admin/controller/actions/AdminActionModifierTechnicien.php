<?php

if ($action === 'modifier_technicien') {
        $num = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $technicien = $pdo->prepare(
                'SELECT e.NumUsers FROM employe e
                 JOIN fonction f ON f.Numfonc = e.Numfonc
                 WHERE e.NumUsers = ? AND LOWER(f.libellefonc) LIKE ?'
            );
            $technicien->execute([$num, '%maintenance%']);
            if (!$technicien->fetchColumn()) {
                throw new RuntimeException('Ce compte n’est pas un technicien de maintenance.');
            }
            Utilisateur::modifierCompte($num, $_POST);
            journalise('MODIFICATION_TECHNICIEN_MAINTENANCE', 'NumUsers=' . $num);
            flash('succes', 'Compte technicien mis à jour.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=maintenance');
    }
