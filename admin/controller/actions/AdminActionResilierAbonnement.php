<?php

if ($onglet === 'abonnements' && in_array($action, ['resilier_abonnement', 'suspendre_abonnement', 'reactiver_abonnement', 'renouveler_abonnement', 'changer_formule_abonnement'], true)) {
        $adherentId = (int) ($_POST['adherent'] ?? 0);
        $formuleId = (int) ($_POST['formule_actuelle'] ?? 0);
        $dateDebutActuelle = (string) ($_POST['date_debut_actuelle'] ?? '');
        try {
            $dateCheck = DateTimeImmutable::createFromFormat('!Y-m-d', $dateDebutActuelle);
            if (!$adherentId || !$formuleId || !$dateCheck || $dateCheck->format('Y-m-d') !== $dateDebutActuelle) throw new RuntimeException('Abonnement introuvable.');
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT statut_souscription FROM souscrire WHERE NumUsers=? AND Numabo=? AND date_debut=? FOR UPDATE');
            $lock->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            $statutActuel = $lock->fetchColumn();
            if (!$statutActuel) throw new RuntimeException('Abonnement introuvable.');
            if ($action === 'resilier_abonnement') {
                $pdo->prepare("UPDATE souscrire SET statut_souscription='Resiliee', date_fin=CURDATE() WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            } elseif ($action === 'suspendre_abonnement') {
                $pdo->prepare("UPDATE souscrire SET statut_souscription='Suspendue' WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            } elseif ($action === 'reactiver_abonnement') {
                $dateSet = $statutActuel === 'Resiliee' ? ', date_fin=NULL' : '';
                $pdo->prepare("UPDATE souscrire SET statut_souscription='En cours' $dateSet WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
            } else {
                $nouvelleFormule = $action === 'renouveler_abonnement' ? $formuleId : (int) ($_POST['nouvelle_formule'] ?? 0);
                $offreQuery = $pdo->prepare('SELECT o.type_offre, b.duree_engagement_mois FROM abonnement a JOIN offre o ON o.Numoffre=a.Numoffre LEFT JOIN basic b ON b.Numoffre=o.Numoffre WHERE a.Numabo=? AND a.actif_abo=1');
                $offreQuery->execute([$nouvelleFormule]);
                $offre = $offreQuery->fetch(PDO::FETCH_ASSOC);
                if (!$offre) throw new RuntimeException('Choisissez une formule active.');
                $finExistanteQuery = $pdo->prepare('SELECT date_fin FROM souscrire WHERE NumUsers=? AND Numabo=? AND date_debut=?');
                $finExistanteQuery->execute([$adherentId, $formuleId, $dateDebutActuelle]);
                $finExistante = $finExistanteQuery->fetchColumn();
                $nouveauDebut = $finExistante && $finExistante >= date('Y-m-d') ? (new DateTimeImmutable($finExistante))->modify('+1 day') : new DateTimeImmutable('today');
                $mois = $offre['type_offre'] === 'BASIC' ? max(1, (int) $offre['duree_engagement_mois']) : 1;
                $nouvelleFin = $nouveauDebut->modify('+' . $mois . ' months')->modify('-1 day');
                $pdo->prepare("UPDATE souscrire SET statut_souscription='Terminee' WHERE NumUsers=? AND Numabo=? AND date_debut=?")
                    ->execute([$adherentId, $formuleId, $dateDebutActuelle]);
                $pdo->prepare("INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription) VALUES (?, ?, ?, ?, 'En cours')")
                    ->execute([$adherentId, $nouvelleFormule, $nouveauDebut->format('Y-m-d'), $nouvelleFin->format('Y-m-d')]);
            }
            $pdo->commit();
            journalise(strtoupper($action), 'NumUsers=' . $adherentId . ', Numabo=' . $formuleId);
            flash('succes', $action === 'resilier_abonnement' ? 'Abonnement résilié.' : ($action === 'suspendre_abonnement' ? 'Abonnement suspendu.' : ($action === 'reactiver_abonnement' ? 'Abonnement réactivé.' : 'Abonnement renouvelé ou formule modifiée.')));
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=abonnements&id=' . $adherentId . '&formule=' . $formuleId . '&debut=' . rawurlencode($dateDebutActuelle));
    }
