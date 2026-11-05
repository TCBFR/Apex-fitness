# Espace employé

- `espace.php` gère les onglets et actions selon le rôle employé; les vues spécialisées sont dans `view/`.
- `EmployeController` et `model/employe.php` traitent les données du personnel.
- `profil.php` et `profil_api.php` gèrent le profil; les services sous `service/` portent le stockage d’images et la préparation du schéma d’inventaire.
- Les modifications d’onglets ou de rôles doivent préserver les contrôles d’accès de `espace.php`.
