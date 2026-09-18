<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/controller/MessageController.php';
exige_role('ADHERENT');

$moi    = utilisateur()['NumUsers'];
$onglet = $_GET['onglet'] ?? 'planning';
$messageController = new MessageController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'repondre_message') {
  verifie_csrf();
  try {
    $messageController->set($moi, (int) ($_POST['message_parent'] ?? 0), (string) ($_POST['message'] ?? ''));
    flash('succes', 'Réponse envoyée.');
  } catch (Throwable $exception) {
    flash('erreur', $exception->getMessage());
  }
  redirige('adherent.php?onglet=messages');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'envoyer_message') {
  verifie_csrf();
  try {
    $messageController->setInitial($moi, (string) ($_POST['message'] ?? ''));
    flash('succes', 'Message envoyé à l’équipe.');
  } catch (Throwable $exception) {
    flash('erreur', $exception->getMessage());
  }
  redirige('adherent.php?onglet=messages');
}

$messages = $messageController->getAllThreads($moi);

/* ---------------------------------------------------------------------
   Fiche adherent : statut et abonnement en cours
   --------------------------------------------------------------------- */
$st = db()->prepare(
    "SELECT ad.*, s.libelle_statut,
            ab.Numabo, ab.libelle_abo, ab.type_abo,
            so.date_debut, so.date_fin, so.statut_souscription,
            f.nb_reservations_max_mois
     FROM adherent ad
     JOIN statut s ON s.Numstatut = ad.Numstatut
     LEFT JOIN souscrire so ON so.NumUsers = ad.NumUsers AND so.statut_souscription = 'En cours'
     LEFT JOIN abonnement ab ON ab.Numabo = so.Numabo
     LEFT JOIN freemium f ON f.Numabo = ab.Numabo
     WHERE ad.NumUsers = ?");
$st->execute([$moi]);
$fiche = $st->fetch();

$estActif = $fiche && $fiche['libelle_statut'] === 'Actif';

/* ---------------------------------------------------------------------
   ACTION : reserver un creneau
   Les quatre regles du cahier des charges sont verifiees ici, cote
   serveur, dans une transaction. Le JS n'est qu'un confort.
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reserver') {
    verifie_csrf();
    $numCreneau = (int) ($_POST['creneau'] ?? 0);
    $pdo = db();

    try {
        $pdo->beginTransaction();

        // Regle 1 : l'adherent doit etre actif
        if (!$estActif) {
            throw new RuntimeException(
                'Votre compte est ' . mb_strtolower($fiche['libelle_statut'] ?? 'inconnu') .
                '. Régularisez votre abonnement à l\'accueil avant de réserver.');
        }

        // Verrou sur le creneau pour eviter deux reservations simultanees
        $q = $pdo->prepare(
            'SELECT c.Numcreneaux, c.capacite_max, c.date_heure_debut, a.libelleact
             FROM creneaux c JOIN activite a ON a.Numact = c.Numact
             WHERE c.Numcreneaux = ? FOR UPDATE');
        $q->execute([$numCreneau]);
        $creneau = $q->fetch();

        if (!$creneau) {
            throw new RuntimeException('Ce créneau n\'existe plus.');
        }
        if (strtotime($creneau['date_heure_debut']) < time()) {
            throw new RuntimeException('Ce créneau est déjà passé.');
        }

        // Regle 2 : creneau complet
        $q = $pdo->prepare(
            "SELECT COUNT(*) FROM reservation
             WHERE Numcreneaux = ? AND statut_reservation <> 'Annulee'");
        $q->execute([$numCreneau]);
        if ((int) $q->fetchColumn() >= (int) $creneau['capacite_max']) {
            throw new RuntimeException(
                'Le créneau est complet (' . (int) $creneau['capacite_max'] . ' places).');
        }

        // Regle 3 : double reservation
        $q = $pdo->prepare(
            "SELECT Numres, statut_reservation FROM reservation
             WHERE NumUsers = ? AND Numcreneaux = ?");
        $q->execute([$moi, $numCreneau]);
        $existante = $q->fetch();

        if ($existante && $existante['statut_reservation'] !== 'Annulee') {
            throw new RuntimeException('Vous avez déjà une réservation sur ce créneau.');
        }

        // Regle 4 : quota mensuel de l'offre Freemium
        if ($fiche['type_abo'] === 'FREEMIUM') {
            $q = $pdo->prepare(
                "SELECT COUNT(*) FROM reservation r
                 JOIN creneaux c ON c.Numcreneaux = r.Numcreneaux
                 WHERE r.NumUsers = ? AND r.statut_reservation <> 'Annulee'
                   AND YEAR(c.date_heure_debut) = YEAR(?) AND MONTH(c.date_heure_debut) = MONTH(?)");
            $q->execute([$moi, $creneau['date_heure_debut'], $creneau['date_heure_debut']]);
            $quota = (int) $fiche['nb_reservations_max_mois'];
            if ((int) $q->fetchColumn() >= $quota) {
                throw new RuntimeException(
                    "L'offre Freemium est limitée à $quota réservations par mois.");
            }
        }

        if ($existante) {   // une annulation precedente est reactivee
            $pdo->prepare(
                "UPDATE reservation SET statut_reservation = 'Reservee',
                        date_heure_reservation = NOW() WHERE Numres = ?")
                ->execute([$existante['Numres']]);
        } else {
            $pdo->prepare(
                'INSERT INTO reservation (NumUsers, Numcreneaux) VALUES (?, ?)')
                ->execute([$moi, $numCreneau]);
        }

        $pdo->commit();
        journalise('RESERVATION', 'Numcreneaux=' . $numCreneau);
        flash('succes', 'Réservation enregistrée pour ' . $creneau['libelleact'] . '.');

    } catch (RuntimeException $ex) {
        $pdo->rollBack();
        flash('erreur', $ex->getMessage());
    } catch (PDOException $ex) {
        $pdo->rollBack();
        flash('erreur', 'La réservation a échoué. Réessayez dans un instant.');
    }
    redirige('adherent.php?onglet=planning');
}

/* ---------------------------------------------------------------------
   ACTION : annuler une reservation
   --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler') {
    verifie_csrf();
    $numRes = (int) ($_POST['reservation'] ?? 0);

    $q = db()->prepare(
        'SELECT r.Numres, c.date_heure_debut FROM reservation r
         JOIN creneaux c ON c.Numcreneaux = r.Numcreneaux
         WHERE r.Numres = ? AND r.NumUsers = ?');
    $q->execute([$numRes, $moi]);
    $res = $q->fetch();

    if (!$res) {
        flash('erreur', 'Réservation introuvable.');
    } elseif (strtotime($res['date_heure_debut']) - time() < 2 * 3600) {
        flash('erreur', 'L\'annulation n\'est plus possible moins de 2 heures avant le cours.');
    } else {
        db()->prepare("UPDATE reservation SET statut_reservation = 'Annulee' WHERE Numres = ?")
            ->execute([$numRes]);
        journalise('ANNULATION', 'Numres=' . $numRes);
        flash('succes', 'Réservation annulée, la place est de nouveau disponible.');
    }
    redirige('adherent.php?onglet=reservations');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler_abonnement') {
    verifie_csrf();
    $st = db()->prepare(
        "SELECT Numabo, date_debut FROM souscrire
         WHERE NumUsers = ? AND statut_souscription = 'En cours'
         ORDER BY date_debut DESC LIMIT 1"
    );
    $st->execute([$moi]);
    $actif = $st->fetch();

    if (!$actif) {
        flash('erreur', 'Vous n’avez pas d’abonnement actif à annuler.');
    } else {
        $finMois = date('Y-m-d', strtotime('last day of this month'));
        db()->prepare(
            "UPDATE souscrire SET statut_souscription = 'Resiliee', date_fin = ?
             WHERE NumUsers = ? AND statut_souscription = 'En cours'"
        )->execute([$finMois, $moi]);
        journalise('RESILIATION_ABONNEMENT', 'NumUsers=' . $moi . '; Numabo=' . (int) $actif['Numabo']);
        flash('info', 'Votre abonnement actuel est annulé jusqu’à la fin du mois. Choisissez une nouvelle formule pour reprendre au début du mois prochain.');
    }
    redirige('paiement.php?switch=1');
}

/* ---------------------------------------------------------------------
   Donnees des onglets
   --------------------------------------------------------------------- */
$creneaux = db()->query(
    'SELECT * FROM v_creneau_places WHERE date_heure_debut >= NOW()
     ORDER BY date_heure_debut')->fetchAll();

$st = db()->prepare(
    "SELECT Numcreneaux, statut_reservation FROM reservation
     WHERE NumUsers = ? AND statut_reservation <> 'Annulee'");
$st->execute([$moi]);
$dejaReserves = array_column($st->fetchAll(), 'statut_reservation', 'Numcreneaux');

$st = db()->prepare(
    'SELECT r.*, c.date_heure_debut, c.date_heure_fin, a.libelleact, s.nomsalle
     FROM reservation r
     JOIN creneaux c ON c.Numcreneaux = r.Numcreneaux
     JOIN activite a ON a.Numact = c.Numact
     JOIN salle s    ON s.Numsalle = c.Numsalle
     WHERE r.NumUsers = ?
     ORDER BY c.date_heure_debut DESC');
$st->execute([$moi]);
$mesReservations = $st->fetchAll();

$st = db()->prepare(
    'SELECT p.*, ab.libelle_abo FROM paiement p
     JOIN abonnement ab ON ab.Numabo = p.Numabo
     WHERE p.NumUsers = ? ORDER BY p.date_paiement DESC');
$st->execute([$moi]);
$mesPaiements = $st->fetchAll();

$titrePage  = 'Mon espace';
$pageActive = 'adherent';
require __DIR__ . '/includes/header.php';
?>

<div class="wrap page-head">
  <h1>Bonjour <?= e(utilisateur()['prenomUsers']) ?></h1>
  <p class="lead">
    <?= e($fiche['matriculeadh']) ?> &middot;
    <span class="tag <?= $estActif ? 'tag-ok' : ($fiche['libelle_statut'] === 'Suspendu' ? 'tag-warn' : 'tag-bad') ?>">
      <?= e($fiche['libelle_statut']) ?>
    </span>
    <?php if ($fiche['libelle_abo']): ?>
      &middot; formule <?= e($fiche['libelle_abo']) ?> jusqu'au <?= dateFr($fiche['date_fin']) ?>
    <?php endif; ?>
  </p>
</div>

<div class="wrap section">
  <nav class="onglets">
    <a href="?onglet=planning"     <?= $onglet === 'planning' ? 'aria-current="page"' : '' ?>>Planning</a>
    <a href="?onglet=reservations" <?= $onglet === 'reservations' ? 'aria-current="page"' : '' ?>>Mes réservations</a>
    <a href="?onglet=abonnement"   <?= $onglet === 'abonnement' ? 'aria-current="page"' : '' ?>>Mon abonnement</a>
    <a href="?onglet=messages"     <?= $onglet === 'messages' ? 'aria-current="page"' : '' ?>>Messages reçus</a>
  </nav>

  <?php if ($onglet === 'planning'): ?>

    <?php if (!$estActif): ?>
      <p class="flash flash-info">Votre compte est <?= e(mb_strtolower($fiche['libelle_statut'])) ?> :
         la réservation est bloquée jusqu'à régularisation à l'accueil.</p>
    <?php endif; ?>

    <?php if (!$creneaux): ?>
      <div class="vide">Aucun créneau à venir n'est encore programmé.</div>
    <?php else: ?>
      <div class="creneaux">
        <?php foreach ($creneaux as $c):
            $num = (int) $c['Numcreneaux'];
            $restantes = (int) $c['places_restantes'];
            $pct = $c['capacite_max'] > 0 ? round($c['places_prises'] / $c['capacite_max'] * 100) : 0;
            $deja = isset($dejaReserves[$num]);
        ?>
          <article class="creneau" id="c<?= $num ?>">
            <h3><?= e($c['libelleact']) ?></h3>
            <p class="when"><?= jourHeure($c['date_heure_debut'], $c['date_heure_fin']) ?>
               &middot; <?= e($c['nomsalle']) ?></p>
            <div class="jauge"><i class="<?= $restantes === 0 ? 'pleine' : '' ?>" style="width:<?= $pct ?>%"></i></div>
            <div class="bas">
              <span class="tag <?= $restantes === 0 ? 'tag-bad' : ($restantes <= 3 ? 'tag-warn' : 'tag-ok') ?>">
                <?= $restantes === 0 ? 'Complet' : $restantes . ' place' . ($restantes > 1 ? 's' : '') ?>
              </span>

              <?php if ($deja): ?>
                <span class="tag tag-mute">Déjà réservé</span>
              <?php else: ?>
                <form method="post" style="margin:0">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="reserver">
                  <input type="hidden" name="creneau" value="<?= $num ?>">
                  <button class="btn btn-sm btn-vert" type="submit"
                          <?= ($restantes === 0 || !$estActif) ? 'disabled' : '' ?>>Réserver</button>
                </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php elseif ($onglet === 'reservations'): ?>

    <?php if (!$mesReservations): ?>
      <div class="vide">
        Vous n'avez encore aucune réservation.
        <p style="margin-top:14px"><a class="btn btn-vert" href="?onglet=planning">Voir le planning</a></p>
      </div>
    <?php else: ?>
      <div class="table-scroll">
        <table class="tableau">
          <thead><tr><th>Activité</th><th>Créneau</th><th>Salle</th><th>Réservée le</th><th>Statut</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($mesReservations as $r):
              $aVenir = strtotime($r['date_heure_debut']) > time();
              $cls = $r['statut_reservation'] === 'Annulee' ? 'tag-mute'
                   : ($r['statut_reservation'] === 'Absente' ? 'tag-bad' : 'tag-ok');
          ?>
            <tr>
              <td><?= e($r['libelleact']) ?></td>
              <td><?= jourHeure($r['date_heure_debut'], $r['date_heure_fin']) ?></td>
              <td><?= e($r['nomsalle']) ?></td>
              <td class="mono"><?= dateFr($r['date_heure_reservation'], true) ?></td>
              <td><span class="tag <?= $cls ?>"><?= e($r['statut_reservation']) ?></span></td>
              <td>
                <?php if ($aVenir && $r['statut_reservation'] === 'Reservee'): ?>
                  <form method="post" style="margin:0">
                    <?= champ_csrf() ?>
                    <input type="hidden" name="action" value="annuler">
                    <input type="hidden" name="reservation" value="<?= (int) $r['Numres'] ?>">
                    <button class="btn btn-sm btn-danger" type="submit"
                            data-confirme="Annuler cette réservation ?">Annuler</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="note">L'annulation reste possible jusqu'à 2 heures avant le début du cours.</p>
    <?php endif; ?>

  <?php elseif ($onglet === 'messages'): ?>

    <div class="form" style="max-width:760px;margin-bottom:20px">
      <h2 style="font-size:18px">Écrire à l’équipe</h2>
      <form method="post">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="envoyer_message">
        <textarea name="message" rows="3" placeholder="Votre message à l’équipe..." required></textarea>
        <button class="btn btn-vert btn-sm" type="submit">Envoyer</button>
      </form>
    </div>

    <?php if (!$messages): ?>
      <div class="vide">Aucun message reçu.</div>
    <?php else: foreach ($messages as $thread): $last = end($thread); ?>
      <article class="carte message-conversation">
        <h3>Historique de la conversation</h3>
        <div class="messages-fil">
          <?php foreach ($thread as $message): $estMoi = (int) $message['NumUsers_emetteur'] === $moi; ?>
            <div class="message-bulle <?= $estMoi ? 'message-droite' : 'message-gauche' ?>">
              <strong><?= $estMoi ? 'Vous' : e($message['prenomUsers'] . ' ' . $message['nomUsers']) ?></strong>
              <p><?= nl2br(e($message['message'])) ?></p>
              <small><?= dateFr($message['date_envoi'], true) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ((int) $last['NumUsers_destinataire'] === $moi): ?>
          <form method="post" class="message-reponse">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="repondre_message">
            <input type="hidden" name="message_parent" value="<?= (int) $last['IdMessage'] ?>">
            <textarea name="message" rows="2" placeholder="Répondre à ce message..." required></textarea>
            <button class="btn btn-vert btn-sm" type="submit">Répondre</button>
          </form>
        <?php endif; ?>
      </article>
    <?php endforeach; endif; ?>

  <?php else: ?>

    <div class="grille-3" style="grid-template-columns:1fr 1fr;align-items:start">
      <article class="carte">
        <h3>Ma formule</h3>
        <?php if ($fiche['libelle_abo']): ?>
          <p class="prix" style="font-size:28px;font-weight:600;margin:6px 0"><?= e($fiche['libelle_abo']) ?></p>
          <p class="muted">
            Du <?= dateFr($fiche['date_debut']) ?> au <?= dateFr($fiche['date_fin']) ?><br>
            Souscription <?= e(mb_strtolower($fiche['statut_souscription'])) ?>
          </p>
          <form method="post" style="margin-top:12px">
            <?= champ_csrf() ?>
            <input type="hidden" name="action" value="annuler_abonnement">
            <button class="btn btn-danger btn-sm" type="submit" data-confirme="Annuler votre abonnement actuel et choisir une autre formule ?">Annuler mon abonnement</button>
          </form>
        <?php else: ?>
          <p class="muted">Aucune souscription en cours.</p>
        <?php endif; ?>
          <p style="margin-top:12px"><a class="btn btn-vert btn-sm" href="<?= url('paiement.php') ?>">Payer maintenant</a>
            <a class="btn btn-ghost btn-sm" href="<?= url('abonnements.php') ?>">Voir les formules</a></p>
      </article>

      <article class="carte">
        <h3>Ma fiche</h3>
        <p class="muted">
          Matricule <?= e($fiche['matriculeadh']) ?><br>
          Inscrit depuis le <?= dateFr($fiche['Date_inscriptionadh']) ?><br>
          Fin d'abonnement : <?= dateFr($fiche['Date_finadh']) ?><br>
          <?= e(utilisateur()['mailUsers']) ?>
        </p>
        <p><a class="btn btn-ghost btn-sm" href="<?= url('contact.php?sujet=' . urlencode('Modification de ma fiche')) ?>">Demander une correction</a></p>
      </article>
    </div>

    <h2 style="margin-top:34px">Mes paiements</h2>
    <?php if (!$mesPaiements): ?>
      <div class="vide">Aucun paiement enregistré.</div>
    <?php else: ?>
      <div class="table-scroll">
        <table class="tableau">
          <thead><tr><th>Date</th><th>Formule</th><th>Moyen</th><th>Montant</th></tr></thead>
          <tbody>
          <?php foreach ($mesPaiements as $p): ?>
            <tr>
              <td class="mono"><?= dateFr($p['date_paiement'], true) ?></td>
              <td><?= e($p['libelle_abo']) ?></td>
              <td><?= e($p['moyen_paiement']) ?></td>
              <td><?= euros($p['montant']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
