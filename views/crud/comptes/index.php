<div class="compteurs">
  <?php foreach ([
      'total' => 'comptes', 'admins' => 'administrateurs', 'employes' => 'employés',
      'adherents' => 'adhérents', 'cumuls' => 'cumuls employé + adhérent',
  ] as $key => $label): ?>
    <div class="compteur"><div class="v"><?= $statsRoles[$key] ?></div><div class="k"><?= $label ?></div></div>
  <?php endforeach; ?>
</div>

<?php if ($compteModifie): ?><div class="form">
  <h2 style="font-size:18px">Modifier un compte</h2>
  <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;align-items:end">
    <?= champ_csrf() ?><input type="hidden" name="action" value="modifier_utilisateur"><input type="hidden" name="utilisateur" value="<?= (int) $compteModifie['NumUsers'] ?>">
    <div><label for="mod-nom">Nom</label><input id="mod-nom" name="nom" value="<?= e($compteModifie['nomUsers']) ?>" required></div>
    <div><label for="mod-prenom">Prénom</label><input id="mod-prenom" name="prenom" value="<?= e($compteModifie['prenomUsers']) ?>" required></div>
    <div><label for="mod-mail">E-mail</label><input id="mod-mail" name="mail" type="email" value="<?= e($compteModifie['mailUsers']) ?>" required></div>
    <div><label for="mod-tel">Téléphone</label><input id="mod-tel" name="tel" value="<?= e($compteModifie['telUsers']) ?>"></div>
    <button class="btn btn-vert" type="submit">Enregistrer</button><a class="btn btn-ghost" href="admin.php?onglet=utilisateurs">Annuler</a>
  </form>
</div><?php endif; ?>

<div class="form" style="margin-bottom:26px">
  <h2 style="font-size:18px">Créer un compte</h2>
  <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;align-items:end">
    <?= champ_csrf() ?><input type="hidden" name="action" value="creer_utilisateur">
    <div><label for="nom">Nom</label><input type="text" id="nom" name="nom" required></div>
    <div><label for="prenom">Prénom</label><input type="text" id="prenom" name="prenom" required></div>
    <div><label for="mail">E-mail</label><input type="email" id="mail" name="mail" required></div>
    <div><label for="tel">Téléphone</label><input type="text" id="tel" name="tel"></div>
    <div><label for="motdepasse">Mot de passe</label><input type="password" id="motdepasse" name="motdepasse" minlength="8" required></div>
    <div><label for="fonction">Fonction</label><select id="fonction" name="fonction">
      <?php foreach ($fonctions as $fonction): ?><option value="<?= (int) $fonction['Numfonc'] ?>"><?= e($fonction['libellefonc']) ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Rôles</label>
      <?php foreach (['ADHERENT' => 'Adhérent', 'EMPLOYE' => 'Employé', 'ADMIN' => 'Administrateur'] as $role => $label): ?>
        <label style="font-weight:400"><input type="checkbox" name="roles[]" value="<?= $role ?>" style="width:auto"> <?= $label ?></label>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-vert" type="submit">Créer le compte</button>
  </form>
</div>

<div class="table-scroll"><table class="tableau">
  <thead><tr><th>Nom</th><th>E-mail</th><th>Rôles</th><th>Créé le</th><th>Actions</th></tr></thead>
  <tbody><?php foreach ($utilisateurs as $user): ?><tr>
    <td><?= e($user['nomUsers'] . ' ' . $user['prenomUsers']) ?><?php if ($user['libellefonc']): ?><br><span class="mono"><?= e($user['libellefonc']) ?></span><?php endif; ?></td>
    <td><?= e($user['mailUsers']) ?></td>
    <td><?php if ($user['est_admin']): ?><span class="tag tag-bad">Admin</span><?php endif; ?> <?php if ($user['est_employe']): ?><span class="tag tag-warn">Employé</span><?php endif; ?> <?php if ($user['est_adherent']): ?><span class="tag tag-ok">Adhérent</span><?php endif; ?></td>
    <td class="mono"><?= dateFr($user['Date_CreationUsers']) ?></td>
    <td style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-sm btn-ghost" href="admin.php?onglet=utilisateurs&amp;modifier=<?= (int) $user['NumUsers'] ?>">Modifier</a>
      <?php if ($user['est_employe'] && !$user['est_adherent']): ?><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="ajouter_adherent"><input type="hidden" name="utilisateur" value="<?= (int) $user['NumUsers'] ?>"><button class="btn btn-sm btn-ghost" type="submit">Ajouter adhérent</button></form><?php endif; ?>
      <form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="utilisateur" value="<?= (int) $user['NumUsers'] ?>"><button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer définitivement ce compte ?">Supprimer</button></form>
    </td>
  </tr><?php endforeach; ?></tbody>
</table></div>
