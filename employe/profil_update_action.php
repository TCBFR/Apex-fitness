<?php

function employeProfilUpdate(int $id): never
{
$input = json_decode(file_get_contents('php://input') ?: '[]', true);
if (!is_array($input)) {
    employeProfilReply(['error' => 'Données invalides.'], 400);
}
$token = $input['csrf'] ?? '';
if (!is_string($token) || $token === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) {
    employeProfilReply(['error' => 'Session expirée. Rechargez la page.'], 419);
}

$nom = $input['nom'] ?? null;
$prenom = $input['prenom'] ?? null;
$email = $input['email'] ?? null;
$telephone = $input['telephone'] ?? '';

try {
    $communeId = filter_var($input['id_commune'] ?? null, FILTER_VALIDATE_INT);
    if (!is_string($nom) || !is_string($prenom) || !is_string($email) || !is_string($telephone)) {
        throw new InvalidArgumentException('Données de profil invalides.');
    }
    $nom = trim($nom);
    $prenom = trim($prenom);
    $email = trim($email);
    $telephone = trim($telephone);
    if ($nom === '' || mb_strlen($nom) > 60 || $prenom === '' || mb_strlen($prenom) > 60) {
        throw new InvalidArgumentException('Le nom et le prénom sont obligatoires (60 caractères maximum).');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        throw new InvalidArgumentException('Adresse email invalide.');
    }
    if ($telephone !== '') {
        if (!preg_match('/^[0-9 ]+$/', $telephone)) {
            throw new InvalidArgumentException('Saisissez uniquement les chiffres du numéro de téléphone.');
        }
        $digits = str_replace(' ', '', $telephone);
        if (strlen($digits) < 6 || strlen($digits) > 10) {
            throw new InvalidArgumentException('Le numéro doit contenir entre 6 et 10 chiffres.');
        }
        $telephone = trim(chunk_split($digits, 2, ' '));
    }
    if (!$communeId || $communeId < 1) {
        throw new InvalidArgumentException('Sélectionnez une commune dans la liste proposée.');
    }

    $pdo = db();
    $communeQuery = $pdo->prepare('SELECT nom_commune, code_postal FROM commune WHERE id_commune = ?');
    $communeQuery->execute([$communeId]);
    $commune = $communeQuery->fetch(PDO::FETCH_ASSOC);
    if (!$commune) {
        throw new InvalidArgumentException('La commune sélectionnée est introuvable.');
    }
    $emailQuery = $pdo->prepare('SELECT 1 FROM utilisateur WHERE mailUsers = ? AND NumUsers <> ?');
    $emailQuery->execute([$email, $id]);
    if ($emailQuery->fetchColumn()) {
        throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'UPDATE utilisateur
                SET nomUsers = ?, prenomUsers = ?, mailUsers = ?, telUsers = ?
              WHERE NumUsers = ?'
        )->execute([$nom, $prenom, $email, $telephone !== '' ? $telephone : null, $id]);
        $pdo->prepare('UPDATE employe SET id_commune = ? WHERE NumUsers = ?')->execute([$communeId, $id]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    journalise('MODIFICATION_PROFIL_EMPLOYE');
    employeProfilReply(['profile' => [
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'telephone' => $telephone,
        'id_commune' => $communeId,
        'nom_commune' => $commune['nom_commune'],
        'code_postal' => $commune['code_postal'],
    ]]);
} catch (InvalidArgumentException $exception) {
    employeProfilReply(['error' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    error_log('[Profil employé] ' . $exception->getMessage());
    employeProfilReply(['error' => 'Une erreur est survenue. Réessayez.'], 500);
}
}
