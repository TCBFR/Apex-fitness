<?php
// Champs « Commune » (autocomplétion) et « Code postal » (rempli automatiquement).
// Seul id_commune est envoyé au serveur ; le reste n'est qu'un affichage.
// La page qui inclut ce fichier charge assets/css/commune.css et assets/js/commune.js.
$commune = $commune ?? ['id_commune' => '', 'nom_commune' => '', 'code_postal' => ''];
?>
<div class="field commune-recherche">
    <label class="field-label" for="commune">Commune</label>
    <input class="field-value" type="text" id="commune"
           value="<?= e((string) $commune['nom_commune']) ?>"
           data-url="<?= e(url('commune/recherche.php')) ?>"
           autocomplete="off" placeholder="Saisir au moins 2 caractères" required>
    <div class="commune-resultats" id="commune-resultats" hidden></div>
    <input type="hidden" name="id_commune" id="id_commune" value="<?= e((string) $commune['id_commune']) ?>">
    <p id="commune-message" aria-live="polite"></p>
</div>
<div class="field">
    <label class="field-label" for="code_postal">Code postal</label>
    <input class="field-value" type="text" id="code_postal"
           value="<?= e((string) $commune['code_postal']) ?>" readonly tabindex="-1">
</div>