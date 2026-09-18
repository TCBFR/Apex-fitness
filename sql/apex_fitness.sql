-- =====================================================================
--  Apex Fitness - base de donnees
--  Derivee du MCD v4 (heritage T sur UTILISATEUR, XT sur STATUT,
--  INTERACTION_ADHERENT et ABONNEMENT).
--
--  Les tables marquees [COMPLEMENT] n'existent pas dans le MCD v4 :
--  elles corrigent les manques releves face au cahier des charges.
-- =====================================================================

DROP DATABASE IF EXISTS apex_fitness;
CREATE DATABASE apex_fitness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apex_fitness;

-- ---------------------------------------------------------------------
-- 1. Utilisateurs et heritage (T : un utilisateur peut cumuler
--    EMPLOYE + ADHERENT, jamais l'inverse - voir trigger plus bas)
-- ---------------------------------------------------------------------
CREATE TABLE utilisateur (
  NumUsers            INT AUTO_INCREMENT PRIMARY KEY,
  nomUsers            VARCHAR(60)  NOT NULL,
  prenomUsers         VARCHAR(60)  NOT NULL,
  mailUsers           VARCHAR(150) NOT NULL UNIQUE,
  telUsers            VARCHAR(20),
  passwordUsers       VARCHAR(255) NOT NULL,
  Date_CreationUsers  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fonction (
  Numfonc     INT AUTO_INCREMENT PRIMARY KEY,
  libellefonc VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

-- STATUT : entite mere, specialisee en XT
CREATE TABLE statut (
  Numstatut      INT AUTO_INCREMENT PRIMARY KEY,
  libelle_statut VARCHAR(40) NOT NULL,
  type_statut    ENUM('EQUIPEMENT','MAINTENANCE','ADHERENT') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE etat_equipement (
  Numstatut INT PRIMARY KEY,
  FOREIGN KEY (Numstatut) REFERENCES statut(Numstatut) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE statut_maintenance (
  Numstatut INT PRIMARY KEY,
  ordre     TINYINT NOT NULL,           -- position dans le parcours
  FOREIGN KEY (Numstatut) REFERENCES statut(Numstatut) ON DELETE CASCADE
) ENGINE=InnoDB;

-- [COMPLEMENT] le cahier des charges impose actif / suspendu / expire
CREATE TABLE statut_adherent (
  Numstatut INT PRIMARY KEY,
  FOREIGN KEY (Numstatut) REFERENCES statut(Numstatut) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employe (
  NumUsers         INT PRIMARY KEY,
  matriculeemp     VARCHAR(20) NOT NULL UNIQUE,
  Date_embaucheemp DATE NOT NULL,
  Numfonc          INT NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (Numfonc)  REFERENCES fonction(Numfonc)
) ENGINE=InnoDB;

CREATE TABLE administrateur (
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
  Numstatut           INT NOT NULL,    -- [COMPLEMENT]
  FOREIGN KEY (NumUsers)  REFERENCES utilisateur(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (Numstatut) REFERENCES statut_adherent(Numstatut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Abonnements (heritage XT)
-- ---------------------------------------------------------------------
CREATE TABLE abonnement (
  Numabo               INT AUTO_INCREMENT PRIMARY KEY,
  libelle_abo          VARCHAR(60) NOT NULL,
  description_abo      TEXT,
  date_mise_en_service DATE NOT NULL,
  actif_abo            BOOLEAN NOT NULL DEFAULT 1,
  type_abo             ENUM('FREEMIUM','BASIC','PLUS') NOT NULL  -- discriminant
) ENGINE=InnoDB;

CREATE TABLE freemium (
  Numabo                  INT PRIMARY KEY,
  prix_mensuel_reduit     DECIMAL(6,2) NOT NULL,
  taux_reduction          DECIMAL(5,2) NOT NULL,
  justificatif_requis     VARCHAR(120) NOT NULL,
  nb_reservations_max_mois INT NOT NULL,
  FOREIGN KEY (Numabo) REFERENCES abonnement(Numabo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE basic (
  Numabo                INT PRIMARY KEY,
  prix_annuel           DECIMAL(7,2) NOT NULL,
  duree_engagement_mois INT NOT NULL DEFAULT 12,
  reconduction_tacite   BOOLEAN NOT NULL DEFAULT 1,
  FOREIGN KEY (Numabo) REFERENCES abonnement(Numabo) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE abonnement_plus (
  Numabo                 INT PRIMARY KEY,
  prix_mensuel_plus      DECIMAL(6,2) NOT NULL,
  supplement_mensuel     DECIMAL(6,2) NOT NULL,
  seances_coach_incluses INT NOT NULL,
  acces_illimite         BOOLEAN NOT NULL DEFAULT 1,
  FOREIGN KEY (Numabo) REFERENCES abonnement(Numabo) ON DELETE CASCADE
) ENGINE=InnoDB;

-- association SOUSCRIRE, porteuse de dates
CREATE TABLE souscrire (
  NumUsers            INT NOT NULL,
  Numabo              INT NOT NULL,
  date_debut          DATE NOT NULL,
  date_fin            DATE,
  statut_souscription ENUM('En cours','Terminee','Resiliee') NOT NULL DEFAULT 'En cours',
  PRIMARY KEY (NumUsers, Numabo, date_debut),
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers)  ON DELETE CASCADE,
  FOREIGN KEY (Numabo)   REFERENCES abonnement(Numabo)
) ENGINE=InnoDB;

CREATE TABLE paiement (
  Numpaiement    INT AUTO_INCREMENT PRIMARY KEY,
  montant        DECIMAL(8,2) NOT NULL,
  date_paiement  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  moyen_paiement ENUM('CB','Virement','Especes','Prelevement') NOT NULL,
  NumUsers       INT NOT NULL,
  Numabo         INT NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers),
  FOREIGN KEY (Numabo)   REFERENCES abonnement(Numabo)
) ENGINE=InnoDB;

CREATE TABLE message_adherent (
  IdMessage             INT AUTO_INCREMENT PRIMARY KEY,
  NumUsers_emetteur     INT NOT NULL,
  NumUsers_destinataire INT NOT NULL,
  IdMessage_parent      INT NULL,
  message               TEXT NOT NULL,
  date_envoi            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  lu                    BOOLEAN NOT NULL DEFAULT 0,
  FOREIGN KEY (NumUsers_emetteur) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (NumUsers_destinataire) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. Salles, activites, creneaux, reservations
-- ---------------------------------------------------------------------
CREATE TABLE salle (
  Numsalle          INT AUTO_INCREMENT PRIMARY KEY,
  nomsalle          VARCHAR(60) NOT NULL,
  capacitesalle     INT NOT NULL,
  localisationsalle VARCHAR(80),
  superficiesalle   DECIMAL(6,2),
  etatsalle         ENUM('Ouverte','Fermee','Travaux') NOT NULL DEFAULT 'Ouverte'
) ENGINE=InnoDB;

-- [COMPLEMENT] absente du MCD v4, exigee par la section 4 du cahier
CREATE TABLE activite (
  Numact           INT AUTO_INCREMENT PRIMARY KEY,
  libelleact       VARCHAR(60) NOT NULL,
  duree_minutes    INT,
  capacite_defaut  INT NOT NULL,
  descriptionact   TEXT
) ENGINE=InnoDB;

CREATE TABLE programme_entrainement (
  Numprogent        INT AUTO_INCREMENT PRIMARY KEY,
  titre             VARCHAR(100) NOT NULL,
  descriptionprog   TEXT,
  niveau            ENUM('Debutant','Intermediaire','Avance') NOT NULL,
  date_debutprogent DATE,
  date_finprogent   DATE,
  Numsalle          INT,
  FOREIGN KEY (Numsalle) REFERENCES salle(Numsalle)
) ENGINE=InnoDB;

CREATE TABLE creneaux (
  Numcreneaux      INT AUTO_INCREMENT PRIMARY KEY,
  date_heure_debut DATETIME NOT NULL,
  date_heure_fin   DATETIME NOT NULL,
  capacite_max     INT NOT NULL,
  Numsalle         INT NOT NULL,
  Numact           INT NOT NULL,
  NumUsers_coach   INT NULL,           -- [COMPLEMENT] coach eventuel
  Numprogent       INT NULL,
  -- interdit deux activites dans la meme salle au meme moment
  UNIQUE KEY uk_salle_creneau (Numsalle, date_heure_debut),
  FOREIGN KEY (Numsalle)       REFERENCES salle(Numsalle),
  FOREIGN KEY (Numact)         REFERENCES activite(Numact),
  FOREIGN KEY (NumUsers_coach) REFERENCES employe(NumUsers),
  FOREIGN KEY (Numprogent)     REFERENCES programme_entrainement(Numprogent)
) ENGINE=InnoDB;

CREATE TABLE reservation (
  Numres                 INT AUTO_INCREMENT PRIMARY KEY,
  date_heure_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut_reservation     ENUM('Reservee','Annulee','Presente','Absente') NOT NULL DEFAULT 'Reservee',
  NumUsers               INT NOT NULL,
  Numcreneaux            INT NOT NULL,
  -- interdit la double reservation d'un meme adherent sur un meme creneau
  UNIQUE KEY uk_adh_creneau (NumUsers, Numcreneaux),
  FOREIGN KEY (NumUsers)    REFERENCES adherent(NumUsers)   ON DELETE CASCADE,
  FOREIGN KEY (Numcreneaux) REFERENCES creneaux(Numcreneaux) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Materiel et maintenance
-- ---------------------------------------------------------------------
CREATE TABLE fournisseur (
  Numfourn       INT AUTO_INCREMENT PRIMARY KEY,
  nomfourn       VARCHAR(80) NOT NULL,
  telephonefourn VARCHAR(20),
  siret          VARCHAR(14),
  mailfourn      VARCHAR(150)
) ENGINE=InnoDB;

CREATE TABLE type_equipement (
  Numtypeequip     INT AUTO_INCREMENT PRIMARY KEY,
  libelletypeequip VARCHAR(60) NOT NULL,
  descriptionequip TEXT
) ENGINE=InnoDB;

CREATE TABLE equipement (
  Numequip                  INT AUTO_INCREMENT PRIMARY KEY,
  num_inventaire            VARCHAR(20) NOT NULL UNIQUE,  -- [COMPLEMENT]
  nomequip                  VARCHAR(80) NOT NULL,
  marque                    VARCHAR(60),
  modele                    VARCHAR(60),
  date_achatequip           DATE,
  prix_achatequip           DECIMAL(9,2),
  date_derniere_maintenance DATE,
  Numtypeequip              INT NOT NULL,
  Numfourn                  INT,
  Numstatut                 INT NOT NULL,   -- etat courant
  Numsalle                  INT NOT NULL,   -- [COMPLEMENT] localisation
  FOREIGN KEY (Numtypeequip) REFERENCES type_equipement(Numtypeequip),
  FOREIGN KEY (Numfourn)     REFERENCES fournisseur(Numfourn),
  FOREIGN KEY (Numstatut)    REFERENCES etat_equipement(Numstatut),
  FOREIGN KEY (Numsalle)     REFERENCES salle(Numsalle)
) ENGINE=InnoDB;

CREATE TABLE demande_maintenance (
  Nummaint                 INT AUTO_INCREMENT PRIMARY KEY,
  description_problememaint TEXT NOT NULL,
  priorite                 ENUM('Basse','Moyenne','Haute') NOT NULL DEFAULT 'Moyenne',
  date_demandemaint        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Numequip                 INT NOT NULL,
  NumUsers                 INT NOT NULL,   -- employe qui signale
  Numstatut                INT NOT NULL,   -- statut courant
  FOREIGN KEY (Numequip)  REFERENCES equipement(Numequip),
  FOREIGN KEY (NumUsers)  REFERENCES employe(NumUsers),
  FOREIGN KEY (Numstatut) REFERENCES statut_maintenance(Numstatut)
) ENGINE=InnoDB;

-- [COMPLEMENT] historique exige par la section 8 du cahier des charges
CREATE TABLE intervention (
  Numinterv        INT AUTO_INCREMENT PRIMARY KEY,
  date_intervention DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  commentaire      TEXT,
  Nummaint         INT NOT NULL,
  NumUsers         INT NOT NULL,
  Numstatut        INT NOT NULL,  -- statut atteint
  FOREIGN KEY (Nummaint)  REFERENCES demande_maintenance(Nummaint) ON DELETE CASCADE,
  FOREIGN KEY (NumUsers)  REFERENCES employe(NumUsers),
  FOREIGN KEY (Numstatut) REFERENCES statut_maintenance(Numstatut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. Interactions adherent (heritage XT : AVIS / FAQ)
-- ---------------------------------------------------------------------
CREATE TABLE interaction_adherent (
  NumInteraction   INT AUTO_INCREMENT PRIMARY KEY,
  date_interaction DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NumUsers         INT NULL,
  type_interaction ENUM('AVIS','FAQ') NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE avis (
  NumInteraction INT PRIMARY KEY,
  noteavis       TINYINT NOT NULL CHECK (noteavis BETWEEN 1 AND 5),
  commentaire    TEXT,
  reponse        TEXT NULL,
  Numres         INT NULL,
  FOREIGN KEY (NumInteraction) REFERENCES interaction_adherent(NumInteraction) ON DELETE CASCADE,
  FOREIGN KEY (Numres)         REFERENCES reservation(Numres) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE faq (
  NumInteraction INT PRIMARY KEY,
  question       TEXT NOT NULL,
  reponse        TEXT,
  statut_faq     ENUM('En attente','Publiee','Archivee') NOT NULL DEFAULT 'En attente',
  categorie      VARCHAR(40) NOT NULL DEFAULT 'General',
  FOREIGN KEY (NumInteraction) REFERENCES interaction_adherent(NumInteraction) ON DELETE CASCADE
) ENGINE=InnoDB;

-- [COMPLEMENT] page contact
CREATE TABLE message_contact (
  Nummessage   INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(80)  NOT NULL,
  mail         VARCHAR(150) NOT NULL,
  sujet        VARCHAR(120) NOT NULL,
  message      TEXT NOT NULL,
  date_envoi   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  traite       BOOLEAN NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- [COMPLEMENT] journaux d'activite exiges pour l'administrateur
CREATE TABLE journal (
  Numjournal  INT AUTO_INCREMENT PRIMARY KEY,
  date_action DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  NumUsers    INT NULL,
  action      VARCHAR(60) NOT NULL,
  cible       VARCHAR(120),
  ip          VARCHAR(45),
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. Regle : un ADHERENT ne peut pas devenir EMPLOYE.
--    Un EMPLOYE peut en revanche souscrire un abonnement.
-- ---------------------------------------------------------------------
DELIMITER //
CREATE TRIGGER trg_employe_avant_adherent
BEFORE INSERT ON employe
FOR EACH ROW
BEGIN
  IF EXISTS (SELECT 1 FROM adherent WHERE NumUsers = NEW.NumUsers) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Un adherent ne peut pas etre recrute comme employe sur le meme compte.';
  END IF;
END//

CREATE TRIGGER trg_adherent_apres_embauche
BEFORE INSERT ON adherent
FOR EACH ROW
BEGIN
  DECLARE d DATE;
  SELECT Date_embaucheemp INTO d FROM employe WHERE NumUsers = NEW.NumUsers;
  IF d IS NOT NULL AND d > NEW.Date_inscriptionadh THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Date d inscription anterieure a la date d embauche.';
  END IF;
END//
DELIMITER ;

-- =====================================================================
--  DONNEES DE DEMONSTRATION
--  Les mots de passe sont poses par install.php (password_hash).
-- =====================================================================

INSERT INTO fonction (Numfonc, libellefonc) VALUES
 (1,'Coach sportif'), (2,'Accueil'), (3,'Technicien maintenance'), (4,'Responsable');

INSERT INTO statut (Numstatut, libelle_statut, type_statut) VALUES
 (1,'Disponible','EQUIPEMENT'), (2,'Maintenance','EQUIPEMENT'), (3,'Hors service','EQUIPEMENT'),
 (4,'Ouverte','MAINTENANCE'), (5,'Prise en charge','MAINTENANCE'),
 (6,'En attente de piece','MAINTENANCE'), (7,'Terminee','MAINTENANCE'),
 (8,'Actif','ADHERENT'), (9,'Suspendu','ADHERENT'), (10,'Expire','ADHERENT');

INSERT INTO etat_equipement (Numstatut) VALUES (1),(2),(3);
INSERT INTO statut_maintenance (Numstatut, ordre) VALUES (4,1),(5,2),(6,3),(7,4);
INSERT INTO statut_adherent (Numstatut) VALUES (8),(9),(10);

INSERT INTO utilisateur (NumUsers, nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers) VALUES
 -- Mot de passe de demonstration pour tous les comptes : apex2026
 (1,'Vasseur','Marion','admin@apex.fr','05 55 10 20 30','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (2,'Roux','Camille','camille.roux@apex.fr','06 41 77 30 02','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (3,'Anselme','Marc','marc.anselme@apex.fr','06 22 81 14 55','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (4,'Nabil','Sofiane','s.nabil@mail.fr','06 12 44 90 11','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (5,'Perrin','Lea','lea.perrin@mail.fr','06 55 08 23 74','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (6,'Diallo','Awa','awa.diallo@mail.fr','07 82 19 65 40','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (7,'Lemoine','Hugo','h.lemoine@mail.fr','06 30 55 12 88','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (8,'Ferreira','Ines','i.ferreira@mail.fr','07 60 41 09 27','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW');

INSERT INTO administrateur (NumUsers, matriculeadmin, niveau_droits, date_affectationadmin) VALUES
 (1,'ADM-001','TOTAL','2024-01-08');

INSERT INTO employe (NumUsers, matriculeemp, Date_embaucheemp, Numfonc) VALUES
 (2,'EMP-014','2023-09-01',1),
 (3,'EMP-021','2022-03-15',3);

-- Camille (employee) est aussi adherente : cumul autorise par l'heritage T
INSERT INTO adherent (NumUsers, matriculeadh, Date_inscriptionadh, Date_finadh, Numstatut) VALUES
 (2,'ADH-0063','2024-02-01','2026-12-31',8),
 (4,'ADH-0041','2023-11-12','2027-01-31',8),
 (5,'ADH-0058','2025-04-03','2026-10-15',8),
 (6,'ADH-0077','2024-06-30','2026-06-30',10),
 (7,'ADH-0081','2025-01-20','2027-03-01',9),
 (8,'ADH-0094','2025-09-05','2026-11-20',8);

INSERT INTO abonnement (Numabo, libelle_abo, description_abo, date_mise_en_service, actif_abo, type_abo) VALUES
 (1,'Freemium','Acces aux creneaux de base, tarif reduit sur justificatif.','2025-01-01',1,'FREEMIUM'),
 (2,'Basic','L offre annuelle standard, sans limite de reservation.','2024-01-01',1,'BASIC'),
 (3,'Abonnement +','Le mensuel enrichi : coaching inclus et acces illimite.','2025-06-01',1,'PLUS');

INSERT INTO freemium VALUES (1, 14.90, 50.00, 'Carte etudiante ou attestation France Travail', 8);
INSERT INTO basic    VALUES (2, 348.00, 12, 1);
INSERT INTO abonnement_plus VALUES (3, 49.90, 20.90, 2, 1);

INSERT INTO souscrire (NumUsers, Numabo, date_debut, date_fin, statut_souscription) VALUES
 (2,3,'2026-01-01','2026-12-31','En cours'),
 (4,2,'2026-02-01','2027-01-31','En cours'),
 (5,3,'2025-10-16','2026-10-15','En cours'),
 (6,1,'2025-07-01','2026-06-30','Terminee'),
 (7,1,'2026-03-02','2027-03-01','En cours'),
 (8,2,'2025-11-21','2026-11-20','En cours');

INSERT INTO paiement (montant, date_paiement, moyen_paiement, NumUsers, Numabo) VALUES
 (49.90,'2026-09-01 08:12:00','Prelevement',2,3),
 (348.00,'2026-02-01 14:40:00','CB',4,2),
 (49.90,'2026-09-01 08:12:00','Prelevement',5,3),
 (14.90,'2026-09-02 09:05:00','CB',7,1),
 (348.00,'2025-11-21 17:22:00','Virement',8,2);

INSERT INTO salle (Numsalle, nomsalle, capacitesalle, localisationsalle, superficiesalle, etatsalle) VALUES
 (1,'Salle Cardio',30,'RDC aile est',180.50,'Ouverte'),
 (2,'Studio 2',25,'1er etage',120.00,'Ouverte'),
 (3,'Salle Musculation',40,'RDC aile ouest',240.00,'Ouverte');

INSERT INTO activite (Numact, libelleact, duree_minutes, capacite_defaut, descriptionact) VALUES
 (1,'Musculation',NULL,20,'Acces libre a la salle de musculation.'),
 (2,'Fitness',60,20,'Cours collectif tonique, tous niveaux.'),
 (3,'Cross-training',60,15,'Circuit intensif en petits groupes.'),
 (4,'Yoga',60,15,'Postures et respiration, niveau debutant a intermediaire.'),
 (5,'Cycling',45,20,'Velo en musique, format court.'),
 (6,'Zumba',60,25,'Danse fitness sur rythmes latinos.');

INSERT INTO creneaux (Numcreneaux, date_heure_debut, date_heure_fin, capacite_max, Numsalle, Numact, NumUsers_coach) VALUES
 (1,'2026-09-15 18:00:00','2026-09-15 19:00:00',15,2,3,2),
 (2,'2026-09-15 18:00:00','2026-09-15 19:00:00',15,1,4,2),
 (3,'2026-09-15 19:15:00','2026-09-15 20:00:00',20,1,5,2),
 (4,'2026-09-15 20:00:00','2026-09-15 21:00:00',25,2,6,NULL),
 (5,'2026-09-16 12:15:00','2026-09-16 13:15:00',20,2,2,2),
 (6,'2026-09-16 18:30:00','2026-09-16 20:00:00',12,3,1,NULL),
 (7,'2026-09-17 18:00:00','2026-09-17 19:00:00',15,1,4,2),
 (8,'2026-09-17 19:15:00','2026-09-17 20:00:00',20,2,5,NULL);

INSERT INTO reservation (NumUsers, Numcreneaux, date_heure_reservation, statut_reservation) VALUES
 (4,1,'2026-09-12 10:02:00','Reservee'),
 (5,1,'2026-09-12 11:40:00','Reservee'),
 (8,1,'2026-09-13 09:15:00','Reservee'),
 (8,2,'2026-09-13 09:16:00','Reservee'),
 (2,3,'2026-09-14 20:31:00','Reservee'),
 (4,3,'2026-09-14 21:02:00','Reservee'),
 (5,4,'2026-09-10 12:00:00','Presente'),
 (8,5,'2026-09-14 08:45:00','Reservee'),
 (4,7,'2026-09-14 19:20:00','Reservee');

INSERT INTO fournisseur (Numfourn, nomfourn, telephonefourn, siret, mailfourn) VALUES
 (1,'Technogym France','01 45 22 87 00','40329281100024','contact@technogym.fr'),
 (2,'Life Fitness Distribution','01 60 11 44 90','51228734500017','sav@lifefitness.fr'),
 (3,'Hammer Sport','03 88 21 09 71','39944120800033','pro@hammer-sport.fr');

INSERT INTO type_equipement (Numtypeequip, libelletypeequip, descriptionequip) VALUES
 (1,'Cardio','Appareils d endurance.'),
 (2,'Force','Machines et bancs de musculation.'),
 (3,'Accessoire','Petit materiel.');

INSERT INTO equipement (Numequip, num_inventaire, nomequip, marque, modele, date_achatequip, prix_achatequip, date_derniere_maintenance, Numtypeequip, Numfourn, Numstatut, Numsalle) VALUES
 (1,'MAT-001','Velo elliptique','Technogym','Synchro 700','2023-04-11',4200.00,'2026-05-20',1,1,1,1),
 (2,'MAT-002','Velo elliptique','Technogym','Synchro 700','2023-04-11',4200.00,'2026-03-02',1,1,2,1),
 (3,'MAT-003','Banc de musculation','Hammer','Bermuda XT','2022-09-30',780.00,'2026-01-15',2,3,1,3),
 (4,'MAT-004','Tapis de course','Life Fitness','T5 Track','2021-06-18',5100.00,'2025-12-04',1,2,3,1),
 (5,'MAT-005','Rameur','Concept2','RowErg','2024-02-02',1250.00,'2026-06-11',1,2,1,1),
 (6,'MAT-006','Presse a cuisses','Hammer','Leg Press Pro','2022-11-08',2300.00,'2026-04-28',2,3,1,3),
 (7,'MAT-007','Tapis de sol','Domyos','Confort 10mm','2025-01-20',24.90,NULL,3,3,1,2);

INSERT INTO demande_maintenance (Nummaint, description_problememaint, priorite, date_demandemaint, Numequip, NumUsers, Numstatut) VALUES
 (1,'L ecran ne s allume plus','Haute','2026-09-15 08:30:00',2,2,5),
 (2,'Moteur bloque, tapis immobilise','Haute','2026-09-08 17:05:00',4,3,6);

INSERT INTO intervention (date_intervention, commentaire, Nummaint, NumUsers, Numstatut) VALUES
 ('2026-09-15 08:30:00','Demande ouverte par l accueil',1,2,4),
 ('2026-09-15 10:12:00','Diagnostic : nappe ecran a remplacer',1,3,5),
 ('2026-09-08 17:05:00','Demande ouverte',2,3,4),
 ('2026-09-09 09:00:00','Prise en charge, demontage du carter',2,3,5),
 ('2026-09-10 11:30:00','Moteur commande chez le fournisseur',2,3,6);

INSERT INTO interaction_adherent (NumInteraction, date_interaction, NumUsers, type_interaction) VALUES
 (1,'2026-09-11 19:40:00',4,'AVIS'),
 (2,'2026-09-12 08:10:00',5,'AVIS'),
 (3,'2026-08-20 10:00:00',NULL,'FAQ'),
 (4,'2026-08-20 10:01:00',NULL,'FAQ'),
 (5,'2026-08-20 10:02:00',NULL,'FAQ'),
 (6,'2026-08-20 10:03:00',NULL,'FAQ'),
 (7,'2026-08-20 10:04:00',NULL,'FAQ'),
 (8,'2026-08-20 10:05:00',NULL,'FAQ');

INSERT INTO avis (NumInteraction, noteavis, commentaire, Numres) VALUES
 (1,5,'Tres bon cours de cycling, rythme soutenu.',NULL),
 (2,4,'Studio un peu chaud mais coach au top.',NULL);

INSERT INTO faq (NumInteraction, question, reponse, statut_faq, categorie) VALUES
 (3,'Comment annuler une reservation ?','Depuis votre espace adherent, onglet Mes reservations, bouton Annuler. L annulation est possible jusqu a 2 heures avant le debut du creneau.','Publiee','Reservations'),
 (4,'Que se passe-t-il si un creneau est complet ?','Le bouton de reservation est desactive. Les places se liberent au fil des annulations, pensez a revenir consulter le planning.','Publiee','Reservations'),
 (5,'Puis-je changer d abonnement en cours d annee ?','Oui pour les offres mensuelles Freemium et Abonnement +. L offre Basic etant annuelle, le changement prend effet a l echeance.','Publiee','Abonnements'),
 (6,'Quels justificatifs pour le tarif Freemium ?','Une carte etudiante en cours de validite ou une attestation France Travail de moins de trois mois.','Publiee','Abonnements'),
 (7,'Un materiel est en panne, que faire ?','Signalez-le a l accueil. Un employe cree une demande de maintenance et l appareil est retire du service jusqu a reparation.','Publiee','Materiel'),
 (8,'Peut-on venir sans reserver ?','Pour la musculation en acces libre, oui. Les cours collectifs necessitent une reservation.','Publiee','General');

INSERT INTO message_contact (nom, mail, sujet, message, traite) VALUES
 ('Julien Marty','j.marty@mail.fr','Horaires du dimanche','Bonjour, la salle est-elle ouverte le dimanche matin ?',0);

INSERT INTO journal (date_action, NumUsers, action, cible, ip) VALUES
 ('2026-09-15 08:30:00',2,'CREATION_MAINTENANCE','Nummaint=1','192.168.1.24'),
 ('2026-09-15 07:55:00',1,'CONNEXION','admin@apex.fr','192.168.1.10');

-- ---------------------------------------------------------------------
-- 7. Vues utilitaires
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW v_creneau_places AS
SELECT c.Numcreneaux, c.date_heure_debut, c.date_heure_fin, c.capacite_max,
       a.libelleact, s.nomsalle,
       COUNT(r.Numres) AS places_prises,
       c.capacite_max - COUNT(r.Numres) AS places_restantes
FROM creneaux c
JOIN activite a ON a.Numact = c.Numact
JOIN salle s    ON s.Numsalle = c.Numsalle
LEFT JOIN reservation r ON r.Numcreneaux = c.Numcreneaux AND r.statut_reservation <> 'Annulee'
GROUP BY c.Numcreneaux;

CREATE OR REPLACE VIEW v_adherent_complet AS
SELECT u.NumUsers, u.nomUsers, u.prenomUsers, u.mailUsers, u.telUsers,
       ad.matriculeadh, ad.Date_inscriptionadh, ad.Date_finadh,
       st.libelle_statut AS statut_adherent,
       ab.libelle_abo, ab.type_abo
FROM adherent ad
JOIN utilisateur u ON u.NumUsers = ad.NumUsers
JOIN statut st     ON st.Numstatut = ad.Numstatut
LEFT JOIN souscrire so ON so.NumUsers = ad.NumUsers AND so.statut_souscription = 'En cours'
LEFT JOIN abonnement ab ON ab.Numabo = so.Numabo;
