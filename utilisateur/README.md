# Comptes utilisateur

- `connexion.php` et `inscription.php` gèrent les requêtes des formulaires et délèguent le traitement aux contrôleurs.
- `controller/UtilisateurController.php` prend en charge la connexion, les comptes de démonstration et le CRUD utilisateur.
- `controller/InscriptionController.php` orchestre la validation et la création du compte adhérent.
- `model/utilisateur.php` assemble les traitements de compte; le CRUD partagé est dans `config/model/ModelCrud*.php`.
- `view/` contient les formulaires de connexion et d’inscription.

Les contrôleurs conservent les méthodes utilisées par les pages existantes; les modèles restent responsables de la persistance et des règles de compte.
