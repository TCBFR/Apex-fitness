<?php

if ($action === 'creer_utilisateur') {
        try {
            $num = Utilisateur::creerCompte($_POST);
            journalise('CREATION_UTILISATEUR', 'NumUsers=' . $num);
            flash('succes', 'Compte créé.');
        } catch (Throwable $exception) {
            $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail est déjà utilisée.'
                : $exception->getMessage();
            flash('erreur', $message);
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }
