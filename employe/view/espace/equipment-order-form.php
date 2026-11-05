      <?php if ($modeCommandeMateriel): ?>
        <section class="inventory-order-form-panel" aria-label="Demander une commande de matériel">
          <header><div><h3>Commander un matériel</h3><p>La demande sera transmise à l’administrateur pour validation.</p></div><a href="<?= e(url('employe/espace.php?onglet=materiel')) ?>" aria-label="Fermer"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a></header>
          <?php if (!$equipements): ?>
            <p>Aucun matériel n’est disponible pour une demande de réapprovisionnement.</p>
          <?php else: ?>
            <form method="post">
              <?= champ_csrf() ?>
              <input type="hidden" name="action" value="commander_materiel">
              <label>Matériel<select name="materiel" required><?php foreach ($equipements as $equipement): ?><option value="<?= (int) $equipement['Numequip'] ?>"><?= e($equipement['nomequip']) ?> · stock <?= (int) $equipement['stock_actuel'] ?></option><?php endforeach; ?></select></label>
              <label>Quantité souhaitée<input type="number" name="quantite" min="1" max="10000" required value="1"></label>
              <label>Message à l’administrateur<textarea name="message" maxlength="500" rows="3" placeholder="Précisez le besoin ou l’urgence (facultatif)"></textarea></label>
              <button type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Envoyer la demande</button>
            </form>
          <?php endif; ?>
        </section>
      <?php endif; ?>
