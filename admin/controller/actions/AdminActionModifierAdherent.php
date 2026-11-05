<?php

if ($action === 'modifier_adherent') {
        $id = (int) ($_POST['utilisateur'] ?? 0);
        try {
            $adherentExiste = $pdo->prepare('SELECT 1 FROM adherent WHERE NumUsers = ?');
            $adherentExiste->execute([$id]);
            if (!$adherentExiste->fetchColumn()) {
                throw new RuntimeException('Cet adhérent est introuvable.');
            }

            $nom = trim((string) ($_POST['nom'] ?? ''));
            $prenom = trim((string) ($_POST['prenom'] ?? ''));
            $mail = mb_strtolower(trim((string) ($_POST['mail'] ?? '')));
            $tel = trim((string) ($_POST['tel'] ?? ''));
            $adresse = trim((string) ($_POST['adresse_postale'] ?? ''));
            $communeId = filter_var($_POST['id_commune'] ?? null, FILTER_VALIDATE_INT);
            $naissance = trim((string) ($_POST['date_naissance'] ?? ''));
            $sexe = trim((string) ($_POST['sexe'] ?? ''));
            $dateInscription = trim((string) ($_POST['Date_inscriptionadh'] ?? ''));
            $dateFin = trim((string) ($_POST['Date_finadh'] ?? ''));

            if ($nom === '' || mb_strlen($nom) > 60 || $prenom === '' || mb_strlen($prenom) > 60) {
                throw new InvalidArgumentException('Le nom et le prénom sont obligatoires (60 caractères maximum).');
            }
            if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Adresse e-mail invalide.');
            }
            if (mb_strlen($tel) > 20 || mb_strlen($adresse) > 255) {
                throw new InvalidArgumentException('Le téléphone ou l’adresse dépasse la longueur autorisée.');
            }
            $dateValide = static function (string $date, bool $optionnelle = false): bool {
                if ($optionnelle && $date === '') return true;
                $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                $errors = DateTimeImmutable::getLastErrors();
                return $value !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                    && $value->format('Y-m-d') === $date;
            };
            if (!$dateValide($naissance, true) || !$dateValide($dateInscription) || !$dateValide($dateFin, true)) {
                throw new InvalidArgumentException('Une des dates saisies est invalide.');
            }
            if (!in_array($sexe, ['Femme', 'Homme', 'Autre', 'Préfère ne pas répondre'], true)) {
                throw new InvalidArgumentException('Sélectionnez une option pour le sexe.');
            }
            $communeQuery = $pdo->prepare('SELECT nom_commune, code_postal FROM commune WHERE id_commune = ?');
            $communeQuery->execute([$communeId]);
            $commune = $communeQuery->fetch(PDO::FETCH_ASSOC);
            if (!$commune) throw new InvalidArgumentException('Sélectionnez une commune valide.');

            $pdo->beginTransaction();
            $pdo->prepare('UPDATE utilisateur SET nomUsers = ?, prenomUsers = ?, mailUsers = ?, telUsers = ? WHERE NumUsers = ?')
                ->execute([$nom, $prenom, $mail, $tel !== '' ? $tel : null, $id]);
            $pdo->prepare(
                'UPDATE adherent SET date_naissance = ?, sexe = ?, adresse_postale = ?, id_commune = ?, ville = ?, code_postal = ?, Date_inscriptionadh = ?, Date_finadh = ? WHERE NumUsers = ?'
            )->execute([
                $naissance !== '' ? $naissance : null,
                $sexe,
                $adresse !== '' ? $adresse : null,
                $communeId,
                $commune['nom_commune'],
                $commune['code_postal'],
                $dateInscription,
                $dateFin !== '' ? $dateFin : null,
                $id,
            ]);
            $pdo->commit();
            journalise('MODIFICATION_ADHERENT', 'NumUsers=' . $id);
            flash('succes', 'Fiche adhérent mise à jour.');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('erreur', $exception->getMessage());
        }
        redirige('admin/espace.php?onglet=adherents&id=' . $id . '&vue=voir');
    }
