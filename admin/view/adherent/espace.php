<?php
/**
 * Espace adherent - Page d'espace pour les adhérents
 * Organized MVC structure for Apex Fitness application
 */
?>
<section class="adherents-page">
    <header class="adherents-intro">
        <div>
            <h3>Répertoire des adhérents</h3>
            <p>Consultez et gérez les informations des membres.</p>
        </div>
    </header>

    <div class="adherents-layout">
        <div class="adherents-list-wrap">
            <label class="adherents-search">
                <span aria-hidden="true">⌕</span>
                <input type="search" id="recherche-adherent" placeholder="Rechercher un nom, un e-mail ou une commune" autocomplete="off">
            </label>
            <p class="adherents-hint">Cliquez sur un adhérent pour consulter sa fiche.</p>

            <div class="adherents-grid">
                <!-- Les adhérents seront affichés ici par le contrôleur -->
            </div>
        </div>
    </div>
</section>