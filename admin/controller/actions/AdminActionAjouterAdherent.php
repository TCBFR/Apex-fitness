<?php

if ($action === 'ajouter_adherent') {
        $num = (int) $_POST['utilisateur'];
        try {
            Utilisateur::ajouterAdherent($num);
            journalise('AJOUT_ROLE_ADHERENT', 'NumUsers=' . $num);
            flash('succes', 'Rôle adhérent ajouté : le cumul employé + adhérent est autorisé.');
        } catch (Throwable $exception) {
            flash('erreur', 'Ajout impossible : ' . $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }
