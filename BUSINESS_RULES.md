# Règles et architecture métier

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
- Les traits `ModelCrudOperations` et `ModelCrudKeys` centralisent les
  opérations CRUD génériques. Les modèles métier conservent leurs requêtes et
  traitements propres; les contrôleurs coordonnent les actions et les vues
  présentent les données.
- Les héritages du MCD sont représentés par `Utilisateur` et ses modèles
  spécialisés, `Offre` et ses offres spécialisées, `Statut` et ses statuts
  spécialisés, ainsi que `InteractionAdherent`, `Avis` et `Faq`. Les opérations
  qui écrivent un parent et une spécialisation utilisent des transactions.
- La déconnexion détruit la session et les rôles portés par le compte.

## CRUD par table

Les écrans CRUD disponibles s’appuient sur les métadonnées MySQL pour identifier
les champs, clés primaires et clés étrangères. Les identifiants de connexion
restent configurés dans `config/config.php`.

## Sécurité

- Mots de passe hachés avec `password_hash()` / `password_verify()` (argon2id).
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
