<?php
/**
 * Gabarit commun : icônes SVG, <head>, sidebar, topbar, hero, pied de page.
 */
require_once __DIR__ . '/bootstrap.php';

const ICONS = [
    'home'      => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
    'calendar'  => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>',
    'calcheck'  => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
    'calweek'   => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>',
    'chart'     => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    'card'      => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
    'message'   => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/>',
    'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'bell'      => '<path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>',
    'left'      => '<path d="m15 18-6-6 6-6"/>',
    'right'     => '<path d="m9 18 6-6-6-6"/>',
    'down'      => '<path d="m6 9 6 6 6-6"/>',
    'sliders'   => '<line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/>',
    'headset'   => '<path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 1 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z"/><path d="M21 16v2a4 4 0 0 1-4 4h-5"/>',
    'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    'grid'      => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
    'check'     => '<path d="M20 6 9 17l-5-5"/>',
    'x'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    'timer'     => '<line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/>',
    'scale'     => '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
    'droplet'   => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
    'moon'      => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
    'trend'     => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
    'signal'    => '<path d="M2 20h.01"/><path d="M7 20v-4"/><path d="M12 20v-8"/><path d="M17 20V8"/><path d="M22 4v16"/>',
    'pin'       => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    'dumbbell'  => '<path d="M14.4 14.4 9.6 9.6"/><path d="M18.657 21.485a2 2 0 1 1-2.829-2.828l-1.767 1.768a2 2 0 1 1-2.829-2.829l6.364-6.364a2 2 0 1 1 2.829 2.829l-1.768 1.767a2 2 0 1 1 2.828 2.829z"/><path d="m21.5 21.5-1.4-1.4"/><path d="M3.9 3.9 2.5 2.5"/><path d="M6.404 12.768a2 2 0 1 1-2.829-2.829l1.768-1.767a2 2 0 1 1-2.828-2.829l2.828-2.828a2 2 0 1 1 2.829 2.828l1.767-1.768a2 2 0 1 1 2.829 2.829z"/>',
    'heart'     => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/>',
    'lotus'     => '<circle cx="12" cy="12" r="3"/><path d="M12 16.5A4.5 4.5 0 1 1 7.5 12 4.5 4.5 0 1 1 12 7.5a4.5 4.5 0 1 1 4.5 4.5 4.5 4.5 0 1 1-4.5 4.5"/><path d="M12 7.5V9"/><path d="M7.5 12H9"/><path d="M16.5 12H15"/><path d="M12 16.5V15"/>',
    'flame'     => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
    'activity'  => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>',
    'target'    => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
    'menu'      => '<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>',
];

function icon(string $name, int $size = 20, string $class = ''): string
{
    $paths = ICONS[$name] ?? ICONS['target'];
    return '<svg class="ico ' . e($class) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
}

function logo(): string
{
    return '<a href="planning.php" class="logo" data-testid="sidebar-logo">'
        . '<svg width="40" height="40" viewBox="0 0 48 48" aria-hidden="true"><path d="M24 3 44 44H34L24 22 14 44H4Z" fill="#CCFF00"/><path d="M15 36h18l-3-6H18Z" fill="#0D1117"/></svg>'
        . '<span>ATHLE<b>TIX</b></span></a>';
}

/** Données exposées au JS (catégories + icônes). */
function atx_js_config(): array
{
    $cats = [];
    foreach (CATEGORIES as $k => $c) $cats[$k] = $c + ['svg' => icon($c['icon'], 16)];
    return ['categories' => $cats, 'images' => CAT_IMAGES, 'apiUrl' => url('adherent/api.php'), 'reservationsUrl' => url('adherent/view/reservations.php'), 'csrf' => csrf(), 'icons' => [
        'user' => icon('user', 13), 'clock' => icon('clock', 14), 'check' => icon('check', 12),
        'pin' => icon('pin', 14), 'signal' => icon('signal', 14), 'right' => icon('right', 16), 'calendar' => icon('calendar', 14),
    ]];
}

function page_head(string $active, string $title): void
{
    // Correspondance clé de page -> clé de la sidebar universelle
    $activeNav = ['performances' => 'performance'][$active] ?? $active;
    ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> — ATHLETIX</title>
  <meta name="description" content="ATHLETIX — planning des cours, réservations et suivi de performances de votre salle de sport.">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'><path d='M24 3 44 44H34L24 22 14 44H4Z' fill='%23CCFF00'/></svg>">
  <link rel="stylesheet" href="<?= e(url('assets/css/adherent/pl_res_perf.css')) ?>">
  <style>
    /* Sidebar universelle (fixe) : le contenu se décale de sa largeur */
    .app{display:block}
    .main{margin-left:250px;min-width:0}
    @media (min-width:861px){#sidebar{transform:none!important}}
    @media (max-width:860px){.main{margin-left:0}}
  </style>
  <script>window.ATX = <?= json_encode(atx_js_config(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  window.ATX.api = async (action, params = {}, method = 'GET') => {
    const url = new URL(window.ATX.apiUrl, window.location.href);
    url.searchParams.set('action', action);
    if (method === 'GET') Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
    const response = await fetch(url, {method, headers: method === 'POST' ? {'Content-Type':'application/json'} : {},
      body: method === 'POST' ? JSON.stringify({...params, csrf: window.ATX.csrf}) : undefined});
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Erreur de communication.');
    return result;
  };
  window.ATX.esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  window.ATX.cap = value => value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
  window.ATX.hm = value => String(value).slice(11, 16);
  window.ATX.frDate = (value, options = {}) => new Date(value).toLocaleDateString('fr-FR', options);
  window.ATX.syncFilter = (filter, value) => document.querySelectorAll('[data-filter="' + filter + '"]').forEach(button => button.classList.toggle('is-active', button.dataset.value === value));
  window.ATX.bookAction = async (action, id) => {
    try { const result = await window.ATX.api(action, {id}, 'POST'); return result.session || null; }
    catch (error) { alert(error.message); return null; }
  };
  </script>
  <?php if ($active === 'planning'): ?>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/fr.global.min.js"></script>
  <?php endif; ?>
</head>
<body data-page="<?= e($active) ?>">
<div class="app">
  <?php include dirname(__DIR__) . '/sidebar.php'; ?>

  <div class="main">
    <?php include dirname(__DIR__) . '/topbar.php'; ?>
    <main class="content">
<?php
}

function hero(string $ic, string $title, string $subtitle, string $testid): void
{
    ?>
      <section class="hero" data-testid="<?= e($testid) ?>">
        <div class="hero-img" style="background-image:url('<?= e(HERO_IMAGE) ?>')"></div>
        <div class="hero-body">
          <span class="hero-icon"><?= icon($ic, 44) ?></span>
          <div>
            <h1 data-testid="page-title"><?= e($title) ?></h1>
            <p><?= $subtitle ?></p>
          </div>
        </div>
        <div class="hero-motto"><span>Plus</span><span>fort</span><span>chaque</span><span>jour</span></div>
        <span class="hero-slash"></span>
      </section>
<?php
}

function help_banner(): void
{
    ?>
      <section class="panel help" data-testid="help-banner">
        <span class="help-icon"><?= icon('headset', 26) ?></span>
        <div><strong>Besoin d’un conseil ?</strong><p>Notre équipe est là pour vous aider à choisir les séances adaptées à vos objectifs.</p></div>
        <a class="btn btn-outline" href="<?= e(url('adherent/view/messagerie.php')) ?>" data-testid="help-contact-button">Contacter l’accueil <?= icon('right', 16) ?></a>
      </section>
<?php
}

function page_foot(): void
{
    ?>
    </main>
  </div>
</div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php
}
