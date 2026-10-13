(function () {
  'use strict';

  window.addEventListener('load', function () {
    requestAnimationFrame(function () {
      document.body.classList.add('loaded');
    });
  });

  var form = document.getElementById('signup-form');
  if (!form) return;

  var dateInput = document.getElementById('date_naissance');
  dateInput.addEventListener('input', function () {
    var chiffres = dateInput.value.replace(/\D/g, '').slice(0, 8);
    var dateFormatee = chiffres.slice(0, 2);
    if (chiffres.length > 2) dateFormatee += '-' + chiffres.slice(2, 4);
    if (chiffres.length > 4) dateFormatee += '-' + chiffres.slice(4, 8);
    dateInput.value = dateFormatee;
  });

  function dateNaissanceValide(valeur) {
    var morceaux = valeur.split('-');
    if (morceaux.length !== 3) return false;

    var jour = Number(morceaux[0]);
    var mois = Number(morceaux[1]);
    var annee = Number(morceaux[2]);
    var date = new Date(0);
    date.setFullYear(annee, mois - 1, jour);
    date.setHours(0, 0, 0, 0);

    return date.getFullYear() === annee
      && date.getMonth() === mois - 1
      && date.getDate() === jour;
  }

  form.addEventListener('submit', function (event) {
    var fields = Array.from(form.querySelectorAll('.field'));
    var valid = true;

    fields.forEach(function (field) {
      var input = field.querySelector('input');
      var message = field.querySelector('.field-msg');
      field.classList.remove('error');
      message.textContent = '';

      if (input.required && !input.value.trim()) {
        message.textContent = 'Ce champ est obligatoire.';
        field.classList.add('error');
        valid = false;
      } else if (input.type === 'email' && input.value && !input.validity.valid) {
        message.textContent = 'Saisissez une adresse e-mail valide.';
        field.classList.add('error');
        valid = false;
      } else if (input.id === 'date_naissance' && input.value
        && (!input.validity.valid || !dateNaissanceValide(input.value))) {
        message.textContent = 'Saisissez une date réelle au format JJ-MM-AAAA.';
        field.classList.add('error');
        valid = false;
      } else if (input.id === 'password' && input.value.length < 8) {
        message.textContent = 'Le mot de passe doit contenir au moins 8 caractères.';
        field.classList.add('error');
        valid = false;
      } else if (input.id === 'confirmation' && input.value !== document.getElementById('password').value) {
        message.textContent = 'Les mots de passe ne correspondent pas.';
        field.classList.add('error');
        valid = false;
      }
    });

    if (!valid) {
      event.preventDefault();
      return;
    }

    document.getElementById('signup-button').classList.add('loading');
  });
})();
