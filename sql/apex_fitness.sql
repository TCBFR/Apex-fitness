DROP DATABASE IF EXISTS fitness;
CREATE DATABASE fitness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fitness;

-- 1. UTILISATEUR et specialisations (heritage T)
CREATE TABLE utilisateur (
  NumUsers           INT AUTO_INCREMENT PRIMARY KEY,
  nomUsers           VARCHAR(60)  NOT NULL,
  prenomUsers        VARCHAR(60)  NOT NULL,
  mailUsers          VARCHAR(150) NOT NULL UNIQUE,
  telUsers           VARCHAR(20),
  passwordUsers      VARCHAR(255) NOT NULL,
  Date_CreationUsers DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE commune (
  id_commune  INT AUTO_INCREMENT PRIMARY KEY,
  code_insee  CHAR(5)      NOT NULL,
  nom_commune VARCHAR(100) NOT NULL,
  code_postal CHAR(5)      NOT NULL,
  UNIQUE KEY uq_commune_cp (code_insee, code_postal),
  KEY idx_commune_nom (nom_commune),
  KEY idx_commune_cp (code_postal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO commune (code_insee, nom_commune, code_postal) VALUES
('19031', 'Brive-la-Gaillarde', '19100'),
('19272', 'Tulle', '19000');

ALTER TABLE adherent
  ADD COLUMN id_commune INT NULL,
  ADD CONSTRAINT fk_adherent_commune FOREIGN KEY (id_commune) REFERENCES commune (id_commune);

CREATE TABLE compte_demo (
  NumUsers INT PRIMARY KEY,
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE journal (
  Numjournal  INT AUTO_INCREMENT PRIMARY KEY,
  date_action DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NumUsers    INT NULL,
  action      VARCHAR(60) NOT NULL,
  cible       VARCHAR(120),
  ip          VARCHAR(45),
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE message_contact (
  Nummessage INT AUTO_INCREMENT PRIMARY KEY,
  nom        VARCHAR(120) NOT NULL,
  mail       VARCHAR(150) NOT NULL,
  sujet      VARCHAR(160) NOT NULL,
  message    TEXT NOT NULL,
  date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fonction (
  Numfonc     INT AUTO_INCREMENT PRIMARY KEY,
  libellefonc VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE employe (
  NumUsers         INT PRIMARY KEY,
  matriculeemp     VARCHAR(20) NOT NULL UNIQUE,
  Date_embaucheemp DATE NOT NULL,
  Numfonc          INT NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (Numfonc)  REFERENCES fonction(Numfonc)
) ENGINE=InnoDB;

CREATE TABLE fournisseur (
  NumUsers       INT PRIMARY KEY,
  nomfourn       VARCHAR(80) NOT NULL,
  telephonefourn VARCHAR(20),
  siret          VARCHAR(14),
  mailfourn      VARCHAR(150),
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admin (
  NumUsers              INT PRIMARY KEY,
  matriculeadmin        VARCHAR(20) NOT NULL UNIQUE,
  niveau_droits         VARCHAR(30) NOT NULL DEFAULT 'TOTAL',
  date_affectationadmin DATE NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE adherent (
  NumUsers            INT PRIMARY KEY,
  matriculeadh        VARCHAR(20) NOT NULL UNIQUE,
  Date_inscriptionadh DATE NOT NULL,
  Date_finadh         DATE,
  date_naissance      DATE,
  ville               VARCHAR(100),
  code_postal         CHAR(5),
  adresse_postale     VARCHAR(255),
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE qr_code (
  Numqr              INT AUTO_INCREMENT PRIMARY KEY,
  code_qr            VARCHAR(120) NOT NULL UNIQUE,
  date_generation_qr DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_expiration_qr DATETIME NOT NULL,
  actif_qr           BOOLEAN NOT NULL DEFAULT 1,
  NumUsers           INT NOT NULL,
  UNIQUE KEY uq_qr_adherent (NumUsers),
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. OFFRE (heritage XT), ABONNEMENT, SOUSCRIRE, PAIEMENT
CREATE TABLE offre (
  Numoffre          INT AUTO_INCREMENT PRIMARY KEY,
  libelle_offre     VARCHAR(60) NOT NULL,
  description_offre TEXT,
  type_offre        ENUM('FREEMIUM','BASIC','PLUS') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE freemium (
  Numoffre                 INT PRIMARY KEY,
  prix_mensuel_reduit      DECIMAL(6,2) NOT NULL,
  taux_reduction           DECIMAL(5,2) NOT NULL,
  justificatif_requis      VARCHAR(120) NOT NULL,
  nb_reservations_max_mois INT NOT NULL,
  FOREIGN KEY (Numoffre) REFERENCES offre(Numoffre) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE basic (
  Numoffre              INT PRIMARY KEY,
  prix_annuel           DECIMAL(7,2) NOT NULL,
  duree_engagement_mois INT NOT NULL DEFAULT 12,
  reconduction_tacite   BOOLEAN NOT NULL DEFAULT 1,
  FOREIGN KEY (Numoffre) REFERENCES offre(Numoffre) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE abonnement_plus (
  Numoffre               INT PRIMARY KEY,
  prix_mensuel_plus      DECIMAL(6,2) NOT NULL,
  supplement_mensuel     DECIMAL(6,2) NOT NULL,
  seances_coach_incluses INT NOT NULL,
  acces_illimite         BOOLEAN NOT NULL DEFAULT 1,
  FOREIGN KEY (Numoffre) REFERENCES offre(Numoffre) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE abonnement (
  Numabo               INT AUTO_INCREMENT PRIMARY KEY,
  libelle_abo          VARCHAR(60) NOT NULL,
  description_abo      TEXT,
  date_mise_en_service DATE NOT NULL,
  actif_abo            BOOLEAN NOT NULL DEFAULT 1,
  Numoffre             INT NOT NULL,
  FOREIGN KEY (Numoffre) REFERENCES offre(Numoffre)
) ENGINE=InnoDB;

INSERT INTO offre (Numoffre, libelle_offre, description_offre, type_offre) VALUES
  (1, 'Freemium', 'Tarif mensuel reduit sur presentation d un justificatif.', 'FREEMIUM'),
  (2, 'Basic', 'Forfait annuel avec engagement de 12 mois.', 'BASIC'),
  (3, 'Premium', 'Acces mensuel avec coaching inclus.', 'PLUS');

INSERT INTO freemium (Numoffre, prix_mensuel_reduit, taux_reduction, justificatif_requis, nb_reservations_max_mois)
VALUES (1, 20.00, 10.00, 'Justificatif etudiant ou demandeur d emploi', 4);

INSERT INTO basic (Numoffre, prix_annuel, duree_engagement_mois, reconduction_tacite)
VALUES (2, 350.00, 12, 1);

INSERT INTO abonnement_plus (Numoffre, prix_mensuel_plus, supplement_mensuel, seances_coach_incluses, acces_illimite)
VALUES (3, 45.00, 0.00, 4, 1);

INSERT INTO abonnement (libelle_abo, description_abo, date_mise_en_service, actif_abo, Numoffre) VALUES
  ('Freemium', 'Formule mensuelle a tarif reduit.', CURRENT_DATE, 1, 1),
  ('Basic', 'Formule annuelle avec engagement.', CURRENT_DATE, 1, 2),
  ('Premium', 'Formule mensuelle avec coaching inclus.', CURRENT_DATE, 1, 3);

CREATE TABLE souscrire (
  NumUsers            INT NOT NULL,
  Numabo              INT NOT NULL,
  date_debut          DATE NOT NULL,
  date_fin            DATE,
  statut_souscription ENUM('En cours','Terminee','Resiliee') NOT NULL DEFAULT 'En cours',
  PRIMARY KEY (NumUsers, Numabo, date_debut),
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (Numabo)   REFERENCES abonnement(Numabo)
) ENGINE=InnoDB;

CREATE TABLE paiement (
  Numpaiement    INT AUTO_INCREMENT PRIMARY KEY,
  montant        DECIMAL(8,2) NOT NULL,
  date_paiement  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  moyen_paiement VARCHAR(30) NOT NULL,
  NumUsers       INT NOT NULL,
  Numabo         INT NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers),
  FOREIGN KEY (Numabo)   REFERENCES abonnement(Numabo)
) ENGINE=InnoDB;

-- 3. SALLE, ACTIVITE, RESERVATION
CREATE TABLE salle (
  Numsalle          INT AUTO_INCREMENT PRIMARY KEY,
  nomsalle          VARCHAR(60) NOT NULL,
  capacitesalle     INT NOT NULL,
  localisationsalle VARCHAR(80),
  superficiesalle   DECIMAL(6,2),
  etatsalle         VARCHAR(20) NOT NULL DEFAULT 'Ouverte'
) ENGINE=InnoDB;

CREATE TABLE activite (
  Numactivite       INT AUTO_INCREMENT PRIMARY KEY,
  titre             VARCHAR(100) NOT NULL,
  type_activite     VARCHAR(20)  NOT NULL DEFAULT 'cardio',
  description       TEXT,
  niveau            VARCHAR(20),
  date_heure_debut  DATETIME NOT NULL,
  date_heure_fin    DATETIME NOT NULL,
  capacite_max      INT NOT NULL,
  date_debutprogent DATE,
  date_finprogent   DATE,
  Numsalle          INT NOT NULL,
  NumUsers          INT NOT NULL,          -- employe (coach)
  id_serie          CHAR(32) NULL,
  UNIQUE KEY uk_salle_debut (Numsalle, date_heure_debut),
  KEY idx_activite_serie (id_serie),
  FOREIGN KEY (Numsalle) REFERENCES salle(Numsalle),
  FOREIGN KEY (NumUsers) REFERENCES employe(NumUsers)
) ENGINE=InnoDB;

CREATE TABLE reservation (
  Numres                 INT AUTO_INCREMENT PRIMARY KEY,
  date_heure_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut_reservation     VARCHAR(20) NOT NULL DEFAULT 'Reservee',
  NumUsers               INT NOT NULL,
  Numactivite            INT NOT NULL,
  UNIQUE KEY uk_adh_activite (NumUsers, Numactivite),
  FOREIGN KEY (NumUsers)    REFERENCES adherent(NumUsers)    ON DELETE CASCADE,
  FOREIGN KEY (Numactivite) REFERENCES activite(Numactivite) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE liste_attente (
  Numattente INT AUTO_INCREMENT PRIMARY KEY,
  NumUsers INT NOT NULL,
  Numactivite INT NOT NULL,
  date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_attente_adherent_activite (NumUsers, Numactivite),
  KEY idx_attente_ordre (Numactivite, date_inscription),
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (Numactivite) REFERENCES activite(Numactivite) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notification_adherent (
  Numnotification INT AUTO_INCREMENT PRIMARY KEY,
  NumUsers INT NOT NULL,
  Numres INT NULL,
  type_notification VARCHAR(30) NOT NULL,
  titre VARCHAR(120) NOT NULL,
  message VARCHAR(500) NOT NULL,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_lecture DATETIME NULL,
  email_envoye_le DATETIME NULL,
  UNIQUE KEY uk_notification_res_type (Numres, type_notification),
  KEY idx_notification_user_date (NumUsers, date_creation),
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (Numres) REFERENCES reservation(Numres) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. STATUT (heritage XT), EQUIPEMENT, MAINTENANCE
CREATE TABLE statut (
  Numstatut      INT AUTO_INCREMENT PRIMARY KEY,
  libelle_statut VARCHAR(40) NOT NULL,
  type_statut    ENUM('EQUIPEMENT','MAINTENANCE') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE etat_equipement (
  Numstatut INT PRIMARY KEY,
  FOREIGN KEY (Numstatut) REFERENCES statut(Numstatut) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE statut_maintenance (
  Numstatut INT PRIMARY KEY,
  FOREIGN KEY (Numstatut) REFERENCES statut(Numstatut) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE type_equipement (
  Numtypeequip     INT AUTO_INCREMENT PRIMARY KEY,
  libelletypeequip VARCHAR(60) NOT NULL,
  descriptionequip TEXT
) ENGINE=InnoDB;

CREATE TABLE equipement (
  Numequip                  INT AUTO_INCREMENT PRIMARY KEY,
  nomequip                  VARCHAR(80) NOT NULL,
  marque                    VARCHAR(60),
  modele                    VARCHAR(60),
  date_achatequip           DATE,
  prix_achatequip           DECIMAL(9,2),
  date_derniere_maintenance DATE,
  Numtypeequip              INT NOT NULL,
  NumUsers_fourn            INT NOT NULL,
  Numstatut                 INT NOT NULL,
  FOREIGN KEY (Numtypeequip)   REFERENCES type_equipement(Numtypeequip),
  FOREIGN KEY (NumUsers_fourn) REFERENCES fournisseur(NumUsers),
  FOREIGN KEY (Numstatut)      REFERENCES etat_equipement(Numstatut)
) ENGINE=InnoDB;

CREATE TABLE demande_maintenance (
  Nummaint                  INT AUTO_INCREMENT PRIMARY KEY,
  description_problememaint TEXT NOT NULL,
  priorite                  VARCHAR(20) NOT NULL DEFAULT 'Moyenne',
  date_demandemaint         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NumUsers                  INT NOT NULL,
  NumUsers_technicien       INT NULL,
  Numequip                  INT NOT NULL,
  Numstatut                 INT NOT NULL,
  FOREIGN KEY (NumUsers)  REFERENCES employe(NumUsers),
  FOREIGN KEY (NumUsers_technicien) REFERENCES employe(NumUsers) ON DELETE SET NULL,
  FOREIGN KEY (Numequip)  REFERENCES equipement(Numequip),
  FOREIGN KEY (Numstatut) REFERENCES statut_maintenance(Numstatut)
) ENGINE=InnoDB;

CREATE TABLE intervention (
  Numintervention INT AUTO_INCREMENT PRIMARY KEY,
  date_intervention DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  commentaire TEXT,
  Nummaint INT NOT NULL,
  NumUsers INT NOT NULL,
  Numstatut INT NOT NULL,
  FOREIGN KEY (Nummaint) REFERENCES demande_maintenance(Nummaint) ON DELETE CASCADE,
  FOREIGN KEY (NumUsers) REFERENCES employe(NumUsers),
  FOREIGN KEY (Numstatut) REFERENCES statut_maintenance(Numstatut)
) ENGINE=InnoDB;

-- 5. INTERACTION_ADHERENT (heritage XT : AVIS / FAQ)
CREATE TABLE interaction_adherent (
  NumInteraction   INT AUTO_INCREMENT PRIMARY KEY,
  date_interaction DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  type_interaction ENUM('AVIS','FAQ') NOT NULL,
  NumUsers         INT NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE avis (
  NumInteraction INT PRIMARY KEY,
  noteavis       TINYINT NOT NULL CHECK (noteavis BETWEEN 1 AND 5),
  commentaire    TEXT,
  reponse        TEXT,
  Numres         INT NULL UNIQUE,
  FOREIGN KEY (NumInteraction) REFERENCES interaction_adherent(NumInteraction) ON DELETE CASCADE,
  FOREIGN KEY (Numres)         REFERENCES reservation(Numres) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE faq (
  NumInteraction INT PRIMARY KEY,
  question       TEXT NOT NULL,
  reponse        TEXT,
  statut_faq     VARCHAR(20) NOT NULL DEFAULT 'En attente',
  categorie      VARCHAR(80) NOT NULL DEFAULT 'General',
  FOREIGN KEY (NumInteraction) REFERENCES interaction_adherent(NumInteraction) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
--  DONNEES DE DEMO (mot de passe de tous les comptes : apex2026)
-- =====================================================================
INSERT INTO utilisateur (NumUsers, nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers) VALUES
 (1,'Vasseur','Marion','admin@apex.fr','05 55 10 20 30','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (2,'Roux','Camille','camille.roux@apex.fr','06 41 77 30 02','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (3,'Anselme','Marc','marc.anselme@apex.fr','06 22 81 14 55','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW');

INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh)
VALUES (2, 'DEMO-ADH-2', CURRENT_DATE);

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Quelles formules proposez-vous ?', 'Freemium : 143 EUR par mois, avec 10 % de réduction, sur présentation d’un justificatif étudiant ou demandeur d’emploi et dans la limite de 4 réservations par mois. Basic : 2 000 EUR par an, avec 12 mois d’engagement. Premium : 300 EUR par mois, avec 4 séances de coaching et un accès illimité.', 'Publiee', 'Abonnements');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Comment réserver un cours ?', 'Connectez-vous à votre espace adhérent, puis choisissez un créneau disponible dans le planning. La formule Freemium est limitée à 4 réservations par mois.', 'Publiee', 'Réservations');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Jusqu’à quand puis-je annuler une réservation ?', 'Vous pouvez annuler une réservation jusqu’à 2 heures avant le début du cours. Passé ce délai, l’annulation n’est plus possible depuis votre espace.', 'Publiee', 'Réservations');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Sous quel délai recevrai-je une réponse à mon message ?', 'L’accueil vous répond sous 48 heures ouvrées. Vous pouvez envoyer votre demande depuis le formulaire de contact.', 'Publiee', 'Contact');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Comment puis-je consulter les cours proposés ?', 'Consultez le planning depuis le site ou votre espace adhérent. Vous y trouverez les activités et leurs créneaux.', 'Publiee', 'Planning');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Comment réserver une place pour un cours ?', 'Connectez-vous à votre espace adhérent, ouvrez le planning puis choisissez un créneau disponible pour réserver.', 'Publiee', 'Réservations');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Où puis-je retrouver mes réservations ?', 'Vos réservations sont consultables dans la rubrique « Réservations » de votre espace adhérent.', 'Publiee', 'Réservations');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Comment consulter ou changer mon abonnement ?', 'Retrouvez votre formule et son statut dans la rubrique « Abonnement » de votre espace adhérent. Les offres disponibles sont présentées dans le catalogue.', 'Publiee', 'Abonnements');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Comment contacter l’équipe Apex Fitness ?', 'Utilisez le formulaire de contact du site pour envoyer votre demande à l’équipe.', 'Publiee', 'Contact');

INSERT INTO interaction_adherent (type_interaction, NumUsers) VALUES ('FAQ', 2);
SET @faq_id = LAST_INSERT_ID();
INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
  (@faq_id, 'Comment laisser un avis sur mon expérience ?', 'Depuis votre espace adhérent, ouvrez la rubrique « Avis », attribuez une note et partagez votre commentaire.', 'Publiee', 'Avis');

INSERT INTO utilisateur (NumUsers, nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers) VALUES
  (4, 'Nabil', 'S.', 's.nabil@mail.fr', NULL, '$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
  (5, 'Diallo', 'Awa', 'awa.diallo@mail.fr', NULL, '$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
  (6, 'Lemoine', 'H.', 'h.lemoine@mail.fr', NULL, '$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
  (7, 'Moreau', 'Lina', 'lina.moreau@apex.fr', NULL, '$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
  (8, 'Petit', 'Noah', 'noah.petit@apex.fr', NULL, '$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW');

INSERT INTO admin (NumUsers, matriculeadmin, date_affectationadmin)
VALUES (1, 'DEMO-ADM-1', CURRENT_DATE);

INSERT INTO fonction (Numfonc, libellefonc) VALUES
  (1, 'Coach'),
  (2, 'Technicien maintenance'),
  (3, 'Accueil');

INSERT INTO employe (NumUsers, matriculeemp, Date_embaucheemp, Numfonc) VALUES
  (2, 'DEMO-EMP-2', CURRENT_DATE, 1),
  (3, 'DEMO-EMP-3', CURRENT_DATE, 2),
  (7, 'DEMO-EMP-7', CURRENT_DATE, 1),
  (8, 'DEMO-EMP-8', CURRENT_DATE, 3);

INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh) VALUES
  (4, 'DEMO-ADH-4', CURRENT_DATE, NULL),
  (5, 'DEMO-ADH-5', DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR), DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)),
  (6, 'DEMO-ADH-6', CURRENT_DATE, NULL),
  (7, 'DEMO-ADH-7', CURRENT_DATE, NULL);

INSERT INTO compte_demo (NumUsers) VALUES
  (1), (2), (3), (4), (5), (6), (7), (8);

-- Fournisseur et inventaire de démonstration
INSERT INTO utilisateur (nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers)
VALUES ('Apex', 'Fournitures', 'fournisseur.demo@apex.fr', NULL,
        '$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW');
SET @fournisseur_demo = LAST_INSERT_ID();
INSERT INTO fournisseur (NumUsers, nomfourn, telephonefourn, siret, mailfourn)
VALUES (@fournisseur_demo, 'Fournisseur de démonstration', NULL, NULL, 'fournisseur.demo@apex.fr');

INSERT INTO statut (libelle_statut, type_statut) VALUES
  ('Disponible', 'EQUIPEMENT'),
  ('Maintenance', 'EQUIPEMENT'),
  ('Hors service', 'EQUIPEMENT'),
  ('Ouverte', 'MAINTENANCE'),
  ('En attente', 'MAINTENANCE'),
  ('En cours', 'MAINTENANCE'),
  ('En attente de piece', 'MAINTENANCE'),
  ('À valider', 'MAINTENANCE'),
  ('Terminee', 'MAINTENANCE');
INSERT INTO etat_equipement (Numstatut)
  SELECT Numstatut FROM statut WHERE type_statut = 'EQUIPEMENT';
INSERT INTO statut_maintenance (Numstatut)
  SELECT Numstatut FROM statut WHERE type_statut = 'MAINTENANCE';

INSERT INTO type_equipement (libelletypeequip, descriptionequip) VALUES
  ('Cardio-training', 'Équipements de cardio-training'),
  ('Musculation', 'Appareils et accessoires de musculation'),
  ('Accessoire fitness', 'Accessoires pour les activités fitness');

INSERT INTO equipement
  (nomequip, marque, modele, date_achatequip, prix_achatequip, Numtypeequip, NumUsers_fourn, Numstatut)
SELECT demo.nomequip, 'Apex Fitness', demo.modele, CURRENT_DATE, demo.prix,
       te.Numtypeequip, @fournisseur_demo, s.Numstatut
FROM (
  SELECT 'Tapis de course' AS nomequip, 'RUN-500' AS modele, 1899.00 AS prix, 'Cardio-training' AS type_nom
  UNION ALL SELECT 'Vélo elliptique', 'ELLIPTIC-300', 1299.00, 'Cardio-training'
  UNION ALL SELECT 'Vélo d’appartement', 'BIKE-200', 799.00, 'Cardio-training'
  UNION ALL SELECT 'Rameur', 'ROW-400', 999.00, 'Cardio-training'
  UNION ALL SELECT 'Presse à jambes', 'LEG-PRESS-01', 2499.00, 'Musculation'
  UNION ALL SELECT 'Banc de musculation', 'BENCH-02', 499.00, 'Musculation'
  UNION ALL SELECT 'Haltères (lot)', 'DUMBBELL-SET', 350.00, 'Musculation'
  UNION ALL SELECT 'Tapis de fitness', 'FIT-MAT-01', 29.00, 'Accessoire fitness'
) AS demo
JOIN type_equipement te ON te.libelletypeequip = demo.type_nom
JOIN statut s ON s.libelle_statut = 'Disponible' AND s.type_statut = 'EQUIPEMENT';

-- Parcours de démonstration du compte Awa : adhésion active et vrais cours
-- futurs en base pour tester la réservation depuis le planning adhérent.
UPDATE adherent
SET Date_inscriptionadh = CURRENT_DATE,
    Date_finadh = DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR)
WHERE NumUsers = (SELECT NumUsers FROM utilisateur WHERE mailUsers = 'awa.diallo@mail.fr');

INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription)
SELECT u.NumUsers, a.Numabo, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH), 'En cours'
FROM utilisateur u
JOIN adherent ad ON ad.NumUsers = u.NumUsers
JOIN abonnement a ON a.libelle_abo = 'Premium' AND a.actif_abo = 1
WHERE u.mailUsers = 'awa.diallo@mail.fr'
  AND NOT EXISTS (
    SELECT 1 FROM souscrire s
    WHERE s.NumUsers = u.NumUsers AND s.Numabo = a.Numabo
      AND s.statut_souscription = 'En cours'
  );

INSERT INTO salle (nomsalle, capacitesalle, localisationsalle, superficiesalle, etatsalle) VALUES
  ('Studio cardio', 16, 'Rez-de-chaussée', 85.00, 'Ouverte'),
  ('Studio zen', 14, 'Étage 1', 70.00, 'Ouverte'),
  ('Zone cross', 12, 'Rez-de-chaussée', 95.00, 'Ouverte'),
  ('Plateau muscu', 20, 'Rez-de-chaussée', 140.00, 'Ouverte');

INSERT INTO activite
  (titre, type_activite, description, niveau, date_heure_debut, date_heure_fin,
   capacite_max, Numsalle, NumUsers)
SELECT cours.titre, cours.type_activite, cours.description, 'Tous niveaux',
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL cours.jour DAY), cours.debut),
       TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL cours.jour DAY), cours.fin),
       cours.capacite, s.Numsalle, coach.NumUsers
FROM (
  SELECT 'Cardio HIIT' AS titre, 'cardio' AS type_activite,
         'Circuit cardio encadré, adapté à tous les niveaux.' AS description,
         1 AS jour, '09:00:00' AS debut, '10:00:00' AS fin, 16 AS capacite,
         'Studio cardio' AS salle
  UNION ALL SELECT 'Yoga Flow', 'yoga', 'Mobilité, respiration et renforcement doux.',
         2, '12:00:00', '13:00:00', 14, 'Studio zen'
  UNION ALL SELECT 'Cross Training', 'cross', 'Séance fonctionnelle en petit groupe.',
         3, '17:30:00', '18:30:00', 12, 'Zone cross'
  UNION ALL SELECT 'Renforcement musculaire', 'musculation', 'Travail complet guidé par le coach.',
         4, '18:00:00', '19:00:00', 20, 'Plateau muscu'
) AS cours
JOIN salle s ON s.nomsalle = cours.salle
JOIN utilisateur coach ON coach.mailUsers = 'lina.moreau@apex.fr'
JOIN employe e ON e.NumUsers = coach.NumUsers
WHERE NOT EXISTS (
  SELECT 1 FROM activite existante
  WHERE existante.Numsalle = s.Numsalle
    AND existante.date_heure_debut = TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL cours.jour DAY), cours.debut)
);
