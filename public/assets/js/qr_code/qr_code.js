(function () {
    'use strict';
    var form = document.getElementById('qr-form');
    if (!form) { return; }

    var input = document.getElementById('qr-valeur');
    var box = document.getElementById('qr-resultat');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var url = document.body.dataset.urlVerifier;

    function afficher(data) {
        var m = data.membre || {};
        document.getElementById('qr-badge').textContent = data.statut || 'ERREUR';
        document.getElementById('qr-badge').className = 'badge ' + (data.ok ? 'badge--active' : 'badge--danger');
        document.getElementById('qr-message').textContent = data.message || '';
        document.getElementById('qr-nom').textContent = m.nom_complet || '—';
        document.getElementById('qr-matricule').textContent = m.matricule || '—';
        document.getElementById('qr-formule').textContent = m.formule || '—';
        box.hidden = false;
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var valeur = input.value.trim();
        if (!valeur) { return; }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrf },
            body: 'valeur=' + encodeURIComponent(valeur),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(afficher)
            .catch(function () { afficher({ ok: false, statut: 'ERREUR', message: 'Erreur réseau.' }); })
            .finally(function () { input.value = ''; input.focus(); });
    });
})();