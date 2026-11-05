# Messages et contact

- `index.php` prépare le formulaire public et transmet ses actions à `ContactController`.
- `model/ContactMessage.php` persiste les demandes; le contrôleur valide les champs et journalise l’envoi.
- `MessageController` orchestre les conversations d’accueil et `MessageSuppressionController` autorise la suppression.
- Les données de conversation sont dans `model/Message.php` et `MessageSuppression.php`, avec les accès spécialisés répartis dans les traits voisins.
- `view/` contient les écrans de contact et de gestion des messages.

## Comprendre `self::`

Voir le fichier `SELF_EXPLANATION.md` à la racine du projet pour une explication simple.
