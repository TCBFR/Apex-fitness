<?php

if ($action === 'creer_technicien') {
        try {
            if ($fonctionMaintenance === null) {
                throw new RuntimeException('Aucune fonction de maintenance n’est configurée.');
            }
            $donnees = $_POST;
            $donnees['roles'] = ['EMPLOYE'];
            $donnees['fonction'] = (int) $fonctionMaintenance['Numfonc'];
            $num = Utilisateur::creerCompte($donnees);
            journalise('CREATION_TECHNICIEN_MAINTENANCE', 'NumUsers=' . $num);
            flash('succes', 'Compte technicien de maintenance créé.');
        } catch (Throwable $exception) {
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=maintenance');
    }
