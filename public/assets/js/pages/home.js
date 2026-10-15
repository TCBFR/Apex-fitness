(function () {
  'use strict';

  window.addEventListener('load', function () {
    requestAnimationFrame(function () { document.body.classList.add('loaded'); });
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
