(function () {
  'use strict';

  window.addEventListener('load', function () {
    requestAnimationFrame(function () {
      document.body.classList.add('loaded');
    });
  });

  var stage = document.getElementById('stage');
  if (stage && window.matchMedia('(pointer:fine)').matches) {
    stage.addEventListener('mousemove', function (event) {
      var bounds = stage.getBoundingClientRect();
      stage.style.setProperty('--mx', ((event.clientX - bounds.left) / bounds.width - 0.5).toFixed(3));
      stage.style.setProperty('--my', ((event.clientY - bounds.top) / bounds.height - 0.5).toFixed(3));
    });
    stage.addEventListener('mouseleave', function () {
      stage.style.setProperty('--mx', 0);
      stage.style.setProperty('--my', 0);
    });
  }

  var password = document.getElementById('password');
  var toggle = document.getElementById('toggle-password');
  if (password && toggle) {
    toggle.addEventListener('click', function () {
      var show = password.type === 'password';
      password.type = show ? 'text' : 'password';
      toggle.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
  }

  document.querySelectorAll('.demo-account').forEach(function (button) {
    button.addEventListener('click', function () {
      var email = document.getElementById('email');
      if (!email || !password) return;
      email.value = button.dataset.email || '';
      password.value = button.dataset.password || '';
      email.closest('.field').classList.remove('error');
      password.closest('.field').classList.remove('error');
      if (!password.value) {
        password.focus();
      }
    });
  });

  var form = document.getElementById('login-form');
  if (form) {
    form.addEventListener('submit', function (event) {
      var email = document.getElementById('email');
      var valid = true;
      [email, password].forEach(function (input) {
        var field = input.closest('.field');
        var message = field.querySelector('.field-msg');
        field.classList.remove('error');
        message.textContent = '';
        if (!input.value.trim()) {
          message.textContent = 'Ce champ est obligatoire.';
          field.classList.add('error');
          valid = false;
        }
      });
      if (email.value && !email.validity.valid) {
        email.closest('.field').querySelector('.field-msg').textContent = 'Entrez une adresse e-mail valide.';
        email.closest('.field').classList.add('error');
        valid = false;
      }
      if (!valid) {
        event.preventDefault();
        return;
      }
      document.getElementById('signin-btn').classList.add('loading');
    });
  }
})();
