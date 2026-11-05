<?php

declare(strict_types=1);

trait MessageConversationSchema
{
    private function assureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS message_adherent (
                IdMessage INT AUTO_INCREMENT PRIMARY KEY,
                NumUsers_emetteur INT NOT NULL,
                NumUsers_destinataire INT NOT NULL,
                IdMessage_parent INT NULL,
                message TEXT NOT NULL,
                date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                lu BOOLEAN NOT NULL DEFAULT 0,
                FOREIGN KEY (NumUsers_emetteur) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE,
                FOREIGN KEY (NumUsers_destinataire) REFERENCES utilisateur(NumUsers) ON DELETE CASCADE
            ) ENGINE=InnoDB'
        );
        $column = $this->pdo->query("SHOW COLUMNS FROM message_adherent LIKE 'IdMessage_parent'")->fetch();
        if (!$column) $this->pdo->exec('ALTER TABLE message_adherent ADD IdMessage_parent INT NULL AFTER NumUsers_destinataire');
    }
}
