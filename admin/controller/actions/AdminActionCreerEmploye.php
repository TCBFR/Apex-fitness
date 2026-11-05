<?php

if ($action === 'creer_employe') {
        try {
            if ((string) ($_POST['motdepasse'] ?? '') !== (string) ($_POST['confirmation'] ?? '')) {
                throw new InvalidArgumentException('Les mots de passe ne correspondent pas.');
            }
            $fonctionId = filter_var($_POST['fonction'] ?? null, FILTER_VALIDATE_INT);
            if (!$fonctionId || !in_array((int) $fonctionId, $idsFonctionsEmploye, true)) {
                throw new InvalidArgumentException('Choisissez la fonction Maintenance ou Accueil.');
            }
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $communeQuery = $pdo->prepare('SELECT id_commune FROM commune WHERE id_commune=?');
            $communeQuery->execute([$communeId]);
            if (!$communeQuery->fetchColumn()) throw new InvalidArgumentException('Choisissez une commune dans la liste.');
            $donnees = $_POST;
            $donnees['roles'] = ['EMPLOYE'];
            $donnees['fonction'] = (int) $fonctionId;
            $num = Utilisateur::creerCompte($donnees);
            $pdo->prepare('UPDATE employe SET id_commune=? WHERE NumUsers=?')->execute([$communeId, $num]);
            journalise('CREATION_EMPLOYE', 'NumUsers=' . $num);
            flash('succes', 'Le compte employé a été créé.');
        } catch (Throwable $exception) {
            $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail ou ce matricule est déjà utilisé.'
                : $exception->getMessage();
            flash('erreur', $message);
        }
        redirige('admin/espace.php?onglet=utilisateurs');
    }
