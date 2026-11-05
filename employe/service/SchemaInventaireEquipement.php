<?php

declare(strict_types=1);

final class SchemaInventaireEquipement
{
    public static function ensure(PDO $pdo): void
    {
        $columns = [
            'image_equipement' => 'ALTER TABLE equipement ADD image_equipement VARCHAR(255) NULL AFTER modele',
            'stock_actuel' => 'ALTER TABLE equipement ADD stock_actuel INT UNSIGNED NOT NULL DEFAULT 0 AFTER image_equipement',
            'seuil_alerte' => 'ALTER TABLE equipement ADD seuil_alerte INT UNSIGNED NOT NULL DEFAULT 5 AFTER stock_actuel',
        ];
        foreach ($columns as $column => $sql) {
            if (!$pdo->query('SHOW COLUMNS FROM equipement LIKE ' . $pdo->quote($column))->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec($sql);
            }
        }

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS mouvement_stock (
                Nummouvement INT AUTO_INCREMENT PRIMARY KEY,
                Numequip INT NOT NULL,
                NumUsers INT NOT NULL,
                type_mouvement ENUM('ENTREE','SORTIE') NOT NULL,
                quantite INT UNSIGNED NOT NULL,
                date_mouvement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                commentaire VARCHAR(255) NULL,
                KEY idx_mouvement_equipement_date (Numequip, date_mouvement),
                FOREIGN KEY (Numequip) REFERENCES equipement(Numequip),
                FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers)
            ) ENGINE=InnoDB"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS commande_materiel (
                Numcommande INT AUTO_INCREMENT PRIMARY KEY,
                Numequip INT NOT NULL,
                NumUsers INT NOT NULL,
                quantite INT UNSIGNED NOT NULL,
                message VARCHAR(500) NULL,
                statut_commande ENUM('En attente','Approuvée','Refusée','Reçue') NOT NULL DEFAULT 'En attente',
                date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                date_decision DATETIME NULL,
                date_reception DATETIME NULL,
                NumUsers_admin INT NULL,
                KEY idx_commande_statut_date (statut_commande, date_demande),
                FOREIGN KEY (Numequip) REFERENCES equipement(Numequip),
                FOREIGN KEY (NumUsers) REFERENCES utilisateur(NumUsers),
                FOREIGN KEY (NumUsers_admin) REFERENCES utilisateur(NumUsers)
            ) ENGINE=InnoDB"
        );
    }
}
