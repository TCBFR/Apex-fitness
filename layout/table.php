<?php
$viewData = $crud ?? null;
if (!is_array($viewData) || !isset($viewData['table'])) {
    throw new LogicException('Les données CRUD doivent être fournies par le contrôleur.');
}
$crud = $viewData;
$titrePage = 'Gestion : ' . str_replace('_', ' ', $crud['table']);
$pageActive = 'crud';
$route = url($crud['table'] . '/index.php');
$primary = array_column(array_filter($crud['columns'], static fn(array $column): bool => $column['COLUMN_KEY'] === 'PRI'), 'COLUMN_NAME');
$visibleColumns = array_values(array_filter(
    $crud['columns'],
    static fn(array $column): bool => stripos($column['COLUMN_NAME'], 'password') === false
));
require __DIR__ . '/header.php';
?>
<section class="crud-page wrap">
    <div class="crud-heading">
        <div>
            <p class="crud-eyebrow"><a href="<?= e(url('admin/tables.php')) ?>">Administration des tables</a></p>
            <h1><?= e($titrePage) ?></h1>
        </div>
        <a class="btn btn-sm" href="<?= e(url('admin/tables.php')) ?>">Toutes les tables</a>
    </div>

    <?php if ($crud['error'] !== null): ?>
        <p class="flash flash-erreur" role="alert"><?= e($crud['error']) ?></p>
    <?php endif; ?>

    <div class="crud-layout">
        <section class="crud-form-section" aria-labelledby="crud-form-title">
            <h2 id="crud-form-title"><?= $crud['action'] === 'edit' ? 'Modifier la ligne' : 'Nouvelle ligne' ?></h2>
            <form method="post" action="<?= e($route) ?>" class="crud-form">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="<?= $crud['action'] === 'edit' ? 'update' : 'create' ?>">
                <?php if ($crud['action'] === 'edit'): ?>
                    <input type="hidden" name="key" value="<?= e($crud['key']) ?>">
                <?php endif; ?>
                <?php foreach ($crud['columns'] as $column): ?>
                    <?php
                    $name = $column['COLUMN_NAME'];
                    $extra = strtolower($column['EXTRA']);
                    if (str_contains($extra, 'auto_increment') || str_contains($extra, 'generated') ||
                        ($crud['action'] === 'edit' && in_array($name, $primary, true))) {
                        continue;
                    }
                    $value = (string) ($crud['values'][$name] ?? '');
                    $isPassword = stripos($name, 'password') !== false;
                    $required = $column['IS_NULLABLE'] === 'NO' && $column['COLUMN_DEFAULT'] === null &&
                        !($crud['action'] === 'edit' && $isPassword);
                    $foreign = $crud['foreignOptions'][$name] ?? null;
                    $isBoolean = $column['DATA_TYPE'] === 'tinyint' && $column['COLUMN_TYPE'] === 'tinyint(1)';
                    $type = strtolower($column['DATA_TYPE']);
                    $inputType = match ($type) {
                        'date' => 'date',
                        'datetime', 'timestamp' => 'datetime-local',
                        'time' => 'time',
                        'int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'decimal', 'float', 'double' => 'number',
                        default => 'text',
                    };
                    if ($inputType === 'datetime-local') {
                        $value = str_replace(' ', 'T', substr($value, 0, 16));
                    }
                    ?>
                    <label class="crud-field" for="field-<?= e($name) ?>">
                        <span><?= e(str_replace('_', ' ', $name)) ?><?= $required ? ' *' : '' ?></span>
                        <?php if ($isPassword): ?>
                            <input id="field-<?= e($name) ?>" type="password" name="champs[<?= e($name) ?>]"
                                value="" <?= $crud['action'] === 'create' ? 'required minlength="8"' : '' ?> autocomplete="new-password">
                        <?php elseif ($isBoolean): ?>
                            <input id="field-<?= e($name) ?>" type="checkbox" name="champs[<?= e($name) ?>]" value="1" <?= $value === '1' ? 'checked' : '' ?>>
                        <?php elseif ($foreign !== null): ?>
                            <select id="field-<?= e($name) ?>" name="champs[<?= e($name) ?>]" <?= $required ? 'required' : '' ?>>
                                <?php if (!$required): ?><option value="">Aucune sélection</option><?php endif; ?>
                                <?php foreach ($foreign['options'] as $option): ?>
                                    <option value="<?= e($option['value']) ?>" <?= $value === $option['value'] ? 'selected' : '' ?>>
                                        <?= e($option['label']) ?> (<?= e($option['value']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($column['ENUM_OPTIONS']): ?>
                            <select id="field-<?= e($name) ?>" name="champs[<?= e($name) ?>]" <?= $required ? 'required' : '' ?>>
                                <?php if (!$required): ?><option value="">Aucune sélection</option><?php endif; ?>
                                <?php foreach ($column['ENUM_OPTIONS'] as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $value === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif (in_array($type, ['text', 'mediumtext', 'longtext', 'tinytext'], true)): ?>
                            <textarea id="field-<?= e($name) ?>" name="champs[<?= e($name) ?>]" rows="3" <?= $required ? 'required' : '' ?>><?= e($value) ?></textarea>
                        <?php else: ?>
                            <input id="field-<?= e($name) ?>" type="<?= e($inputType) ?>" name="champs[<?= e($name) ?>]"
                                value="<?= e($value) ?>" <?= $required ? 'required' : '' ?>
                                <?= $inputType === 'number' ? 'step="any"' : '' ?>>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
                <?php if ($crud['action'] === 'edit' && array_diff($primary, array_column($visibleColumns, 'COLUMN_NAME'))): ?>
                    <p class="crud-note">Les champs de clé primaire ne sont pas modifiables.</p>
                <?php endif; ?>
                <div class="crud-form-actions">
                    <button class="btn" type="submit"><?= $crud['action'] === 'edit' ? 'Enregistrer' : 'Créer' ?></button>
                    <?php if ($crud['action'] === 'edit'): ?>
                        <a class="crud-cancel" href="<?= e($route) ?>">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="crud-list-section" aria-labelledby="crud-list-title">
            <div class="crud-list-heading">
                <h2 id="crud-list-title">Enregistrements</h2>
                <span><?= count($crud['rows']) ?> affiché<?= count($crud['rows']) > 1 ? 's' : '' ?>, maximum 250</span>
            </div>
            <div class="crud-table-scroll">
                <table class="crud-table">
                    <thead><tr>
                        <?php foreach ($visibleColumns as $column): ?>
                            <th scope="col"><?= e($column['COLUMN_NAME']) ?></th>
                        <?php endforeach; ?>
                        <th scope="col"><span class="sr">Actions</span></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($crud['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($visibleColumns as $column): ?>
                                    <?php $cell = (string) ($row[$column['COLUMN_NAME']] ?? ''); ?>
                                    <td><?= e(mb_strlen($cell) > 100 ? mb_substr($cell, 0, 97) . '...' : $cell) ?></td>
                                <?php endforeach; ?>
                                <td class="crud-row-actions">
                                    <a href="<?= e($route) ?>?action=edit&amp;key=<?= e($row['_encoded_key']) ?>">Modifier</a>
                                    <form method="post" action="<?= e($route) ?>" onsubmit="return confirm('Supprimer cette ligne ?')">
                                        <?= champ_csrf() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="key" value="<?= e($row['_encoded_key']) ?>">
                                        <button class="crud-delete" type="submit">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$crud['rows']): ?>
                            <tr><td colspan="<?= count($visibleColumns) + 1 ?>" class="crud-empty">Aucun enregistrement.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>