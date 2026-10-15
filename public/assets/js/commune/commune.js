// Autocomplétion de la commune (inscription + profil). Balisage : commune/view/champ.php
const champ = document.getElementById('commune');
const liste = document.getElementById('commune-resultats');
const identifiant = document.getElementById('id_commune');
const codePostal = document.getElementById('code_postal');
const message = document.getElementById('commune-message');

let timer;
let controleur;
let derniereRecherche = 0;

function fermerListe() {
    liste.replaceChildren();
    liste.hidden = true;
}

// Bloque l'envoi du formulaire tant qu'aucune commune de la liste n'est choisie.
function verifierSelection() {
    champ.setCustomValidity(identifiant.value ? '' : 'Choisissez une commune dans la liste.');
}

function choisir(commune) {
    champ.value = commune.nom_commune;
    codePostal.value = commune.code_postal;
    identifiant.value = commune.id_commune;
    verifierSelection();
    fermerListe();
    message.textContent = '';
}

function creerItem(commune) {
    const item = document.createElement('div');
    item.className = 'commune-item';

    const nom = document.createElement('span');
    nom.textContent = commune.nom_commune;

    const cp = document.createElement('span');
    cp.className = 'commune-detail';
    cp.textContent = commune.code_postal;

    item.append(nom, cp);
    item.addEventListener('click', () => choisir(commune));
    return item;
}

champ.addEventListener('input', () => {
    // Pas de chiffres : le code postal se remplit tout seul.
    champ.value = champ.value.replace(/[0-9]/g, '');

    clearTimeout(timer);
    const numero = ++derniereRecherche;

    if (controleur) {
        controleur.abort();
        controleur = null;
    }

    // La sélection précédente n'est plus valide.
    identifiant.value = '';
    codePostal.value = '';
    verifierSelection();
    fermerListe();

    const texte = champ.value.trim();
    if (texte.length < 2) {
        message.textContent = '';
        return;
    }

    message.textContent = 'Recherche en cours...';

    timer = setTimeout(async () => {
        const c = new AbortController();
        controleur = c;

        try {
            // L'adresse contient déjà un « ? » (index.php?route=...) : on ajoute q proprement.
            const adresse = new URL(champ.dataset.url, window.location.href);
            adresse.searchParams.set('q', texte);

            const response = await fetch(adresse, { signal: c.signal });
            if (!response.ok) {
                throw new Error('Erreur HTTP');
            }
            const communes = await response.json();

            // Ignore les réponses d'une ancienne recherche.
            if (numero !== derniereRecherche) return;

            if (communes.length === 0) {
                message.textContent = 'Aucune commune trouvée.';
                return;
            }

            message.textContent = '';
            communes.forEach(commune => liste.appendChild(creerItem(commune)));
            liste.hidden = false;
        } catch (e) {
            if (e.name !== 'AbortError' && numero === derniereRecherche) {
                message.textContent = 'Erreur lors de la recherche.';
            }
        }
    }, 300);
});

document.addEventListener('click', event => {
    if (!event.target.closest('.commune-recherche')) {
        fermerListe();
    }
});

verifierSelection();