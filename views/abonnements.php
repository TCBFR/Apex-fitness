<?php $offres = $offres ?? []; $aboCourant = $aboCourant ?? null; require __DIR__ . '/../includes/header.php'; ?>
<div class="wrap page-head"><h1>Trois formules, aucun frais caché</h1><p class="lead">Freemium et Abonnement + sont mensuels et résiliables ; Basic est annuel avec engagement.</p></div>
<div class="wrap section"><div class="offres">
<?php foreach ($offres as $offre):
  $courant = $aboCourant !== null && $aboCourant === (int) $offre['Numabo'];
  if ($offre['type_abo'] === 'FREEMIUM') {
    $prix = euros($offre['prix_mensuel_reduit']) . ' <small>/ mois</small>';
    $detail = 'Réduction de ' . (int) $offre['taux_reduction'] . ' %';
    $points = [(int) $offre['nb_reservations_max_mois'] . ' réservations par mois', 'Justificatif : ' . $offre['justificatif_requis'], 'Sans engagement'];
  } elseif ($offre['type_abo'] === 'BASIC') {
    $prix = euros($offre['prix_annuel']) . ' <small>/ an</small>';
    $detail = 'Engagement de ' . (int) $offre['duree_engagement_mois'] . ' mois';
    $points = ['Réservations illimitées', $offre['reconduction_tacite'] ? 'Reconduction tacite' : 'Sans reconduction', 'Accès aux trois salles'];
  } else {
    $prix = euros($offre['prix_mensuel_plus']) . ' <small>/ mois</small>';
    $detail = 'Supplément de ' . euros($offre['supplement_mensuel']);
    $points = [(int) $offre['seances_coach_incluses'] . ' séances de coach incluses', $offre['acces_illimite'] ? 'Accès illimité' : 'Accès limité', 'Résiliable chaque mois'];
  }
?>
<article class="offre <?= $offre['type_abo'] === 'PLUS' ? 'vedette' : '' ?>"><h3><?= e($offre['libelle_abo']) ?></h3><p class="muted"><?= e($offre['description_abo']) ?></p><div class="prix"><?= $prix ?></div><p class="mono"><?= e($detail) ?></p><ul><?php foreach ($points as $point): ?><li><?= e($point) ?></li><?php endforeach; ?></ul>
<?php if ($courant): ?><span class="tag tag-ok">Votre formule actuelle</span><?php elseif (a_role('ADHERENT')): ?><a class="btn btn-ghost" href="<?= url('paiement.php?switch=1') ?>">Changer de formule</a><?php else: ?><a class="btn btn-vert" href="<?= url('contact.php?sujet=' . urlencode('Inscription : ' . $offre['libelle_abo'])) ?>">Souscrire</a><?php endif; ?></article>
<?php endforeach; ?></div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
