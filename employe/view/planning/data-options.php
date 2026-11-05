<?php

/* ---- Listes du formulaire (tolérant sur les noms de colonnes) ------------ */
$pick = static function (array $row, array $keys, $default = null) {
    foreach ($keys as $key) {
        if (isset($row[$key]) && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return $default;
};
$premierTexte = static function (array $row): string {
    foreach ($row as $v) {
        if (is_string($v) && $v !== '' && !is_numeric($v)) {
            return $v;
        }
    }
    return '';
};

$salles = [];
foreach ((array) ($planningDonnees['salles'] ?? []) as $row) {
    if (!is_array($row) || !$row) {
        continue;
    }
    $id  = (int) $pick($row, ['Numsalle', 'num_salle', 'id'], reset($row));
    $nom = (string) $pick($row, ['nomsalle', 'nom', 'libelle', 'libellesalle'], $premierTexte($row));
    $cap = (int) $pick($row, ['capacite', 'capacite_max', 'capacite_salle'], 0);
    if ($id > 0 && $nom !== '') {
        $salles[] = ['id' => $id, 'nom' => $nom, 'capacite' => $cap];
    }
}

$coachs = [];
foreach ((array) ($planningDonnees['coachs'] ?? []) as $row) {
    if (!is_array($row) || !$row) {
        continue;
    }
    $id     = (int) $pick($row, ['NumUsers', 'Numcoach', 'id'], reset($row));
    $prenom = (string) $pick($row, ['prenomUsers', 'prenom'], '');
    $nom    = (string) $pick($row, ['nomUsers', 'nom'], '');
    $label  = trim($prenom . ' ' . $nom);
    if ($label === '') {
        $label = (string) $pick($row, ['nom_complet', 'libelle'], $premierTexte($row));
    }
    if ($id > 0 && $label !== '') {
        $coachs[] = ['id' => $id, 'prenom' => $prenom !== '' ? $prenom : $label, 'label' => $label];
    }
}

$titres = [];
foreach ((array) ($planningDonnees['titres'] ?? []) as $t) {
    $v = is_array($t) ? (string) (reset($t) ?: '') : (string) $t;
    if ($v !== '') {
        $titres[] = $v;
    }
}
$titres = array_values(array_unique($titres));

$cfg = [
    'eventsUrl' => $PE['events_url'],
    'types' => $types,
    'createAction' => $PE['action_creer'],
    'slotMinTime' => !empty($accueil) ? '08:00:00' : '07:00:00',
    'slotMaxTime' => !empty($accueil) ? '18:00:00' : '21:00:00',
];
