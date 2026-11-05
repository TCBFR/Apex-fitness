# Configuration et services partagés

- `config.php` centralise les paramètres de connexion à la base.
- `function.php` fournit les fonctions partagées de session, d’accès aux rôles, de sécurité des formulaires et de connexion.
- `DemoInstaller.php` et `demo_inventory.php` préparent les données et le matériel de démonstration.
- `model/ModelCrud*.php` factorise le CRUD générique réutilisé par les modèles métier.

Les scripts d’installation sont distincts des requêtes courantes; les pages délèguent leurs opérations métier aux contrôleurs de chaque module.
