-- Migration for AdminSetup.php moved SQL statements
-- These were originally in admin/controller/AdminSetup.php
-- Create intervention table (associated with 'equipements' tab)
CREATE TABLE IF NOT EXISTS intervention (
    Numintervention INT AUTO_INCREMENT PRIMARY KEY,
    date_intervention DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire TEXT NULL,
    Nummaint INT NOT NULL,
    NumUsers INT NOT NULL,
    Numstatut INT NOT NULL,
    FOREIGN KEY (Nummaint) REFERENCES demande_maintenance(Nummaint) ON DELETE CASCADE,
    FOREIGN KEY (NumUsers) REFERENCES employe(NumUsers),
    FOREIGN KEY (Numstatut) REFERENCES statut_maintenance(Numstatut)
) ENGINE=InnoDB;

-- Add NumUsers_technicien column to demande_maintenance (associated with 'salles' tab)
-- This is conditional: only if column doesn't already exist
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'demande_maintenance' AND column_name = 'NumUsers_technicien'
    ) THEN
        ALTER TABLE demande_maintenance ADD COLUMN NumUsers_technicien INT NULL AFTER NumUsers;
    END IF;
END $$;

-- Modify statut_souscription ENUM in souscrire table (associated with 'abonnements' tab)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'souscrire' AND column_name = 'statut_souscription'
    ) THEN
        IF NOT (
            SELECT CASE WHEN TYPE = 'enum' THEN EXISTS(
                SELECT 1 FROM pg_enum WHERE enumlabel = 'En cours'
            ) ELSE true END 
            FROM information_schema.columns 
            WHERE table_name = 'souscrire' AND column_name = 'statut_souscription'
        ) THEN
            ALTER TABLE souscrire MODIFY statut_souscription ENUM('En cours','Suspendue','Terminee','Resiliee') NOT NULL DEFAULT 'En cours';
        END IF;
    END IF;
END $$;

-- Ensure sexe column in utilisateur table (associated with 'adherents' tab)
-- Moved to Utilisateur::assurerColonneSexe() model call

-- Ensure commune column in employe table (associated with 'adherents' tab)
-- Moved to Utilisateur::assurerColonneCommuneEmploye() model call

-- Insert default coach functions (associated with 'coachs' tab)
-- These are handled by ActiviteCatalogue::assurer() and Utilisateur::toutesLesFonctions()