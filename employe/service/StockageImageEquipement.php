<?php

declare(strict_types=1);

final class StockageImageEquipement
{
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const IMAGE_TYPES = [
        'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
        'image/png' => ['png', IMAGETYPE_PNG],
        'image/webp' => ['webp', IMAGETYPE_WEBP],
    ];

    public function store(array $upload): ?string
    {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) return null;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('L’image dépasse la limite de transfert du serveur.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Le transfert de l’image a échoué. Vérifiez le fichier puis réessayez.');
        }
        if ((int) ($upload['size'] ?? 0) < 1 || (int) $upload['size'] > self::MAX_SIZE) {
            throw new RuntimeException('L’image ne doit pas dépasser 5 Mo.');
        }

        $temporaryPath = (string) ($upload['tmp_name'] ?? '');
        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException('Le fichier reçu n’est pas une image valide.');
        }
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $imageInfo = @getimagesize($temporaryPath);
        if (!isset(self::IMAGE_TYPES[$mimeType]) || !$imageInfo || $imageInfo[2] !== self::IMAGE_TYPES[$mimeType][1]) {
            throw new RuntimeException('Format non accepté. Utilisez une image JPEG, PNG ou WebP.');
        }
        if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000 || $imageInfo[0] * $imageInfo[1] > 20_000_000) {
            throw new RuntimeException('La résolution de l’image est trop élevée.');
        }

        $relativeDirectory = 'assets/img/equipements';
        $targetDirectory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Le dossier de stockage des images ne peut pas être créé.');
        }
        $fileName = bin2hex(random_bytes(16)) . '.' . self::IMAGE_TYPES[$mimeType][0];
        if (!move_uploaded_file($temporaryPath, $targetDirectory . '/' . $fileName)) {
            throw new RuntimeException('Impossible d’enregistrer cette image.');
        }

        return $relativeDirectory . '/' . $fileName;
    }

    public function remove(?string $relativePath): void
    {
        if (!$relativePath || !preg_match('~\Aassets/img/equipements/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $relativePath)) {
            return;
        }
        $filePath = dirname(__DIR__, 2) . '/public/' . $relativePath;
        if (is_file($filePath) && !unlink($filePath)) {
            error_log('Impossible de supprimer l’image du matériel : ' . $relativePath);
        }
    }
}
