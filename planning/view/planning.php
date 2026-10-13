<?php
/**
 * VIEW du module planning, incluse par employe/espace.php (mode 'employe')
 * et par l'espace adherent (mode 'adherent').
 * Variables attendues, definies par la page avant le require :
 *   $mode             'adherent' ou 'employe'
 *   $planningDonnees  resultat de (new PlanningController())->donnees($mode, $moi)
 * Aucune requete SQL ici : affichage uniquement.
 */
$d     = $planningDonnees;
$jeton = PlanningController::jetonCsrf();
?>

<ul class="planning-legende" aria-label="Légende des activités">
  <?php foreach (PlanningController::TYPES as $cle => $lib): ?>
    <li class="act-<?= e($cle) ?>"><?= e($lib) ?></li>
  <?php endforeach; ?>
  <li class="act-complet">Complet</li>
</ul>

<?php if ($mode === 'adherent'): ?>

  <?php if (!$d['estActif']): ?>
    <p class="flash flash-info">Votre compte est <?= e(mb_strtolower($d['fiche']['libelle_statut'] ?? 'inconnu')) ?> :
       la réservation est bloquée jusqu'à régularisation à l'accueil.</p>
  <?php endif; ?>

  <div id="planning" data-mode="adherent"
       data-csrf="<?= e($jeton['valeur']) ?>" data-csrf-nom="<?= e($jeton['nom']) ?>"
       data-actif="<?= $d['estActif'] ? '1' : '0' ?>"
       data-source="<?= e(url('planning/events.php')) ?>"></div>
  <div id="planning-detail" hidden aria-live="polite"></div>

  <?php if (!$d['creneaux']): ?>
    <div class="vide">Aucun créneau à venir n'est encore programmé.</div>
  <?php else: ?>
    <details>
      <summary style="cursor:pointer;margin-bottom:12px">Afficher les créneaux à venir en liste</summary>
      <div class="creneaux">
        <?php foreach ($d['creneaux'] as $c):
            $num       = (int) $c['Numactivite'];
            $restantes = (int) $c['places_restantes'];
        ?>
          <article class="creneau" id="c<?= $num ?>">
            <h3><?= e($c['titre']) ?></h3>
            <p class="when"><?= jourHeure($c['date_heure_debut'], $c['date_heure_fin']) ?>
               &middot; <?= e($c['nomsalle']) ?></p>
            <div class="jauge"><i class="<?= $restantes === 0 ? 'pleine' : '' ?>" style="width:<?= (int) $c['pct'] ?>%"></i></div>
            <div class="bas">
              <span class="tag <?= $restantes === 0 ? 'tag-bad' : ($restantes <= 3 ? 'tag-warn' : 'tag-ok') ?>">
                <?= $restantes === 0 ? 'Complet' : $restantes . ' place' . ($restantes > 1 ? 's' : '') ?>
              </span>

              <?php if ($c['reserve']): ?>
                <span class="tag tag-mute">Déjà réservé</span>
              <?php else: ?>
                <form method="post" style="margin:0">
                  <?= champ_csrf() ?>
                  <input type="hidden" name="action" value="reserver">
                  <input type="hidden" name="creneau" value="<?= $num ?>">
                  <button class="btn btn-sm btn-vert" type="submit"
                          <?= ($restantes === 0 || !$d['estActif']) ? 'disabled' : '' ?>>Réserver</button>
                </form>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>

<?php else: /* ---------------------------- mode employe ---------------------------- */ ?>

  <div id="planning" data-mode="employe"
       data-csrf="<?= e($jeton['valeur']) ?>" data-csrf-nom="<?= e($jeton['nom']) ?>"
       data-source="<?= e(url('planning/events.php')) ?>"></div>
  <div id="planning-detail" hidden aria-live="polite"></div>

  <div class="form" style="margin-bottom:26px">
    <h2 style="font-size:18px">Ajouter un créneau</h2>
    <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;align-items:end">
      <?= champ_csrf() ?>
      <input type="hidden" name="action" value="creer_creneau">

      <div><label for="titre">Activité</label>
        <input type="text" id="titre" name="titre" maxlength="100" list="titres-existants" required>
        <datalist id="titres-existants">
          <?php foreach ($d['titres'] as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?>
        </datalist></div>

      <div><label for="type">Type</label>
        <select id="type" name="type" required>
          <?php foreach (PlanningController::TYPES as $cle => $lib): ?>
            <option value="<?= e($cle) ?>"><?= e($lib) ?></option>
          <?php endforeach; ?>
        </select></div>

      <div><label for="salle">Salle</label>
        <select id="salle" name="salle" required>
          <?php foreach ($d['salles'] as $s): ?>
            <option value="<?= (int) $s['Numsalle'] ?>"><?= e($s['nomsalle']) ?> (<?= (int) $s['capacitesalle'] ?>)</option>
          <?php endforeach; ?>
        </select></div>

      <div><label for="debut">Début</label>
        <input type="datetime-local" id="debut" name="debut" required></div>

      <div><label for="duree">Durée (min)</label>
        <input type="number" id="duree" name="duree" value="60" min="15" max="480" step="15" required></div>

      <div><label for="capacite">Places</label>
        <input type="number" id="capacite" name="capacite" value="15" min="1" required></div>

      <div><label for="coach">Coach</label>
        <select id="coach" name="coach" required>
          <?php foreach ($d['coachs'] as $c): ?>
            <option value="<?= (int) $c['NumUsers'] ?>" <?= (int) $c['NumUsers'] === (int) $d['moi'] ? 'selected' : '' ?>>
              <?= e($c['prenomUsers'] . ' ' . $c['nomUsers']) ?></option>
          <?php endforeach; ?>
        </select></div>

      <div><label><input type="checkbox" id="repeter" name="repeter" value="1"> Répéter chaque semaine</label></div>

      <div><label for="jusqu_au">Jusqu'au</label>
        <input type="date" id="jusqu_au" name="jusqu_au" disabled></div>

      <div><button class="btn btn-vert" type="submit">Ajouter au planning</button></div>
    </form>
    <p class="aide">Avec « Répéter chaque semaine », le même créneau (même jour, même heure) est créé chaque semaine jusqu'à la date choisie, 52 semaines au maximum. Une date où la salle ou le coach est déjà pris est ignorée et signalée. Les contrôles sont faits côté serveur.</p>
  </div>

  <div class="table-scroll">
    <table class="tableau">
      <thead><tr><th>Activité</th><th>Créneau</th><th>Salle</th><th>Occupation</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($d['creneaux'] as $c): ?>
        <tr>
          <td><?= e($c['titre']) ?></td>
          <td><?= jourHeure($c['date_heure_debut'], $c['date_heure_fin']) ?></td>
          <td><?= e($c['nomsalle']) ?></td>
          <td style="min-width:180px">
            <div class="jauge"><i class="<?= (int) $c['pct'] >= 100 ? 'pleine' : '' ?>" style="width:<?= (int) $c['pct'] ?>%"></i></div>
            <span class="mono"><?= (int) $c['places_prises'] ?>/<?= (int) $c['capacite_max'] ?></span>
          </td>
          <td>
            <form method="post" style="margin:0">
              <?= champ_csrf() ?>
              <input type="hidden" name="action" value="supprimer_creneau">
              <input type="hidden" name="creneau" value="<?= (int) $c['Numactivite'] ?>">
              <input type="hidden" name="portee" value="un">
              <button class="btn btn-sm btn-danger" type="submit"
                      data-confirme="Supprimer ce créneau et ses <?= (int) $c['places_prises'] ?> réservation(s) ?">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

<?php endif; ?>