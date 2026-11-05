# Fournisseurs et personnel

- `gestion.php` vérifie les droits, orchestre les actions POST et affiche l’onglet demandé.
- `controller/FournisseurController.php` transmet les opérations fournisseur au modèle; `GestionController.php` gère les employés et leurs fonctions.
- `model/FournisseurDomain.php` compose les traitements d’accès et de gestion répartis dans les traits voisins.
- `view/crud.php` et `view/personnel.php` présentent les formulaires et listes correspondants.
