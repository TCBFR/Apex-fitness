var champMail = document.getElementById("mail");
var champMotDePasse = document.getElementById("motdepasse");
document.querySelectorAll(".compte-demo").forEach(function (compte) {
  compte.addEventListener("click", function () {
    if (!champMail || !champMotDePasse) return;
    champMail.value = compte.dataset.identifiant;
    champMotDePasse.value = compte.dataset.motdepasse;
    champMail.focus();
  });
});
