<?php

declare(strict_types=1);

trait AdherentEspaceProfilePhoto
{
    public function updateProfilePhoto(int $id, string $photoPath): ?string
    {
        $columns = $this->pdo->query('SHOW COLUMNS FROM adherent')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('photo_profil', $columns, true)) {
            throw new RuntimeException('La base de données ne permet pas encore d’enregistrer la photo de profil.');
        }

        $this->pdo->beginTransaction();
        try {
            $query = $this->pdo->prepare('SELECT photo_profil FROM adherent WHERE NumUsers = ? FOR UPDATE');
            $query->execute([$id]);
            $currentPhoto = $query->fetchColumn();
            if ($currentPhoto === false) throw new RuntimeException('Profil adhérent introuvable.');

            $this->pdo->prepare('UPDATE adherent SET photo_profil = ? WHERE NumUsers = ?')->execute([$photoPath, $id]);
            $this->pdo->commit();
            return $currentPhoto !== '' && $currentPhoto !== null ? (string) $currentPhoto : null;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
