<?php
require __DIR__ . '/includes/dashboard/data.php';
require __DIR__ . '/includes/dashboard/icons.php';
$active = $_GET['page'] ?? 'dashboard';

// Pages du dossier adherent/ : le site passe par le routeur index.php?route=...
$pageFiles = [
    'dashboard'    => 'espace.php',
    'planning'     => 'view/planning.php',
    'reservations' => 'view/reservations.php',
    'performance'  => 'view/performance.php',
    'abonnement'   => 'view/abonnement.php',
    'messages'     => 'view/messagerie.php',
    'profil'       => 'view/profil.php',
];
$navHref = fn(string $id): string => isset($pageFiles[$id])
    ? 'index.php?route=adherent/' . $pageFiles[$id]
    : '?page=' . $id;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATHLETIX — Tableau de bord</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
<div class="app">
    <?php $activeNav = $active; include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <button class="burger" id="burger" data-testid="mobile-menu-btn" aria-label="Menu"><?= icon('menu', 22) ?></button>
            <div class="topbar-right">
                <button class="bell" id="bellBtn" data-testid="notifications-btn" aria-label="Notifications">
                    <?= icon('bell', 20) ?><span class="dot"></span>
                </button>
                <div class="user-menu">
                    <button class="avatar-btn" id="avatarBtn" data-testid="user-menu-btn">
                        <span class="avatar"><?= e($user['initial']) ?></span><?= icon('down', 14) ?>
                    </button>
                    <div class="dropdown" id="userDropdown" data-testid="user-dropdown">
                        <a href="<?= e($navHref('profil')) ?>" data-testid="dropdown-profile">Mon profil</a>
                        <a href="<?= e($navHref('abonnement')) ?>" data-testid="dropdown-subscription">Mon abonnement</a>
                        <a href="#" data-testid="dropdown-logout">Déconnexion</a>
                    </div>
                </div>
            </div>
        </header>

        <section class="hero reveal" data-testid="hero-section" style="--hero:url('<?= e($heroImg) ?>')">
            <div class="hero-content">
                <p class="eyebrow">Bonjour, <?= e($user['name']) ?></p>
                <h1 data-testid="hero-title">Prêt à repousser<br>tes <em>limites</em> ?</h1>
                <p class="hero-sub">Bienvenue dans ton espace ATHLETIX.</p>
                <a href="<?= e($navHref('planning')) ?>" class="btn btn-lime" data-testid="hero-explore-planning-btn">
                    Explorer le planning <?= icon('chevron', 16) ?>
                </a>
            </div>
            <div class="hero-sign" aria-hidden="true">PLUS<br>FORT<br>CHAQUE<br>JOUR</div>
        </section>

        <section class="stats">
            <?php foreach ($stats as $i => $s): ?>
                <a href="#" class="stat reveal" style="--d:<?= ($i + 1) * 70 ?>ms" data-testid="stat-card-<?= $i ?>">
                    <span class="stat-icon"><?= icon($s['icon'], 30) ?></span>
                    <span class="stat-label"><?= e($s['label']) ?></span>
                    <span class="stat-value<?= $s['accent'] ? ' lime' : '' ?>" data-testid="stat-value-<?= $i ?>"><?= e($s['value']) ?></span>
                    <span class="stat-arrow"><?= icon('chevron', 16) ?></span>
                </a>
            <?php endforeach; ?>
        </section>

        <section class="bottom">
            <div class="panel appointments reveal" style="--d:350ms" data-testid="appointments-panel">
                <div class="panel-head"><?= icon('calendar', 24) ?><h2>Tes prochains rendez-vous</h2></div>
                <div class="empty">
                    <span class="empty-icon"><?= icon('calendar', 34) ?></span>
                    <p class="empty-title" data-testid="appointments-empty-text">Aucune séance prévue pour le moment</p>
                    <p class="empty-sub">Réserve une séance et garde le rythme !</p>
                    <button class="btn btn-lime btn-wide" id="bookBtn" data-testid="book-session-btn">
                        <?= icon('calendar', 16) ?> Réserver une séance <?= icon('chevron', 16) ?>
                    </button>
                </div>
                <span class="corner corner-tr"></span>
                <span class="corner corner-bl"></span>
            </div>

            <div class="panel discover reveal" style="--d:420ms" data-testid="discover-panel">
                <div class="panel-head">
                    <?= icon('star', 24) ?><h2>À découvrir chez ATHLETIX</h2>
                    <a href="#" class="see-all" data-testid="discover-see-all">Voir tout <?= icon('chevron', 13) ?></a>
                </div>
                <div class="cards">
                    <?php foreach ($discover as $i => $c): ?>
                        <a href="#" class="dcard" data-testid="discover-card-<?= $i ?>">
                            <div class="dcard-img" style="background-image:url('<?= e($c['img']) ?>')"></div>
                            <div class="dcard-body">
                                <span class="dcard-icon"><?= icon($c['icon'], 22) ?></span>
                                <h3><?= e($c['title']) ?></h3>
                                <p><?= e($c['text']) ?></p>
                                <span class="dcard-arrow"><?= icon('arrow', 14) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="modal" id="bookModal" data-testid="book-modal" aria-hidden="true">
    <div class="modal-box">
        <h3>Réserver une séance</h3>
        <form id="bookForm">
            <label>Activité
                <select name="activity" data-testid="book-activity-select">
                    <option>Musculation</option><option>Cardio</option><option>Yoga &amp; bien-être</option>
                </select>
            </label>
            <label>Date<input type="date" name="date" required data-testid="book-date-input"></label>
            <label>Heure<input type="time" name="time" required data-testid="book-time-input"></label>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" id="closeModal" data-testid="book-cancel-btn">Annuler</button>
                <button type="submit" class="btn btn-lime" data-testid="book-submit-btn">Confirmer</button>
            </div>
        </form>
    </div>
</div>
<div class="toast" id="toast" data-testid="toast"></div>

<script src="assets/js/main.js"></script>
</body>
</html>

<style>
:root {
    --bg: #0b0d0c;
    --sidebar: #0e1110;
    --panel: #121514;
    --panel-2: #161a18;
    --border: #1f2422;
    --text: #f2f4f1;
    --muted: #9aa19c;
    --lime: #c6f432;
    --lime-dark: #a8d61c;
    --radius: 10px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { background: var(--bg); color: var(--text); font-family: 'Archivo', sans-serif; -webkit-font-smoothing: antialiased; }
a { color: inherit; text-decoration: none; }
button { font: inherit; cursor: pointer; border: 0; background: none; color: inherit; }
::selection { background: var(--lime); color: #000; }
.ico { flex-shrink: 0; }

.app { display: flex; min-height: 100vh; }


/* Main */
.main { flex: 1; margin-left: 250px; padding: 0 36px 36px; min-width: 0; display: flex; flex-direction: column; min-height: 100vh; }
.topbar { height: 64px; flex-shrink: 0; display: flex; align-items: center; justify-content: flex-end; }
.topbar-right { display: flex; align-items: center; gap: 18px; }
.burger { display: none; margin-right: auto; }
.bell { position: relative; padding: 4px; color: #e5e8e5; transition: color .2s; }
.bell:hover { color: var(--lime); }
.bell .dot { position: absolute; top: 3px; right: 3px; width: 7px; height: 7px; border-radius: 50%; background: var(--lime); border: 1.5px solid var(--bg); }
.user-menu { position: relative; }
.avatar-btn { display: flex; align-items: center; gap: 6px; color: var(--muted); }
.avatar { width: 30px; height: 30px; border-radius: 50%; background: #3b4250; color: #fff; font-size: 13px; font-weight: 600; display: grid; place-items: center; }
.dropdown {
    position: absolute; right: 0; top: 34px; background: var(--panel-2); border: 1px solid var(--border); border-radius: 8px;
    min-width: 170px; padding: 6px; opacity: 0; transform: translateY(-6px); pointer-events: none;
    transition: opacity .2s, transform .2s; z-index: 40;
}
.dropdown.open { opacity: 1; transform: none; pointer-events: auto; }
.dropdown a { display: block; padding: 9px 10px; font-size: 13px; border-radius: 5px; }
.dropdown a:hover { background: #20251f; color: var(--lime); }

/* Hero */
.hero {
    position: relative; height: clamp(250px, 33vh, 400px); flex-shrink: 0; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border);
    background:
        linear-gradient(90deg, #0b0d0c 0%, rgba(11,13,12,.94) 32%, rgba(11,13,12,.35) 62%, rgba(11,13,12,.2) 100%),
        linear-gradient(0deg, rgba(198,244,50,.08), rgba(198,244,50,.08)),
        var(--hero) center 40% / cover no-repeat;
    display: flex; align-items: center; padding: 0 48px;
}
.hero::after {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background: repeating-linear-gradient(90deg, transparent 0 140px, rgba(198,244,50,.05) 140px 142px);
}
.hero-content { position: relative; z-index: 2; }
.eyebrow { text-transform: uppercase; letter-spacing: 2.8px; font-size: 13px; color: #cfd4d0; margin-bottom: 14px; }
.hero h1 { font-size: clamp(38px, 3.6vw, 58px); line-height: 1.12; font-weight: 800; letter-spacing: -.5px; }
.hero h1 em { font-style: normal; color: var(--lime); }
.hero-sub { font-size: 16px; color: #d6dad7; margin: 14px 0 26px; }
.hero-sign {
    position: absolute; left: 47%; top: 50%; transform: translateY(-50%); z-index: 1;
    font-size: 15px; font-weight: 700; line-height: 1.35; color: rgba(255,255,255,.55); letter-spacing: 1.4px;
    padding-left: 14px; border-left: 2px solid var(--lime);
}

/* Buttons */
.btn {
    display: inline-flex; align-items: center; gap: 10px; padding: 13px 22px; border-radius: 6px;
    font-size: 14.5px; font-weight: 600; transition: background-color .2s, transform .2s, box-shadow .2s;
}
.btn-lime { background: var(--lime); color: #0a0c0a; }
.btn-lime:hover { background: #d6ff52; transform: translateY(-1px); box-shadow: 0 6px 22px rgba(198,244,50,.25); }
.btn-lime:active { transform: translateY(0); }
.btn-ghost { border: 1px solid var(--border); color: var(--text); }
.btn-ghost:hover { background: #1a1e1c; }
.btn-wide { padding: 15px 32px; }

/* Stats */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 22px 0; flex-shrink: 0; }
.stat {
    position: relative; background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 24px 26px; min-height: 140px; display: flex; flex-direction: column;
    transition: border-color .2s, transform .2s, background-color .2s;
}
.stat:hover { border-color: #34402a; transform: translateY(-2px); background: var(--panel-2); }
.stat-icon { color: var(--lime); margin-bottom: 14px; }
.stat-label { font-size: 14px; color: #c8cdc9; }
.stat-value { font-size: 22px; font-weight: 700; margin-top: 6px; }
.stat-value.lime { color: var(--lime); }
.stat-arrow { position: absolute; right: 22px; bottom: 24px; color: #c8cdc9; transition: transform .2s, color .2s; }
.stat:hover .stat-arrow { transform: translateX(3px); color: var(--lime); }

/* Bottom panels */
.bottom { display: grid; grid-template-columns: 0.95fr 1.1fr; gap: 20px; flex: 1; }
.panel { position: relative; background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); padding: 26px; overflow: hidden; min-height: 320px; display: flex; flex-direction: column; }
.panel-head { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; }
.panel-head h2 { font-size: 17px; font-weight: 600; }
.see-all { margin-left: auto; font-size: 13px; color: var(--lime); display: flex; align-items: center; gap: 4px; }
.see-all:hover { text-decoration: underline; }

.empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; text-align: center; padding: 8px 0 4px; position: relative; z-index: 2; }
.empty-icon { width: 80px; height: 80px; border-radius: 50%; border: 1px solid #3a403c; display: grid; place-items: center; margin-bottom: 20px; color: #e9ece9; }
.empty-title { font-size: 17px; font-weight: 500; }
.empty-sub { font-size: 14px; color: var(--muted); margin: 10px 0 26px; }
.corner { position: absolute; width: 0; height: 0; z-index: 1; }
.corner-tr { top: 0; right: 0; border-top: 96px solid rgba(198,244,50,.9); border-left: 70px solid transparent;
    filter: drop-shadow(-6px 6px 0 rgba(198,244,50,.18)); clip-path: polygon(30% 0, 100% 0, 100% 100%); }
.corner-bl { bottom: 0; left: 0; border-bottom: 88px solid rgba(198,244,50,.9); border-right: 64px solid transparent;
    clip-path: polygon(0 0, 0 100%, 70% 100%); }

.cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; flex: 1; }
.dcard { background: var(--panel-2); border: 1px solid var(--border); border-radius: 7px; overflow: hidden; display: flex; flex-direction: column; transition: border-color .2s, transform .25s; }
.dcard:hover { border-color: rgba(198,244,50,.5); transform: translateY(-3px); }
.dcard-img { flex: 1; min-height: 150px; background-size: cover; background-position: center; filter: saturate(.75) brightness(.8); transition: filter .3s; }
.dcard:hover .dcard-img { filter: saturate(1) brightness(.95); }
.dcard-body { position: relative; padding: 16px 16px 20px; }
.dcard-icon { color: var(--lime); display: block; margin-bottom: 8px; }
.dcard h3 { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
.dcard p { font-size: 13px; color: var(--muted); margin-top: 7px; line-height: 1.5; padding-right: 26px; }
.dcard-arrow { position: absolute; right: 16px; bottom: 20px; color: var(--lime); transition: transform .2s; }
.dcard:hover .dcard-arrow { transform: translateX(3px); }

/* Modal & toast */
.modal { position: fixed; inset: 0; background: rgba(0,0,0,.65); backdrop-filter: blur(14px); display: grid; place-items: center; opacity: 0; pointer-events: none; transition: opacity .25s; z-index: 60; }
.modal.open { opacity: 1; pointer-events: auto; }
.modal-box { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; padding: 24px; width: min(380px, 92vw); transform: scale(.96); transition: transform .25s; }
.modal.open .modal-box { transform: none; }
.modal-box h3 { font-size: 17px; margin-bottom: 16px; }
.modal-box label { display: flex; flex-direction: column; gap: 6px; font-size: 12px; color: var(--muted); margin-bottom: 12px; }
.modal-box input, .modal-box select { background: var(--bg); border: 1px solid var(--border); color: var(--text); border-radius: 6px; padding: 10px; font: inherit; font-size: 13px; color-scheme: dark; }
.modal-box input:focus, .modal-box select:focus { outline: none; border-color: var(--lime); }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
.toast { position: fixed; bottom: 24px; right: 24px; background: var(--lime); color: #0a0c0a; font-size: 13px; font-weight: 600; padding: 12px 18px; border-radius: 8px; transform: translateY(20px); opacity: 0; transition: opacity .25s, transform .25s; z-index: 70; }
.toast.show { opacity: 1; transform: none; }

/* Reveal */
.reveal { opacity: 0; transform: translateY(12px); animation: rise .6s cubic-bezier(.2,.7,.2,1) forwards; animation-delay: var(--d, 0ms); }
@keyframes rise { to { opacity: 1; transform: none; } }

/* Responsive */
@media (max-width: 1100px) {
    .stats { grid-template-columns: repeat(2, 1fr); }
    .bottom { grid-template-columns: 1fr; }
    .hero-sign { display: none; }
}
@media (max-width: 860px) {
    .main { margin-left: 0; padding: 0 14px 20px; min-height: auto; }
    .burger { display: block; }
    .hero h1 { font-size: 30px; }
    .hero { padding: 0 24px; }
    .dcard-img { min-height: 120px; }
    .cards { grid-template-columns: 1fr; }
    .stats { grid-template-columns: 1fr 1fr; gap: 10px; }
}

</style>

<script>
  (() => {
    const $ = (id) => document.getElementById(id);
    const dropdown = $('userDropdown');
    const modal = $('bookModal');
    const toast = $('toast');
    let toastTimer;

    const showToast = (msg) => {
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
    };

    $('avatarBtn').addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => dropdown.classList.remove('open'));

    $('bellBtn').addEventListener('click', () => showToast('Aucune nouvelle notification'));

    const toggleModal = (open) => {
        modal.classList.toggle('open', open);
        modal.setAttribute('aria-hidden', String(!open));
    };
    $('bookBtn').addEventListener('click', () => toggleModal(true));
    $('closeModal').addEventListener('click', () => toggleModal(false));
    modal.addEventListener('click', (e) => { if (e.target === modal) toggleModal(false); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggleModal(false); });

    $('bookForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const data = new FormData(e.target);
        toggleModal(false);
        e.target.reset();
        showToast(`Séance ${data.get('activity')} réservée le ${data.get('date')} à ${data.get('time')}`);
    });
})();

</script>