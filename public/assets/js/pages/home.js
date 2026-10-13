(function () {
  'use strict';

  window.addEventListener('load', function () {
    requestAnimationFrame(function () { document.body.classList.add('loaded'); });
  });

  var nav = document.getElementById('nav');
  var toggle = document.getElementById('nav-toggle');
  var links = document.getElementById('nav-links');

  function updateNav() {
    nav.classList.toggle('scrolled', window.scrollY > 30);
  }

  function closeMenu() {
    links.classList.remove('open');
    toggle.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Ouvrir le menu');
  }

  window.addEventListener('scroll', updateNav, { passive: true });
  updateNav();

  toggle.addEventListener('click', function () {
    var ouvert = links.classList.toggle('open');
    toggle.classList.toggle('open', ouvert);
    toggle.setAttribute('aria-expanded', String(ouvert));
    toggle.setAttribute('aria-label', ouvert ? 'Fermer le menu' : 'Ouvrir le menu');
  });

  links.addEventListener('click', function (event) {
    if (event.target.closest('a')) closeMenu();
  });

  var elements = document.querySelectorAll('[data-reveal]');
  if (!('IntersectionObserver' in window)) {
    elements.forEach(function (element) { element.classList.add('in'); });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('in');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.15 });

  elements.forEach(function (element) { observer.observe(element); });
})();
