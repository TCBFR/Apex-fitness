# Planning des activités

- `controller/planning.php` rassemble les règles métier et expose les actions utilisées par les espaces adhérent, employé et administration.
- Les fichiers `controller/Planning*Controller.php` regroupent ces actions par flux; `planning.php` compose leurs traits et conserve les constantes partagées.
- `model/planning.php` compose les accès SQL décrits dans `model/Planning*Queries.php`.
- `view/` contient les écrans de planning et le point d’entrée des événements FullCalendar.

La création récurrente valide d’abord les occurrences et leurs dates, puis vérifie les conflits et enregistre la série dans une transaction. Les contrôleurs existants restent les points d’entrée pour ne pas modifier les routes.
