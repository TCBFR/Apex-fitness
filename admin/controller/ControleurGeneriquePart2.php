<?php

trait ControleurGeneriquePart2
{
    public function traiter(): array
    {
        $error = null;
        $formValues = [];
        $selectedKey = null;
        $action = (string) ($_GET['action'] ?? 'create');

        $this->traiterPost($error, $formValues, $selectedKey, $action);
        $record = $this->chargerEnregistrement($error, $formValues, $selectedKey, $action);
        $rows = $this->model->findAll($this->pdo, $this->columns());
        foreach ($rows as &$row) {
            $row['_encoded_key'] = $this->encodeKey($row);
        }
        unset($row);

        return [
            'table' => $this->table,
            'columns' => $this->columns(),
            'foreignOptions' => $this->foreignOptions(),
            'rows' => $rows,
            'action' => $action,
            'key' => $selectedKey === null ? '' : $this->encodeKey($selectedKey),
            'values' => $formValues,
            'error' => $error,
        ];
    }

    private function traiterPost(
        ?string &$error,
        array &$formValues,
        ?array &$selectedKey,
        string &$action
    ): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        verifie_csrf();
        $action = (string) ($_POST['action'] ?? '');
        try {
            $this->executerActionPost($action, $formValues, $selectedKey);
            redirige($this->table . '/index.php');
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
