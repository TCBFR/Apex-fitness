# Apex Fitness — application de gestion de salle de sport

Projet PHP / MySQL construit à partir du MCD v4 (héritage T sur UTILISATEUR,
XT sur STATUT, INTERACTION_ADHERENT et ABONNEMENT).

## Installation

1. Copier le dossier dans la racine web (`htdocs/`, `www/`, `/var/www/html`).
2. Importer `sql/apex_fitness.sql` dans phpMyAdmin ou en ligne de commande :
   ```
   mysql -u root -p < sql/apex_fitness.sql
   ```
3. Adapter les identifiants dans `config/config.php` (`DB_USER`, `DB_PASS`,
   et `APP_BASE` si le site est dans un sous-dossier).
4. Ouvrir `install.php` dans le navigateur, puis **supprimer ce fichier**.
5. Se connecter depuis `login.php`.

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
index.php          accueil public, planning et activités
login.php          connexion commune aux trois profils
logout.php
abonnements.php    les trois offres, lues dans les entités filles
faq.php            questions publiées, dépôt de question par un adhérent
avis.php           avis publics, dépôt réservé aux adhérents non employés
contact.php        formulaire enregistré en base
adherent.php       planning, mes réservations, mon abonnement
employe.php        tableau de bord, adhérents, créneaux, matériel, maintenance, questions
admin.php          utilisateurs et rôles, statistiques, messages, journal
gestion.php        CRUD fournisseurs, accueil et coachs
paiement.php       paiement et réactivation immédiate d'un abonnement
install.php        pose les mots de passe de démonstration, à supprimer
config/config.php  connexion PDO, sessions, CSRF, helpers
includes/auth.php  authentification et contrôle des rôles
includes/header.php, footer.php
Model/             classes d'accès aux données
controller/        contrôleurs des pages et des règles métier
controller/crud/   CRUD séparés : comptes, messages et fournisseurs
assets/css/style.css
assets/js/app.js
sql/apex_fitness.sql
```

## Règles de gestion implémentées

Les contrôles du cahier des charges sont faits **côté serveur** ; le JS ne sert
qu'au confort (menu, filtres, confirmations).

- Deux activités ne peuvent pas occuper la même salle au même moment :
  contrainte `UNIQUE (Numsalle, date_heure_debut)` plus test de chevauchement
  dans `employe.php`.
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
- Un adhérent peut payer depuis `paiement.php`, même si son statut est expiré :
  la souscription précédente est terminée, le paiement est enregistré, une
  nouvelle souscription est créée et le statut repasse à Actif dans une seule
  transaction.
- Les contrôleurs MVC regroupent les CRUD des comptes, des messages et des
  fournisseurs dans `controller/crud/`, avec leurs vues dans `views/crud/`.
  La déconnexion détruit toute la session et tous les rôles
  portés par le compte connecté.

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
