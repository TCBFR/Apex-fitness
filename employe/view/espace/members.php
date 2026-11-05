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
      <thead><tr><th>Matricule</th><th>Nom</th><th>Contact</th><th>Formule</th><th>Statut</th><th>Fin</th></tr></thead>
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
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p class="vide" data-vide hidden>Aucun adhérent ne correspond à cette recherche.</p>
  </div>
