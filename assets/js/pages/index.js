// Comportements de la page d'accueil.
document.querySelectorAll('a[href="#planning"]').forEach(function (link) {
  link.addEventListener("click", function (event) {
    var cible = document.getElementById("planning");
    if (!cible) return;
    event.preventDefault();
    cible.scrollIntoView({ behavior: "smooth" });
  });
});
