ALTER TABLE souscrire
  MODIFY statut_souscription ENUM('En cours','Suspendue','Terminee','Resiliee')
  NOT NULL DEFAULT 'En cours';
