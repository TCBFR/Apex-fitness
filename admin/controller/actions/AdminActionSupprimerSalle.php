<?php

if ($onglet === 'salles' && in_array($action, ['creer_salle', 'modifier_salle', 'supprimer_salle'], true)) {
        $retourSalles = 'admin/espace.php?onglet=salles';
        $nouvelleImageSalle = null;
        try {
            if ($action === 'supprimer_salle') {
                $idSalle = filter_var($_POST['salle'] ?? null, FILTER_VALIDATE_INT);
                $querySalle = $pdo->prepare('SELECT Numsalle, image_salle FROM salle WHERE Numsalle=?');
                $querySalle->execute([$idSalle]);
                $salleASupprimer = $querySalle->fetch(PDO::FETCH_ASSOC);
                if (!$salleASupprimer) throw new RuntimeException('Cette salle est introuvable.');
                $pdo->prepare('DELETE FROM salle WHERE Numsalle=?')->execute([(int) $idSalle]);
                SalleAdmin::supprimerImage($salleASupprimer['image_salle'] ?? null);
                journalise('SUPPRESSION_SALLE', 'Numsalle=' . (int) $idSalle);
                flash('succes', 'Salle supprimée.');
            } else {
                [$nomSalle, $capaciteSalle, $localisationSalle, $superficieSalle, $etatSalle, $descriptionSalle] = SalleAdmin::valider($_POST);
                $nouvelleImageSalle = SalleAdmin::recevoirImage($_FILES['image'] ?? []);
                if ($action === 'creer_salle') {
                    $pdo->prepare('INSERT INTO salle (nomsalle,capacitesalle,localisationsalle,superficiesalle,etatsalle,description_salle,image_salle) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$nomSalle, $capaciteSalle, $localisationSalle, $superficieSalle, $etatSalle, $descriptionSalle, $nouvelleImageSalle]);
                    $idSalle = (int) $pdo->lastInsertId();
                    $nouvelleImageSalle = null;
                    journalise('CREATION_SALLE', 'Numsalle=' . $idSalle);
                    flash('succes', 'Salle ajoutée à la liste.');
                    $retourSalles .= '&id=' . $idSalle;
                } else {
                    $idSalle = filter_var($_POST['salle'] ?? null, FILTER_VALIDATE_INT);
                    $querySalle = $pdo->prepare('SELECT Numsalle, image_salle FROM salle WHERE Numsalle=?');
                    $querySalle->execute([$idSalle]);
                    $salleAModifier = $querySalle->fetch(PDO::FETCH_ASSOC);
                    if (!$salleAModifier) throw new RuntimeException('Cette salle est introuvable.');
                    $ancienneImageSalle = $salleAModifier['image_salle'] ?? null;
                    $imageSalle = $nouvelleImageSalle ?? ($ancienneImageSalle && isset($_POST['retirer_image']) ? null : $ancienneImageSalle);
                    $pdo->prepare('UPDATE salle SET nomsalle=?,capacitesalle=?,localisationsalle=?,superficiesalle=?,etatsalle=?,description_salle=?,image_salle=? WHERE Numsalle=?')
                        ->execute([$nomSalle, $capaciteSalle, $localisationSalle, $superficieSalle, $etatSalle, $descriptionSalle, $imageSalle, (int) $idSalle]);
                    if ($nouvelleImageSalle || ($ancienneImageSalle && isset($_POST['retirer_image']))) SalleAdmin::supprimerImage($ancienneImageSalle);
                    $nouvelleImageSalle = null;
                    journalise('MODIFICATION_SALLE', 'Numsalle=' . (int) $idSalle);
                    flash('succes', 'Salle mise à jour.');
                    $retourSalles .= '&id=' . (int) $idSalle;
                }
            }
        } catch (PDOException $exception) {
            SalleAdmin::supprimerImage($nouvelleImageSalle);
            flash('erreur', $action === 'supprimer_salle' ? 'Cette salle est utilisée par le planning et ne peut pas être supprimée.' : 'La salle n’a pas pu être enregistrée. Vérifiez ses informations.');
            if ($action === 'modifier_salle') $retourSalles .= '&vue=modifier&id=' . (int) ($_POST['salle'] ?? 0);
            elseif ($action === 'creer_salle') $retourSalles .= '&vue=creer';
        } catch (Throwable $exception) {
            SalleAdmin::supprimerImage($nouvelleImageSalle);
            flash('erreur', $exception->getMessage());
            if ($action === 'modifier_salle') $retourSalles .= '&vue=modifier&id=' . (int) ($_POST['salle'] ?? 0);
            elseif ($action === 'creer_salle') $retourSalles .= '&vue=creer';
        }
        redirige($retourSalles);
    }
