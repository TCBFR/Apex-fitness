  <section class="ticket-form-card">
    <div class="ticket-form-intro">
      <span class="ticket-form-icon" aria-hidden="true">+</span>
      <div>
        <p class="admin-eyebrow">DEMANDE D’INTERVENTION</p>
        <h3>Signaler un problème matériel</h3>
        <p>Renseignez l’équipement concerné et les détails du problème. Votre demande sera transmise à l’équipe de maintenance.</p>
      </div>
    </div>
    <?php if ($equipementsTicketables): ?>
      <form method="post" class="ticket-form">
        <?= champ_csrf() ?>
        <input type="hidden" name="action" value="declarer_panne">
        <div class="ticket-form-grid">
          <div class="ticket-field ticket-field-full">
            <label for="ticket-equipement">Équipement concerné <span aria-hidden="true">*</span></label>
            <select id="ticket-equipement" name="equipement" required>
              <option value="">Sélectionnez un équipement</option>
              <?php foreach ($equipementsTicketables as $equipement): ?>
                <option value="<?= (int) $equipement['Numequip'] ?>">
                  <?= e($equipement['nomequip'] . ' — ' . $equipement['num_inventaire'] . ' · ' . $equipement['libelle_statut']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small>Les appareils ayant déjà un ticket ouvert ne peuvent pas être sélectionnés.</small>
          </div>
          <div class="ticket-field ticket-field-full">
            <label for="ticket-description">Description du problème <span aria-hidden="true">*</span></label>
            <textarea id="ticket-description" name="description" placeholder="Ex. : le tapis s’arrête après quelques minutes d’utilisation…" required minlength="5" rows="5"></textarea>
            <small>Décrivez les symptômes observés pour aider le technicien à préparer son intervention.</small>
          </div>
          <div class="ticket-field">
            <label for="ticket-priorite">Niveau de priorité</label>
            <select id="ticket-priorite" name="priorite">
              <option value="Moyenne">Normale</option>
              <option value="Haute">Haute</option>
              <option value="Basse">Basse</option>
            </select>
          </div>
          <label class="ticket-out-of-service">
            <input type="checkbox" name="hors_service">
            <span><strong>Mettre l’équipement hors service</strong><small>À cocher si l’appareil présente un risque ou ne peut plus être utilisé.</small></span>
          </label>
        </div>
        <div class="ticket-form-actions">
          <span><span aria-hidden="true">*</span> Champ obligatoire</span>
          <button class="btn btn-vert" type="submit">Transmettre le ticket</button>
        </div>
      </form>
    <?php else: ?>
      <p class="vide"><?= $equipements
        ? 'Tous les équipements ont déjà un ticket en cours. Vous pourrez créer un nouveau ticket lorsqu’un appareil sera libéré.'
        : 'Aucun équipement n’est enregistré dans l’inventaire pour le moment.' ?></p>
    <?php endif; ?>
  </section>
  <p class="ticket-form-back"><a href="<?= e(url('employe/espace.php?onglet=' . ($accueil ? 'suivi-tickets' : 'materiel'))) ?>"><?= $accueil ? 'Consulter le suivi de mes tickets' : 'Consulter l’inventaire et le suivi des tickets' ?></a></p>
