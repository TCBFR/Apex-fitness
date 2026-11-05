# Point d’entrée HTTP

- `index.php` valide et normalise le chemin demandé, puis le mappe explicitement vers une page interne.
- `includes/public_icons.php` fournit les icônes réutilisables.
- `.htaccess` configure la réécriture; `.user.ini` contient une configuration locale à ne pas modifier lors du refactoring.
