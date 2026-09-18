<?php

require_once __DIR__ . '/Model.php';

final class Fournisseur extends Model
{
    public function tous(): array
    {
        return $this->pdo->query('SELECT * FROM fournisseur ORDER BY nomfourn')->fetchAll();
    }

    public function enregistrer(array $donnees, ?int $id = null): void
    {
        if ($id === null) {
            $requete = $this->pdo->prepare(
                'INSERT INTO fournisseur (nomfourn, telephonefourn, siret, mailfourn) VALUES (?, ?, ?, ?)'
            );
            $requete->execute([$donnees['nom'], $donnees['telephone'], $donnees['siret'], $donnees['mail']]);
            return;
        }

        $requete = $this->pdo->prepare(
            'UPDATE fournisseur SET nomfourn = ?, telephonefourn = ?, siret = ?, mailfourn = ? WHERE Numfourn = ?'
        );
        $requete->execute([$donnees['nom'], $donnees['telephone'], $donnees['siret'], $donnees['mail'], $id]);
    }

    public function supprimer(int $id): void
    {
        $requete = $this->pdo->prepare('DELETE FROM fournisseur WHERE Numfourn = ?');
        $requete->execute([$id]);
    }
}
