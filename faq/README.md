# Foire aux questions

- `index.php` traite les demandes de gestion et délègue au contrôleur FAQ.
- `controller/FaqController.php` orchestre les actions d’administration et d’adhérent.
- `model/FaqDomain.php` assemble les traitements répartis entre persistance, contenu et modération.
- `public.php` et `view/` affichent les questions publiées et les formulaires.

## Comprendre `self::`

Voir le fichier `SELF_EXPLANATION.md` à la racine du projet pour une explication simple.

`assurerQuestionsDemo()` ajoute uniquement les questions manquantes et requiert au moins un adhérent comme auteur.
