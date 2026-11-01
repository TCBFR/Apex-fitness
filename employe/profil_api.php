<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/function.php';

header('Content-Type: application/json; charset=utf-8');

function employeProfilReply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!a_role('EMPLOYE') || !est_technicien_maintenance()) {
    employeProfilReply(['error' => 'Accès interdit.'], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    employeProfilReply(['error' => 'Méthode non autorisée.'], 405);
}

$action = $_GET['action'] ?? null;
if (!is_string($action)) {
    employeProfilReply(['error' => 'Action inconnue.'], 404);
}

$id = (int) utilisateur()['NumUsers'];

if ($action === 'profile_photo') {
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && $_POST === [] && $_FILES === []) {
        employeProfilReply(
            ['error' => 'Le fichier dépasse la limite de réception du serveur. Vérifiez la configuration post_max_size.'],
            413
        );
    }

    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) {
        employeProfilReply(['error' => 'Session expirée. Rechargez la page.'], 419);
    }

    require_once dirname(__DIR__) . '/adherent/service/ProfilePhotoStorage.php';
    $storage = new ProfilePhotoStorage();
    $newPath = null;
    $photoUpdated = false;
    try {
        $photoQuery = db()->prepare('SELECT photo_profil FROM employe WHERE NumUsers = ?');
        $photoQuery->execute([$id]);
        $oldPath = $photoQuery->fetchColumn();
        if ($oldPath === false) {
            employeProfilReply(['error' => 'Profil employé introuvable.'], 404);
        }

        $newPath = $storage->store($_FILES['photo'] ?? []);
        db()->prepare('UPDATE employe SET photo_profil = ? WHERE NumUsers = ?')->execute([$newPath, $id]);
        $photoUpdated = true;
        $storage->remove(is_string($oldPath) ? $oldPath : null);
        journalise('MODIFICATION_PHOTO_PROFIL_EMPLOYE');
        employeProfilReply(['photo_url' => url($newPath)]);
    } catch (InvalidArgumentException $exception) {
        employeProfilReply(['error' => $exception->getMessage()], 422);
    } catch (Throwable $exception) {
        if ($newPath !== null && !$photoUpdated) {
            $storage->remove($newPath);
        }
        error_log('[Photo profil employé] ' . $exception->getMessage());
        employeProfilReply(['error' => 'La photo n’a pas pu être enregistrée. Réessayez.'], 500);
    }
}

if ($action !== 'profile') {
    employeProfilReply(['error' => 'Action inconnue.'], 404);
}

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
