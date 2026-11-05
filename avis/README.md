# Avis adhérents

- `controller/AvisController.php` gère les actions de publication et de réponse.
- `model/AvisDomain.php` compose les traitements de création, de persistance et d’affichage.
- `index.php` et `public.php` routent les vues d’administration et la page publique.

La création vérifie la note, la longueur du commentaire, l’adhésion et l’absence d’un avis antérieur. Les évolutions de colonnes restent encapsulées dans le modèle.
