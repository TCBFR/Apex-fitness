<?php

function employeProfilPhoto(int $id): never
{

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

    require_once dirname(__DIR__) . '/adherent/service/StockagePhotoProfil.php';
    $storage = new StockagePhotoProfil();
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
