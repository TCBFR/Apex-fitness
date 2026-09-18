<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/FaqController.php';
require_once __DIR__ . '/controller/MessageController.php';
require_once __DIR__ . '/controller/crud/messages/MessageCrudController.php';
exige_role('EMPLOYE');

$moi    = utilisateur()['NumUsers'];
$onglet = $_GET['onglet'] ?? 'tableau';
$pdo    = db();
$faq = new FaqController();
$maintenance = est_technicien_maintenance();
$messageController = new MessageCrudController();

if ($maintenance && $onglet !== 'maintenance') {
  redirige('employe.php?onglet=maintenance');
}
if (!$maintenance && $onglet === 'maintenance') {
  redirige('employe.php?onglet=tableau');
}

/* =====================================================================
   ACTIONS
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

  $actionsMaintenance = ['declarer_panne', 'avancer_maintenance'];
  if ($maintenance && !in_array($action, $actionsMaintenance, true)) {
    http_response_code(403);
    flash('erreur', 'Votre compte est réservé à la maintenance des équipements.');
    redirige('employe.php?onglet=maintenance');
  }
  if (!$maintenance && in_array($action, $actionsMaintenance, true)) {
    http_response_code(403);
    flash('erreur', 'La maintenance est réservée au technicien maintenance.');
    redirige('employe.php?onglet=tableau');
  }

  if (!$maintenance && $action === 'repondre_message') {
    try {
      $messageController->update($moi, (int) ($_POST['message_parent'] ?? 0), (string) ($_POST['message'] ?? ''));
      flash('succes', 'Réponse envoyée.');
    } catch (Throwable $exception) {
      flash('erreur', $exception->getMessage());
    }
    redirige('employe.php?onglet=messages');
  }

  if (!$maintenance && $action === 'supprimer_message') {
    try {
      $messageController->delete($moi, (int) ($_POST['message_parent'] ?? 0));
      flash('succes', 'Conversation supprimée.');
    } catch (Throwable $exception) {
      flash('erreur', $exception->getMessage());
    }
    redirige('employe.php?onglet=messages');
  }

    /* ---- creer un creneau ------------------------------------------ */
    if ($action === 'creer_creneau') {
        $numact   = (int) $_POST['activite'];
        $numsalle = (int) $_POST['salle'];
        $debut    = $_POST['debut'] ?? '';
        $duree    = max(15, (int) $_POST['duree']);
        $capacite = max(1, (int) $_POST['capacite']);
        $coach    = ($_POST['coach'] ?? '') !== '' ? (int) $_POST['coach'] : null;

        try {
            if (strtotime($debut) === false) {
                throw new RuntimeException('Date de début invalide.');
            }
            $fin = date('Y-m-d H:i:s', strtotime($debut) + $duree * 60);

            // La capacite ne peut pas depasser celle de la salle
            $q = $pdo->prepare('SELECT capacitesalle FROM salle WHERE Numsalle = ?');
            $q->execute([$numsalle]);
            $capSalle = (int) $q->fetchColumn();
            if ($capacite > $capSalle) {
                throw new RuntimeException("La salle accueille au maximum $capSalle personnes.");
            }

            // Chevauchement dans la meme salle
            $q = $pdo->prepare(
                'SELECT COUNT(*) FROM creneaux
                 WHERE Numsalle = ? AND date_heure_debut < ? AND date_heure_fin > ?');
            $q->execute([$numsalle, $fin, $debut]);
            if ((int) $q->fetchColumn() > 0) {
                throw new RuntimeException('Cette salle est déjà occupée sur ce créneau.');
            }

            $pdo->prepare(
                'INSERT INTO creneaux (date_heure_debut, date_heure_fin, capacite_max, Numsalle, Numact, NumUsers_coach)
                 VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([date('Y-m-d H:i:s', strtotime($debut)), $fin, $capacite, $numsalle, $numact, $coach]);

            journalise('CREATION_CRENEAU', 'Numcreneaux=' . $pdo->lastInsertId());
            flash('succes', 'Créneau ajouté au planning.');
        } catch (RuntimeException $ex) {
            flash('erreur', $ex->getMessage());
        } catch (PDOException $ex) {
            flash('erreur', 'Création impossible : la salle est déjà prise à cette heure exacte.');
        }
        redirige('employe.php?onglet=creneaux');
    }

    /* ---- declarer une panne ---------------------------------------- */
    if ($action === 'declarer_panne') {
        $numequip = (int) $_POST['equipement'];
        $desc     = trim($_POST['description'] ?? '');
        $priorite = in_array($_POST['priorite'] ?? '', ['Basse', 'Moyenne', 'Haute'], true)
                    ? $_POST['priorite'] : 'Moyenne';
        $horsService = isset($_POST['hors_service']);

        if (mb_strlen($desc) < 5) {
            flash('erreur', 'Décrivez le problème en quelques mots.');
            redirige('employe.php?onglet=materiel');
        }

        try {
            $pdo->beginTransaction();

            // statut "Ouverte" de la maintenance, etat cible de l'equipement
            $statutOuverte = (int) $pdo->query(
                "SELECT Numstatut FROM statut WHERE type_statut='MAINTENANCE' AND libelle_statut='Ouverte'")->fetchColumn();
            $etatCible = (int) $pdo->query(
                "SELECT Numstatut FROM statut WHERE type_statut='EQUIPEMENT' AND libelle_statut='"
                . ($horsService ? 'Hors service' : 'Maintenance') . "'")->fetchColumn();

            $pdo->prepare(
                'INSERT INTO demande_maintenance (description_problememaint, priorite, Numequip, NumUsers, Numstatut)
                 VALUES (?, ?, ?, ?, ?)')
                ->execute([$desc, $priorite, $numequip, $moi, $statutOuverte]);
            $nummaint = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)')
                ->execute(['Demande ouverte', $nummaint, $moi, $statutOuverte]);

            $pdo->prepare('UPDATE equipement SET Numstatut = ? WHERE Numequip = ?')
                ->execute([$etatCible, $numequip]);

            $pdo->commit();
            journalise('CREATION_MAINTENANCE', 'Nummaint=' . $nummaint);
            flash('succes', 'Panne enregistrée, l\'appareil est retiré du service.');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            flash('erreur', 'Enregistrement impossible.');
        }
        redirige('employe.php?onglet=maintenance');
    }

    /* ---- faire avancer une demande de maintenance ------------------- */
    if ($action === 'avancer_maintenance') {
        $nummaint  = (int) $_POST['maintenance'];
        $nouveau   = (int) $_POST['statut'];
        $commentaire = trim($_POST['commentaire'] ?? '') ?: null;

        try {
            $pdo->beginTransaction();

            $pdo->prepare('UPDATE demande_maintenance SET Numstatut = ? WHERE Nummaint = ?')
                ->execute([$nouveau, $nummaint]);

            // L'historique est conserve : une ligne par changement d'etat
            $pdo->prepare(
                'INSERT INTO intervention (commentaire, Nummaint, NumUsers, Numstatut) VALUES (?, ?, ?, ?)')
                ->execute([$commentaire, $nummaint, $moi, $nouveau]);

            $libelle = $pdo->query("SELECT libelle_statut FROM statut WHERE Numstatut = $nouveau")->fetchColumn();

            if ($libelle === 'Terminee') {
                $dispo = (int) $pdo->query(
                    "SELECT Numstatut FROM statut WHERE type_statut='EQUIPEMENT' AND libelle_statut='Disponible'")->fetchColumn();
                $pdo->prepare(
                    'UPDATE equipement e
                     JOIN demande_maintenance d ON d.Numequip = e.Numequip
                     SET e.Numstatut = ?, e.date_derniere_maintenance = CURDATE()
                     WHERE d.Nummaint = ?')
                    ->execute([$dispo, $nummaint]);
            }

            $pdo->commit();
            journalise('MAJ_MAINTENANCE', 'Nummaint=' . $nummaint . ' -> ' . $libelle);
            flash('succes', 'Demande mise à jour.');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            flash('erreur', 'Mise à jour impossible.');
        }
        redirige('employe.php?onglet=maintenance');
    }

    /* ---- changer le statut d'un adherent ---------------------------- */
    if ($action === 'statut_adherent') {
        $num    = (int) $_POST['adherent'];
        $statut = (int) $_POST['statut'];
        $pdo->prepare('UPDATE adherent SET Numstatut = ? WHERE NumUsers = ?')->execute([$statut, $num]);
        journalise('MAJ_STATUT_ADHERENT', 'NumUsers=' . $num);
        flash('succes', 'Statut de l\'adhérent mis à jour.');
        redirige('employe.php?onglet=adherents');
    }

    /* ---- repondre a une question de FAQ ----------------------------- */
    if ($action === 'repondre_faq') {
        $num     = (int) $_POST['interaction'];
        $reponse = trim($_POST['reponse'] ?? '');
      $faq->repondre($num, $reponse, $_POST['categorie'] ?? 'General');
        journalise('REPONSE_FAQ', 'NumInteraction=' . $num);
        flash('succes', $reponse !== '' ? 'Réponse publiée.' : 'Réponse enregistrée comme brouillon.');
        redirige('employe.php?onglet=faq');
    }

    if ($action === 'supprimer_faq') {
      $num = (int) $_POST['interaction'];
      $faq->supprimer($num);
      journalise('SUPPRESSION_FAQ', 'NumInteraction=' . $num);
      flash('succes', 'Question supprimée.');
      redirige('employe.php?onglet=faq');
    }

}

/* =====================================================================
   DONNEES
   ===================================================================== */
$aujourdhui = date('Y-m-d');

$stats = [
    'adherents' => (int) $pdo->query(
        "SELECT COUNT(*) FROM adherent a JOIN statut s ON s.Numstatut=a.Numstatut WHERE s.libelle_statut='Actif'")->fetchColumn(),
    'reservations' => (int) $pdo->query(
        "SELECT COUNT(*) FROM reservation r JOIN creneaux c ON c.Numcreneaux=r.Numcreneaux
         WHERE DATE(c.date_heure_debut)=CURDATE() AND r.statut_reservation<>'Annulee'")->fetchColumn(),
    'creneaux' => (int) $pdo->query(
        'SELECT COUNT(*) FROM creneaux WHERE DATE(date_heure_debut)=CURDATE()')->fetchColumn(),
    'dispo' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Disponible'")->fetchColumn(),
    'maint' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Maintenance'")->fetchColumn(),
    'hs' => (int) $pdo->query(
        "SELECT COUNT(*) FROM equipement e JOIN statut s ON s.Numstatut=e.Numstatut WHERE s.libelle_statut='Hors service'")->fetchColumn(),
];

$remplissage = $pdo->query(
    'SELECT * FROM v_creneau_places WHERE DATE(date_heure_debut)=CURDATE() ORDER BY date_heure_debut')->fetchAll();

$adherents = $pdo->query(
    'SELECT v.*, u.telUsers FROM v_adherent_complet v
     JOIN utilisateur u ON u.NumUsers = v.NumUsers
     ORDER BY v.nomUsers, v.prenomUsers')->fetchAll();

$statutsAdherent = $pdo->query(
    "SELECT s.Numstatut, s.libelle_statut FROM statut_adherent sa
     JOIN statut s ON s.Numstatut = sa.Numstatut ORDER BY s.Numstatut")->fetchAll();

$creneaux = $pdo->query(
    'SELECT * FROM v_creneau_places ORDER BY date_heure_debut DESC LIMIT 40')->fetchAll();

$activites = $pdo->query('SELECT * FROM activite ORDER BY libelleact')->fetchAll();
$salles    = $pdo->query('SELECT * FROM salle ORDER BY nomsalle')->fetchAll();
$coachs    = $pdo->query(
    "SELECT e.NumUsers, u.nomUsers, u.prenomUsers FROM employe e
     JOIN utilisateur u ON u.NumUsers=e.NumUsers
     JOIN fonction f ON f.Numfonc=e.Numfonc WHERE f.libellefonc='Coach sportif'")->fetchAll();

$equipements = $pdo->query(
    'SELECT eq.*, t.libelletypeequip, sa.nomsalle, st.libelle_statut
     FROM equipement eq
     JOIN type_equipement t ON t.Numtypeequip = eq.Numtypeequip
     JOIN salle sa ON sa.Numsalle = eq.Numsalle
     JOIN statut st ON st.Numstatut = eq.Numstatut
     ORDER BY eq.num_inventaire')->fetchAll();

$maintenances = $pdo->query(
    'SELECT d.*, eq.num_inventaire, eq.nomequip, st.libelle_statut, sm.ordre,
            u.nomUsers, u.prenomUsers
     FROM demande_maintenance d
     JOIN equipement eq ON eq.Numequip = d.Numequip
     JOIN statut st ON st.Numstatut = d.Numstatut
     JOIN statut_maintenance sm ON sm.Numstatut = d.Numstatut
     JOIN utilisateur u ON u.NumUsers = d.NumUsers
     ORDER BY sm.ordre, d.date_demandemaint DESC')->fetchAll();

$statutsMaint = $pdo->query(
    'SELECT s.Numstatut, s.libelle_statut, sm.ordre FROM statut_maintenance sm
     JOIN statut s ON s.Numstatut = sm.Numstatut ORDER BY sm.ordre')->fetchAll();

$questionsFaq = $faq->questions();
$messages = $maintenance ? [] : $messageController->getThreads($moi);
$titrePage  = 'Gestion';
$pageActive = 'employe';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap page-head">
  <h1>Poste de gestion</h1>
  <p class="lead"><?= e(utilisateur()['prenomUsers'] . ' ' . utilisateur()['nomUsers']) ?>
     &middot; <?= dateFr($aujourdhui) ?></p>
</div>

<div class="wrap section">
  <nav class="onglets">
    <?php foreach ([
        'tableau'     => 'Tableau de bord',
        'creneaux'    => 'Créneaux',
        'adherents'   => 'Adhérents',
        'materiel'    => 'Matériel',
        'maintenance' => 'Maintenance',
        'faq'         => 'Questions',
        'messages'    => 'Messages',
    ] as $cle => $lib): ?>
      <?php if (($cle === 'maintenance' && !$maintenance) || ($cle === 'messages' && $maintenance)) continue; ?>
      <a href="?onglet=<?= $cle ?>" <?= $onglet === $cle ? 'aria-current="page"' : '' ?>><?= $lib ?></a>
    <?php endforeach; ?>
    <?php if ($maintenance): ?><a href="<?= url('gestion.php') ?>">Fournisseurs</a><?php endif; ?>
  </nav>

<?php if ($onglet === 'tableau'): ?>

  <div class="compteurs">
    <div class="compteur"><div class="v"><?= $stats['adherents'] ?></div><div class="k">adhérents actifs</div></div>
    <div class="compteur"><div class="v"><?= $stats['reservations'] ?></div><div class="k">réservations aujourd'hui</div></div>
    <div class="compteur"><div class="v"><?= $stats['creneaux'] ?></div><div class="k">créneaux aujourd'hui</div></div>
    <div class="compteur"><div class="v"><?= $stats['dispo'] ?></div><div class="k">matériel disponible</div></div>
    <div class="compteur warn"><div class="v"><?= $stats['maint'] ?></div><div class="k">en maintenance</div></div>
    <div class="compteur bad"><div class="v"><?= $stats['hs'] ?></div><div class="k">hors service</div></div>
  </div>

  <div class="barres">
    <h2 style="font-size:17px">Taux de remplissage du jour</h2>
    <?php if (!$remplissage): ?>
      <p class="muted">Aucun créneau programmé aujourd'hui.</p>
    <?php else: foreach ($remplissage as $r):
        $pct = $r['capacite_max'] > 0 ? round($r['places_prises'] / $r['capacite_max'] * 100) : 0; ?>
      <div class="barre">
        <span><?= e($r['libelleact']) ?></span>
        <span class="piste"><span class="part" style="width:<?= $pct ?>%"></span></span>
        <span class="n"><?= (int) $r['places_prises'] ?>/<?= (int) $r['capacite_max'] ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

<?php elseif ($onglet === 'adherents'): ?>

  <div class="barre-outils">
    <input type="search" data-filtre="#t-adherents" placeholder="Nom, matricule ou e-mail" aria-label="Rechercher un adhérent">
    <select data-filtre="#t-adherents" data-colonne="4" aria-label="Filtrer par statut">
      <option value="">Tous les statuts</option>
      <?php foreach ($statutsAdherent as $s): ?>
        <option value="<?= e($s['libelle_statut']) ?>"><?= e($s['libelle_statut']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="table-scroll">
    <table class="tableau" id="t-adherents">
      <thead><tr><th>Matricule</th><th>Nom</th><th>Contact</th><th>Formule</th><th>Statut</th><th>Fin</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($adherents as $a):
          $cls = $a['statut_adherent'] === 'Actif' ? 'tag-ok'
               : ($a['statut_adherent'] === 'Suspendu' ? 'tag-warn' : 'tag-bad'); ?>
        <tr>
          <td class="mono"><?= e($a['matriculeadh']) ?></td>
          <td><?= e($a['nomUsers'] . ' ' . $a['prenomUsers']) ?></td>
          <td><?= e($a['mailUsers']) ?><br><span class="mono"><?= e($a['telUsers']) ?></span></td>
          <td><?= e($a['libelle_abo'] ?? '—') ?></td>
          <td><span class="tag <?= $cls ?>"><?= e($a['statut_adherent']) ?></span></td>
          <td class="mono"><?= dateFr($a['Date_finadh']) ?></td>
          <td>
            <form method="post" style="display:flex;gap:6px;margin:0">
              <?= champ_csrf() ?>
              <input type="hidden" name="action" value="statut_adherent">
              <input type="hidden" name="adherent" value="<?= (int) $a['NumUsers'] ?>">
              <select name="statut" style="padding:5px 8px;border:1px solid #DCDAD2;border-radius:3px">
                <?php foreach ($statutsAdherent as $s): ?>
                  <option value="<?= (int) $s['Numstatut'] ?>" <?= $s['libelle_statut'] === $a['statut_adherent'] ? 'selected' : '' ?>>
                    <?= e($s['libelle_statut']) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-ghost" type="submit">Appliquer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p class="vide" data-vide hidden>Aucun adhérent ne correspond à cette recherche.</p>
  </div>

<?php elseif ($onglet === 'creneaux'): ?>

  <div class="form" style="margin-bottom:26px">
    <h2 style="font-size:18px">Ajouter un créneau</h2>
    <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;align-items:end">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="creer_creneau">

      <div><label for="activite">Activité</label>
        <select id="activite" name="activite" required>
          <?php foreach ($activites as $a): ?>
            <option value="<?= (int) $a['Numact'] ?>" data-duree="<?= (int) $a['duree_minutes'] ?>">
              <?= e($a['libelleact']) ?></option>
          <?php endforeach; ?>
        </select></div>

      <div><label for="salle">Salle</label>
        <select id="salle" name="salle" required>
          <?php foreach ($salles as $s): ?>
            <option value="<?= (int) $s['Numsalle'] ?>"><?= e($s['nomsalle']) ?> (<?= (int) $s['capacitesalle'] ?>)</option>
          <?php endforeach; ?>
        </select></div>

      <div><label for="debut">Début</label>
        <input type="datetime-local" id="debut" name="debut" required></div>

      <div><label for="duree">Durée (min)</label>
        <input type="number" id="duree" name="duree" value="60" min="15" step="15" required></div>

      <div><label for="capacite">Places</label>
        <input type="number" id="capacite" name="capacite" value="15" min="1" required></div>

      <div><label for="coach">Coach</label>
        <select id="coach" name="coach">
          <option value="">Sans coach</option>
          <?php foreach ($coachs as $c): ?>
            <option value="<?= (int) $c['NumUsers'] ?>"><?= e($c['prenomUsers'] . ' ' . $c['nomUsers']) ?></option>
          <?php endforeach; ?>
        </select></div>

      <div><button class="btn btn-vert" type="submit">Ajouter au planning</button></div>
    </form>
    <p class="aide">Deux activités ne peuvent pas occuper la même salle au même moment : le contrôle est fait côté serveur et par une contrainte d'unicité en base.</p>
  </div>

  <div class="table-scroll">
    <table class="tableau">
      <thead><tr><th>Activité</th><th>Créneau</th><th>Salle</th><th>Occupation</th></tr></thead>
      <tbody>
      <?php foreach ($creneaux as $c):
          $pct = $c['capacite_max'] > 0 ? round($c['places_prises'] / $c['capacite_max'] * 100) : 0; ?>
        <tr>
          <td><?= e($c['libelleact']) ?></td>
          <td><?= jourHeure($c['date_heure_debut'], $c['date_heure_fin']) ?></td>
          <td><?= e($c['nomsalle']) ?></td>
          <td style="min-width:180px">
            <div class="jauge"><i class="<?= $pct >= 100 ? 'pleine' : '' ?>" style="width:<?= $pct ?>%"></i></div>
            <span class="mono"><?= (int) $c['places_prises'] ?>/<?= (int) $c['capacite_max'] ?></span>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php elseif ($onglet === 'materiel'): ?>

  <div class="barre-outils">
    <input type="search" data-filtre="#t-materiel" placeholder="Nom, marque ou n° d'inventaire" aria-label="Rechercher du matériel">
    <select data-filtre="#t-materiel" data-colonne="4" aria-label="Filtrer par état">
      <option value="">Tous les états</option>
      <option value="Disponible">Disponible</option>
      <option value="Maintenance">Maintenance</option>
      <option value="Hors service">Hors service</option>
    </select>
  </div>

  <div class="table-scroll">
    <table class="tableau" id="t-materiel">
      <thead><tr><th>N° inventaire</th><th>Équipement</th><th>Type</th><th>Salle</th><th>État</th><th>Dernière maintenance</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($equipements as $eq):
          $cls = $eq['libelle_statut'] === 'Disponible' ? 'tag-ok'
               : ($eq['libelle_statut'] === 'Maintenance' ? 'tag-warn' : 'tag-bad'); ?>
        <tr>
          <td class="mono"><?= e($eq['num_inventaire']) ?></td>
          <td><?= e($eq['nomequip']) ?><br><span class="mono"><?= e($eq['marque'] . ' ' . $eq['modele']) ?></span></td>
          <td><?= e($eq['libelletypeequip']) ?></td>
          <td><?= e($eq['nomsalle']) ?></td>
          <td><span class="tag <?= $cls ?>"><?= e($eq['libelle_statut']) ?></span></td>
          <td class="mono"><?= dateFr($eq['date_derniere_maintenance']) ?></td>
          <td>
            <?php if ($eq['libelle_statut'] === 'Disponible'): ?>
              <details>
                <summary class="btn btn-sm btn-ghost" style="display:inline-block">Déclarer une panne</summary>
                <form method="post" style="margin-top:10px;display:grid;gap:8px;min-width:250px">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="declarer_panne">
                  <input type="hidden" name="equipement" value="<?= (int) $eq['Numequip'] ?>">
                  <input type="text" name="description" placeholder="Décrivez le problème" required
                         style="padding:7px 10px;border:1px solid #DCDAD2;border-radius:3px">
                  <select name="priorite" style="padding:7px 10px;border:1px solid #DCDAD2;border-radius:3px">
                    <option>Moyenne</option><option>Haute</option><option>Basse</option>
                  </select>
                  <label style="font-size:13.5px"><input type="checkbox" name="hors_service" style="width:auto"> Mettre hors service</label>
                  <button class="btn btn-sm btn-vert" type="submit">Enregistrer</button>
                </form>
              </details>
            <?php else: ?>
              <span class="tag tag-mute">Demande en cours</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p class="vide" data-vide hidden>Aucun équipement ne correspond à cette recherche.</p>
  </div>

<?php elseif ($onglet === 'maintenance'): ?>

  <?php if (!$maintenances): ?>
    <div class="vide">Aucune demande de maintenance. Les pannes déclarées depuis l'onglet Matériel apparaissent ici.</div>
  <?php else: ?>
    <div class="table-scroll">
      <table class="tableau">
        <thead><tr><th>N°</th><th>Équipement</th><th>Problème</th><th>Priorité</th><th>Signalée par</th><th>Statut</th><th>Faire avancer</th></tr></thead>
        <tbody>
        <?php foreach ($maintenances as $m): ?>
          <tr>
            <td class="mono"><?= (int) $m['Nummaint'] ?></td>
            <td><?= e($m['num_inventaire']) ?><br><span class="mono"><?= e($m['nomequip']) ?></span></td>
            <td><?= e($m['description_problememaint']) ?></td>
            <td><span class="tag <?= $m['priorite'] === 'Haute' ? 'tag-bad' : 'tag-mute' ?>"><?= e($m['priorite']) ?></span></td>
            <td><?= e($m['prenomUsers'] . ' ' . $m['nomUsers']) ?><br>
                <span class="mono"><?= dateFr($m['date_demandemaint']) ?></span></td>
            <td><span class="tag <?= $m['libelle_statut'] === 'Terminee' ? 'tag-ok' : ($m['libelle_statut'] === 'En attente de piece' ? 'tag-bad' : 'tag-warn') ?>">
                <?= e($m['libelle_statut']) ?></span></td>
            <td>
              <?php if ($m['libelle_statut'] !== 'Terminee'): ?>
                <form method="post" style="display:grid;gap:6px;min-width:230px">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="avancer_maintenance">
                  <input type="hidden" name="maintenance" value="<?= (int) $m['Nummaint'] ?>">
                  <select name="statut" style="padding:6px 9px;border:1px solid #DCDAD2;border-radius:3px">
                    <?php foreach ($statutsMaint as $s):
                        if ((int) $s['ordre'] <= (int) $m['ordre']) continue; ?>
                      <option value="<?= (int) $s['Numstatut'] ?>"><?= e($s['libelle_statut']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="commentaire" placeholder="Commentaire d'intervention"
                         style="padding:6px 9px;border:1px solid #DCDAD2;border-radius:3px">
                  <button class="btn btn-sm btn-ghost" type="submit">Enregistrer l'intervention</button>
                </form>
              <?php else: ?>
                <span class="tag tag-mute">Clôturée</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="note">Chaque changement de statut crée une ligne dans <code>intervention</code> :
       l'historique demandé par le cahier des charges est conservé, y compris le commentaire et l'auteur.</p>
  <?php endif; ?>

<?php elseif ($onglet === 'messages'): ?>
  <?php require __DIR__ . '/views/crud/messages/index.php'; ?>

<?php else: ?>

  <?php if (!$questionsFaq): ?>
    <div class="vide">Aucune question posée pour le moment.</div>
  <?php else: foreach ($questionsFaq as $q): ?>
    <article class="carte" style="margin-bottom:14px">
      <p class="mono">
        <?= dateFr($q['date_interaction'], true) ?> &middot;
        <?= $q['nomUsers'] ? e($q['prenomUsers'] . ' ' . $q['nomUsers']) : 'Rédaction interne' ?> &middot;
        <span class="tag <?= $q['statut_faq'] === 'Publiee' ? 'tag-ok' : 'tag-warn' ?>"><?= e($q['statut_faq']) ?></span>
      </p>
      <h3><?= e($q['question']) ?></h3>
      <form method="post" style="display:grid;gap:10px;margin-top:10px">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="repondre_faq">
        <input type="hidden" name="interaction" value="<?= (int) $q['NumInteraction'] ?>">
        <textarea name="reponse" placeholder="Rédigez la réponse publiée sur la page FAQ"
                  style="min-height:90px;padding:9px 11px;border:1px solid #DCDAD2;border-radius:3px"><?= e($q['reponse'] ?? '') ?></textarea>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <input type="text" name="categorie" value="<?= e($q['categorie']) ?>" placeholder="Catégorie"
                 style="padding:8px 11px;border:1px solid #DCDAD2;border-radius:3px">
          <button class="btn btn-sm btn-vert" type="submit">Publier la réponse</button>
        </div>
      </form>
      <form method="post" style="margin-top:8px">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="supprimer_faq">
        <input type="hidden" name="interaction" value="<?= (int) $q['NumInteraction'] ?>">
        <button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer cette question ?">Supprimer</button>
      </form>
    </article>
  <?php endforeach; endif; ?>

<?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
