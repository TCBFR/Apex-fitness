<?php
/** Sidebar commune aux espaces adhérent, employé et administration. */
$sidebarItems = $sidebarItems ?? [];
$sidebarActive = $sidebarActive ?? '';
$sidebarHome = $sidebarHome ?? url('accueil/index.php');
$sidebarLabel = $sidebarLabel ?? 'Navigation';
$sidebarSubtitle = $sidebarSubtitle ?? 'Espace membre';

$sidebarIcons = [
    'home' => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'bag' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2M9 13h6"/>',
    'chart' => '<path d="M3 21h18M6 21v-7M11 21V6M16 21v-10M21 21v-4"/>',
    'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
    'message' => '<path d="M4 5h16v11H8l-4 4zM8 10h8"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
    'users' => '<circle cx="9" cy="8" r="3"/><path d="M2.5 20c.5-3.5 2.7-5 6.5-5s6 1.5 6.5 5M16 5a3 3 0 0 1 0 6M17 15c2.5.4 4 2 4.5 5"/>',
    'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
    'building' => '<path d="M4 21V4h16v17M2 21h20M8 8h2M14 8h2M8 12h2M14 12h2M10 21v-4h4v4"/>',
    'tool' => '<path d="M14 6a5 5 0 0 0-6 6L3 17l4 4 5-5a5 5 0 0 0 6-6l-3 3-4-4z"/>',
    'plus' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
    'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.6 2.6 0 1 1 4.5 1.8c-1.2 1.2-2 1.5-2 3M12 17.5v.1"/>',
    'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
];

$sidebarIcon = static function (string $name) use ($sidebarIcons): string {
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($sidebarIcons[$name] ?? $sidebarIcons['home']) . '</svg>';
};
?>
<aside class="sidebar" id="sidebar" data-testid="sidebar">
  <a href="<?= e($sidebarHome) ?>" class="logo" data-testid="logo-link" aria-label="Accueil ATHLETIX">
    <svg width="34" height="34" viewBox="0 0 40 40" aria-hidden="true">
      <path d="M20 3 37 37H28L20 20 12 37H3Z" fill="var(--sb-lime)"/>
      <path d="M20 20 24.5 29H15.5Z" fill="var(--sb-bg)"/>
    </svg>
    <span>ATHLETI<b>X</b></span>
  </a>
  <nav class="nav" aria-label="<?= e($sidebarLabel) ?>">
    <?php foreach ($sidebarItems as $item): ?>
      <?php $active = $sidebarActive === $item['id']; ?>
      <a href="<?= e($item['href']) ?>" class="nav-item<?= $active ? ' active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> data-testid="nav-<?= e($item['id']) ?>">
        <?= $sidebarIcon($item['icon']) ?>
        <span><?= e($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-foot">
    <p class="tagline"><?= e($sidebarSubtitle) ?></p>
    <span class="tagline-bar"></span>
    <div class="slash slash-a"></div>
    <div class="slash slash-b"></div>
  </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('sidebar');
    var button = document.getElementById('burger');
    if (!sidebar || !button) return;

    button.addEventListener('click', function () {
        var ouvert = sidebar.classList.toggle('open');
        button.setAttribute('aria-expanded', String(ouvert));
    });

    document.addEventListener('click', function (event) {
        if (!sidebar.classList.contains('open')) return;
        if (sidebar.contains(event.target) || button.contains(event.target)) return;
        sidebar.classList.remove('open');
        button.setAttribute('aria-expanded', 'false');
    });
});
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap');
#sidebar,#sidebar div,#sidebar nav,#sidebar a,#sidebar span,#sidebar b,#sidebar p{all:revert;box-sizing:border-box;margin:0;padding:0}
#sidebar{--sb-width:250px;--sb-font-size:15.5px;--sb-icon-size:22px;--sb-lime:#c6f432;--sb-bg:#0b0d0c;--sb-side:#0e1110;--sb-line:#1f2422;width:var(--sb-width);padding:22px 12px;background:var(--sb-side);border-right:1px solid var(--sb-line);display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;z-index:40;overflow:hidden;font:400 var(--sb-font-size)/1.4 Archivo,system-ui,sans-serif;text-align:left;color:#f2f4f1;transition:transform .3s ease}
#sidebar a{color:inherit;text-decoration:none;cursor:pointer}
#sidebar svg{display:block;flex-shrink:0;max-width:none}
#sidebar .ico{width:var(--sb-icon-size);height:var(--sb-icon-size);fill:none;stroke:currentColor}
#sidebar .logo{display:flex;align-items:center;gap:8px;padding:0 12px;margin-bottom:36px}
#sidebar .logo svg{width:34px;height:34px}
#sidebar .logo span{font-weight:800;font-size:24px;letter-spacing:.3px;color:#f2f4f1}
#sidebar .logo b{font-weight:800;color:var(--sb-lime)}
#sidebar .nav{display:flex;flex:1 1 auto;flex-direction:column;gap:6px;min-height:0;overflow-y:auto;scrollbar-width:thin}
#sidebar .nav-item{display:flex;align-items:center;gap:14px;padding:15px 14px;border-radius:6px;font-size:var(--sb-font-size);font-weight:400;color:#d5d9d6;border:1px solid transparent;transition:background-color .2s,color .2s,border-color .2s}
#sidebar .nav-item span{font-size:inherit}
#sidebar .nav-item:hover{background:#151917;color:#fff}
#sidebar .nav-item.active{background:#1b1f1d;border-color:#262c29;color:#fff;box-shadow:inset 3px 0 0 var(--sb-lime)}
#sidebar .nav-item.active .ico{color:var(--sb-lime)}
#sidebar .sidebar-foot{margin-top:auto;position:relative;padding:0 12px 70px}
#sidebar .tagline{font-style:italic;font-size:16px;line-height:1.35;color:#e6e9e6}
#sidebar .tagline-bar{display:block;width:20px;height:2px;background:var(--sb-lime);margin-top:10px}
#sidebar .slash{position:absolute;background:var(--sb-lime)}
#sidebar .slash-a{width:120px;height:34px;right:-40px;bottom:26px;transform:rotate(-45deg);opacity:.9;clip-path:polygon(0 100%,100% 0,100% 40%,30% 100%)}
#sidebar .slash-b{width:90px;height:60px;right:-10px;bottom:-20px;transform:rotate(-45deg);opacity:.25}
@media(max-width:860px){#sidebar{transform:translateX(-100%)}#sidebar.open{transform:none;box-shadow:20px 0 60px rgba(0,0,0,.6)}}
</style>
