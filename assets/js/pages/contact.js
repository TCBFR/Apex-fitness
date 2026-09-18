document.querySelectorAll("[data-compteur]").forEach(function (champ) {
  var sortie = document.querySelector(champ.dataset.compteur);
  if (!sortie) return;
  var max = Number(champ.getAttribute("maxlength")) || 0;
  function actualiser() {
    sortie.textContent = max
      ? champ.value.length + " / " + max + " caractères"
      : champ.value.length + " caractères";
  }
  champ.addEventListener("input", actualiser);
  actualiser();
});
