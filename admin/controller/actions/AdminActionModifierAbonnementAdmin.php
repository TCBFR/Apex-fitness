<?php

if ($onglet === 'abonnements' && $action === 'modifier_abonnement_admin') {
        $adherentId = (int) ($_POST['adherent'] ?? 0);
        $formuleId = (int) ($_POST['formule_actuelle'] ?? 0);
        $dateDebutActuelle = trim((string) ($_POST['date_debut_actuelle'] ?? ''));
        $dateFinNouvelle = trim((string) ($_POST['date_fin'] ?? ''));
        try {
            $dateDebutValidee = DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebutActuelle);
            $dateFinValidee = $dateFinNouvelle === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $dateFinNouvelle);
            if (!$adherentId || !$formuleId || !$dateDebutValidee || $dateDebutValidee->format('Y-m-d') !== $dateDebutActuelle) throw new RuntimeException('Abonnement introuvable.');
            if ($dateFinNouvelle !== '' && (!$dateFinValidee || $dateFinValidee->format('Y-m-d') !== $dateFinNouvelle || $dateFinValidee < $dateDebutValidee)) throw new RuntimeException('La date d’échéance doit être valide et postérieure au début de l’abonnement.');
            $pdo->beginTransaction();
            $verifierAbo = $pdo->prepare('SELECT 1 FROM souscrire WHERE NumUsers=? AND Numabo=? AND date_debut=? FOR UPDATE');
            $verifierAbo->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            if (!$verifierAbo->fetchColumn()) throw new RuntimeException('Abonnement introuvable.');
            $pdo->prepare('UPDATE souscrire SET date_fin=? WHERE NumUsers=? AND Numabo=? AND date_debut=?')->execute([$dateFinNouvelle !== '' ? $dateFinNouvelle : null, $adherentId, $formuleId, $dateDebutActuelle]);
            $pdo->commit();
            journalise('MODIFICATION_ECHEANCE_ABONNEMENT', 'NumUsers=' . $adherentId . ', Numabo=' . $formuleId);
            flash('succes', 'Échéance de l’abonnement mise à jour.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=abonnements&id=' . $adherentId . '&formule=' . $formuleId . '&debut=' . rawurlencode($dateDebutActuelle));
    }
