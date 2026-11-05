<div class="field">
  <div class="input-wrap"><input type="email" name="mail" id="email" value="<?= e($donnees['mail']) ?>" placeholder="Adresse e-mail" autocomplete="email" maxlength="150" required></div>
  <p class="field-msg"></p>
</div>
<div class="field">
  <div class="input-wrap"><input type="text" name="adresse_postale" id="adresse_postale" value="<?= e($donnees['adresse_postale']) ?>" placeholder="Adresse postale" autocomplete="street-address" maxlength="255" required></div>
  <p class="field-msg"></p>
</div>
<div class="field">
  <div class="input-wrap"><input type="password" name="motdepasse" id="password" placeholder="Mot de passe (8 caractères minimum)" autocomplete="new-password" minlength="8" required></div>
  <p class="field-msg"></p>
</div>
<div class="field">
  <div class="input-wrap"><input type="password" name="confirmation" id="confirmation" placeholder="Confirmer le mot de passe" autocomplete="new-password" required></div>
  <p class="field-msg"></p>
</div>
<button type="submit" class="btn" id="signup-button" data-testid="sign-up-button">
  <span class="btn-label">Créer mon compte</span><span class="btn-spinner" aria-hidden="true"></span>
</button>
