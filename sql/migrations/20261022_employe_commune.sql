ALTER TABLE employe
  ADD COLUMN id_commune INT NULL,
  ADD CONSTRAINT fk_employe_commune FOREIGN KEY (id_commune) REFERENCES commune (id_commune);
