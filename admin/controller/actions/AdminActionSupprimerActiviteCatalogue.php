<?php

if ($onglet === 'activites' && in_array($action, ['creer_activite_catalogue', 'modifier_activite_catalogue', 'supprimer_activite_catalogue'], true)) {
        $retourActivites = 'admin/espace.php?onglet=activites';
        $nouvelleImageActivite = null;
        try {
            if ($action === 'supprimer_activite_catalogue') {
                $idActivite = filter_var($_POST['activite'] ?? null, FILTER_VALIDATE_INT);
                $activiteASupprimer = $idActivite ? ActiviteCatalogue::trouver($pdo, (int) $idActivite) : null;
                if (!$activiteASupprimer) throw new RuntimeException('Activité introuvable.');
                $pdo->prepare('DELETE FROM activite_catalogue WHERE Numcatalogue=?')->execute([(int) $idActivite]);
                ActiviteCatalogue::supprimerImage($activiteASupprimer['image_path'] ?? null);
                journalise('SUPPRESSION_ACTIVITE', 'Numcatalogue=' . (int) $idActivite);
                flash('succes', 'Activité supprimée de la page publique.');
            } else {
                [$titreActivite, $typeActivite, $descriptionActivite] = ActiviteCatalogue::valider($_POST);
                $nouvelleImageActivite = ActiviteCatalogue::recevoirImage($_FILES['image'] ?? []);
                if ($action === 'creer_activite_catalogue') {
                    $pdo->prepare('INSERT INTO activite_catalogue (titre,type_activite,description,image_path) VALUES (?,?,?,?)')
                        ->execute([$titreActivite, $typeActivite, $descriptionActivite, $nouvelleImageActivite]);
                    $nouvelleImageActivite = null;
                    journalise('CREATION_ACTIVITE', 'type=' . $typeActivite);
                    flash('succes', 'Activité ajoutée à la page publique.');
                } else {
                    $idActivite = filter_var($_POST['activite'] ?? null, FILTER_VALIDATE_INT);
                    $activiteAModifier = $idActivite ? ActiviteCatalogue::trouver($pdo, (int) $idActivite) : null;
                    if (!$activiteAModifier) throw new RuntimeException('Activité introuvable.');
                    $ancienneImageActivite = $activiteAModifier['image_path'] ?? null;
                    $imageAConserver = $nouvelleImageActivite ?? ($ancienneImageActivite && isset($_POST['retirer_image']) ? null : $ancienneImageActivite);
                    $pdo->prepare('UPDATE activite_catalogue SET titre=?,type_activite=?,description=?,image_path=? WHERE Numcatalogue=?')
                        ->execute([$titreActivite, $typeActivite, $descriptionActivite, $imageAConserver, (int) $idActivite]);
                    if ($nouvelleImageActivite || ($ancienneImageActivite && isset($_POST['retirer_image']))) {
                        ActiviteCatalogue::supprimerImage($ancienneImageActivite);
                    }
                    $nouvelleImageActivite = null;
                    journalise('MODIFICATION_ACTIVITE', 'Numcatalogue=' . (int) $idActivite);
                    flash('succes', 'Activité modifiée sur la page publique.');
                    $retourActivites .= '&activite_id=' . (int) $idActivite . '&vue=voir';
                }
            }
        } catch (Throwable $exception) {
            ActiviteCatalogue::supprimerImage($nouvelleImageActivite);
            flash('erreur', $exception->getMessage());
            if ($action === 'modifier_activite_catalogue') $retourActivites .= '&activite_id=' . (int) ($_POST['activite'] ?? 0) . '&vue=modifier';
            elseif ($action === 'creer_activite_catalogue') $retourActivites .= '&vue=creer';
        }
        redirige($retourActivites);
    }
