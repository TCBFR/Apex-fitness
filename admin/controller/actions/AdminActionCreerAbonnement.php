<?php

if ($onglet === 'abonnements' && $action === 'creer_abonnement') {
        try {
            $adherentId = filter_var($_POST['adherent'] ?? null, FILTER_VALIDATE_INT);
            $formuleId = filter_var($_POST['formule'] ?? null, FILTER_VALIDATE_INT);
            $debut = trim((string) ($_POST['date_debut'] ?? ''));
            $fin = trim((string) ($_POST['date_fin'] ?? ''));
            $adherentCheck = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers=?');
            $adherentCheck->execute([$adherentId]);
            $formuleCheck = $pdo->prepare('SELECT 1 FROM abonnement WHERE Numabo=? AND actif_abo=1');
            $formuleCheck->execute([$formuleId]);
            if (!$adherentCheck->fetchColumn() || !$formuleCheck->fetchColumn()) throw new RuntimeException('Choisissez un adhérent et une formule valides.');
            $debutValide = DateTimeImmutable::createFromFormat('!Y-m-d', $debut);
            $finValide = $fin === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $fin);
            if (!$debutValide || $debutValide->format('Y-m-d') !== $debut || ($fin !== '' && (!$finValide || $finValide->format('Y-m-d') !== $fin || $finValide < $debutValide))) {
                throw new RuntimeException('Vérifiez les dates de l’abonnement.');
            }
            $pdo->prepare("INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription) VALUES (?, ?, ?, ?, 'En cours')")
                ->execute([(int) $adherentId, (int) $formuleId, $debut, $fin !== '' ? $fin : null]);
            journalise('CREATION_ABONNEMENT', 'NumUsers=' . (int) $adherentId . ', Numabo=' . (int) $formuleId);
            flash('succes', 'Abonnement créé.');
        } catch (Throwable $exception) { flash('erreur', $exception->getMessage()); }
        redirige('admin/espace.php?onglet=abonnements');
    }
