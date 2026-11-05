<?php

declare(strict_types=1);

trait AdherentEspaceProfileUpdate
{
    public function updateProfile(int $id, array $fields): void
    {
        $allowed = ['nom'=>'nomUsers', 'prenom'=>'prenomUsers', 'email'=>'mailUsers', 'telephone'=>'telUsers'];
        $set = [];
        $values = [];
        $adherentSet = [];
        $adherentValues = [];
        foreach ($allowed as $key=>$column) {
            if (!array_key_exists($key, $fields)) continue;
            $value = trim((string)$fields[$key]);
            if (in_array($key, ['nom','prenom'], true) && ($value === '' || mb_strlen($value)>60)) {
                throw new InvalidArgumentException('Le nom et le prénom sont obligatoires (60 caractères maximum).');
            }
            if ($key === 'email' && ($value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL) || mb_strlen($value)>150)) {
                throw new InvalidArgumentException('Adresse email invalide.');
            }
            if ($key === 'telephone' && $value !== '') {
                if (!preg_match('/^[0-9 ]+$/', $value)) {
                    throw new InvalidArgumentException('Saisissez uniquement les chiffres du numéro de téléphone.');
                }
                $digits = str_replace(' ', '', $value);
                if (strlen($digits) < 6 || strlen($digits) > 10) {
                    throw new InvalidArgumentException('Le numéro doit contenir entre 6 et 10 chiffres.');
                }
                $value = trim(chunk_split($digits, 2, ' '));
            }
            $set[] = "`$column` = ?";
            $values[] = $value === '' ? null : $value;
        }
        $adherentColumns = $this->pdo->query('SHOW COLUMNS FROM adherent')->fetchAll(PDO::FETCH_COLUMN);
        if (array_key_exists('id_commune', $fields)) {
            $communeId = filter_var($fields['id_commune'], FILTER_VALIDATE_INT);
            if (!$communeId || $communeId < 1) {
                throw new InvalidArgumentException('Sélectionnez une commune dans la liste proposée.');
            }
            $communeQuery = $this->pdo->prepare('SELECT nom_commune, code_postal FROM commune WHERE id_commune = ?');
            $communeQuery->execute([$communeId]);
            $commune = $communeQuery->fetch(PDO::FETCH_ASSOC);
            if (!$commune) throw new InvalidArgumentException('La commune sélectionnée est introuvable.');
            if (!in_array('id_commune', $adherentColumns, true)) {
                throw new RuntimeException('La base de données ne permet pas encore d’enregistrer la commune.');
            }
            $adherentSet[] = '`id_commune` = ?';
            $adherentValues[] = $communeId;
            if (in_array('ville', $adherentColumns, true)) {
                $adherentSet[] = '`ville` = ?';
                $adherentValues[] = $commune['nom_commune'];
            }
            if (in_array('code_postal', $adherentColumns, true)) {
                $adherentSet[] = '`code_postal` = ?';
                $adherentValues[] = $commune['code_postal'];
            }
        }
        if (array_key_exists('adresse_postale', $fields)) {
            $address = trim((string) $fields['adresse_postale']);
            if ($address === '' || mb_strlen($address) > 255) {
                throw new InvalidArgumentException('L’adresse est obligatoire (255 caractères maximum).');
            }
            if (!in_array('adresse_postale', $adherentColumns, true)) {
                throw new RuntimeException('La base de données ne permet pas encore d’enregistrer l’adresse.');
            }
            $adherentSet[] = '`adresse_postale` = ?';
            $adherentValues[] = $address;
        }
        if (!$set && !$adherentSet) throw new InvalidArgumentException('Aucune information à modifier.');
        if (array_key_exists('email', $fields)) {
            $q=$this->pdo->prepare('SELECT 1 FROM utilisateur WHERE mailUsers=? AND NumUsers<>?');
            $q->execute([trim((string)$fields['email']), $id]);
            if ($q->fetchColumn()) throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }
        $this->pdo->beginTransaction();
        try {
            if ($set) {
                $values[] = $id;
                $this->pdo->prepare('UPDATE utilisateur SET ' . implode(', ', $set) . ' WHERE NumUsers=?')->execute($values);
            }
            if ($adherentSet) {
                $adherentValues[] = $id;
                $this->pdo->prepare('UPDATE adherent SET ' . implode(', ', $adherentSet) . ' WHERE NumUsers=?')->execute($adherentValues);
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
