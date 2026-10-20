CREATE TABLE IF NOT EXISTS objectif_quotidien (
  NumUsers INT NOT NULL,
  date_objectif DATE NOT NULL,
  objectif VARCHAR(80) NOT NULL,
  atteint TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (NumUsers, date_objectif, objectif),
  CONSTRAINT fk_objectif_quotidien_adherent
    FOREIGN KEY (NumUsers) REFERENCES adherent(NumUsers) ON DELETE CASCADE
) ENGINE=InnoDB;
