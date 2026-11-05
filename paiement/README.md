# Paiements

- `controller/PaiementController.php` relie les pages de paiement au modèle.
- `model/PaiementDomain.php` compose le CRUD partagé avec les traitements d’offres, de paiement et d’abonnement.
- `payer.php` traite le formulaire de règlement; `view/` contient les écrans associés.

`payer()` orchestre les validations et écritures de contrat et de souscription; les requêtes métier sont séparées dans les traits du modèle.
