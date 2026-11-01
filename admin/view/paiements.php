<?php
$montantTrenteJours = 0.0;
$dateLimitePaiement = new DateTimeImmutable('-30 days');
$moyensPaiementAdmin = [];
foreach ($historiquePaiements as $lignePaiement) {
    if (new DateTimeImmutable($lignePaiement['date_paiement']) >= $dateLimitePaiement) $montantTrenteJours += (float) $lignePaiement['montant'];
    $moyensPaiementAdmin[$lignePaiement['moyen_paiement']] = true;
}
$paiementsReussisAdmin = count($historiquePaiements);
$paiementCourant = $paiementSelectionneAdmin;
$referencePaiement = static fn(array $paiement): string => '#PAY-' . str_pad((string) $paiement['Numpaiement'], 4, '0', STR_PAD_LEFT);
$formatMontantPaiement = static fn($montant): string => number_format((float) $montant, 2, ',', ' ') . ' €';
?>
<section class="admin-payments-page-content">
  <header class="admin-payments-heading"><div class="admin-payments-title"><span aria-hidden="true">▣</span><div><h2>Historique des paiements</h2><p>Consultez l’historique des règlements de vos adhérents, recherchez et téléchargez les factures.</p></div></div></header>

  <div class="admin-payment-stats">
    <article class="payment-stat-amount"><span class="payment-stat-icon" aria-hidden="true">▤</span><div><small>MONTANT ENCAISSÉ (30 JOURS)</small><strong><?= e($formatMontantPaiement($montantTrenteJours)) ?></strong><span>Somme des paiements enregistrés</span></div></article>
    <article class="payment-stat-success"><span class="payment-stat-icon" aria-hidden="true">✓</span><div><small>PAIEMENTS ENREGISTRÉS</small><strong><?= $paiementsReussisAdmin ?></strong><span>Règlements présents dans l’historique</span></div></article>
    <article class="payment-stat-methods"><span class="payment-stat-icon" aria-hidden="true">◷</span><div><small>MOYENS DE PAIEMENT</small><strong><?= count($moyensPaiementAdmin) ?></strong><span>Moyens utilisés par les adhérents</span></div></article>
    <article class="payment-stat-invoices"><span class="payment-stat-icon" aria-hidden="true">⇩</span><div><small>FACTURES DISPONIBLES</small><strong><?= $paiementsReussisAdmin ?></strong><span>Téléchargement PDF à la demande</span></div></article>
  </div>

  <div class="admin-payment-filters">
    <label class="admin-payment-search"><span aria-hidden="true">⌕</span><input type="search" id="recherche-paiement" placeholder="Rechercher un adhérent, une référence, une formule…" autocomplete="off"></label>
    <label><small>Formule</small><select id="filtre-paiement-formule"><option value="">Toutes les formules</option><?php foreach (array_unique(array_column($historiquePaiements, 'libelle_abo')) as $formulePaiement): ?><option value="<?= e(mb_strtolower($formulePaiement)) ?>"><?= e($formulePaiement) ?></option><?php endforeach; ?></select></label>
    <label><small>Moyen de paiement</small><select id="filtre-paiement-moyen"><option value="">Tous les moyens</option><?php foreach (array_keys($moyensPaiementAdmin) as $moyenPaiement): ?><option value="<?= e(mb_strtolower($moyenPaiement)) ?>"><?= e($moyenPaiement) ?></option><?php endforeach; ?></select></label>
    <label><small>Période</small><select id="filtre-paiement-periode"><option value="">Toutes les dates</option><option value="30">30 derniers jours</option><option value="90">90 derniers jours</option><option value="365">12 derniers mois</option></select></label>
  </div>

  <div class="admin-payment-layout">
    <section class="admin-payment-list">
      <header><div><h3>Liste des paiements</h3><p><?= count($historiquePaiements) ?> paiement<?= count($historiquePaiements) === 1 ? '' : 's' ?></p></div><button type="button" id="selectionner-paiements" aria-label="Sélectionner les paiements visibles">⋮</button></header>
      <?php if (!$historiquePaiements): ?><div class="admin-payment-empty">Aucun paiement enregistré.</div><?php else: ?>
        <div class="admin-payment-table-scroll"><table class="admin-payment-table"><thead><tr><th><input id="selectionner-tout-paiement" type="checkbox" aria-label="Tout sélectionner"></th><th>Adhérent</th><th>Formule / abonnement</th><th>Date</th><th>Moyen de paiement</th><th>Montant</th><th>Statut</th><th>Référence</th><th>Actions</th></tr></thead><tbody id="paiements-lignes">
          <?php foreach ($historiquePaiements as $paiement):
            $nomComplet = trim($paiement['prenomUsers'] . ' ' . $paiement['nomUsers']);
            $reference = $referencePaiement($paiement);
            $recherchePaiement = mb_strtolower($nomComplet . ' ' . $paiement['mailUsers'] . ' ' . $paiement['libelle_abo'] . ' ' . $paiement['moyen_paiement'] . ' ' . $reference);
            $datePaiementCourante = new DateTimeImmutable($paiement['date_paiement']);
            $clePaiementSelectionne = $paiementCourant && (int) $paiementCourant['Numpaiement'] === (int) $paiement['Numpaiement'];
            $urlPaiementCourant = url('admin/espace.php?onglet=paiements&paiement=' . (int) $paiement['Numpaiement']);
          ?>
            <tr data-paiement-row data-search="<?= e($recherchePaiement) ?>" data-formula="<?= e(mb_strtolower($paiement['libelle_abo'])) ?>" data-method="<?= e(mb_strtolower($paiement['moyen_paiement'])) ?>" data-date="<?= e($datePaiementCourante->format('Y-m-d')) ?>" class="<?= $clePaiementSelectionne ? 'is-selected' : '' ?>">
              <td><input type="checkbox" class="payment-row-checkbox" aria-label="Sélectionner le paiement <?= e($reference) ?>"></td>
              <td><span class="payment-member-avatar"><?= e(mb_strtoupper(mb_substr($paiement['prenomUsers'], 0, 1) . mb_substr($paiement['nomUsers'], 0, 1))) ?></span><span class="payment-member-name"><strong><?= e($nomComplet) ?></strong><small><?= e($paiement['mailUsers']) ?></small></span></td>
              <td><strong><?= e($paiement['libelle_abo']) ?></strong><small><?= $paiement['date_debut_abo'] ? e(dateFr($paiement['date_debut_abo'])) . ($paiement['date_fin_abo'] ? ' → ' . e(dateFr($paiement['date_fin_abo'])) : '') : 'Abonnement' ?></small></td>
              <td><strong><?= e(dateFr($paiement['date_paiement'])) ?></strong><small><?= $datePaiementCourante->format('H:i') ?></small></td>
              <td><?= e($paiement['moyen_paiement']) ?></td>
              <td><strong><?= e($formatMontantPaiement($paiement['montant'])) ?></strong></td>
              <td><span class="payment-status"><i></i>Réussi</span></td>
              <td><span class="payment-reference"><?= e($reference) ?></span></td>
              <td><div class="payment-row-actions"><a href="<?= e($urlPaiementCourant) ?>" aria-label="Voir le paiement <?= e($reference) ?>" title="Voir">◉</a><a href="<?= e(url('admin/facture.php?id=' . (int) $paiement['Numpaiement'])) ?>" aria-label="Télécharger la facture <?= e($reference) ?>" title="Télécharger le PDF">⇩</a></div></td>
            </tr>
          <?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
      <footer class="admin-payment-pagination"><span id="paiements-pagination-info"></span><div><button type="button" id="paiements-precedent">Précédent</button><span id="paiements-pages"></span><button type="button" id="paiements-suivant">Suivant</button></div></footer>
      <p class="admin-payment-filter-empty" id="paiements-vide" hidden>Aucun paiement ne correspond à ces filtres.</p>
    </section>

    <?php if ($paiementCourant): $reference = $referencePaiement($paiementCourant); $nomMembreCourant = trim($paiementCourant['prenomUsers'] . ' ' . $paiementCourant['nomUsers']); ?>
      <aside class="admin-payment-detail">
        <header><div><h3>Détails du paiement</h3><a href="<?= e(url('admin/espace.php?onglet=paiements')) ?>">← Retour à la liste</a></div></header>
        <section class="payment-detail-total"><div class="payment-success-icon">✓</div><strong>Paiement réussi</strong><b><?= e($formatMontantPaiement($paiementCourant['montant'])) ?></b><span>Référence : <?= e($reference) ?></span></section>
        <section class="payment-detail-info"><span class="payment-detail-icon">♙</span><div><small>Adhérent</small><strong><?= e($nomMembreCourant) ?></strong><span><?= e($paiementCourant['mailUsers']) ?></span></div></section>
        <section class="payment-detail-info"><span class="payment-detail-icon">▦</span><div><small>Formule / abonnement</small><strong><?= e($paiementCourant['libelle_abo']) ?></strong><span><?= $paiementCourant['date_debut_abo'] ? 'Du ' . e(dateFr($paiementCourant['date_debut_abo'])) . ($paiementCourant['date_fin_abo'] ? ' au ' . e(dateFr($paiementCourant['date_fin_abo'])) : '') : 'Période non renseignée' ?></span></div></section>
        <section class="payment-detail-info"><span class="payment-detail-icon">▤</span><div><small>Moyen de paiement</small><strong><?= e($paiementCourant['moyen_paiement']) ?></strong></div></section>
        <section class="payment-detail-info"><span class="payment-detail-icon">▦</span><div><small>Date du paiement</small><strong><?= e(dateFr($paiementCourant['date_paiement'], true)) ?></strong></div></section>
        <section class="payment-detail-info"><span class="payment-detail-icon">€</span><div><small>Montant réglé</small><strong><?= e($formatMontantPaiement($paiementCourant['montant'])) ?></strong></div></section>
        <section class="payment-invoice-card"><span>▧</span><div><strong>Facture disponible</strong><small>Facture_<?= e(str_replace('#', '', $reference)) ?>.pdf</small></div><a href="<?= e(url('admin/facture.php?id=' . (int) $paiementCourant['Numpaiement'])) ?>">⇩ Télécharger</a></section>
        <a class="payment-member-link" href="<?= e(url('admin/espace.php?onglet=adherents&id=' . (int) $paiementCourant['NumUsers'] . '&vue=voir')) ?>">♙ Voir la fiche adhérent</a>
      </aside>
    <?php else: ?><aside class="admin-payment-detail payment-detail-empty"><h3>Aucun paiement sélectionné</h3><p>Les nouveaux règlements apparaîtront ici.</p></aside><?php endif; ?>
  </div>
</section>
<script>
(() => {
  const rows=[...document.querySelectorAll('[data-paiement-row]')], search=document.getElementById('recherche-paiement'), formula=document.getElementById('filtre-paiement-formule'), method=document.getElementById('filtre-paiement-moyen'), period=document.getElementById('filtre-paiement-periode');
  const info=document.getElementById('paiements-pagination-info'), pageOut=document.getElementById('paiements-pages');let page=1;const pageSize=8;
  function render(){const q=search.value.trim().toLocaleLowerCase('fr'),f=formula.value,m=method.value,days=Number(period.value||0),cutoff=days?new Date():null;if(cutoff)cutoff.setDate(cutoff.getDate()-days);let visible=rows.filter(row=>{const date=new Date(row.dataset.date+'T00:00:00');return row.dataset.search.includes(q)&&(!f||row.dataset.formula===f)&&(!m||row.dataset.method===m)&&(!cutoff||date>=cutoff);});const pages=Math.max(1,Math.ceil(visible.length/pageSize));page=Math.max(1,Math.min(page,pages));rows.forEach(row=>row.hidden=true);visible.slice((page-1)*pageSize,page*pageSize).forEach(row=>row.hidden=false);info.textContent=visible.length?`Affichage de ${(page-1)*pageSize+1} à ${Math.min(page*pageSize,visible.length)} sur ${visible.length} paiements`:'0 paiement';pageOut.textContent=`${page} / ${pages}`;document.getElementById('paiements-vide').hidden=visible.length>0;document.getElementById('paiements-precedent').disabled=page<=1;document.getElementById('paiements-suivant').disabled=page>=pages;}
  [search,formula,method,period].forEach(element=>element.addEventListener('input',()=>{page=1;render()}));document.getElementById('paiements-precedent').addEventListener('click',()=>{page--;render()});document.getElementById('paiements-suivant').addEventListener('click',()=>{page++;render()});
  document.getElementById('selectionner-tout-paiement')?.addEventListener('change',event=>document.querySelectorAll('#paiements-lignes tr:not([hidden]) .payment-row-checkbox').forEach(box=>box.checked=event.target.checked));
})();
</script>
