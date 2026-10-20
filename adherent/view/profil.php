<?php
require_once __DIR__ . '/../../config/function.php';
exige_role('ADHERENT');
require dirname(__DIR__) . '/includes/profil/data.php';

$commune = [
    'id_commune' => $user['id_commune'] ?? '',
    'nom_commune' => $user['nom_commune'] ?? '',
    'code_postal' => $user['code_postal'] ?? '',
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon profil – ATHLETIX</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/commune/commune.css')) ?>">
<script src="<?= e(url('assets/js/commune/commune.js')) ?>" defer></script>
</head>
<body>

<div class="app-shell">
    <?php $activeNav = 'profil'; include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include dirname(__DIR__) . '/includes/topbar.php'; ?>


<!-- Hero -->
        <section class="hero">
            <div class="hero-left">
                <div class="hero-icon"><i class="fa-regular fa-circle-user"></i></div>
                <div>
                    <h1>Mon profil</h1>
                    <p>Gérez vos informations personnelles et retrouvez les détails de votre abonnement.</p>
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
                            <div class="avatar-fallback">
                                <img id="profileAvatar" src="<?= $user['avatar'] ? e(url($user['avatar'])) : '' ?>" alt="Photo de profil" <?= $user['avatar'] ? '' : 'hidden' ?>>
                                <i id="profileAvatarFallback" class="fa-solid fa-user" <?= $user['avatar'] ? 'hidden' : '' ?>></i>
                            </div>
                            <form id="photoForm" class="photo-form" enctype="multipart/form-data">
                                <?= champ_csrf() ?>
                                <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/png,image/webp" hidden>
                                <label for="photoUpload" class="photo-edit" title="Modifier la photo de profil" aria-label="Modifier la photo de profil"><i class="fa-solid fa-camera"></i></label>
                            </form>
                        </div>
                    </div>

                    <div class="identity-info">
                        <div class="identity-name">
                            <h2><?= htmlspecialchars($user['prenom'].' '.$user['nom']); ?></h2>
                            <span class="verified" title="Compte vérifié"><i class="fa-solid fa-circle-check"></i></span>
                        </div>
                        <p class="muted">Membre ATHLETIX depuis le <?= htmlspecialchars($user['membre_depuis']); ?></p>
                        <span class="badge badge-green">Abonnement <?= e(mb_strtolower($abonnement['statut'])) ?></span>

                        <ul class="contact-list">
                            <li><i class="fa-regular fa-envelope"></i> <span id="profileContactEmail"><?= e($user['email']) ?></span></li>
                            <li><i class="fa-solid fa-phone"></i> <span id="profileContactPhone"><?= e($user['telephone'] ?: 'Téléphone non renseigné') ?></span></li>
                        </ul>
                    </div>

                    <button class="btn btn-ghost btn-edit-top" type="submit" form="profileForm">
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

                    <form id="profileForm">
                        <div class="form-grid">
                            <?php foreach (['nom'=>'Nom','prenom'=>'Prénom','telephone'=>'Téléphone','email'=>'Email'] as $key=>$label): ?>
            <label class="field">
                                <span class="field-label"><?= e($label) ?></span>
                                <input class="field-value" type="<?= $key === 'email' ? 'email' : ($key === 'telephone' ? 'tel' : 'text') ?>" name="<?= e($key) ?>" value="<?= e($user[$key]) ?>" <?= $key !== 'telephone' ? 'required' : '' ?><?php if ($key === 'telephone'): ?> inputmode="numeric" autocomplete="tel-national" maxlength="14" placeholder="06 12 34 56 78" aria-describedby="phoneHelp"<?php endif; ?>>
                            </label>
                            <?php endforeach; ?>
                            <?php include dirname(__DIR__, 2) . '/commune/view/champ.php'; ?>
                            <label class="field field-wide">
                                <span class="field-label">Adresse</span>
                                <input class="field-value" type="text" name="adresse_postale" value="<?= e((string) ($user['adresse_postale'] ?? '')) ?>" maxlength="255" required>
                            </label>
                        </div>
                        <p id="profileMessage" role="status" aria-live="polite"></p>
                    </form>
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
                            <img class="qr-member-number" src="<?= e($user['qr_image']) ?>" alt="QR code de votre carte adhérent">
                        </div>
                    </div>

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
                            <?php if ($abonnement['renouvellement']): ?><span class="muted xs">Renouvellement <?= e($abonnement['renouvellement']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <ul class="access-list">
                        <?php foreach ($abonnement['acces'] as $a): ?>
                            <li><i class="fa-solid fa-<?= $a['icon']; ?>"></i> <?= htmlspecialchars($a['label']); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <a href="<?= e(url('adherent/view/abonnement.php')) ?>" class="btn btn-primary btn-block">
                        Gérer mon abonnement <i class="fa-solid fa-chevron-right right"></i>
                    </a>
                </section>

            </aside>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const phoneInput = document.querySelector('#profileForm [name="telephone"]');
    if (phoneInput) {
        const digitsOnly = value => value.replace(/\D/g, '').slice(0, 10);
        const formatPhone = value => digitsOnly(value).match(/.{1,2}/g)?.join(' ') || '';
        const initialDigits = phoneInput.value.replace(/\D/g, '');
        if (initialDigits.length <= 10) phoneInput.value = formatPhone(initialDigits);
        phoneInput.addEventListener('input', () => {
            const digitsBeforeCaret = phoneInput.value.slice(0, phoneInput.selectionStart ?? phoneInput.value.length).replace(/\D/g, '').length;
            const digits = digitsOnly(phoneInput.value);
            phoneInput.value = formatPhone(digits);
            let caret = 0;
            let countedDigits = 0;
            while (caret < phoneInput.value.length && countedDigits < digitsBeforeCaret) {
                if (/\d/.test(phoneInput.value[caret])) countedDigits++;
                caret++;
            }
            phoneInput.setSelectionRange(caret, caret);
        });
    }

    document.getElementById('photoUpload')?.addEventListener('change', async event => {
        const file = event.currentTarget.files?.[0];
        if (!file) return;
        const form = document.getElementById('photoForm');
        const message = document.getElementById('profileMessage');
        const formData = new FormData(form);
        formData.set('photo', file);
        message.textContent = 'Enregistrement de la photo…';
        try {
            const endpoint = <?= json_encode(url('adherent/api.php?action=profile_photo'), JSON_UNESCAPED_SLASHES) ?>;
            const response = await fetch(endpoint, {method: 'POST', body: formData});
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'La photo n’a pas pu être enregistrée.');
            const avatar = document.getElementById('profileAvatar');
            avatar.src = result.photo_url + '?v=' + Date.now();
            avatar.hidden = false;
            document.getElementById('profileAvatarFallback').hidden = true;
            message.textContent = 'Photo de profil enregistrée.';
        } catch (error) {
            message.textContent = error.message;
        } finally {
            event.currentTarget.value = '';
        }
    });

    document.getElementById('profileForm')?.addEventListener('submit', async event => {
        event.preventDefault();
        const saveButton = document.querySelector('.btn-edit-top');
        if (event.submitter !== saveButton) return;
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form).entries());
        const message = document.getElementById('profileMessage');
        saveButton.disabled = true;
        try {
            const result = await window.ADH_API('profile', data);
            message.textContent = 'Informations enregistrées.';
            const profile = result.profile;
            document.querySelector('.identity-name h2').textContent = profile.prenom + ' ' + profile.nom;
            document.querySelector('#profileContactEmail').textContent = profile.email;
            document.querySelector('#profileContactPhone').textContent = profile.telephone || 'Téléphone non renseigné';
            document.querySelector('.member-infos strong').textContent = profile.prenom + ' ' + profile.nom;
        } catch (error) { message.textContent = error.message; }
        finally { saveButton.disabled = false; }
    });

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
.main-content{width:calc(100% - 250px);min-width:0;margin-left:250px;padding:0 0 60px;max-width:1600px}
.main-content > .hero,.main-content > .grid-2{margin-inline:36px}

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
.grid-2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,400px);gap:22px;min-width:0}
.col-main,.col-side{display:flex;flex-direction:column;gap:22px;min-width:0}

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

/* ===== Identity card ===== */
.identity-card{display:grid;grid-template-columns:auto 1fr auto;gap:28px;align-items:flex-start}
.avatar-ring{
    position:relative;width:128px;height:128px;border-radius:50%;
    border:2px solid var(--green);padding:4px;
    box-shadow:0 0 24px rgba(182,255,46,.3);
}
.avatar-fallback{
    width:100%;height:100%;border-radius:50%;object-fit:cover;background:#1a2328;display:grid;place-items:center;color:var(--muted);font-size:52px;
}
.avatar-fallback{display:grid}
.avatar-fallback img{width:100%;height:100%;border-radius:50%;object-fit:cover}
#profileAvatar[hidden],#profileAvatarFallback[hidden]{display:none}
.photo-form{position:absolute;right:2px;bottom:2px;z-index:2}
.photo-edit{width:36px;height:36px;display:grid;place-items:center;border:2px solid var(--bg);border-radius:50%;background:var(--green);color:#07100a;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.4)}
.photo-edit:hover{background:var(--green-2);transform:scale(1.05)}
.identity-name{display:flex;align-items:center;gap:10px}
.identity-name h2{font-size:26px;font-weight:700}
.verified{color:var(--green);font-size:20px}
.identity-info .muted{font-size:13px;margin-top:4px;margin-bottom:12px}
.contact-list{list-style:none;margin-top:18px;display:flex;flex-direction:column;gap:10px}
.contact-list li{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:14px}
.contact-list i{color:var(--green);width:18px}
.btn-edit-top{align-self:flex-start}
.btn-edit-top:hover{background:var(--green-2);border-color:var(--green-2);color:#07100a;transform:translateY(-1px)}

/* ===== Badges ===== */
.badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:40px;font-size:12px;font-weight:500}
.badge-green{background:rgba(182,255,46,.12);color:var(--green);border:1px solid rgba(182,255,46,.3)}

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
    background:#0a1115;border:1px solid var(--border);
    border-radius:12px;padding:14px 16px;transition:.2s;
}
.field:hover{border-color:var(--border-strong)}
.field-label{display:block;font-size:11px;color:var(--muted);margin-bottom:4px;text-transform:none}
.field .field-value{width:100%;background:transparent;border:0;color:var(--text);font:inherit;font-size:14px;outline:none}
.field-value{font-size:14px;font-weight:500;line-height:1.4}
.field-wide{grid-column:1 / -1}

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
.member-infos strong{font-size:14px}
.member-infos span{font-size:11px;color:var(--muted)}
.qr-member-number{display:block;width:88px;height:88px;border:1px solid var(--border);border-radius:6px;background:#fff;object-fit:contain;padding:4px}

/* ===== Sub-grid (profil sidebar abonnement) ===== */
.sub-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px}
.sub-grid strong{display:block;margin-top:4px;font-size:15px}
.green-text{color:var(--green)}
.big{font-size:22px !important;font-weight:700}
.access-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin-bottom:20px}
.access-list li{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)}
.access-list i{color:var(--green);width:18px}

/* ===== Responsive ===== */
@media(max-width:1280px){
    .grid-2{grid-template-columns:1fr}
    .form-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:860px){
    .main-content{width:100%;margin-left:0;padding:0 0 18px}
    .main-content > .hero,.main-content > .grid-2{margin-inline:18px}
    .identity-card{grid-template-columns:1fr;text-align:center}
    .avatar-ring{margin:0 auto}
    .hero-visual{display:none}
    .form-grid,.sub-grid{grid-template-columns:1fr}
}

</style>
</body>
</html>
