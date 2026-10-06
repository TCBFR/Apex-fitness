/* =====================================================================
   Apex Fitness - comportements cote client.
   Le JS ne fait qu'ameliorer l'experience : toute action reste valable
   sans lui, les controles metier sont refaits en PHP.
   ===================================================================== */
(function () {
  "use strict";

  /* --- menu mobile --------------------------------------------------- */
  var burger = document.getElementById("burger");
  var menu = document.getElementById("menu");
  if (burger && menu) {
    burger.addEventListener("click", function () {
      var ouvert = menu.classList.toggle("ouvert");
      burger.setAttribute("aria-expanded", String(ouvert));
    });
  }

  /* --- filtrage instantane des tableaux ------------------------------
     Usage : <input data-filtre="#id-du-tableau">
             <select data-filtre="#id-du-tableau" data-colonne="4">
  -------------------------------------------------------------------- */
  function filtreTableau(cible) {
    var table = document.querySelector(cible);
    if (!table) return;

    var controles = document.querySelectorAll('[data-filtre="' + cible + '"]');
    var lignes = table.tBodies[0] ? Array.prototype.slice.call(table.tBodies[0].rows) : [];
    var messageVide = table.parentNode.querySelector("[data-vide]");

    function appliquer() {
      var visibles = 0;

      lignes.forEach(function (tr) {
        var garde = true;

        controles.forEach(function (ctrl) {
          var v = ctrl.value.trim().toLowerCase();
          if (!v) return;

          if (ctrl.dataset.colonne !== undefined) {
            var cell = tr.cells[Number(ctrl.dataset.colonne)];
            var txt = cell ? cell.textContent.trim().toLowerCase() : "";
            if (txt.indexOf(v) === -1) garde = false;
          } else if (tr.textContent.toLowerCase().indexOf(v) === -1) {
            garde = false;
          }
        });

        tr.hidden = !garde;
        if (garde) visibles++;
      });

      if (messageVide) messageVide.hidden = visibles !== 0;
    }

    controles.forEach(function (c) {
      c.addEventListener("input", appliquer);
      c.addEventListener("change", appliquer);
    });
  }

  var cibles = {};
  document.querySelectorAll("[data-filtre]").forEach(function (c) {
    cibles[c.dataset.filtre] = true;
  });
  Object.keys(cibles).forEach(filtreTableau);

  /* --- confirmation avant une action destructrice --------------------- */
  document.querySelectorAll("[data-confirme]").forEach(function (el) {
    el.addEventListener("click", function (ev) {
      if (!window.confirm(el.dataset.confirme)) ev.preventDefault();
    });
  });

})();
