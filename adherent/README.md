# Espace adhérent

## Parcours du tableau de bord

- `espace.php` vérifie le rôle, charge les données préparées et affiche la page.
- `controller/AdherentDashboardController.php` assemble les données du tableau de bord et ses liens de navigation.
- `model/AdherentEspace.php` compose les traitements de données regroupés par responsabilité dans les fichiers `AdherentEspace*.php`.
- `includes/dashboard/` contient les fragments de vue et les données destinés au tableau de bord.
- `public/assets/css/adherent/dashboard.css` contient sa présentation.

Les autres pages adhérent passent progressivement au même découpage : les contrôleurs préparent les données et les modèles portent les accès à la base. Les routes publiques restent déclarées dans `public/index.php`.
