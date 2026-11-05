<?php

trait ControleurGeneriquePart6
{
    private function executerActionPost(
        string $action,
        array &$formValues,
        ?array &$selectedKey
    ): void {
        if ($action === 'delete') {
            $this->model->delete(
                $this->pdo,
                $this->columns(),
                $this->decodeKey((string) ($_POST['key'] ?? ''))
            );
            flash('succes', 'Ligne supprimée.');
            return;
        }
        if (!in_array($action, ['create', 'update'], true)) {
            throw new InvalidArgumentException('Action non reconnue.');
        }

        $selectedKey = $action === 'update'
            ? $this->decodeKey((string) ($_POST['key'] ?? ''))
            : null;
        $formValues = is_array($_POST['champs'] ?? null) ? $_POST['champs'] : [];
        if ($selectedKey === null) {
            $this->model->create($this->pdo, $this->columns(), $formValues);
        } else {
            $this->model->update($this->pdo, $this->columns(), $formValues, $selectedKey);
        }
        flash('succes', $action === 'create' ? 'Ligne créée.' : 'Ligne modifiée.');
    }

    private function chargerEnregistrement(
        ?string &$error,
        array &$formValues,
        ?array &$selectedKey,
        string &$action
    ): ?array {
        if ($action === 'update' && $error !== null && $selectedKey !== null) {
            $record = $this->model->findById($this->pdo, $this->columns(), $selectedKey);
            if ($record !== null) {
                $formValues = array_merge($record, $formValues);
                $action = 'edit';
                return $record;
            }
        }
        if ($action !== 'edit' || $selectedKey !== null) {
            if ($action !== 'create') {
                $action = 'create';
            }
            return null;
        }

        try {
            $selectedKey = $this->decodeKey((string) ($_GET['key'] ?? ''));
            $record = $this->model->findById($this->pdo, $this->columns(), $selectedKey);
            if ($record === null) {
                throw new RuntimeException('Cette ligne n’existe plus.');
            }
            $formValues = $record;
            return $record;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
            $action = 'create';
            return null;
        }
    }
}
