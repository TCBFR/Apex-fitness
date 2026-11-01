CREATE TABLE IF NOT EXISTS acceptation_contrat_abonnement (
  Numacceptation       INT AUTO_INCREMENT PRIMARY KEY,
  NumUsers             INT NOT NULL,
  Numabo               INT NOT NULL,
  Numpaiement          INT NOT NULL UNIQUE,
  version_contrat      VARCHAR(30) NOT NULL,
  contenu_contrat      LONGTEXT NOT NULL,
  date_acceptation     DATETIME NOT NULL,
  FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers),
  FOREIGN KEY (Numabo) REFERENCES abonnement(Numabo),
  FOREIGN KEY (Numpaiement) REFERENCES paiement(Numpaiement)
) ENGINE=InnoDB;
