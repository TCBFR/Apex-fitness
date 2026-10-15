CREATE TABLE IF NOT EXISTS liste_attente (
  Numattente INT AUTO_INCREMENT PRIMARY KEY,
  NumUsers INT NOT NULL,
  Numactivite INT NOT NULL,
  date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_attente_adherent_activite (NumUsers, Numactivite),
  KEY idx_attente_ordre (Numactivite, date_inscription),
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE,
  FOREIGN KEY (Numactivite) REFERENCES activite(Numactivite) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_adherent (
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
