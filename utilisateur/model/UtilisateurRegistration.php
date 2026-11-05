<?php

declare(strict_types=1);

trait UtilisateurRegistration
{
    public static function inscrireAdherent(array $data): int
    {
        self::assurerColonneSexe(db());
        $nom = trim((string) ($data['nom'] ?? ''));
        $prenom = trim((string) ($data['prenom'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['mail'] ?? '')));
        $dateNaissance = trim((string) ($data['date_naissance'] ?? ''));
        $sexe = trim((string) ($data['sexe'] ?? ''));
        $ville = trim((string) ($data['ville'] ?? ''));
        $codePostal = trim((string) ($data['code_postal'] ?? ''));
        $idCommune = (int) ($data['id_commune'] ?? 0);
        if ($idCommune > 0) {
            require_once dirname(__DIR__, 2) . '/commune/model/commune.php';
            $commune = ModeleCommune::trouver($idCommune);
        if ($commune === null) {
        throw new InvalidArgumentException('Sélectionnez une commune dans la liste.');
    }
        $ville = (string) $commune['nom_commune'];
        $codePostal = (string) $commune['code_postal'];
}
        $adresse = trim((string) ($data['adresse_postale'] ?? ''));
        $password = (string) ($data['motdepasse'] ?? '');

        if ($nom === '' || $prenom === '') {
            throw new InvalidArgumentException('Le nom et le prénom sont obligatoires.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Saisissez une adresse e-mail valide.');
        }
        $dateSaisie = DateTimeImmutable::createFromFormat('!d-m-Y', $dateNaissance);
        $erreursDate = DateTimeImmutable::getLastErrors();
        if ($dateSaisie === false
            || ($erreursDate !== false && ($erreursDate['warning_count'] > 0 || $erreursDate['error_count'] > 0))
            || $dateSaisie->format('d-m-Y') !== $dateNaissance) {
            throw new InvalidArgumentException('Saisissez une date de naissance valide.');
        }
        $dateNaissance = $dateSaisie->format('Y-m-d');
        if (!in_array($sexe, ['Femme', 'Homme', 'Autre', 'Préfère ne pas répondre'], true)) {
            throw new InvalidArgumentException('Sélectionnez une option pour le sexe.');
        }
        if ($ville === '' || strlen($ville) > 100) {
            throw new InvalidArgumentException('Saisissez une ville valide (100 caractères maximum).');
        }
        if (!preg_match('/^\d{5}$/', $codePostal)) {
            throw new InvalidArgumentException('Le code postal doit contenir 5 chiffres.');
        }
        if ($adresse === '' || strlen($adresse) > 255) {
            throw new InvalidArgumentException('Saisissez une adresse postale (255 caractères maximum).');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $compte = new self($nom, $prenom, $email, $password);
            $id = $compte->createAccount($pdo);
            $pdo->prepare(
                'INSERT INTO adherent (
                    NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh,
                    date_naissance, sexe, ville, code_postal, adresse_postale
                 ) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), ?, ?, ?, ?, ?)'
            )->execute([
                $id,
                'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT),
                $dateNaissance,
                $sexe,
                $ville,
                $codePostal,
                $adresse,
            ]);
            self::creerQrAdherent(
                $pdo,
                $id,
                'ADH-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
            );
            $pdo->commit();

            return $id;
        } catch (PDOException $exception) {
            $pdo->rollBack();
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw new InvalidArgumentException('Cette adresse e-mail est déjà utilisée.');
            }
            throw $exception;
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
