<div class="form" style="max-width:760px;margin-bottom:26px">
  <h2 style="font-size:18px"><?= $fournisseurModifie ? 'Modifier un fournisseur' : 'Ajouter un fournisseur' ?></h2>
  <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end">
    <?= champ_csrf() ?><input type="hidden" name="action" value="fournisseur_enregistrer"><input type="hidden" name="id" value="<?= $fournisseurModifie ? (int) $fournisseurModifie['Numfourn'] : '' ?>">
    <div><label for="nom">Nom</label><input id="nom" name="nom" value="<?= e($fournisseurModifie['nomfourn'] ?? '') ?>" required></div>
    <div><label for="telephone">Téléphone</label><input id="telephone" name="telephone" value="<?= e($fournisseurModifie['telephonefourn'] ?? '') ?>"></div>
    <div><label for="siret">SIRET</label><input id="siret" name="siret" maxlength="14" value="<?= e($fournisseurModifie['siret'] ?? '') ?>"></div>
    <div><label for="mail">E-mail</label><input id="mail" name="mail" type="email" value="<?= e($fournisseurModifie['mailfourn'] ?? '') ?>"></div>
    <button class="btn btn-vert" type="submit"><?= $fournisseurModifie ? 'Enregistrer' : 'Ajouter' ?></button>
    <?php if ($fournisseurModifie): ?><a class="btn btn-ghost" href="gestion.php">Annuler</a><?php endif; ?>
  </form>
</div>
<div class="table-scroll"><table class="tableau"><thead><tr><th>Nom</th><th>Téléphone</th><th>SIRET</th><th>E-mail</th><th>Action</th></tr></thead><tbody>
<?php foreach ($fournisseurs as $fournisseur): ?><tr>
  <td><?= e($fournisseur['nomfourn']) ?></td><td><?= e($fournisseur['telephonefourn']) ?></td><td class="mono"><?= e($fournisseur['siret']) ?></td><td><?= e($fournisseur['mailfourn']) ?></td>
  <td style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-sm btn-ghost" href="?onglet=fournisseurs&amp;modifier=<?= (int) $fournisseur['Numfourn'] ?>">Modifier</a><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="fournisseur_supprimer"><input type="hidden" name="id" value="<?= (int) $fournisseur['Numfourn'] ?>"><button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer ce fournisseur ?">Supprimer</button></form></td>
</tr><?php endforeach; ?></tbody></table></div>
