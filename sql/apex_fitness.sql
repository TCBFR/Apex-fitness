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
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE qr_code (
  Numqr              INT AUTO_INCREMENT PRIMARY KEY,
  code_qr            VARCHAR(120) NOT NULL UNIQUE,
  date_generation_qr DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_expiration_qr DATETIME NOT NULL,
  actif_qr           BOOLEAN NOT NULL DEFAULT 1,
  NumUsers           INT NOT NULL,
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
  description       TEXT,
  niveau            VARCHAR(20),
  date_heure_debut  DATETIME NOT NULL,
  date_heure_fin    DATETIME NOT NULL,
  capacite_max      INT NOT NULL,
  date_debutprogent DATE,
  date_finprogent   DATE,
  Numsalle          INT NOT NULL,
  NumUsers          INT NOT NULL,          -- employe (coach)
  UNIQUE KEY uk_salle_debut (Numsalle, date_heure_debut),
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
  Numequip                  INT NOT NULL,
  Numstatut                 INT NOT NULL,
  FOREIGN KEY (NumUsers)  REFERENCES employe(NumUsers),
  FOREIGN KEY (Numequip)  REFERENCES equipement(Numequip),
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
  Numres         INT NOT NULL UNIQUE,
  FOREIGN KEY (NumInteraction) REFERENCES interaction_adherent(NumInteraction) ON DELETE CASCADE,
  FOREIGN KEY (Numres)         REFERENCES reservation(Numres) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE faq (
  NumInteraction INT PRIMARY KEY,
  question       TEXT NOT NULL,
  reponse        TEXT,
  statut_faq     VARCHAR(20) NOT NULL DEFAULT 'En attente',
  FOREIGN KEY (NumInteraction) REFERENCES interaction_adherent(NumInteraction) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
--  DONNEES DE DEMO (mot de passe de tous les comptes : apex2026)
-- =====================================================================
INSERT INTO utilisateur (NumUsers, nomUsers, prenomUsers, mailUsers, telUsers, passwordUsers) VALUES
 (1,'Vasseur','Marion','admin@apex.fr','05 55 10 20 30','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (2,'Roux','Camille','camille.roux@apex.fr','06 41 77 30 02','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjsWqhiHynF8CCPQwSoYd7EW'),
 (3,'Anselme','Marc','marc.anselme@apex.fr','06 22 81 14 55','$2y$10$VB0rJNYf4.JZSfYc/etFueMu1IA.PHjs