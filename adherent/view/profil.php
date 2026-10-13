<?php
exige_role('ADHERENT');
require dirname(__DIR__) . '/includes/profil/data.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon profil – ATHLETIX</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/sidebar.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<div class="app-shell">
    <?php $activeNav = 'profil'; include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <button class="burger" id="burger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>


<!-- Hero -->
        <section class="hero">
            <div class="hero-left">
                <div class="hero-icon"><i class="fa-regular fa-circle-user"></i></div>
                <div>
                    <h1>Mon profil</h1>
                    <p>Gérez vos informations personnelles, votre abonnement<br>et vos préférences depuis votre espace membre.</p>
                </div>
            </div>
            <div class="hero-visual" aria-hidden="true">
                <span class="hero-tag">PLUS</span>
                <span class="hero-tag">FORT</span>
                <span class="hero-tag">CHAQUE</span>
                <span class="hero-tag">JOUR</span>
            </div>
        </section>

        <!-- Grid 2 colonnes -->
        <div class="grid-2">

            <!-- ===== Colonne principale ===== -->
            <div class="col-main">

                <!-- Carte identité -->
                <section class="card identity-card">
                    <div class="identity-left">
                        <div class="avatar-ring">
                            <img src="assets/images/avatar.png" alt="Avatar Thomas Martin" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="avatar-fallback"><i class="fa-solid fa-user"></i></div>
                            <button class="avatar-edit" aria-label="Modifier la photo"><i class="fa-solid fa-camera"></i></button>
                        </div>
                    </div>

                    <div class="identity-info">
                        <div class="identity-name">
                            <h2><?= htmlspecialchars($user['prenom'].' '.$user['nom']); ?></h2>
                            <span class="verified" title="Compte vérifié"><i class="fa-solid fa-circle-check"></i></span>
                        </div>
                        <p class="muted">Membre ATHLETIX depuis le <?= htmlspecialchars($user['membre_depuis']); ?></p>
                        <span class="badge badge-green">Abonnement actif</span>

                        <ul class="contact-list">
                            <li><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($user['email']); ?></li>
                            <li><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($user['telephone']); ?></li>
                            <li><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($user['ville']); ?></li>
                        </ul>
                    </div>

                    <button class="btn btn-ghost btn-edit-top">
                        <i class="fa-regular fa-pen-to-square"></i>
                        Modifier les informations
                    </button>
                </section>

                <!-- Informations personnelles -->
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-regular fa-user"></i></div>
                        <h3>Informations personnelles</h3>
                    </header>

                    <div class="form-grid">
                        <?php
                        $fields = [
                            ['Nom', $user['nom']],
                            ['Adresse', $user['adresse_l1']."<br>".$user['adresse_l2']],
                            ['Prénom', $user['prenom']],
                            ['Téléphone', $user['telephone']],
                            ['Date de naissance', $user['date_naissance']],
                            ['Email', $user['email']],
                            ['Sexe', $user['sexe']],
                        ];
                        foreach ($fields as $f): ?>
                            <div class="field">
                                <span class="field-label"><?= $f[0]; ?></span>
                                <span class="field-value"><?= $f[1]; ?></span>
                                <button class="field-edit" aria-label="Modifier <?= $f[0]; ?>"><i class="fa-regular fa-pen-to-square"></i></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Préférences de notification -->
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-regular fa-bell"></i></div>
                        <div>
                            <h3>Préférences de notification</h3>
                            <p class="muted small">Choisissez comment vous souhaitez être informé</p>
                        </div>
                    </header>

                    <div class="prefs-grid">
                        <?php
                        $prefs = [
                            ['icon'=>'fa-regular fa-comment-dots','titre'=>'Messages','sub'=>'Coach, accueil, administration','on'=>true],
                            ['icon'=>'fa-regular fa-calendar','titre'=>'Rappels de réservation','sub'=>'Heure de vos séances','on'=>true],
                            ['icon'=>'fa-regular fa-newspaper','titre'=>'Actualités','sub'=>'Événements et nouveautés','on'=>true],
                            ['icon'=>'fa-solid fa-tag','titre'=>'Promotions','sub'=>'Offres et avantages','on'=>true],
                        ];
                        foreach ($prefs as $i=>$p): ?>
                            <div class="pref">
                                <i class="<?= $p['icon']; ?>"></i>
                                <div class="pref-text">
                                    <strong><?= $p['titre']; ?></strong>
                                    <span><?= $p['sub']; ?></span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" <?= $p['on']?'checked':''; ?> data-pref="<?= $i; ?>">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <!-- ===== Colonne latérale ===== -->
            <aside class="col-side">

                <!-- Carte adhérent -->
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-regular fa-credit-card"></i></div>
                        <h3>Carte d'adhérent</h3>
                    </header>

                    <div class="member-card">
                        <div class="member-card-inner">
                            <div>
                                <div class="brand">
                                    <span class="logo-mark"><i class="fa-solid fa-angles-up"></i></span>
                                    <span class="logo-text">ATHLETIX</span>
                                </div>
                                <p class="card-sub">CARTE MEMBRE</p>
                                <div class="member-infos">
                                    <strong><?= htmlspecialchars($user['prenom'].' '.$user['nom']); ?></strong>
                                    <span>N° Membre : <?= htmlspecialchars($user['num_membre']); ?></span>
                                </div>
                            </div>
                            <img class="qr" src="assets/images/qr-placeholder.png" alt="QR Code fictif">
                        </div>
                    </div>

                    <button class="btn btn-ghost btn-block">
                        <i class="fa-regular fa-eye"></i> Voir ma carte complète
                        <i class="fa-solid fa-chevron-right right"></i>
                    </button>
                </section>

                <!-- Mon abonnement résumé -->
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-solid fa-crown"></i></div>
                        <h3>Mon abonnement</h3>
                    </header>

                    <div class="sub-grid">
                        <div>
                            <p class="muted small">Formule</p>
                            <strong class="green-text big"><?= htmlspecialchars($abonnement['formule']); ?></strong>
                        </div>
                        <div>
                            <p class="muted small"><i class="fa-regular fa-calendar"></i> Prochaine échéance</p>
                            <strong><?= htmlspecialchars($abonnement['prochaine_echeance']); ?></strong>
                            <span class="muted xs">Renouvellement <?= htmlspecialchars($abonnement['renouvellement']); ?></span>
                        </div>
                    </div>

                    <p class="muted small mt">Accès illimité</p>
                    <ul class="access-list">
                        <?php foreach ($abonnement['acces'] as $a): ?>
                            <li><i class="fa-solid fa-<?= $a['icon']; ?>"></i> <?= htmlspecialchars($a['label']); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <a href="abonnement.php" class="btn btn-primary btn-block">
                        Gérer mon abonnement <i class="fa-solid fa-chevron-right right"></i>
                    </a>
                </section>

                <!-- Besoin d'aide -->
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-solid fa-headset"></i></div>
                        <h3>Besoin d'aide ?</h3>
                    </header>
                    <p class="muted small">Une question sur votre compte, votre abonnement ou vos réservations.</p>
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
    // ===== ATHLETIX — Petites interactions =====
document.addEventListener('DOMContentLoaded', () => {

    // Switch de préférences (persistance localStorage simple)
    document.querySelectorAll('.switch input[data-pref]').forEach(input => {
        const key = 'athletix-pref-' + input.dataset.pref;
        const saved = localStorage.getItem(key);
        if (saved !== null) input.checked = saved === '1';
        input.addEventListener('change', () => {
            localStorage.setItem(key, input.checked ? '1' : '0');
        });
    });

    // Boutons "edit" sur les champs → petite anim + mock
    document.querySelectorAll('.field-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            const field = btn.closest('.field');
            field.style.borderColor = 'var(--green)';
            setTimeout(() => field.style.borderColor = '', 900);
        });
    });

    // Bouton "Modifier les informations"
    const topEdit = document.querySelector('.btn-edit-top');
    if (topEdit) {
        topEdit.addEventListener('click', () => {
            document.querySelectorAll('.field').forEach((f, i) => {
                setTimeout(() => {
                    f.style.borderColor = 'var(--green)';
                    setTimeout(() => f.style.borderColor = '', 600);
                }, i * 80);
            });
        });
    }

    // Avatar edit (mock)
    const avatarEdit = document.querySelector('.avatar-edit');
    if (avatarEdit) {
        avatarEdit.addEventListener('click', () => {
            const ring = document.querySelector('.avatar-ring');
            ring.animate(
                [{ transform: 'scale(1)' }, { transform: 'scale(1.05)' }, { transform: 'scale(1)' }],
                { duration: 400 }
            );
        });
    }

    // Animation de la barre de progression au chargement
    const fill = document.querySelector('.progress-fill');
    if (fill) {
        const w = fill.style.width;
        fill.style.width = '0';
        requestAnimationFrame(() => {
            setTimeout(() => (fill.style.width = w), 100);
        });
    }

    // Chip utilisateur (menu factice)
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
    /* ===== ATHLETIX — Design System ===== */
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

/* ===== Identity card ===== */
.identity-card{display:grid;grid-template-columns:auto 1fr auto;gap:28px;align-items:flex-start}
.avatar-ring{
    position:relative;width:128px;height:128px;border-radius:50%;
    border:2px solid var(--green);padding:4px;
    box-shadow:0 0 24px rgba(182,255,46,.3);
}
.avatar-ring img,.avatar-fallback{
    width:100%;height:100%;border-radius:50%;object-fit:cover;background:#1a2328;display:grid;place-items:center;color:var(--muted);font-size:52px;
}
.avatar-fallback{display:none}
.avatar-edit{
    position:absolute;bottom:6px;right:6px;width:32px;height:32px;border-radius:50%;
    background:var(--green);color:#0a0a0a;display:grid;place-items:center;font-size:12px;
    box-shadow:0 0 14px var(--green-glow);
}
.identity-name{display:flex;align-items:center;gap:10px}
.identity-name h2{font-size:26px;font-weight:700}
.verified{color:var(--green);font-size:20px}
.identity-info .muted{font-size:13px;margin-top:4px;margin-bottom:12px}
.contact-list{list-style:none;margin-top:18px;display:flex;flex-direction:column;gap:10px}
.contact-list li{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:14px}
.contact-list i{color:var(--green);width:18px}
.btn-edit-top{align-self:flex-start}

/* ===== Badges ===== */
.badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:40px;font-size:12px;font-weight:500}
.badge-green{background:rgba(182,255,46,.12);color:var(--green);border:1px solid rgba(182,255,46,.3)}
.badge-lg{padding:7px 14px;font-size:13px;position:absolute;top:20px;left:20px}
.badge.small{padding:3px 10px;font-size:11px}

/* ===== Buttons ===== */
.btn{display:inline-flex;align-items:center;gap:10px;padding:11px 18px;border-radius:12px;font-size:13px;font-weight:500;transition:all .2s;border:1px solid transparent}
.btn-ghost{background:#121a1f;border:1px solid var(--border-strong);color:var(--text)}
.btn-ghost:hover{border-color:var(--green);color:var(--green)}
.btn-outline{background:transparent;border:1px solid var(--border-strong);color:var(--text);justify-content:space-between;width:100%}
.btn-outline:hover{border-color:var(--green);color:var(--green)}
.btn-primary{background:var(--green);color:#07100a;font-weight:600;justify-content:space-between;width:100%;box-shadow:0 0 20px var(--green-glow)}
.btn-primary:hover{background:var(--green-2);transform:translateY(-1px)}
.btn-block{width:100%;justify-content:space-between}
.btn .right{margin-left:auto}

/* ===== Form grid (profil) ===== */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{
    position:relative;background:#0a1115;border:1px solid var(--border);
    border-radius:12px;padding:14px 44px 14px 16px;transition:.2s;
}
.field:hover{border-color:var(--border-strong)}
.field-label{display:block;font-size:11px;color:var(--muted);margin-bottom:4px;text-transform:none}
.field-value{font-size:14px;font-weight:500;line-height:1.4}
.field-edit{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--muted);width:28px;height:28px;border-radius:8px;display:grid;place-items:center;transition:.2s}
.field-edit:hover{color:var(--green);background:rgba(182,255,46,.08)}

/* ===== Preferences ===== */
.prefs-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.pref{display:flex;align-items:flex-start;gap:12px;background:#0a1115;border:1px solid var(--border);border-radius:12px;padding:14px}
.pref > i{color:var(--green);font-size:16px;margin-top:2px}
.pref-text{flex:1;display:flex;flex-direction:column;gap:2px}
.pref-text strong{font-size:13px;font-weight:600}
.pref-text span{font-size:11px;color:var(--muted);line-height:1.3}

/* ===== Switch ===== */
.switch{position:relative;display:inline-block;width:40px;height:22px;flex-shrink:0}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:#2a3842;border-radius:40px;transition:.3s}
.slider::before{content:"";position:absolute;height:16px;width:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.3s}
.switch input:checked + .slider{background:var(--green)}
.switch input:checked + .slider::before{transform:translateX(18px);background:#0a0a0a}

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
    transform:rotate(0deg);
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

/* ===== Sub-grid (profil sidebar abonnement) ===== */
.sub-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px}
.sub-grid strong{display:block;margin-top:4px;font-size:15px}
.green-text{color:var(--green)}
.big{font-size:22px !important;font-weight:700}
.access-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:20px}
.access-list li{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)}
.access-list i{color:var(--green);width:18px}

/* ===== Abonnement details ===== */
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

/* ===== Progress ===== */
.progress-wrap{margin-top:24px}
.progress-head{display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-bottom:8px}
.progress{height:8px;background:#1a2328;border-radius:40px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--green-2),var(--green));border-radius:40px;box-shadow:0 0 12px var(--green-glow);transition:width 1s ease}

/* ===== Renew note ===== */
.renew-note{
    margin-top:22px;background:#0a1115;border:1px solid var(--border);
    border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;font-size:13px;color:var(--muted);
}
.renew-note > i{color:var(--green);font-size:16px}
.renew-note span{flex:1;line-height:1.5}
.green-link{color:var(--green);font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px}
.green-link:hover{text-decoration:underline}

/* ===== History table ===== */
.history{width:100%;border-collapse:collapse;margin-top:4px}
.history th{text-align:left;font-size:12px;font-weight:500;color:var(--muted);padding:8px 10px;border-bottom:1px solid var(--border)}
.history td{padding:14px 10px;font-size:13px;border-bottom:1px solid var(--border)}
.history tr:last-child td{border-bottom:none}

/* ===== Status row ===== */
.status-row{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px}
.status-dot{width:36px;height:36px;border-radius:50%;background:rgba(182,255,46,.1);border:1px solid rgba(182,255,46,.3);color:var(--green);display:grid;place-items:center;flex-shrink:0}
.status-row strong{font-size:17px;display:block;margin-bottom:4px}

/* ===== Advantages ===== */
.advantages{list-style:none;display:flex;flex-direction:column;gap:12px;margin-bottom:16px}
.advantages li{display:flex;align-items:center;gap:10px;font-size:13px}
.advantages i{color:var(--green);font-size:12px;width:16px;height:16px;border-radius:50%;background:rgba(182,255,46,.1);display:grid;place-items:center}

/* ===== Responsive ===== */
@media(max-width:1280px){
    .grid-2{grid-template-columns:1fr}
    .sub-main-grid{grid-template-columns:1fr}
    .form-grid,.prefs-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:860px){
    .main-content{margin-left:0;padding:18px}
    .burger{display:flex}
    .main-content{padding-top:70px}
    .identity-card{grid-template-columns:1fr;text-align:center}
    .avatar-ring{margin:0 auto}
    .hero-visual{display:none}
    .form-grid,.prefs-grid,.pills,.sub-grid{grid-template-columns:1fr}
}

</style>