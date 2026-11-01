<?php
require_once __DIR__ . '/../../config/function.php';
if (!empty($profilEmploye)) {
    exige_role('EMPLOYE');
    if (!est_technicien_maintenance()) {
        redirige('employe/espace.php');
    }

    $profileQuery = db()->prepare(
        'SELECT u.nomUsers AS nom, u.prenomUsers AS prenom,
                u.mailUsers AS email, u.telUsers AS telephone,
                e.matriculeemp AS matricule, e.Date_embaucheemp AS date_embauche,
                f.libellefonc AS fonction, e.id_commune, e.photo_profil,
                c.nom_commune, c.code_postal
         FROM utilisateur u
         JOIN employe e ON e.NumUsers = u.NumUsers
         JOIN fonction f ON f.Numfonc = e.Numfonc
         LEFT JOIN commune c ON c.id_commune = e.id_commune
         WHERE u.NumUsers = ?'
    );
    $profileQuery->execute([(int) utilisateur()['NumUsers']]);
    $employeeProfile = $profileQuery->fetch(PDO::FETCH_ASSOC);
    if (!$employeeProfile) {
        http_response_code(404);
        exit('Profil employé introuvable.');
    }

    $user = [
        'prenom' => $employeeProfile['prenom'],
        'nom' => $employeeProfile['nom'],
        'email' => $employeeProfile['email'],
        'telephone' => $employeeProfile['telephone'] ?? '',
        'id_commune' => $employeeProfile['id_commune'] ?? '',
        'nom_commune' => $employeeProfile['nom_commune'] ?? '',
        'code_postal' => $employeeProfile['code_postal'] ?? '',
        'membre_depuis' => dateFr($employeeProfile['date_embauche']),
        'num_membre' => $employeeProfile['matricule'],
        'avatar' => $employeeProfile['photo_profil'] ?? '',
    ];
    $abonnement = ['statut' => 'employé'];
    $employeeId = (int) utilisateur()['NumUsers'];
    $maintenanceActivityQuery = db()->prepare(
        'SELECT i.Nummaint, i.date_intervention, i.commentaire, s.libelle_statut,
                eq.nomequip
           FROM intervention i
           JOIN demande_maintenance d ON d.Nummaint = i.Nummaint
           JOIN equipement eq ON eq.Numequip = d.Numequip
           JOIN statut s ON s.Numstatut = i.Numstatut
          WHERE i.NumUsers = ?
          ORDER BY i.date_intervention DESC
          LIMIT 5'
    );
    $maintenanceActivityQuery->execute([$employeeId]);
    $maintenanceActivity = $maintenanceActivityQuery->fetchAll(PDO::FETCH_ASSOC);
} else {
    exige_role('ADHERENT');
    require dirname(__DIR__) . '/includes/profil/data.php';
}

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
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/commune/commune.css')) ?>">
<?php if (!empty($profilEmploye)): ?>
<link rel="stylesheet" href="<?= e(url('assets/css/pages/admin.css')) ?>">
<?php endif; ?>
<script src="<?= e(url('assets/js/commune/commune.js')) ?>" defer></script>
</head>
<body<?= !empty($profilEmploye) ? ' class="admin-page employee-page maintenance-page maintenance-profile-page"' : '' ?>>

<div class="<?= !empty($profilEmploye) ? 'admin-shell' : 'app-shell' ?>">
    <?php if (!empty($profilEmploye)): ?>
        <?php
        $sidebarItems = [
            ['id' => 'tableau', 'label' => 'Tableau de bord', 'icon' => 'home', 'href' => url('employe/espace.php?onglet=tableau')],
            ['id' => 'maintenance', 'label' => 'Mes tickets', 'icon' => 'tool', 'href' => url('employe/espace.php?onglet=maintenance')],
            ['id' => 'materiel', 'label' => 'Matériel', 'icon' => 'building', 'href' => url('employe/espace.php?onglet=materiel')],
            ['id' => 'profil', 'label' => 'Mon profil', 'icon' => 'user', 'href' => url('employe/profil.php')],
        ];
        $sidebarActive = 'profil';
        $sidebarHome = url('employe/espace.php?onglet=tableau');
        $sidebarBrand = null;
        $sidebarLabel = 'Navigation maintenance';
        $sidebarSubtitle = 'Maintenance';
        require dirname(__DIR__, 2) . '/layout/sidebar.php';
        ?>
    <?php else: ?>
        <?php $activeNav = 'profil'; include dirname(__DIR__) . '/includes/sidebar.php'; ?>
    <?php endif; ?>

    <?php if (!empty($profilEmploye)): ?>
    <div class="admin-main">
            <?php
            $topbarUser = utilisateur();
            $topbarTitle = 'Mon profil';
            $topbarContext = 'Espace sécurisé';
            $topbarClass = 'admin-topbar';
            $topbarLinks = [
                ['label' => 'Tableau de bord', 'href' => url('employe/espace.php?onglet=tableau')],
                ['label' => 'Déconnexion', 'href' => url('utilisateur/deconnexion.php')],
            ];
            require dirname(__DIR__, 2) . '/layout/topbar.php';
            ?>
    <?php else: ?>
    <main class="main-content">
        <?php include dirname(__DIR__) . '/includes/topbar.php'; ?>
    <?php endif; ?>

    <?php if (!empty($profilEmploye)): ?>
    <main class="admin-content maintenance-profile-content">
    <?php endif; ?>

<?php if (!empty($profilEmploye)): ?>
        <header class="employee-profile-heading">
            <h2>Mon profil</h2>
            <p>Gérez vos informations et vos préférences.</p>
        </header>

        <div class="employee-profile-grid">
            <section class="employee-profile-card employee-profile-identity" aria-label="Informations du compte">
                <div class="employee-profile-avatar">
                    <img id="profileAvatar" src="<?= $user['avatar'] ? e(url($user['avatar'])) : '' ?>" alt="Photo de profil" <?= $user['avatar'] ? '' : 'hidden' ?>>
                    <span id="profileAvatarFallback" <?= $user['avatar'] ? 'hidden' : '' ?>><?= e(mb_strtoupper(mb_substr($user['prenom'] ?: '?', 0, 1) . mb_substr($user['nom'] ?: '', 0, 1))) ?></span>
                    <form id="photoForm" class="employee-photo-form" enctype="multipart/form-data">
                        <?= champ_csrf() ?>
                        <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/png,image/webp" hidden>
                        <label for="photoUpload" title="Modifier la photo de profil" aria-label="Modifier la photo de profil"><i class="fa-solid fa-camera" aria-hidden="true"></i></label>
                    </form>
                </div>
                <div class="employee-identity-details">
                    <h3 id="employeeProfileName"><?= e(trim($user['prenom'] . ' ' . $user['nom'])) ?></h3>
                    <span class="employee-role-badge"><i class="fa-solid fa-wrench" aria-hidden="true"></i> <?= e($employeeProfile['fonction']) ?></span>
                    <p><i class="fa-solid fa-envelope" aria-hidden="true"></i><span id="profileContactEmail"><?= e($user['email']) ?></span></p>
                    <p><i class="fa-solid fa-phone" aria-hidden="true"></i><span id="profileContactPhone"><?= e($user['telephone'] ?: 'Téléphone non renseigné') ?></span></p>
                    <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span id="employeeProfileLocation"><?= e(trim($user['nom_commune'] . ' ' . $user['code_postal']) ?: 'Lieu non renseigné') ?></span></p>
                </div>
            </section>

            <section class="employee-profile-card employee-profile-information" aria-labelledby="employee-info-title">
                <header class="employee-profile-section-heading">
                    <h3 id="employee-info-title"><i class="fa-solid fa-gear" aria-hidden="true"></i> Mes informations</h3>
                    <button class="employee-profile-edit-toggle" type="button" aria-expanded="false" aria-controls="profileForm"><i class="fa-solid fa-pen" aria-hidden="true"></i> Modifier</button>
                </header>
                <dl class="employee-profile-details">
                    <div><dt>Nom complet</dt><dd id="employeeProfileFullName"><?= e(trim($user['prenom'] . ' ' . $user['nom'])) ?></dd></div>
                    <div><dt>Poste</dt><dd><?= e($employeeProfile['fonction']) ?></dd></div>
                    <div><dt>Matricule</dt><dd><?= e($employeeProfile['matricule']) ?></dd></div>
                    <div><dt>Email</dt><dd id="employeeProfileEmail"><?= e($user['email']) ?></dd></div>
                    <div><dt>Téléphone</dt><dd id="employeeProfilePhone"><?= e($user['telephone'] ?: 'Non renseigné') ?></dd></div>
                    <div><dt>Lieu</dt><dd id="employeeProfileAddress"><?= e(trim($user['nom_commune'] . ' ' . $user['code_postal']) ?: 'Non renseigné') ?></dd></div>
                    <div><dt>Employé depuis</dt><dd><?= e($user['membre_depuis']) ?></dd></div>
                </dl>
                <form id="profileForm" class="employee-profile-form" hidden>
                    <div class="form-grid">
                        <?php foreach (['nom'=>'Nom','prenom'=>'Prénom','telephone'=>'Téléphone','email'=>'Email'] as $key=>$label): ?>
                            <label class="field">
                                <span class="field-label"><?= e($label) ?></span>
                                <input class="field-value" type="<?= $key === 'email' ? 'email' : ($key === 'telephone' ? 'tel' : 'text') ?>" name="<?= e($key) ?>" value="<?= e($user[$key]) ?>" <?= $key !== 'telephone' ? 'required' : '' ?><?php if ($key === 'telephone'): ?> inputmode="numeric" autocomplete="tel-national" maxlength="14" placeholder="06 12 34 56 78"<?php endif; ?>>
                            </label>
                        <?php endforeach; ?>
                        <?php include dirname(__DIR__, 2) . '/commune/view/champ.php'; ?>
                    </div>
                    <div class="employee-profile-form-actions">
                        <button class="employee-profile-cancel" type="button">Annuler</button>
                        <button class="employee-profile-save" type="submit">Enregistrer</button>
                    </div>
                </form>
                <p id="profileMessage" class="employee-profile-message" role="status" aria-live="polite"></p>
            </section>

            <section class="employee-profile-card employee-profile-tools" aria-labelledby="employee-tools-title">
                <h3 id="employee-tools-title"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i> Mes outils et accès</h3>
                <div class="employee-tool-links">
                    <a href="<?= e(url('employe/espace.php?onglet=maintenance')) ?>"><i class="fa-solid fa-ticket" aria-hidden="true"></i><strong>Gérer mes tickets</strong><span>Voir et traiter les demandes</span><b aria-hidden="true">→</b></a>
                    <a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>"><i class="fa-solid fa-cube" aria-hidden="true"></i><strong>Gérer le matériel</strong><span>Consulter l’inventaire</span><b aria-hidden="true">→</b></a>
                </div>
            </section>

            <section class="employee-profile-card employee-profile-activity" aria-labelledby="employee-activity-title">
                <h3 id="employee-activity-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Mon activité récente</h3>
                <?php if (!$maintenanceActivity): ?>
                    <p class="employee-profile-empty">Aucune intervention enregistrée pour le moment.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($maintenanceActivity as $activity): ?>
                            <li>
                                <span class="employee-activity-dot" aria-hidden="true"></span>
                                <span class="employee-activity-copy"><strong>Ticket #TKT-<?= (int) $activity['Nummaint'] ?> <?= e(mb_strtolower($activity['libelle_statut'])) ?> — <?= e($activity['nomequip']) ?></strong><?php if (trim((string) $activity['commentaire']) !== ''): ?><small><?= e($activity['commentaire']) ?></small><?php endif; ?></span>
                                <time><?= e(dateFr($activity['date_intervention'], true)) ?></time>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
<?php else: ?>
<!-- Hero -->
        <section class="hero">
            <div class="hero-left">
                <div class="hero-icon"><i class="fa-regular fa-circle-user"></i></div>
                <div>
                    <h1>Mon profil</h1>
                    <p><?= !empty($profilEmploye) ? 'Gérez vos informations personnelles et consultez vos informations professionnelles.' : 'Gérez vos informations personnelles et retrouvez les détails de votre abonnement.' ?></p>
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
                            <?php if (empty($profilEmploye)): ?>
                            <form id="photoForm" class="photo-form" enctype="multipart/form-data">
                                <?= champ_csrf() ?>
                                <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/png,image/webp" hidden>
                                <label for="photoUpload" class="photo-edit" title="Modifier la photo de profil" aria-label="Modifier la photo de profil"><i class="fa-solid fa-camera"></i></label>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="identity-info">
                        <div class="identity-name">
                            <h2><?= htmlspecialchars($user['prenom'].' '.$user['nom']); ?></h2>
                            <span class="verified" title="Compte vérifié"><i class="fa-solid fa-circle-check"></i></span>
                        </div>
                        <p class="muted"><?= !empty($profilEmploye) ? 'Employé ATHLETIX depuis le ' : 'Membre ATHLETIX depuis le ' ?><?= e($user['membre_depuis']); ?></p>
                        <span class="badge badge-green"><?= !empty($profilEmploye) ? 'Compte employé' : 'Abonnement ' . e(mb_strtolower($abonnement['statut'])) ?></span>

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
                            <?php if (empty($profilEmploye)): ?>
                            <label class="field field-wide">
                                <span class="field-label">Adresse</span>
                                <input class="field-value" type="text" name="adresse_postale" value="<?= e((string) ($user['adresse_postale'] ?? '')) ?>" maxlength="255" required>
                            </label>
                            <?php endif; ?>
                        </div>
                        <p id="profileMessage" role="status" aria-live="polite"></p>
                    </form>
                </section>

            </div>

            <!-- ===== Colonne latérale ===== -->
            <aside class="col-side">

                <?php if (!empty($profilEmploye)): ?>
                <section class="card">
                    <header class="card-head">
                        <div class="head-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                        <h3>Informations professionnelles</h3>
                    </header>
                    <div class="sub-grid">
                        <div>
                            <p class="muted small">Fonction</p>
                            <strong class="green-text big"><?= e($employeeProfile['fonction']) ?></strong>
                        </div>
                        <div>
                            <p class="muted small">Matricule employé</p>
                            <strong><?= e($employeeProfile['matricule']) ?></strong>
                        </div>
                    </div>
                    <p class="muted small">Date d’embauche : <?= e(dateFr($employeeProfile['date_embauche'])) ?></p>
                </section>
                <?php else: ?>
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
                <?php endif; ?>

            </aside>
        </div>
<?php endif; ?>
    <?php if (!empty($profilEmploye)): ?>
    </main>
    </div>
    <?php else: ?>
    </main>
    <?php endif; ?>
</div>

<?php if (!empty($profilEmploye)): ?>
<script>
window.ADH_API = async function (action, data = {}) {
    const endpoint = <?= json_encode(url('employe/profil_api.php'), JSON_UNESCAPED_SLASHES) ?>;
    const token = <?= json_encode(csrf(), JSON_UNESCAPED_SLASHES) ?>;
    const requestUrl = new URL(endpoint, window.location.href);
    requestUrl.searchParams.set('action', action);
    const response = await fetch(requestUrl.toString(), {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(Object.assign({}, data, {csrf: token}))
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'La demande a échoué.');
    return result;
};
</script>
<?php endif; ?>

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
        const fileInput = event.currentTarget;
        const file = fileInput.files?.[0];
        if (!file) return;
        const form = document.getElementById('photoForm');
        const message = document.getElementById('profileMessage');
        const formData = new FormData(form);
        formData.set('photo', file);
        message.textContent = 'Enregistrement de la photo…';
        try {
            const endpoint = <?= json_encode(
                url(!empty($profilEmploye) ? 'employe/profil_api.php?action=profile_photo' : 'adherent/api.php?action=profile_photo'),
                JSON_UNESCAPED_SLASHES
            ) ?>;
            const response = await fetch(endpoint, {method: 'POST', body: formData});
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch {
                throw new Error('Le serveur a refusé la photo. Vérifiez la taille maximale autorisée et réessayez.');
            }
            if (!response.ok) throw new Error(result.error || 'La photo n’a pas pu être enregistrée.');
            const avatar = document.getElementById('profileAvatar');
            avatar.src = result.photo_url + '?v=' + Date.now();
            avatar.hidden = false;
            document.getElementById('profileAvatarFallback').hidden = true;
            message.textContent = 'Photo de profil enregistrée.';
        } catch (error) {
            message.textContent = error instanceof Error ? error.message : 'La photo n’a pas pu être enregistrée.';
        } finally {
            fileInput.value = '';
        }
    });

    const employeeProfile = document.body.classList.contains('maintenance-profile-page');
    const profileForm = document.getElementById('profileForm');
    const editToggle = document.querySelector('.employee-profile-edit-toggle');
    const cancelEdit = document.querySelector('.employee-profile-cancel');

    editToggle?.addEventListener('click', () => {
        profileForm.hidden = !profileForm.hidden;
        editToggle.setAttribute('aria-expanded', String(!profileForm.hidden));
    });

    cancelEdit?.addEventListener('click', () => {
        profileForm.reset();
        profileForm.hidden = true;
        editToggle?.setAttribute('aria-expanded', 'false');
    });

    profileForm?.addEventListener('submit', async event => {
        event.preventDefault();
        const saveButton = employeeProfile
            ? profileForm.querySelector('.employee-profile-save')
            : document.querySelector('.btn-edit-top');
        if (!employeeProfile && event.submitter !== saveButton) return;
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form).entries());
        const message = document.getElementById('profileMessage');
        saveButton.disabled = true;
        try {
            const result = await window.ADH_API('profile', data);
            message.textContent = 'Informations enregistrées.';
            const profile = result.profile;
            if (employeeProfile) {
                const fullName = profile.prenom + ' ' + profile.nom;
                document.getElementById('employeeProfileName').textContent = fullName;
                document.getElementById('employeeProfileFullName').textContent = fullName;
                document.getElementById('profileContactEmail').textContent = profile.email;
                document.getElementById('profileContactPhone').textContent = profile.telephone || 'Téléphone non renseigné';
                document.getElementById('employeeProfileEmail').textContent = profile.email;
                document.getElementById('employeeProfilePhone').textContent = profile.telephone || 'Non renseigné';
                const location = [profile.nom_commune, profile.code_postal].filter(Boolean).join(' ') || 'Lieu non renseigné';
                document.getElementById('employeeProfileLocation').textContent = location;
                document.getElementById('employeeProfileAddress').textContent = location;
                for (const [name, value] of Object.entries({
                    nom: profile.nom,
                    prenom: profile.prenom,
                    email: profile.email,
                    telephone: profile.telephone || ''
                })) {
                    const input = profileForm.querySelector(`[name="${name}"]`);
                    input.value = value;
                    input.defaultValue = value;
                }
                const communeInput = document.getElementById('commune');
                const communeIdInput = document.getElementById('id_commune');
                const postalInput = document.getElementById('code_postal');
                communeInput.value = profile.nom_commune;
                communeInput.defaultValue = profile.nom_commune;
                communeIdInput.value = profile.id_commune;
                communeIdInput.defaultValue = profile.id_commune;
                postalInput.value = profile.code_postal;
                postalInput.defaultValue = profile.code_postal;
                profileForm.hidden = true;
                editToggle?.setAttribute('aria-expanded', 'false');
            } else {
                document.querySelector('.identity-name h2').textContent = profile.prenom + ' ' + profile.nom;
                document.querySelector('#profileContactEmail').textContent = profile.email;
                document.querySelector('#profileContactPhone').textContent = profile.telephone || 'Téléphone non renseigné';
                const memberName = document.querySelector('.member-infos strong');
                if (memberName) memberName.textContent = profile.prenom + ' ' + profile.nom;
            }
        } catch (error) { message.textContent = error.message; }
        finally { saveButton.disabled = false; }
    });

});

</script>

<?php if (empty($profilEmploye)): ?>
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
<?php endif; ?>
</body>
</html>
