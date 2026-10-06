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

Mot de passe commun aux comptes de démonstration : `apex2026`.

| Compte | Rôles | À tester |
|---|---|---|
| admin@apex.fr | Administratrice | comptes, rôles, statistiques, journal |
| camille.roux@apex.fr | Employée **et** adhérente | le cumul autorisé par l'héritage T |
| marc.anselme@apex.fr | Employé, maintenance | déclaration de panne, interventions |
| s.nabil@mail.fr | Adhérent actif | réservation, annulation |
| awa.diallo@mail.fr | Adhérente expirée | réservation refusée |
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
<autres entités>/   un index.php, model/ et view/ pour chaque entité
layout/table.php    gabarit commun utilisé par les vues des entités
config/             connexion PDO, authentification, sessions, CSRF, helpers
config/function.php fonctions partagées (base, session, authentification, affichage)
layout/             en-tête et pied de page partagés
<entite>/controller contrôleurs rangés dans le dossier de leur fonctionnalité
public/index.php    point d’entrée et routes autorisées
public/.htaccess    réécriture des URL vers le point d’entrée
public/assets/      feuilles CSS, scripts JavaScript et images
sql/apex_fitness.sql
```

`config/config.php` contient uniquement la configuration, sans classe ni
fonction. Les fonctions communes sont chargées depuis `config/function.php`.
Les contrôleurs se trouvent dans le sous-dossier `controller/` de leur entité.

## Règles de gestion implémentées

Les contrôles du cahier des charges sont faits **côté serveur** ; le JS ne sert
qu'au confort (menu, filtres, confirmations).

- Deux activités ne peuvent pas occuper la même salle au même moment :
  contrainte `UNIQUE (Numsalle, date_heure_debut)` plus test de chevauchement
  dans `employe/espace.php`.
- Réservation refusée si le créneau est complet : comptage dans une transaction
  avec `SELECT … FOR UPDATE`, ce qui évite la sur-réservation en cas de clics
  simultanés.
- Réservation refusée si l'adhérent n'est pas actif : lecture de son statut.
- Double réservation impossible : contrainte `UNIQUE (NumUsers, Numcreneaux)`
  sur `reservation`, doublée d'un test applicatif.
- Quota mensuel de l'offre Freemium (`nb_reservations_max_mois`).
- Un adhérent ne peut pas devenir employé : trigger
  `trg_employe_avant_adherent`. L'inverse reste permis, c'est le cumul autorisé
  par l'héritage T.
- L'historique des interventions est conservé : chaque changement de statut
  d'une demande écrit une ligne dans `intervention`.
- Un adhérent peut payer depuis `paiement/payer.php`, même si son statut est expiré :
  la souscription précédente est terminée, le paiement est enregistré, une
  nouvelle souscription est créée et le statut repasse à Actif dans une seule
  transaction.
- Chaque table SQL utilise la classe métier du dossier de l’entité pour ses
  opérations CRUD, sans trait de persistance partagé ni classe adaptatrice par
  table. Toutes les entités exposent les mêmes opérations `create`, `update`,
  `findById`, `findAll` et `delete`. Les opérations métier propres aux quatre
  familles d’héritage portent des noms spécifiques (par exemple
  `createAccount` ou `createOffer`) pour ne pas détourner cette interface. Le
  contrôleur commun traite les actions HTTP et prépare les métadonnées ; les
  lectures et écritures SQL sont dans la classe de l’entité. Chaque dossier
  conserve sa vue, qui réutilise `layout/table.php`.
  Les héritages du MCD sont représentés par les classes `Utilisateur` →
  `Adherent`, `Employe`, `Admin`, `Fournisseur` ; `Offre` → `Freemium`, `Basic`,
  `AbonnementPlus` ; `Statut` → `EtatEquipement`, `StatutMaintenance` ;
  `InteractionAdherent` → `Avis`, `Faq`. Chaque classe fille écrit son
  enregistrement parent et son enregistrement spécialisé dans une transaction.
  La déconnexion détruit toute la session et tous les rôles
  portés par le compte connecté.

## CRUD par table

Un administrateur peut consulter, créer, modifier ou supprimer les
enregistrements des tables depuis les pages de chaque entité. Chaque table
possède son propre dossier avec `model/`, `view/` et `index.php`. Les champs,
clés primaires et clés étrangères sont lus depuis
les métadonnées MySQL ; les opérations de chaque table sont exposées par les
méthodes de sa classe métier. Les identifiants restent configurés dans
`config/config.php`.

## Sécurité

- Mots de passe hachés avec `password_hash()` / `password_verify()`.
- Toutes les requêtes sont préparées (PDO, `ATTR_EMULATE_PREPARES` à `false`).
- Toute sortie passe par `e()` (`htmlspecialchars`).
- Jeton CSRF sur chaque formulaire en POST.
- `session_regenerate_id()` à la connexion.
- Contrôle de rôle en tête de chaque page protégée (`exige_role`).
- Message de connexion identique pour un e-mail inconnu et un mot de passe faux.

À faire avant une mise en ligne réelle : passer `display_errors` à `0`,
utiliser un compte MySQL dédié sans privilège `DROP`, servir le site en HTTPS
et activer l'option `secure` du cookie de session.

## Écarts assumés avec le MCD v4

Le schéma ajoute ce que le MCD ne portait pas et que le cahier des charges
exige. Chaque ajout est commenté `[COMPLEMENT]` dans le fichier SQL :

| Ajout | Raison |
|---|---|
| `activite` | section 4 du cahier des charges, absente du MCD |
| `creneaux.NumUsers_coach` | « éventuellement à un coach » |
| `statut_adherent` | statuts actif / suspendu / expiré |
| `equipement.Numsalle`, `equipement.num_inventaire` | localisation et numéro d'inventaire |
| `intervention` | historique des interventions |
| `journal` | journaux d'activité de l'administrateur |
| `message_contact` | page contact |
| `souscrire.date_debut` / `date_fin` | savoir quel abonnement est en cours |

Si le MCD doit rester strictement identique à la v4 pour la soutenance,
ces tables sont isolées et repérables : il suffit de les retirer, en sachant
que les fonctionnalités correspondantes tomberont avec elles.
