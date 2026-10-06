<div class="compteurs">
  <?php foreach ([
      'total' => 'comptes', 'admins' => 'administrateurs', 'employes' => 'employés',
      'adherents' => 'adhérents', 'cumuls' => 'cumuls employé + adhérent',
  ] as $key => $label): ?>
    <div class="compteur"><div class="v"><?= $statsRoles[$key] ?></div><div class="k"><?= $label ?></div></div>
  <?php endforeach; ?>
</div>

<div class="barre-outils">
  <a class="btn btn-vert" href="<?= e(url('admin/espace.php?onglet=utilisateurs&compte=creer')) ?>">Créer un compte</a>
</div>

<div class="table-scroll"><table class="tableau">
  <thead><tr><th>Nom</th><th>E-mail</th><th>Rôles</th><th>Créé le</th><th>Compte</th><th>Actions</th></tr></thead>
  <tbody><?php foreach ($utilisateurs as $user): ?><tr>
    <td><?= e($user['nomUsers'] . ' ' . $user['prenomUsers']) ?><?php if ($user['libellefonc']): ?><br><span class="mono"><?= e($user['libellefonc']) ?></span><?php endif; ?></td>
    <td><?= e($user['mailUsers']) ?></td>
    <td><?php if ($user['est_admin']): ?><span class="tag tag-bad">Admin</span><?php endif; ?> <?php if ($user['est_employe']): ?><span class="tag tag-warn">Employé</span><?php endif; ?> <?php if ($user['est_adherent']): ?><span class="tag tag-ok">Adhérent</span><?php endif; ?></td>
    <td class="mono"><?= dateFr($user['Date_CreationUsers']) ?></td>
    <td><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs&compte=voir&id=' . (int) $user['NumUsers'])) ?>">Voir le compte</a></td>
    <td style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-sm btn-ghost" href="<?= e(url('admin/espace.php?onglet=utilisateurs&compte=modifier&id=' . (int) $user['NumUsers'])) ?>">Modifier</a>
      <?php if ($user['est_employe'] && !$user['est_adherent']): ?><form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="ajouter_adherent"><input type="hidden" name="utilisateur" value="<?= (int) $user['NumUsers'] ?>"><button class="btn btn-sm btn-ghost" type="submit">Ajouter adhérent</button></form><?php endif; ?>
      <form method="post"><?= champ_csrf() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="utilisateur" value="<?= (int) $user['NumUsers'] ?>"><button class="btn btn-sm btn-danger" type="submit" data-confirme="Supprimer définitivement ce compte ?">Supprimer</button></form>
    </td>
  </tr><?php endforeach; ?></tbody>
</table></div>
