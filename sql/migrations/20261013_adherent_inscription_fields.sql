ALTER TABLE adherent
  ADD COLUMN date_naissance DATE NULL,
  ADD COLUMN ville VARCHAR(100) NULL,
  ADD COLUMN code_postal CHAR(5) NULL,
  ADD COLUMN adresse_postale VARCHAR(255) NULL;
