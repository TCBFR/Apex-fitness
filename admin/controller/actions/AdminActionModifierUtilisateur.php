<?php

if ($action === 'modifier_utilisateur') {
        try {
            Utilisateur::modifierCompte((int) $_POST['utilisateur'], $_POST);
            journalise('MODIFICATION_UTILISATEUR', 'NumUsers=' . (int) $_POST['utilisateur']);
            flash('succes', 'Compte mis à jour.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }
