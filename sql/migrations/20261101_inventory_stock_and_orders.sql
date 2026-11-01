SET @equipement_image_absente := (
  SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipement' AND COLUMN_NAME = 'image_equipement'
);
SET @equipement_image_sql := IF(
  @equipement_image_absente,
  'ALTER TABLE equipement ADD image_equipement VARCHAR(255) NULL AFTER modele',
  'SELECT 1'
);
PREPARE equipement_image_stmt FROM @equipement_image_sql;
EXECUTE equipement_image_stmt;
DEALLOCATE PREPARE equipement_image_stmt;

SET @equipement_stock_absente := (
  SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipement' AND COLUMN_NAME = 'stock_actuel'
);
SET @equipement_stock_sql := IF(
  @equipement_stock_absente,
  'ALTER TABLE equipement ADD stock_actuel INT UNSIGNED NOT NULL DEFAULT 0 AFTER image_equipement',
  'SELECT 1'
);
PREPARE equipement_stock_stmt FROM @equipement_stock_sql;
EXECUTE equipement_stock_stmt;
DEALLOCATE PREPARE equipement_stock_stmt;

SET @equipement_seuil_absent := (
  SELECT COUNT(*) = 0 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipement' AND COLUMN_NAME = 'seuil_alerte'
);
SET @equipement_seuil_sql := IF(
  @equipement_seuil_absent,
  'ALTER TABLE equipement ADD seuil_alerte INT UNSIGNED NOT NULL DEFAULT 5 AFTER stock_actuel',
  'SELECT 1'
);
PREPARE equipement_seuil_stmt FROM @equipement_seuil_sql;
EXECUTE equipement_seuil_stmt;
DEALLOCATE PREPARE equipement_seuil_stmt;

CREATE TABLE IF NOT EXISTS mouvement_stock (
  Nummouvement      INT AUTO_INCREMENT PRIMARY KEY,
  Numequip          INT NOT NULL,
  NumUsers          INT NOT NULL,
  type_mouvement    ENUM('ENTREE','SORTIE') NOT NULL,
  quantite          INT UNSIGNED NOT NULL,
  date_mouvement    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  commentaire       VARCHAR(255),
  KEY idx_mouvement_equipement_date (Numequip, date_mouvement),
  FOREIGN KEY (Numequip) REFERENCES equipement(Numequip),
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS commande_materiel (
  Numcommande      INT AUTO_INCREMENT PRIMARY KEY,
  Numequip         INT NOT NULL,
  NumUsers         INT NOT NULL,
  quantite         INT UNSIGNED NOT NULL,
  message          VARCHAR(500),
  statut_commande  ENUM('En attente','Approuvée','Refusée','Reçue') NOT NULL DEFAULT 'En attente',
  date_demande     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_decision    DATETIME NULL,
  date_reception   DATETIME NULL,
  NumUsers_admin   INT NULL,
  KEY idx_commande_statut_date (statut_commande, date_demande),
  FOREIGN KEY (Numequip) REFERENCES equipement(Numequip),
  FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers),
  FOREIGN KEY (NumUsers_admin) REFERENCES utilisateur(NumUsers)
) ENGINE=InnoDB;
