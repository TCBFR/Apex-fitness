<?php

if ($action === 'creer_coach') {
        try {
            if ((string) ($_POST['motdepasse'] ?? '') !== (string) ($_POST['confirmation'] ?? '')) {
                throw new InvalidArgumentException('Les mots de passe ne correspondent pas.');
            }
            $fonctionId = filter_var($_POST['fonction'] ?? null, FILTER_VALIDATE_INT);
            $fonctionValide = false;
            foreach ($fonctionsCoach as $fonction) {
                if ((int) $fonction['Numfonc'] === (int) $fonctionId) {
                    $fonctionValide = true;
                    break;
                }
            }
            if (!$fonctionValide) throw new InvalidArgumentException('Choisissez une spécialisation de coach valide.');
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $communeQuery = $pdo->prepare('SELECT id_commune FROM commune WHERE id_commune=?');
            $communeQuery->execute([$communeId]);
            if (!$communeQuery->fetchColumn()) throw new InvalidArgumentException('Choisissez une commune dans la liste.');

            $donnees = $_POST;
            $donnees['roles'] = ['EMPLOYE'];
            $donnees['fonction'] = (int) $fonctionId;
            $num = Utilisateur::creerCompte($donnees);
            $pdo->prepare('UPDATE employe SET id_commune=? WHERE NumUsers=?')->execute([$communeId, $num]);
            journalise('CREATION_COACH', 'NumUsers=' . $num);
            flash('succes', 'Le compte coach a été créé avec succès.');
        } catch (Throwable $exception) {
            $message = $exception instanceof PDOException && str_contains($exception->getMessage(), 'Duplicate')
                ? 'Cette adresse e-mail ou ce matricule est déjà utilisé.'
                : $exception->getMessage();
            flash('erreur', $message);
        }
        redirige('admin/espace.php?onglet=coachs');
    }
