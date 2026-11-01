(function () {
  'use strict';

  window.addEventListener('load', function () {
    requestAnimationFrame(function () { document.body.classList.add('loaded'); });
  });

  var carousel = document.querySelector('[data-testimonial-carousel]');
  if (carousel) {
    var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-testimonial-slide]'));
    var dots = Array.prototype.slice.call(carousel.querySelectorAll('[data-testimonial-dot]'));
    var active = 0;
    function showSlide(index) {
      active = (index + slides.length) % slides.length;
      slides.forEach(function (slide, i) {
        var selected = i === active;
        slide.hidden = !selected;
        slide.classList.toggle('is-active', selected);
      });
      dots.forEach(function (dot, i) {
        if (i === active) dot.setAttribute('aria-current', 'true');
        else dot.removeAttribute('aria-current');
      });
    }
    carousel.querySelector('[data-testimonial-prev]').addEventListener('click', function () { showSlide(active - 1); });
    carousel.querySelector('[data-testimonial-next]').addEventListener('click', function () { showSlide(active + 1); });
    dots.forEach(function (dot, i) { dot.addEventListener('click', function () { showSlide(i); }); });
  }

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
