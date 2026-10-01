<?php

declare(strict_types=1);

require_once __DIR__ . '/../../Model/CrudTableModel.php';

class TableCrudController
{
    public function __construct(private CrudTableModel $model)
    {
    }

    public function traiter(): array
    {
        $error = null;
        $formValues = [];
        $selectedKey = null;
        $record = null;
        $action = (string) ($_GET['action'] ?? 'create');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verifie_csrf();
            $action = (string) ($_POST['action'] ?? '');
            try {
                if ($action === 'delete') {
                    $this->model->delete($this->model->decodeKey((string) ($_POST['key'] ?? '')));
                    flash('succes', 'Ligne supprimée.');
                } elseif ($action === 'create' || $action === 'update') {
                    $selectedKey = $action === 'update'
                        ? $this->model->decodeKey((string) ($_POST['key'] ?? ''))
                        : null;
                    $formValues = is_array($_POST['champs'] ?? null) ? $_POST['champs'] : [];
                    $this->model->save($formValues, $selectedKey);
                    flash('succes', $action === 'create' ? 'Ligne créée.' : 'Ligne modifiée.');
                } else {
                    throw new InvalidArgumentException('Action non reconnue.');
                }
                redirige($this->model->tableName() . '/index.php');
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        if ($action === 'update' && $error !== null && $selectedKey !== null) {
            $record = $this->model->find($selectedKey);
            if ($record !== null) {
                $formValues = array_merge($record, $formValues);
                $action = 'edit';
            }
        }

        if ($action === 'edit' && $selectedKey === null) {
            try {
                $selectedKey = $this->model->decodeKey((string) ($_GET['key'] ?? ''));
                $record = $this->model->find($selectedKey);
                if ($record === null) {
                    throw new RuntimeException('Cette ligne n’existe plus.');
                }
                $formValues = $record;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
                $action = 'create';
            }
        } elseif ($action !== 'create') {
            $action = 'create';
        }

        return [
            'table' => $this->model->tableName(),
            'columns' => $this->model->columns(),
            'foreignOptions' => $this->model->foreignOptions(),
            'rows' => $this->model->rows(),
            'action' => $action,
            'key' => $selectedKey === null ? '' : $this->model->encodeKey($selectedKey),
            'values' => $formValues,
            'error' => $error,
            'model' => $this->model,
        ];
    }
}