<?php /** Page d'accueil : contrôle d'un QR code adhérent. */ ?>
<main class="gate" data-testid="qr-verif-page">
    <div class="gate__card card">
        <p class="eyebrow">Accueil</p>
        <h1 class="gate__title">Contrôle d'accès</h1>
        <p class="gate__text">Scannez la carte membre (lecteur QR) ou collez la valeur du code, puis validez.</p>

        <form id="qr-form" autocomplete="off">
            <input type="text" id="qr-valeur" name="valeur" maxlength="150" autofocus
                   placeholder="ATHLETIX|…" aria-label="Valeur du QR code" data-testid="qr-input">
            <button type="submit" class="btn btn--primary">Vérifier</button>
        </form>

        <div id="qr-resultat" class="gate__facts" hidden aria-live="polite" data-testid="qr-result">
            <p><span class="badge" id="qr-badge"></span></p>
            <p id="qr-message"></p>
            <dl>
                <div><dt>Adhérent</dt><dd id="qr-nom"></dd></div>
                <div><dt>Matricule</dt><dd id="qr-matricule"></dd></div>
                <div><dt>Formule</dt><dd id="qr-formule"></dd></div>
            </dl>
        </div>
    </div>
</main>