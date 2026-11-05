# Apex Fitness – Résumé simplifié pour soutenance orale

## Projet
Application de gestion de salle de sport en PHP/MySQL permettant de gérer les adhérents, les réservations de cours, les abonnements, les paiements, les employés et les équipements.

## Architecture générale
- **MVC (Modèle-Vue-Controleur)** : chaque module possède ses propres fichiers de modèle (données), contrôleur (logique) et vue (affichage).
- **PHP 8.1+** avec connexion PDO à MySQL/MariaDB.
- Les fonctions communes (connexion, authentification, affichage) sont dans `config/function.php`.
- La configuration (BDD, secrets) se trouve dans `config/config.php`.
- Sécurité : mots de passe hachés (argon2id), requêtes préparées, `htmlspecialchars` pour la sortie, CSRF, rôles.

## Modules principaux (12 entités)

| Module | Fonctionnalités clés |
|--------|----------------------|
| **accueil** | Planning public, activités, recherche de cours |
| **adherent** | Espace membre : réserver, annuler, voir ses réservations, profil, statistiques |
| **admin** | Espace administrateur : gestion des utilisateurs, rôles, statistiques, journal, configuration |
| **abonnement** | Catalogues d'offres (Free/Premium), gestion des souscriptions |
| **activité** | Liste des cours/activités disponibles |
| **equipement** | Gestion du matériel et des salles |
| **paiement** | Enregistrement des paiements, relance des abonnements |
| **employé** | Espace employé : déclarations de panne, interventions |
| **salle** | Gestion des salles et capacité |
| **avis** | Avis publics sur les cours/activités |
| **faq** | Questions fréquentes publiées |
| **utilisateur** | Connexion, déconnexion, création de compte |

## Fonctionnalités clés (à montrer en oral)

1. **Réservation de cours** : vérification disponibilité salle, quota mensuel (Freemium), liste d'attente, annulation jusqu'à 2h avant.
2. **Gestion des statuts** : actif / suspendu / expiré ; un adhérent ne peut pas devenir employé (trigger SQL), mais l'inverse est permis.
3. **Paiement** : un adhérent peut payer même avec statut expiré ; le paiement crée une nouvelle souscription et repasse le statut à Actif.
4. **Héritage MCD** : la classe `Utilisateur` est la base ; `Employe`, `Adherent`, `Statut` en dérive.
5. **Rapels automatiques** : tâche cron toutes les 15 min pour envoyer les rappels de séances (24h avant).

## Comptes de démonstration (login/mdp = `apex2026`)

| Compte | Rôle | À tester |
|--------|------|----------|
| admin@apex.fr | Administratrice | comptes, rôles, statistiques, journal |
| camille.roux@apex.fr | Employée + adhérente | cumul héritage T |
| marc.anselme@apex.fr | Employé, maintenance | déclaration panne, interventions |
| s.nabil@mail.fr | Adhérent actif | réservation, annulation |
| awa.diallo@mail.fr | Adhérente active (Premium) | réservation de cours |
| h.lemoine@mail.fr | Adhérent suspendu | réservation refusée |

## Installation (rapide)

1. Mettre le dossier `public/` en DocumentRoot du serveur (Apache, avec `AllowOverride All`).
2. Importer `sql/apex_fitness.sql` dans MySQL.
3. Adapter les identifiants dans `config/config.php` (DB_USER, DB_PASS).
4. Ouvrir `/config/install.php` dans le navigateur, puis supprimer ce fichier.
5. Se connecter via la page d'accueil.

## Point d'entrée unique
Toutes les pages sont chargées depuis `public/index.php` qui applique une liste de routes autorisées. Le fichier `.htaccess` réécrit les URLs vers ce point d'entrée.

## À retenir pour l'oral
- C'est un système complet de gestion de salle de sport.
- Architecture MVC propre avec séparation modèle/contrôleur/vue.
- Toutes les règles métiers sont contrôlées côté serveur (sécurité, quota, statut).
- Plusieurs rôles (admin, employé, adhérent) avec des permissions précises.
- Peuvent être démontrés : réservation, paiement, gestion des statuts, espace admin.