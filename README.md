# Apex Fitness — application de gestion de salle de sport

Projet PHP / MySQL construit à partir du MCD v4 (héritage T sur UTILISATEUR,
XT sur STATUT, INTERACTION_ADHERENT et ABONNEMENT).

## Installation

1. Copier le projet sur le serveur puis configurer le **DocumentRoot** (racine
   web) Apache sur le dossier `public/`, pas sur la racine du projet. Autoriser
   les règles `.htaccess` avec `AllowOverride All`.
2. Importer `sql/apex_fitness.sql` dans phpMyAdmin ou en ligne de commande :
   ```
   mysql -u root -p < sql/apex_fitness.sql
   ```
3. Adapter les identifiants dans `config/config.php` (`DB_USER`, `DB_PASS`).
   `APP_BASE` est détecté automatiquement ; le renseigner uniquement si
   l’application est publiée sous un chemin personnalisé.
4. Ouvrir `/config/install.php` dans le navigateur, puis **supprimer ce fichier**
   du projet après l’installation.
5. Ouvrir `/` puis se connecter depuis le lien « Connexion ».

Le dossier `public/` est le seul répertoire à exposer par le serveur web. Il
contient les ressources CSS, JavaScript et images, le point d’entrée `index.php`
et les règles de réécriture Apache. Les autres pages PHP sont chargées par ce
point d’entrée à partir d’une liste de routes autorisées.

Prérequis : PHP 8.1 ou supérieur (le code utilise `never`, `str_contains`,
les fonctions fléchées), MySQL 5.7 / MariaDB 10.4 ou supérieur.

### Liste d’attente et rappels adhérent

Pour une base déjà installée, appliquer une fois la migration
`sql/migrations/20261014_adherent_waitlist_reminders.sql` ainsi que
`sql/migrations/20261020_adherent_daily_objectives.sql`. Les nouvelles
installations récupèrent ces tables depuis `sql/apex_fitness.sql`.

Les rappels sont affichés dans « Mes réservations » 24 heures avant le cours.
Pour les générer régulièrement, ajouter une tâche cron exécutée toutes les
15 minutes (remplacer `/chemin/vers/apex-fitness` par le chemin du projet) :

```
*/15 * * * * /usr/bin/php /chemin/vers/apex-fitness/scripts/rappels_seances.php
```

Une annulation est possible jusqu’à deux heures avant le cours. La place
libérée est attribuée au premier adhérent éligible de la liste d’attente, qui
reçoit une notification dans son compte.

Mot de passe commun aux comptes de démonstration : `apex2026`.

| Compte | Rôles | À tester |
|---|---|---|
| admin@apex.fr | Administratrice | comptes, rôles, statistiques, journal |
| camille.roux@apex.fr | Employée **et** adhérente | le cumul autorisé par l'héritage T |
| marc.anselme@apex.fr | Employé, maintenance | déclaration de panne, interventions |
| s.nabil@mail.fr | Adhérent actif | réservation, annulation |
| awa.diallo@mail.fr | Adhérente active (Premium) | réservation de cours au planning |
| h.lemoine@mail.fr | Adhérent suspendu | réservation refusée |

## Arborescence

```
accueil/            accueil public, planning et activités
abonnement/         catalogue, offres et CRUD de la table abonnement
adherent/           espace adhérent et CRUD
admin/              espace administrateur, tables SQL et CRUD
avis/               avis publics et CRUD
employe/            espace employé et CRUD
faq/                questions publiées et CRUD
fournisseur/        gestion fournisseurs et CRUD
message_contact/    contact et vues de messagerie
paiement/           paiement et CRUD
utilisateur/        connexion, déconnexion et CRUD
<autres entités>/   un index.php, controller/, model/ et view/ par entité
config/             connexion PDO, authentification, sessions, CSRF et helpers
config/function.php fonctions partagées (base, session, authentification, affichage)
layout/             gabarits de navigation et de page partagés
<entite>/controller un contrôleur local pour router chaque fonctionnalité
public/index.php    point d’entrée et routes autorisées
public/.htaccess    réécriture des URL vers le point d’entrée
public/assets/      feuilles CSS, scripts JavaScript et images
sql/apex_fitness.sql
```

`config/config.php` contient uniquement la configuration, sans classe ni
fonction. Les fonctions communes sont chargées depuis `config/function.php`.
L’inventaire de démonstration est initialisé par la classe
`InventaireDemo` définie dans `config/demo_inventory.php`. La classe
`DemoInstaller`, dans `config/DemoInstaller.php`, prépare les comptes et les
données de démonstration depuis `config/install.php`. Cette installation ne
s’exécute plus lorsqu’un employé consulte son espace.
Les fonctionnalités suivent une architecture MVC avec un ou plusieurs contrôleurs,
modèles et vues selon leur périmètre. Les opérations CRUD génériques sont
partagées dans `config/model/`; les règles et requêtes métier restent dans les
modèles locaux. Les détails et règles fonctionnelles sont dans [BUSINESS_RULES.md](./BUSINESS_RULES.md).
