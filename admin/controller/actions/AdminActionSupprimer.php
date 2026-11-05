<?php

if ($action === 'supprimer') {
        $num = (int) $_POST['utilisateur'];
        $retour = (string) ($_POST['retour'] ?? '');
        $estEgalementEmploye = false;
        try {
            if ($retour === 'adherents') {
                $verifieAdherent = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
                $verifieAdherent->execute([$num]);
                if (!$verifieAdherent->fetchColumn()) {
                    throw new RuntimeException('Cet adhérent est introuvable.');
                }
                if ($num === (int) utilisateur()['NumUsers']) {
                    throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.');
                }
                $pdo->beginTransaction();
                $roleEmploye = $pdo->prepare('SELECT 1 FROM employe WHERE NumUsers = ? FOR UPDATE');
                $roleEmploye->execute([$num]);
                $estEgalementEmploye = (bool) $roleEmploye->fetchColumn();
                $pdo->prepare('DELETE FROM acceptation_contrat_abonnement WHERE NumUsers = ?')->execute([$num]);
                $pdo->prepare('DELETE FROM paiement WHERE NumUsers = ?')->execute([$num]);
                if ($estEgalementEmploye) {
                    $pdo->prepare('DELETE FROM adherent WHERE NumUsers = ?')->execute([$num]);
                } else {
                    Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
                }
                $pdo->commit();
            } elseif ($retour === 'coachs') {
                $coachQuery = $pdo->prepare(
                    "SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
                      WHERE e.NumUsers=? AND LOWER(f.libellefonc) IN ($placeholdersFonctionsCoach)"
                );
                $coachQuery->execute([$num, ...$nomsFonctionsCoachCompat]);
                if (!$coachQuery->fetchColumn()) throw new RuntimeException('Ce coach est introuvable.');
                $activitesCoach = $pdo->prepare('SELECT COUNT(*) FROM activite WHERE NumUsers=?');
                $activitesCoach->execute([$num]);
                if ((int) $activitesCoach->fetchColumn() > 0) {
                    throw new RuntimeException('Ce coach est associé à des activités. Réaffectez-les avant de supprimer son compte.');
                }
                Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
            } elseif ($retour === 'employes') {
                $employeQuery = $pdo->prepare(
                    "SELECT e.NumUsers FROM employe e JOIN fonction f ON f.Numfonc=e.Numfonc
                      WHERE e.NumUsers=? AND (LOWER(f.libellefonc) LIKE '%maintenance%' OR LOWER(f.libellefonc) LIKE '%accueil%')"
                );
                $employeQuery->execute([$num]);
                if (!$employeQuery->fetchColumn()) throw new RuntimeException('Cet employé de Maintenance ou d’Accueil est introuvable.');
                if ($num === (int) utilisateur()['NumUsers']) throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.');
                $liaisonMaintenance = $pdo->prepare(
                    'SELECT (SELECT COUNT(*) FROM demande_maintenance WHERE NumUsers=?) + (SELECT COUNT(*) FROM intervention WHERE NumUsers=?)'
                );
                $liaisonMaintenance->execute([$num, $num]);
                if ((int) $liaisonMaintenance->fetchColumn() > 0) {
                    throw new RuntimeException('Cet employé est lié à l’historique de maintenance et ne peut pas être supprimé.');
                }
                Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
            } else {
                Utilisateur::supprimerCompte($num, (int) utilisateur()['NumUsers']);
            }
            journalise(
                $retour === 'adherents' && $estEgalementEmploye ? 'SUPPRESSION_ROLE_ADHERENT' : 'SUPPRESSION_UTILISATEUR',
                'NumUsers=' . $num
            );
            flash(
                'succes',
                $retour === 'adherents' && $estEgalementEmploye
                    ? 'Accès adhérent supprimé. Le compte employé et ses activités ont été conservés.'
                    : ($retour === 'adherents' ? 'Adhérent supprimé.' : 'Compte supprimé.')
            );
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige(match ($retour) {
            'maintenance' => 'admin/espace.php?onglet=maintenance',
            'adherents' => 'admin/espace.php?onglet=adherents',
            'coachs' => 'admin/espace.php?onglet=coachs',
            'employes' => 'admin/espace.php?onglet=utilisateurs',
            default => 'admin/espace.php?onglet=utilisateurs',
        });
    }
