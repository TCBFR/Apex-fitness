# Interactions adhérent

`model/interaction_adherent.php` fournit la persistance de base héritée par les modèles FAQ et Avis. Le CRUD générique est partagé via `config/model/ModelCrud*.php`; la classe conserve l’identité de l’auteur et le type d’interaction.

## Comprendre `self::`

Voir le fichier `SELF_EXPLANATION.md` à la racine du projet pour une explication simple.

`controller/InteractionAdherentController.php` relie le modèle aux opérations CRUD du module.
