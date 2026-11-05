# QR codes et vérification

- `index.php` et `image.php` servent l’interface et l’image du QR code.
- `QrCodeController.php` orchestre la vérification; `verifier.php` et `verifier-api.php` protègent l’accès employé.
- `includes/qr_bootstrap.php` prépare le contexte commun; `model/qr_code.php` porte les accès métier.
