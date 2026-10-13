<?php
exige_role('ADHERENT');
require dirname(__DIR__) . '/includes/profil/data.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon abonnement – ATHLETIX</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="app-shell">
    <?php $activeNav = 'abonnement'; include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <button class="burger" id="burger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>


<!-- Hero -->
        <section class="hero">
            <div class="hero-left">
                <div class="hero-icon"><i class="fa-regular fa-credit-card"></i></div>
                <div>
                    <h1>Mon abonnement</h1>
                    <p>Retrouvez toutes les informations concernant votre abonnement<br>et gérez-le en toute simplicité.</p>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <span class="hero-tag">PLUS</span>
                <span class="hero-tag">FORT</span>
                <span class="hero-tag">CHAQUE</span>
                <span class="hero-tag">JOUR</span>
            </div>
        </section>

        <div class="grid-2">

            <!-- Colonne principale -->
            <div class="col-main">

                <!-- Carte principale abonnement -->
                <section class="card">
                    <span class="badge badge-green badge-lg"><i class="fa-solid fa-lock-open"></i> <?= htmlspecialchars($abonnement['statut']); ?></span>

                    <div class="sub-main-grid">
                        <!-- Carte membre -->
                        <div class="member-card compact">
                            <div class="member-card-inner">
                                <div>
                                    <div class="brand">
                                        <span class="logo-mark"><i class="fa-solid fa-angles-up"></i></span>
                                        <span class="logo-text">ATHLETIX</span>
                                    </div>
                                    <p class="card-sub">CARTE MEMBRE</p>
                                    <div class="member-infos tight">
                                        <strong><?= htmlspecialchars($user['prenom'].' '.$user['nom']); ?></strong>
                                        <span>N° Membre : <?= htmlspecialchars($user['num_membre']); ?></span>
                                    </div>
                                </div>
                                <img class="qr small" src="assets/images/qr-placeholder.png" alt="QR Code fictif">
                            </div>
                        </div>

                        <!-- Détails -->
                        <div class="sub-details">
                            <span class="badge badge-green"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($abonnement['statut']); ?></span>
                            <h2>Abonnement <?= htmlspecialchars($abonnement['formule']); ?></h2>
                            <p class="muted">Accès illimité à toutes les installations et activités.</p>

                            <div class="pills">
                                <?php foreach ($abonnement['acces'] as $a): ?>
                                    <div class="pill">
                                        <i class="fa-solid fa-<?= $a['icon']; ?>"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($a['label']); ?></strong>
                                            <span><?= htmlspecialchars($a['detail']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <ul class="dates-list">
                                <li><i class="fa-regular fa-calendar"></i> <div><span>Durée de l'abonnement</span><strong><?= htmlspecialchars($abonnement['duree']); ?></strong></div></li>
                                <li><i class="fa-regular fa-calendar"></i> <div><span>Date de début</span><strong><?= htmlspecialchars($abonnement['date_debut']); ?></strong></div></li>
                                <li><i class="fa-regular fa-calendar"></i> <div><span>Prochaine échéance</span><strong><?= htmlspecialchars($abonnement['prochaine_echeance']); ?></strong></div></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Progression -->
                    <div class="progress-wrap">
                        <div class="progress-head">
                            <span>Temps restant</span>
                            <span class="green-text"><?= $abonnement['mois_restants']; ?> mois restants</span>
                        </div>
                        <?php $pct = round(($abonnement['mois_restants']/$abonnement['mois_total'])*100); ?>
                        <div class="progress"><div class="progress-fill" style="width: <?= $pct; ?>%"></div></div>
                    </div>

                    <!-- Renouvellement -->
                    <div class="renew-note">
                        <i class="fa-regular fa-calendar"></i>
                        <span>Votre abonnement se renouvelle automatiquement<br>à la date d'échéance.</span>
                        <a href="#" class="green-link">Voir les conditions <i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </section>

                <!-- Historique -->
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-regular fa-clock-rotate-left"></i></div>
                        <h3>Historique de facturation</h3>
                    </header>

                    <table class="history">
                        <thead>
                            <tr><th>Date</th><th>Prestation</th><th>Montant</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abonnement['historique'] as $h): ?>
                                <tr>
                                    <td><?= htmlspecialchars($h['date']); ?></td>
                                    <td><?= htmlspecialchars($h['prestation']); ?></td>
                                    <td><?= htmlspecialchars($h['montant']); ?></td>
                                    <td><span class="badge badge-green small"><?= htmlspecialchars($h['statut']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <a href="#" class="green-link mt">Voir tout l'historique <i class="fa-solid fa-chevron-right"></i></a>
                </section>
            </div>

            <!-- Colonne latérale -->
            <aside class="col-side">

                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-solid fa-crown"></i></div>
                        <h3>Votre abonnement</h3>
                    </header>

                    <div class="status-row">
                        <span class="status-dot"><i class="fa-solid fa-circle-check"></i></span>
                        <div>
                            <strong class="green-text"><?= htmlspecialchars($abonnement['statut']); ?></strong>
                            <p class="muted small">Tout est en ordre, profitez de votre abonnement sans interruption.</p>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-block">
                        <i class="fa-regular fa-calendar"></i> Gérer mon abonnement
                        <i class="fa-solid fa-chevron-right right"></i>
                    </button>
                    <button class="btn btn-outline btn-block">
                        <i class="fa-regular fa-file-lines"></i> Télécharger ma carte membre
                        <i class="fa-solid fa-chevron-right right"></i>
                    </button>
                </section>

                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-regular fa-star"></i></div>
                        <h3>Avantages inclus</h3>
                    </header>
                    <ul class="advantages">
                        <?php foreach ($abonnement['avantages'] as $av): ?>
                            <li><i class="fa-solid fa-check"></i> <?= htmlspecialchars($av); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="#" class="green-link">Voir tous les avantages <i class="fa-solid fa-chevron-right"></i></a>
                </section>

                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-solid fa-headset"></i></div>
                        <h3>Une question ?</h3>
                    </header>
                    <p class="muted small">Notre équipe est disponible pour répondre à toutes vos questions concernant votre abonnement.</p>
                    <button class="btn btn-outline btn-block">
                        Contacter l'accueil <i class="fa-solid fa-chevron-right right"></i>
                    </button>
                </section>
            </aside>
        </div>
    </main>
</div>

<script src="assets/js/script.js"></script>
</body>
</html>

<script>
    // ===== ATHLETIX — JS page ABONNEMENT =====
document.addEventListener('DOMContentLoaded', () => {

    // Animation de la barre de progression au chargement
    const fill = document.querySelector('.progress-fill');
    if (fill) {
        const w = fill.style.width;
        fill.style.width = '0';
        requestAnimationFrame(() => {
            setTimeout(() => (fill.style.width = w), 100);
        });
    }

    // Lignes de l'historique → feedback au survol/click
    document.querySelectorAll('.history tbody tr').forEach(row => {
        row.style.transition = 'background .2s';
        row.addEventListener('mouseenter', () => (row.style.background = 'rgba(182,255,46,.03)'));
        row.addEventListener('mouseleave', () => (row.style.background = ''));
    });

    // Bouton "Télécharger ma carte membre" (mock)
    document.querySelectorAll('.btn-outline, .btn-primary').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.style.transform = 'scale(.98)';
            setTimeout(() => (btn.style.transform = ''), 150);
        });
    });

    // Chip utilisateur
    const chip = document.querySelector('.user-chip');
    if (chip) {
        chip.addEventListener('click', () => {
            chip.style.borderColor = 'var(--green)';
            setTimeout(() => (chip.style.borderColor = ''), 500);
        });
    }
});

</script>
<style>
    /* ===== ATHLETIX — Styles communs à la page ABONNEMENT ===== */
:root{
    --bg:            #05080a;
    --bg-2:          #0a0f12;
    --panel:         #0d1317;
    --panel-2:       #10171c;
    --border:        #1b262c;
    --border-strong: #2a3a42;
    --text:          #e8f2ec;
    --muted:         #8a9ba3;
    --green:         #B6FF2E;
    --green-2:       #8bd81b;
    --green-glow:    rgba(182,255,46,.25);
    --radius:        18px;
    --radius-sm:     12px;
    --font:          'Sora', system-ui, sans-serif;
}

*{box-sizing:border-box;margin:0;padding:0}
html,body{background:var(--bg);color:var(--text);font-family:var(--font);min-height:100vh;-webkit-font-smoothing:antialiased}
a{color:inherit;text-decoration:none}
button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit}

/* ===== Layout ===== */
.app-shell{min-height:100vh}
.main-content{margin-left:250px;padding:22px 36px 60px;max-width:1600px}
.burger{display:none;position:fixed;top:14px;left:14px;z-index:40;width:42px;height:42px;border-radius:50%;background:#0f1619;border:1px solid var(--border);align-items:center;justify-content:center;font-size:16px}

/* ===== Topbar ===== */
.topbar{display:flex;justify-content:flex-end;align-items:center;margin-bottom:20px}
.topbar-right{display:flex;align-items:center;gap:18px}
.icon-btn{
    position:relative;width:42px;height:42px;border-radius:50%;
    background:#0f1619;border:1px solid var(--border);
    display:grid;place-items:center;color:var(--muted);transition:.2s;
}
.icon-btn:hover{color:var(--green);border-color:var(--green)}
.icon-btn .dot{position:absolute;top:10px;right:12px;width:8px;height:8px;background:var(--green);border-radius:50%;box-shadow:0 0 10px var(--green-glow)}
.user-chip{display:flex;align-items:center;gap:10px;padding:4px 10px 4px 4px;background:#0f1619;border:1px solid var(--border);border-radius:40px}
.avatar-mini{width:34px;height:34px;border-radius:50%;background:#223037;color:#cfd9dd;display:grid;place-items:center;font-weight:600}
.user-chip i{color:var(--muted);font-size:12px}

/* ===== Hero ===== */
.hero{
    position:relative;overflow:hidden;border-radius:var(--radius);
    background:
        radial-gradient(600px 240px at 85% 60%, rgba(182,255,46,.08), transparent 60%),
        linear-gradient(100deg, #0a1013 0%, #0a1013 45%, #0d1a14 70%, #09120b 100%);
    border:1px solid var(--border);
    padding:28px 36px;display:flex;align-items:center;gap:28px;min-height:150px;margin-bottom:26px;
}
.hero::before{
    content:"";position:absolute;inset:0;
    background:
        repeating-linear-gradient(90deg, transparent 0 60px, rgba(182,255,46,.03) 60px 61px),
        repeating-linear-gradient(0deg, transparent 0 80px, rgba(255,255,255,.015) 80px 81px);
    pointer-events:none;mask:linear-gradient(90deg,transparent 0%, #000 40%, #000 100%);
}
.hero::after{
    content:"";position:absolute;right:-40px;top:0;bottom:0;width:60%;
    background:
        linear-gradient(115deg, transparent 55%, rgba(182,255,46,.35) 56%, rgba(182,255,46,.35) 57%, transparent 58%),
        linear-gradient(115deg, transparent 60%, rgba(182,255,46,.2) 61%, transparent 63%);
    pointer-events:none;
}
.hero-left{display:flex;align-items:center;gap:18px;position:relative;z-index:2}
.hero-icon{
    width:60px;height:60px;border-radius:14px;
    background:rgba(182,255,46,.1);border:1px solid rgba(182,255,46,.3);
    display:grid;place-items:center;font-size:28px;color:var(--green);
    box-shadow:0 0 24px rgba(182,255,46,.15), inset 0 0 20px rgba(182,255,46,.1);
}
.hero h1{font-size:30px;font-weight:700;letter-spacing:-.5px}
.hero p{color:var(--muted);margin-top:6px;line-height:1.5;font-size:14px}
.hero-visual{position:absolute;right:40px;top:50%;transform:translateY(-50%);display:grid;gap:2px;z-index:2}
.hero-tag{color:#fff;font-weight:800;font-size:20px;letter-spacing:1px;line-height:1}

/* ===== Grid ===== */
.grid-2{display:grid;grid-template-columns:1fr 400px;gap:22px}
.col-main,.col-side{display:flex;flex-direction:column;gap:22px}

/* ===== Card ===== */
.card{
    background:linear-gradient(180deg, var(--panel) 0%, var(--panel-2) 100%);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:24px;position:relative;
}
.card-head{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.head-icon{
    width:36px;height:36px;border-radius:10px;
    background:rgba(182,255,46,.08);color:var(--green);
    display:grid;place-items:center;font-size:16px;
    border:1px solid rgba(182,255,46,.2);
}
.card-head h3{font-size:16px;font-weight:600}
.muted{color:var(--muted)}
.muted.small,.small{font-size:13px}
.muted.xs,.xs{font-size:11px;color:var(--muted)}
.mt{margin-top:14px;display:inline-block}
.green-text{color:var(--green)}

/* ===== Badges ===== */
.badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:40px;font-size:12px;font-weight:500}
.badge-green{background:rgba(182,255,46,.12);color:var(--green);border:1px solid rgba(182,255,46,.3)}
.badge-lg{padding:7px 14px;font-size:13px;position:absolute;top:20px;left:20px}
.badge.small{padding:3px 10px;font-size:11px}

/* ===== Buttons ===== */
.btn{display:inline-flex;align-items:center;gap:10px;padding:11px 18px;border-radius:12px;font-size:13px;font-weight:500;transition:all .2s;border:1px solid transparent}
.btn-outline{background:transparent;border:1px solid var(--border-strong);color:var(--text);justify-content:space-between;width:100%}
.btn-outline:hover{border-color:var(--green);color:var(--green)}
.btn-primary{background:var(--green);color:#07100a;font-weight:600;justify-content:space-between;width:100%;box-shadow:0 0 20px var(--green-glow)}
.btn-primary:hover{background:var(--green-2);transform:translateY(-1px)}
.btn-block{width:100%;justify-content:space-between}
.btn .right{margin-left:auto}

/* ===== Member card ===== */
.member-card{
    background:
        radial-gradient(circle at 90% 0%, rgba(182,255,46,.3), transparent 50%),
        linear-gradient(135deg, #0a1013 0%, #0f1a1e 100%);
    border:1px solid rgba(182,255,46,.2);
    border-radius:14px;padding:20px;position:relative;overflow:hidden;
}
.member-card::after{
    content:"";position:absolute;right:-20px;top:-20px;width:120px;height:120px;
    background:linear-gradient(135deg, transparent 50%, rgba(182,255,46,.5) 51%, rgba(182,255,46,.5) 55%, transparent 56%);
}
.member-card-inner{display:flex;justify-content:space-between;align-items:center;gap:16px;position:relative;z-index:2}
.brand{display:flex;align-items:center;gap:8px}
.logo-mark{width:28px;height:28px;background:var(--green);color:#0a0a0a;display:grid;place-items:center;border-radius:6px;font-size:12px}
.logo-text{font-weight:800;letter-spacing:1.5px;font-size:16px}
.card-sub{font-size:9px;letter-spacing:3px;color:var(--muted);margin-top:4px}
.member-infos{margin-top:34px;display:flex;flex-direction:column;gap:3px}
.member-infos.tight{margin-top:20px}
.member-infos strong{font-size:14px}
.member-infos span{font-size:11px;color:var(--muted)}
.qr{width:88px;height:88px;border-radius:6px;background:#fff;padding:4px}
.qr.small{width:74px;height:74px}
.member-card.compact{padding:18px}

/* ============================================
   STYLES SPÉCIFIQUES — PAGE ABONNEMENT
   ============================================ */

/* ===== Grille principale abonnement ===== */
.sub-main-grid{display:grid;grid-template-columns:280px 1fr;gap:28px;margin-top:40px;align-items:flex-start}
.sub-details h2{font-size:24px;font-weight:700;margin:10px 0 6px}
.sub-details > .muted{font-size:14px;margin-bottom:18px}
.pills{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:20px}
.pill{display:flex;align-items:center;gap:10px;background:#0a1115;border:1px solid var(--border);padding:10px 12px;border-radius:10px}
.pill i{color:var(--green);font-size:14px}
.pill strong{display:block;font-size:13px}
.pill span{font-size:11px;color:var(--muted)}
.dates-list{list-style:none;display:flex;flex-direction:column;gap:10px}
.dates-list li{display:flex;align-items:center;gap:12px}
.dates-list i{color:var(--green);width:18px}
.dates-list span{font-size:12px;color:var(--muted);display:block}
.dates-list strong{font-size:14px}

/* ===== Progression ===== */
.progress-wrap{margin-top:24px}
.progress-head{display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:8px}
.progress{height:8px;background:#1a2328;border-radius:40px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--green-2),var(--green));border-radius:40px;box-shadow:0 0 12px var(--green-glow);transition:width 1s ease}

/* ===== Note renouvellement ===== */
.renew-note{
    margin-top:22px;background:#0a1115;border:1px solid var(--border);
    border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;font-size:13px;color:var(--muted);
}
.renew-note > i{color:var(--green);font-size:16px}
.renew-note span{flex:1;line-height:1.5}
.green-link{color:var(--green);font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px}
.green-link:hover{text-decoration:underline}

/* ===== Historique ===== */
.history{width:100%;border-collapse:collapse;margin-top:4px}
.history th{text-align:left;font-size:12px;font-weight:500;color:var(--muted);padding:8px 10px;border-bottom:1px solid var(--border)}
.history td{padding:14px 10px;font-size:13px;border-bottom:1px solid var(--border)}
.history tr:last-child td{border-bottom:none}

/* ===== Statut sidebar ===== */
.status-row{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}
.status-dot{width:36px;height:36px;border-radius:50%;background:rgba(182,255,46,.1);border:1px solid rgba(182,255,46,.3);color:var(--green);display:grid;place-items:center;flex-shrink:0}
.status-row strong{font-size:17px;display:block;margin-bottom:4px}

/* ===== Avantages ===== */
.advantages{list-style:none;display:flex;flex-direction:column;gap:12px;margin-bottom:16px}
.advantages li{display:flex;align-items:center;gap:10px;font-size:13px}
.advantages i{color:var(--green);font-size:12px;width:16px;height:16px;border-radius:50%;background:rgba(182,255,46,.1);display:grid;place-items:center}

/* ===== Responsive ===== */
@media(max-width:1280px){
    .grid-2{grid-template-columns:1fr}
    .sub-main-grid{grid-template-columns:1fr}
}
@media(max-width:860px){
    .main-content{margin-left:0;padding:18px}
    .burger{display:flex}
    .main-content{padding-top:70px}
    .hero-visual{display:none}
    .pills{grid-template-columns:1fr}
}

</style>