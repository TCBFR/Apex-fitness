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
