<?php
$formatMontantAbo = static fn($montant): string => number_format((float) $montant, 2, ',', ' ') . ' €';
$aujourdhuiAbo = new DateTimeImmutable('today');
$limiteSeptJoursAbo = $aujourdhuiAbo->modify('+7 days');
$nbAboActifs = count(array_filter($abonnementsAdmin, static fn(array $abo): bool => !in_array($abo['statut_affiche'], ['Résilié', 'Suspendu', 'Expiré'], true)));
$nbAboExpireBientot = count(array_filter($abonnementsAdmin, static function (array $abo) use ($aujourdhuiAbo, $limiteSeptJoursAbo): bool {
    if (!$abo['date_fin'] || in_array($abo['statut_affiche'], ['Résilié', 'Suspendu', 'Expiré'], true)) return false;
    $fin = new DateTimeImmutable($abo['date_fin']);
    return $fin >= $aujourdhuiAbo && $fin <= $limiteSeptJoursAbo;
}));
$nbAboExpires = count(array_filter($abonnementsAdmin, static fn(array $abo): bool => $abo['statut_affiche'] === 'Expiré'));
$nbAboRenouveler = count(array_filter($abonnementsAdmin, static fn(array $abo): bool => $abo['statut_affiche'] === 'À renouveler'));
$modifierAboAdmin = $abonnementSelectionne && (string) ($_GET['modifier'] ?? '') === '1';
$cleAboSelectionne = $abonnementSelectionne ? $abonnementSelectionne['NumUsers'] . '|' . $abonnementSelectionne['Numabo'] . '|' . $abonnementSelectionne['date_debut'] : '';
?>
<section class="admin-subscriptions">
  <header class="admin-subscriptions-heading"><div class="admin-subscriptions-title"><span aria-hidden="true">▣</span><div><h2>Gestion des abonnements</h2><p>Consultez et gérez les abonnements de vos adhérents. Renouvelez, suspendez ou résiliez un abonnement.</p></div></div></header>

  <div class="admin-subscriptions-stats admin-subscriptions-stats-four">
    <article class="stat-active"><span class="stat-icon" aria-hidden="true">♟</span><div><small>ABONNEMENTS ACTIFS</small><strong><?= $nbAboActifs ?></strong><span>Adhésions en cours</span></div></article>
    <article class="stat-soon"><span class="stat-icon" aria-hidden="true">▦</span><div><small>EXPIRENT DANS 7 JOURS</small><strong><?= $nbAboExpireBientot ?></strong><span>Échéances à surveiller</span></div></article>
    <article class="stat-expired"><span class="stat-icon" aria-hidden="true">◷</span><div><small>ABONNEMENTS EXPIRÉS</small><strong><?= $nbAboExpires ?></strong><span>À renouveler ou archiver</span></div></article>
    <article class="stat-renew"><span class="stat-icon" aria-hidden="true">↻</span><div><small>À RENOUVELER</small><strong><?= $nbAboRenouveler ?></strong><span>Échéance dans les 30 jours</span></div></article>
  </div>

  <div class="subscription-filters subscription-filters-expanded">
    <label class="subscription-search"><span aria-hidden="true">⌕</span><input id="recherche-abonnement" type="search" placeholder="Rechercher un adhérent, un e-mail ou une formule…" autocomplete="off"></label>
    <select id="filtre-formule" aria-label="Filtrer par formule"><option value="">Toutes les formules</option><?php foreach ($formulesAdmin as $formule): ?><option value="<?= e(mb_strtolower($formule['libelle_abo'])) ?>"><?= e($formule['libelle_abo']) ?></option><?php endforeach; ?></select>
    <select id="filtre-statut-abonnement" aria-label="Filtrer par statut"><option value="">Tous les statuts</option><?php foreach (['Actif','À renouveler','Expiré','Suspendu','Résilié'] as $statutAboFiltre): ?><option value="<?= e($statutAboFiltre) ?>"><?= e($statutAboFiltre) ?></option><?php endforeach; ?></select>
    <div class="subscription-date-range"><span aria-hidden="true">▦</span><input id="filtre-date-debut" type="date" aria-label="Date de début minimale"><span>→</span><input id="filtre-date-fin" type="date" aria-label="Date de début maximale"></div>
  </div>

  <div class="subscription-layout <?= $vueAbonnements === 'liste' ? 'without-detail' : '' ?>">
    <section class="subscription-list-panel">
      <header class="subscription-list-heading"><div><h3>Liste des abonnements</h3><p><?= count($abonnementsAdmin) ?> abonnement<?= count($abonnementsAdmin) === 1 ? '' : 's' ?></p></div><button type="button" aria-label="Options de la liste" title="Options de la liste">⋮</button></header>
      <div class="table-scroll"><table class="tableau subscription-table subscription-table-wide"><thead><tr><th><input type="checkbox" id="abonnements-selectionner" aria-label="Sélectionner tous les abonnements visibles"></th><th>Adhérent</th><th>Formule</th><th>Période</th><th>Statut</th><th>Prochain renouvellement</th><th>Dernier règlement</th><th>Actions</th></tr></thead>
        <tbody id="abonnements-lignes">
          <?php foreach ($abonnementsAdmin as $abo):
            $nom = trim($abo['prenomUsers'] . ' ' . $abo['nomUsers']);
            $cle = $abo['NumUsers'] . '|' . $abo['Numabo'] . '|' . $abo['date_debut'];
            $urlBaseFiche = 'admin/espace.php?onglet=abonnements&id=' . (int) $abo['NumUsers'] . '&formule=' . (int) $abo['Numabo'] . '&debut=' . rawurlencode($abo['date_debut']);
            $urlFiche = url($urlBaseFiche);
            $urlModifier = url($urlBaseFiche . '&modifier=1');
            $joursRestantsAbo = $abo['date_fin'] ? (int) floor((strtotime($abo['date_fin']) - strtotime('today')) / 86400) : null;
            $termeRechercheAbo = mb_strtolower($nom . ' ' . $abo['mailUsers'] . ' ' . $abo['libelle_abo']);
          ?>
          <tr data-abo-row data-search="<?= e($termeRechercheAbo) ?>" data-formule="<?= e(mb_strtolower($abo['libelle_abo'])) ?>" data-statut="<?= e($abo['statut_affiche']) ?>" data-nom="<?= e(mb_strtolower($nom)) ?>" data-date="<?= e($abo['date_debut']) ?>" data-echeance="<?= e($abo['date_fin'] ?? '9999-12-31') ?>" data-cle="<?= e($cle) ?>" class="<?= $cle === $cleAboSelectionne ? 'is-selected' : '' ?>">
            <td><input type="checkbox" class="abo-row-checkbox" aria-label="Sélectionner l’abonnement de <?= e($nom) ?>"></td>
            <td><span class="subscription-avatar"><?= e(mb_strtoupper(mb_substr($abo['prenomUsers'], 0, 1) . mb_substr($abo['nomUsers'], 0, 1))) ?></span><span class="subscription-member"><strong><?= e($nom) ?></strong><small><?= e($abo['mailUsers']) ?></small></span></td>
            <td><span class="subscription-plan plan-<?= e(mb_strtolower($abo['type_offre'])) ?>"><?= e($abo['libelle_abo']) ?></span></td>
            <td><strong><?= e(dateFr($abo['date_debut'])) ?> → <?= e($abo['date_fin'] ? dateFr($abo['date_fin']) : 'Sans échéance') ?></strong><small><?= $abo['date_fin'] ? (max(0, $joursRestantsAbo) . ' jour' . (max(0, $joursRestantsAbo) === 1 ? '' : 's')) : 'Sans limite' ?></small></td>
            <td><span class="subscription-status status-<?= e(str_replace(' ', '-', mb_strtolower($abo['statut_affiche']))) ?>"><i></i><?= e($abo['statut_affiche']) ?></span></td>
            <td><strong><?= e($abo['date_fin'] ? dateFr($abo['date_fin']) : '—') ?></strong><small><?= $joursRestantsAbo !== null && $joursRestantsAbo >= 0 ? 'dans ' . $joursRestantsAbo . ' jour' . ($joursRestantsAbo === 1 ? '' : 's') : ($joursRestantsAbo !== null ? 'Échéance passée' : 'Aucune échéance') ?></small></td>
            <td><strong><?= e($abo['dernier_montant'] !== null ? $formatMontantAbo($abo['dernier_montant']) : $formatMontantAbo($abo['tarif'])) ?></strong><small><?= e($abo['dernier_paiement'] ? dateFr($abo['dernier_paiement']) : 'Aucun règlement') ?></small></td>
            <td><div class="subscription-row-actions"><a href="<?= e($urlFiche) ?>" aria-label="Lire l’abonnement de <?= e($nom) ?>" title="Lire">◉</a><a href="<?= e($urlModifier) ?>" aria-label="Modifier l’abonnement de <?= e($nom) ?>" title="Modifier">✎</a><a href="<?= e($urlFiche) ?>" aria-label="Actions pour <?= e($nom) ?>" title="Actions">⋯</a></div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
      <footer class="subscription-pagination"><span id="abonnements-pagination-info"></span><div><button type="button" id="abonnements-precedent">Précédent</button><span id="abonnements-pages"></span><button type="button" id="abonnements-suivant">Suivant</button></div></footer>
      <p class="subscription-empty" id="abonnements-vide" hidden>Aucun abonnement ne correspond à ces critères.</p>
    </section>

    <?php if ($vueAbonnements !== 'liste' && $abonnementSelectionne): $a = $abonnementSelectionne; $urlBaseFicheSelection = 'admin/espace.php?onglet=abonnements&id=' . (int) $a['NumUsers'] . '&formule=' . (int) $a['Numabo'] . '&debut=' . rawurlencode($a['date_debut']); $postHidden = static function (array $abo): string { return '<input type="hidden" name="adherent" value="' . (int) $abo['NumUsers'] . '"><input type="hidden" name="formule_actuelle" value="' . (int) $abo['Numabo'] . '"><input type="hidden" name="date_debut_actuelle" value="' . e($abo['date_debut']) . '">'; }; ?>
      <aside class="subscription-detail">
        <header><button type="button" class="subscription-close" onclick="location.href='<?= e(url('admin/espace.php?onglet=abonnements&vue=liste')) ?>'" aria-label="Retour à la liste">×</button><p class="admin-eyebrow">DÉTAILS DE L’ABONNEMENT</p><div class="subscription-detail-member"><span class="subscription-avatar large"><?= e(mb_strtoupper(mb_substr($a['prenomUsers'], 0, 1) . mb_substr($a['nomUsers'], 0, 1))) ?></span><div><h3><?= e($a['prenomUsers'] . ' ' . $a['nomUsers']) ?></h3><small><?= e($a['mailUsers']) ?></small></div><span class="subscription-status status-<?= e(str_replace(' ', '-', mb_strtolower($a['statut_affiche']))) ?>"><i></i><?= e($a['statut_affiche']) ?></span></div></header>
        <section class="subscription-detail-card subscription-detail-formula"><div><span>Formule</span><strong class="subscription-plan plan-<?= e(mb_strtolower($a['type_offre'])) ?>"><?= e($a['libelle_abo']) ?></strong></div><b><?= e($formatMontantAbo($a['dernier_montant'] ?? $a['tarif'])) ?> <small>/ période</small></b></section>
        <section class="subscription-detail-card subscription-duration"><div><strong>Période d’abonnement</strong><b><?= $a['date_fin'] ? e(dateFr($a['date_fin'])) : 'Sans échéance' ?></b></div><div class="subscription-progress"><i style="width:<?= $a['date_fin'] ? (strtotime($a['date_fin']) < time() ? '100' : max(5, min(100, (int) round(100 * (1 - (strtotime($a['date_fin']) - time()) / (365 * 86400)))))) : 100 ?>%"></i></div><small><?= e(dateFr($a['date_debut'])) ?><span><?= e($a['date_fin'] ? dateFr($a['date_fin']) : 'En cours') ?></span></small></section>
        <?php if ($modifierAboAdmin): ?>
          <form method="post" class="subscription-edit-form"><?= champ_csrf() ?><input type="hidden" name="action" value="modifier_abonnement_admin"><?= $postHidden($a) ?><h4>Modifier l’échéance</h4><label>Date de début<input type="date" value="<?= e($a['date_debut']) ?>" disabled></label><label>Date de fin<input type="date" name="date_fin" min="<?= e($a['date_debut']) ?>" value="<?= e($a['date_fin'] ?? '') ?>"><small>Laissez vide pour un abonnement sans échéance.</small></label><div><a class="btn btn-ghost" href="<?= e(url($urlBaseFicheSelection)) ?>">Annuler</a><button class="btn btn-vert" type="submit">Enregistrer</button></div></form>
        <?php else: ?>
          <section class="subscription-detail-card"><h4>Détails</h4><dl><div><dt>Formule</dt><dd><?= e($a['libelle_abo']) ?></dd></div><div><dt>Tarif</dt><dd><?= e($formatMontantAbo($a['dernier_montant'] ?? $a['tarif'])) ?></dd></div><div><dt>Début</dt><dd><?= e(dateFr($a['date_debut'])) ?></dd></div><div><dt>Prochain renouvellement</dt><dd><?= e($a['date_fin'] ? dateFr($a['date_fin']) : 'Non défini') ?></dd></div><div><dt>Moyen de paiement</dt><dd><?= e($a['moyen_paiement'] ?: 'Non renseigné') ?></dd></div><div><dt>Dernier règlement</dt><dd><?= e($a['dernier_paiement'] ? dateFr($a['dernier_paiement']) : 'Aucun') ?></dd></div></dl></section>
          <section class="subscription-detail-card"><div class="subscription-payments-title"><h4>Derniers règlements</h4><a href="<?= e(url('admin/espace.php?onglet=paiements')) ?>">Voir tout</a></div><?php if (!$paiementsAbonnement): ?><p class="subscription-no-payments">Aucun règlement enregistré.</p><?php else: foreach (array_slice($paiementsAbonnement, 0, 3) as $paiement): ?><div class="subscription-payment"><span><strong><?= e(dateFr($paiement['date_paiement'])) ?></strong><small><?= e($paiement['moyen_paiement']) ?></small></span><b><?= e($formatMontantAbo($paiement['montant'])) ?></b><a href="<?= e(url('admin/facture.php?id=' . (int) $paiement['Numpaiement'])) ?>">PDF</a></div><?php endforeach; endif; ?></section>
          <section class="subscription-detail-actions"><h4>Actions rapides</h4>
            <?php if (in_array($a['statut_affiche'], ['Résilié', 'Suspendu'], true)): ?><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="reactiver_abonnement"><?= $postHidden($a) ?><button class="btn btn-vert btn-block" type="submit">Réactiver l’abonnement</button></form>
            <?php else: ?><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="renouveler_abonnement"><?= $postHidden($a) ?><button class="btn btn-vert btn-block" type="submit">↻ Renouveler l’abonnement</button></form><form method="post" class="subscription-change-form"><?= champ_csrf() ?><input type="hidden" name="action" value="changer_formule_abonnement"><?= $postHidden($a) ?><select name="nouvelle_formule" aria-label="Nouvelle formule"><?php foreach ($formulesAdmin as $formule): ?><option value="<?= (int) $formule['Numabo'] ?>" <?= (int) $formule['Numabo'] === (int) $a['Numabo'] ? 'disabled' : '' ?>><?= e($formule['libelle_abo']) ?></option><?php endforeach; ?></select><button class="btn btn-ghost" type="submit">Changer de formule</button></form><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="suspendre_abonnement"><?= $postHidden($a) ?><button class="btn btn-ghost btn-block" type="submit" data-confirme="Suspendre cet abonnement ?">Ⅱ Suspendre l’abonnement</button></form><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="resilier_abonnement"><?= $postHidden($a) ?><button class="subscription-cancel" type="submit" data-confirme="Résilier cet abonnement ?">⊗ Résilier l’abonnement</button></form><?php endif; ?>
          </section>
          <a class="subscription-full-details" href="<?= e(url($urlBaseFicheSelection . '&modifier=1')) ?>">✎ Modifier l’échéance</a>
        <?php endif; ?>
      </aside>
    <?php elseif ($vueAbonnements !== 'liste'): ?><aside class="subscription-detail subscription-detail-empty"><span>↗</span><h3>Aucun abonnement enregistré</h3><p>Les abonnements existants apparaîtront ici.</p></aside><?php endif; ?>
  </div>
</section>
<script>
(() => {
  const rows=[...document.querySelectorAll('[data-abo-row]')], search=document.getElementById('recherche-abonnement'), formula=document.getElementById('filtre-formule'), status=document.getElementById('filtre-statut-abonnement'), start=document.getElementById('filtre-date-debut'), end=document.getElementById('filtre-date-fin');
  const info=document.getElementById('abonnements-pagination-info'), pageOut=document.getElementById('abonnements-pages'); let page=1; const pageSize=8;
  function render(){const q=search.value.trim().toLocaleLowerCase('fr'), f=formula.value, st=status.value, from=start.value, to=end.value;let visible=rows.filter(r=>r.dataset.search.includes(q)&&(!f||r.dataset.formule===f)&&(!st||r.dataset.statut===st)&&(!from||r.dataset.date>=from)&&(!to||r.dataset.date<=to));const pages=Math.max(1,Math.ceil(visible.length/pageSize));page=Math.max(1,Math.min(page,pages));rows.forEach(r=>r.hidden=true);visible.slice((page-1)*pageSize,page*pageSize).forEach(r=>r.hidden=false);info.textContent=visible.length?`Affichage de ${(page-1)*pageSize+1} à ${Math.min(page*pageSize,visible.length)} sur ${visible.length} abonnements`:'0 abonnement';pageOut.textContent=`${page} / ${pages}`;document.getElementById('abonnements-vide').hidden=visible.length>0;document.getElementById('abonnements-precedent').disabled=page<=1;document.getElementById('abonnements-suivant').disabled=page>=pages;}
  [search,formula,status,start,end].forEach(el=>el.addEventListener('input',()=>{page=1;render()}));document.getElementById('abonnements-precedent').addEventListener('click',()=>{page--;render()});document.getElementById('abonnements-suivant').addEventListener('click',()=>{page++;render()});
  document.getElementById('abonnements-selectionner')?.addEventListener('change',event=>document.querySelectorAll('#abonnements-lignes tr:not([hidden]) .abo-row-checkbox').forEach(box=>box.checked=event.target.checked));render();
})();
</script>
