<?php
/**
 * Sidebar universelle de l'espace adhérent.
 *
 * Utilisation (dans chaque page, à l'intérieur de <div class="app">) :
 *     <?php $activeNav = 'planning'; include __DIR__ . '/../includes/sidebar.php'; ?>
 *
 * $activeNav : dashboard | planning | reservations | performance | abonnement | messages | profil
 * Si non défini, il est déduit automatiquement du nom du fichier appelant.
 *
 * Prérequis : les fonctions url() et e() du projet.
 */

$navItems = [
    ['id' => 'dashboard',    'label' => 'Tableau de bord',  'icon' => 'home',     'file' => 'espace.php'],
    ['id' => 'planning',     'label' => 'Planning',         'icon' => 'calendar', 'file' => 'view/planning.php'],
    ['id' => 'reservations', 'label' => 'Mes réservations', 'icon' => 'bag',      'file' => 'view/reservations.php'],
    ['id' => 'performance',  'label' => 'Performance',      'icon' => 'chart',    'file' => 'view/performance.php'],
    ['id' => 'abonnement',   'label' => 'Mon abonnement',   'icon' => 'card',     'file' => 'view/abonnement.php'],
    ['id' => 'messages',     'label' => 'Messages',         'icon' => 'message',  'file' => 'view/messagerie.php'],
    ['id' => 'profil',       'label' => 'Mon profil',       'icon' => 'user',     'file' => 'view/profil.php'],
];

// Page active : valeur explicite, sinon détection via le script courant
if (!isset($activeNav)) {
    $current   = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $activeNav = 'dashboard';
    foreach ($navItems as $it) {
        if (basename($it['file']) === $current) { $activeNav = $it['id']; break; }
    }
}

$adherentUrl = fn(string $file): string => url('adherent/' . $file);

$sbPaths = [
    'home'     => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'bag'      => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/><path d="M9 13h6"/>',
    'chart'    => '<path d="M3 21h18"/><path d="M6 21v-7M11 21V6M16 21v-10M21 21v-4"/>',
    'card'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
    'message'  => '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 10h8"/>',
    'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
];
$sbIcon = fn(string $name, int $size = 22): string =>
    '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($sbPaths[$name] ?? '') . '</svg>';
?>
<aside class="sidebar" id="sidebar" data-testid="sidebar">
    <a href="<?= e($adherentUrl('espace.php')) ?>" class="logo" data-testid="logo-link">
        <svg width="34" height="34" viewBox="0 0 40 40" aria-hidden="true">
            <path d="M20 3 37 37H28L20 20 12 37H3Z" fill="var(--sb-lime)"/>
            <path d="M20 20 24.5 29H15.5Z" fill="var(--sb-bg)"/>
        </svg>
        <span>ATHLETI<b>X</b></span>
    </a>

    <nav class="nav" aria-label="Navigation adhérent">
        <?php foreach ($navItems as $item): $isActive = $activeNav === $item['id']; ?>
            <a href="<?= e($adherentUrl($item['file'])) ?>"
               class="nav-item<?= $isActive ? ' active' : '' ?>"
               <?= $isActive ? 'aria-current="page"' : '' ?>
               data-testid="nav-<?= e($item['id']) ?>">
                <?= $sbIcon($item['icon'], 22) ?>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-foot">
        <p class="tagline">Plus fort<br>chaque jour</p>
        <span class="tagline-bar"></span>
        <div class="slash slash-a"></div>
        <div class="slash slash-b"></div>
    </div>
</aside>
<script>
// Burger mobile (le bouton #burger se trouve dans la topbar de chaque page)
document.addEventListener('DOMContentLoaded', function () {
    var sb = document.getElementById('sidebar'), bg = document.getElementById('burger');
    if (!sb || !bg) return;
    bg.addEventListener('click', function () { sb.classList.toggle('open'); });
    document.addEventListener('click', function (e) {
        if (sb.classList.contains('open') && !sb.contains(e.target) && !bg.contains(e.target)) sb.classList.remove('open');
    });
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap');

/* =====================================================================
   Sidebar universelle — rendu IDENTIQUE sur toutes les pages adhérent.
   - Tout est préfixé par #sidebar : aucun CSS de page (.sidebar, .nav-item,
     .logo, .ico, resets globaux...) ne peut la modifier.
   - Les éléments sont remis à zéro (all: revert) puis redéfinis ici.
   - Taille : modifier --sb-font-size / --sb-icon-size une seule fois ici.
   ===================================================================== */
#sidebar, #sidebar div, #sidebar nav, #sidebar a, #sidebar span, #sidebar b, #sidebar p {
    all: revert;
    box-sizing: border-box; margin: 0; padding: 0;
}
#sidebar {
    --sb-width:     250px;
    --sb-font-size: 15.5px;
    --sb-icon-size: 22px;
    --sb-lime: #c6f432;
    --sb-bg:   #0b0d0c;
    --sb-side: #0e1110;
    --sb-line: #1f2422;

    width: var(--sb-width); padding: 22px 12px;
    background: var(--sb-side); border-right: 1px solid var(--sb-line);
    display: flex; flex-direction: column;
    position: fixed; inset: 0 auto 0 0; z-index: 30; overflow: hidden;
    font-family: 'Archivo', system-ui, sans-serif; font-size: var(--sb-font-size);
    line-height: 1.4; font-weight: 400; text-align: left; color: #f2f4f1;
    transition: transform .3s ease;
}
#sidebar a { color: inherit; text-decoration: none; cursor: pointer; }
#sidebar svg { display: block; flex-shrink: 0; max-width: none; }
#sidebar .ico { width: var(--sb-icon-size); height: var(--sb-icon-size); fill: none; stroke: currentColor; }

#sidebar .logo { display: flex; align-items: center; gap: 8px; padding: 0 12px; margin-bottom: 36px; }
#sidebar .logo svg { width: 34px; height: 34px; }
#sidebar .logo span { font-weight: 800; font-size: 24px; letter-spacing: .3px; color: #f2f4f1; }
#sidebar .logo b { font-weight: 800; color: var(--sb-lime); }

#sidebar .nav { display: flex; flex-direction: column; gap: 6px; }
#sidebar .nav-item {
    display: flex; align-items: center; gap: 14px; padding: 15px 14px; border-radius: 6px;
    font-size: var(--sb-font-size); font-weight: 400; color: #d5d9d6; border: 1px solid transparent;
    transition: background-color .2s, color .2s, border-color .2s;
}
#sidebar .nav-item span { font-size: inherit; }
#sidebar .nav-item:hover { background: #151917; color: #fff; }
#sidebar .nav-item.active { background: #1b1f1d; border-color: #262c29; color: #fff; box-shadow: inset 3px 0 0 var(--sb-lime); }
#sidebar .nav-item.active .ico { color: var(--sb-lime); }

#sidebar .sidebar-foot { margin-top: auto; position: relative; padding: 0 12px 70px; }
#sidebar .tagline { font-style: italic; font-size: 16px; line-height: 1.35; color: #e6e9e6; }
#sidebar .tagline-bar { display: block; width: 20px; height: 2px; background: var(--sb-lime); margin-top: 10px; }
#sidebar .slash { position: absolute; background: var(--sb-lime); }
#sidebar .slash-a { width: 120px; height: 34px; right: -40px; bottom: 26px; transform: rotate(-45deg); opacity: .9;
    clip-path: polygon(0 100%, 100% 0, 100% 40%, 30% 100%); }
#sidebar .slash-b { width: 90px; height: 60px; right: -10px; bottom: -20px; transform: rotate(-45deg); opacity: .25; }

@media (max-width: 860px) {
    #sidebar { transform: translateX(-100%); }
    #sidebar.open { transform: none; box-shadow: 20px 0 60px rgba(0,0,0,.6); }
}
</style>