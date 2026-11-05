# Administration

- `espace.php` contrôle le rôle administrateur, choisit l’onglet, traite les actions POST et charge la vue correspondante.
- `view/` contient les écrans spécialisés; garder leurs noms synchronisés avec les onglets autorisés.
- `controller/EntityController.php` fournit les opérations CRUD génériques utilisées par les contrôleurs métier.
- `model/admin.php` centralise les données d’administration; `facture.php` gère l’accès aux factures.

## Comprendre `self::`

Voir le fichier `SELF_EXPLANATION.md` à la racine du projet pour une explication simple.
