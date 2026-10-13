<?php
/**
 * Barre du haut universelle de l'espace adhérent (cloche + menu avatar).
 *
 * Utilisation, juste après la sidebar, au début du contenu principal :
 *     <?php include dirname(__DIR__) . '/includes/topbar.php'; ?>
 *
 * Variable optionnelle : $topbarInitial (ex. 'S.') pour forcer l'initiale affichée.
 * Prérequis : url() et e() du projet. Contient son propre CSS et JS (isolés).
 */

// Initiale affichée dans l'avatar : prénom de l'utilisateur connecté
$tbUser    = function_exists('utilisateur') ? utilisateur() : null;
$tbPrenom  = trim((string)($tbUser['prenomUsers'] ?? ''));
if (isset($topbarInitial) && $topbarInitial !== '') {
    $tbInitial = (string)$topbarInitial;
} elseif ($tbPrenom !== '') {
    $tbInitial = mb_strtoupper(mb_substr($tbPrenom, 0, 1)) . '.';
} else {
    $tbInitial = '?';
}

$tbUrl = fn(string $file): string => url('adherent/' . $file);
?>
<header id="topbar" data-testid="topbar">
    <button type="button" class="tb-burger" id="burger" data-testid="mobile-menu-btn" aria-label="Menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
    </button>

    <div class="tb-right">
        <button type="button" class="tb-bell" id="tbBell" data-testid="notifications-btn" aria-label="Notifications">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
            <span class="tb-dot" id="tbDot"></span>
        </button>

        <div class="tb-user">
            <button type="button" class="tb-avatar-btn" id="tbAvatarBtn" data-testid="user-menu-btn" aria-haspopup="true" aria-expanded="false">
                <span class="tb-avatar"><?= e($tbInitial) ?></span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div class="tb-dd" id="tbDropdown" data-testid="user-dropdown" role="menu">
                <a href="<?= e($tbUrl('view/profil.php')) ?>" role="menuitem" data-testid="dropdown-profile">Mon profil</a>
                <a href="<?= e($tbUrl('view/abonnement.php')) ?>" role="menuitem" data-testid="dropdown-subscription">Mon abonnement</a>
                <a href="<?= e($tbUrl('deconnexion.php')) ?>" role="menuitem" data-testid="dropdown-logout">Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="tb-toast" id="tbToast" role="status" aria-live="polite" data-testid="toast"></div>
</header>

<script>
window.ADH_API = async function (action, data = {}) {
    var endpoint = <?= json_encode(url('adherent/api.php'), JSON_UNESCAPED_SLASHES) ?>;
    var token = <?= json_encode(csrf(), JSON_UNESCAPED_SLASHES) ?>;
    var url = new URL(endpoint, window.location.href);
    url.searchParams.set('action', action);
    var response = await fetch(url.toString(), {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(Object.assign({}, data, {csrf: token}))
    });
    var result = await response.json();
    if (!response.ok) throw new Error(result.error || 'La demande a échoué.');
    return result;
};
</script>

<script>
(function () {
    var btn = document.getElementById('tbAvatarBtn'),
        dd  = document.getElementById('tbDropdown'),
        bell = document.getElementById('tbBell'),
        dot = document.getElementById('tbDot'),
        toast = document.getElementById('tbToast'),
        timer;
    if (!btn || !dd) return;

    function setOpen(open) {
        dd.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', String(open));
    }
    btn.addEventListener('click', function (e) { e.stopPropagation(); setOpen(!dd.classList.contains('open')); });
    document.addEventListener('click', function (e) { if (!dd.contains(e.target)) setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });

    bell.addEventListener('click', function () {
        setOpen(false);
        toast.textContent = 'Aucune nouvelle notification';
        toast.classList.add('show');
        clearTimeout(timer);
        timer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
        if (dot) dot.style.display = 'none';   // notifications marquées comme lues
    });
})();
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap');

/* =====================================================================
   Topbar universelle — rendu IDENTIQUE sur toutes les pages adhérent.
   Tout est préfixé par #topbar et remis à zéro (all: revert) : le CSS
   de la page ne peut pas la modifier.
   ===================================================================== */
#topbar, #topbar div, #topbar button, #topbar a, #topbar span {
    all: revert;
    box-sizing: border-box; margin: 0; padding: 0;
}
#topbar {
    display: flex; align-items: center; justify-content: flex-end;
    height: 64px; flex-shrink: 0; position: relative; z-index: 35;
    font-family: 'Archivo', system-ui, sans-serif; font-size: 14px; line-height: 1.4;
    color: #f2f4f1; background: transparent;
}
#topbar button { font: inherit; color: inherit; background: none; border: 0; cursor: pointer; }
#topbar a { color: inherit; text-decoration: none; }
#topbar svg { display: block; flex-shrink: 0; max-width: none; }

#topbar .tb-right { display: flex; align-items: center; gap: 18px; }
#topbar .tb-burger { display: none; margin-right: auto; padding: 4px; color: #e5e8e5; }
#topbar .tb-bell { position: relative; padding: 4px; color: #e5e8e5; transition: color .2s; }
#topbar .tb-bell:hover { color: #c6f432; }
#topbar .tb-dot { position: absolute; top: 3px; right: 3px; width: 7px; height: 7px; border-radius: 50%; background: #c6f432; border: 1.5px solid #0b0d0c; }
#topbar .tb-user { position: relative; }
#topbar .tb-avatar-btn { display: flex; align-items: center; gap: 6px; color: #9aa19c; }
#topbar .tb-avatar { width: 30px; height: 30px; border-radius: 50%; background: #3b4250; color: #fff; font-size: 13px; font-weight: 600; display: grid; place-items: center; }

#topbar .tb-dd {
    position: absolute; right: 0; top: 38px; background: #161a18; border: 1px solid #1f2422; border-radius: 8px;
    min-width: 170px; padding: 6px; opacity: 0; transform: translateY(-6px); pointer-events: none;
    transition: opacity .2s, transform .2s; z-index: 40;
}
#topbar .tb-dd.open { opacity: 1; transform: none; pointer-events: auto; }
#topbar .tb-dd a { display: block; padding: 9px 10px; font-size: 13px; border-radius: 5px; color: #f2f4f1; }
#topbar .tb-dd a:hover { background: #20251f; color: #c6f432; }

#topbar .tb-toast {
    position: fixed; bottom: 24px; right: 24px; background: #c6f432; color: #0a0c0a;
    font-size: 13px; font-weight: 600; padding: 12px 18px; border-radius: 8px;
    transform: translateY(20px); opacity: 0; pointer-events: none;
    transition: opacity .25s, transform .25s; z-index: 70;
}
#topbar .tb-toast.show { opacity: 1; transform: none; }

@media (max-width: 860px) { #topbar .tb-burger { display: block; } }
</style>
