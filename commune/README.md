# Communes

- `controller/recherche.php` sert les recherches de communes utilisées par les formulaires.
- `model/commune.php` regroupe les requêtes de recherche.
- `import.php` importe communes et codes postaux dans une transaction; il se lance en CLI et dépend du service geo.api.gouv.fr.
- `view/champ.php` rend le champ de sélection.
