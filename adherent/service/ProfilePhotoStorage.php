<?php

declare(strict_types=1);

final class ProfilePhotoStorage
{
    private const MAX_SIZE = 5_242_880;
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function store(array $upload): string
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException($this->uploadError((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        $temporaryPath = (string) ($upload['tmp_name'] ?? '');
        $size = (int) ($upload['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_SIZE) {
            throw new InvalidArgumentException('La photo doit peser 5 Mo maximum.');
        }
        if (!is_uploaded_file($temporaryPath)) {
            throw new InvalidArgumentException('Le fichier transmis n’est pas une photo valide.');
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file($temporaryPath);
        $imageInfo = @getimagesize($temporaryPath);
        if (!isset(self::MIME_EXTENSIONS[$mimeType]) || !$imageInfo || $imageInfo['mime'] !== $mimeType) {
            throw new InvalidArgumentException('Format accepté : JPG, PNG ou WEBP.');
        }
        if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000 || $imageInfo[0] * $imageInfo[1] > 20_000_000) {
            throw new InvalidArgumentException('La résolution de la photo est trop élevée.');
        }

        $relativeDirectory = 'assets/uploads/profiles';
        $targetDirectory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Le dossier des photos de profil ne peut pas être créé.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mimeType];
        if (!move_uploaded_file($temporaryPath, $targetDirectory . '/' . $fileName)) {
            throw new RuntimeException('La photo n’a pas pu être enregistrée.');
        }

        return $relativeDirectory . '/' . $fileName;
    }

    public function remove(?string $relativePath): void
    {
        if (!$relativePath || !preg_match('~^assets/uploads/profiles/[a-f0-9]{32}\.(?:jpg|png|webp)$~', $relativePath)) {
            return;
        }
        $filePath = dirname(__DIR__, 2) . '/public/' . $relativePath;
        if (is_file($filePath)) unlink($filePath);
    }

    private function uploadError(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La photo doit peser 5 Mo maximum.',
            UPLOAD_ERR_PARTIAL => 'Le téléversement de la photo est incomplet.',
            UPLOAD_ERR_NO_FILE => 'Choisissez une photo avant de continuer.',
            default => 'Le téléversement de la photo a échoué.',
        };
    }
}
