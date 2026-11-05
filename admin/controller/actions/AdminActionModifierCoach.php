<?php

if ($action === 'modifier_coach') {
        $id = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $coachQuery = $pdo->prepare(
                "SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
                  WHERE e.NumUsers=? AND LOWER(f.libellefonc) IN ($placeholdersFonctionsCoach)"
            );
            $coachQuery->execute([$id, ...$nomsFonctionsCoachCompat]);
            if (!$coachQuery->fetchColumn()) throw new RuntimeException('Ce coach est introuvable.');

            $matricule = trim((string) ($_POST['matriculeemp'] ?? ''));
            $dateEmbauche = trim((string) ($_POST['Date_embaucheemp'] ?? ''));
            $numFonction = filter_var($_POST['Numfonc'] ?? null, FILTER_VALIDATE_INT);
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $fonctionQuery = $pdo->prepare("SELECT Numfonc FROM fonction WHERE Numfonc=? AND LOWER(libellefonc) IN ($placeholdersFonctionsCoach)");
            $fonctionQuery->execute([$numFonction, ...$nomsFonctionsCoachCompat]);
            if (!$fonctionQuery->fetchColumn()) throw new InvalidArgumentException('Sélectionnez une spécialisation valide.');
            $communeQuery = $pdo->prepare('SELECT id_commune FROM commune WHERE id_commune=?');
            $communeQuery->execute([$communeId]);
            if (!$communeQuery->fetchColumn()) throw new InvalidArgumentException('Choisissez une commune dans la liste.');
            if ($matricule === '' || mb_strlen($matricule) > 20) throw new InvalidArgumentException('Le matricule est obligatoire (20 caractères maximum).');
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateEmbauche);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count'])) || $date->format('Y-m-d') !== $dateEmbauche) {
                throw new InvalidArgumentException('La date d’embauche est invalide.');
            }

            $pdo->beginTransaction();
            Utilisateur::modifierCompte($id, $_POST);
            $pdo->prepare('UPDATE employe SET matriculeemp=?, Date_embaucheemp=?, Numfonc=?, id_commune=? WHERE NumUsers=?')
                ->execute([$matricule, $dateEmbauche, $numFonction, $communeId, $id]);
            $pdo->commit();
            journalise('MODIFICATION_COACH', 'NumUsers=' . $id);
            flash('succes', 'Fiche coach mise à jour.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=coachs&id=' . $id . '&vue=voir');
    }
