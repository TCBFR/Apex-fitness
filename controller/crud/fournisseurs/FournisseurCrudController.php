<?php

require_once __DIR__ . '/../../../Model/Fournisseur.php';

final class FournisseurCrudController
{
    private Fournisseur $fournisseur;

    public function __construct(?Fournisseur $fournisseur = null)
    {
        $this->fournisseur = $fournisseur ?? new Fournisseur();
    }

    public function getAll(): array
    {
        return $this->fournisseur->tous();
    }

    public function create(array $data): void
    {
        $this->save($data);
    }

    public function update(array $data, int $id): void
    {
        $this->save($data, $id);
    }

    public function delete(int $id): void
    {
        $this->fournisseur->supprimer($id);
    }

    private function save(array $data, ?int $id = null): void
    {
        $data = [
            'nom' => trim($data['nom'] ?? ''),
            'telephone' => trim($data['telephone'] ?? ''),
            'siret' => trim($data['siret'] ?? ''),
            'mail' => trim($data['mail'] ?? ''),
        ];
        if ($data['nom'] === '') {
            throw new InvalidArgumentException('Le nom du fournisseur est obligatoire.');
        }
        if ($data['mail'] !== '' && !filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L’e-mail du fournisseur est invalide.');
        }
        $this->fournisseur->enregistrer($data, $id);
    }
}
