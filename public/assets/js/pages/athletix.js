/* Utilitaires partagés des vues publiques ATHLETIX. */
(function () {
  'use strict';

  const $ = (selector, context = document) => context.querySelector(selector);
  const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));
  let toastTimer;

  function toast(message) {
    const element = $('#toast');
    if (!element) return;
    element.textContent = message;
    element.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => element.classList.remove('is-visible'), 3500);
  }

  function setOpen(item, open) {
    item.classList.toggle('is-open', open);
    const trigger = item.querySelector(':scope > .acc-trigger');
    if (trigger) trigger.setAttribute('aria-expanded', String(open));
  }

  function initAccordions(root = document) {
    $$('[data-accordion]', root).forEach((accordion) => {
      const single = accordion.hasAttribute('data-single');
      const items = $$(':scope > .acc-item', accordion);
      items.forEach((item) => {
        const trigger = item.querySelector(':scope > .acc-trigger');
        if (!trigger) return;
        trigger.addEventListener('click', () => {
          const willOpen = !item.classList.contains('is-open');
          if (single) items.forEach((other) => other !== item && setOpen(other, false));
          setOpen(item, willOpen);
        });
      });
    });
  }

  function initFilter(name) {
    const group = $(`[data-filter-group="${name}"]`);
    const target = $(`[data-filter-target="${name}"]`);
    if (!group || !target) return;
    const items = $$('[data-category]', target);
    const empty = target.parentElement.querySelector('.empty-state');

    group.addEventListener('click', (event) => {
      const chip = event.target.closest('.chip');
      if (!chip) return;
      $$('.chip', group).forEach((item) => item.classList.toggle('is-active', item === chip));
      const filter = chip.dataset.filter;
      let visible = 0;
      items.forEach((item) => {
        const show = filter === 'all' || item.dataset.category === filter;
        item.classList.toggle('is-filtered-out', !show);
        item.classList.remove('is-filtered-in');
        if (show) {
          visible++;
          void item.offsetWidth;
          item.classList.add('is-filtered-in', 'is-visible');
        }
      });
      if (empty) empty.hidden = visible > 0;
    });
  }

  function initMenu() {
    const button = $('#menu-toggle');
    if (!button) return;
    const close = () => {
      document.body.classList.remove('menu-open');
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-label', 'Ouvrir le menu');
    };
    button.addEventListener('click', () => {
      const open = document.body.classList.toggle('menu-open');
      button.setAttribute('aria-expanded', String(open));
      button.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
    });
    $$('#main-nav a').forEach((link) => link.addEventListener('click', close));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
  }

  function initReveal() {
    const reveals = $$('.reveal');
    if (!('IntersectionObserver' in window)) {
      reveals.forEach((element) => element.classList.add('is-visible'));
      return;
    }
    const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    }), { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach((element) => observer.observe(element));
  }

  initReveal();
  window.Athletix = { $, $$, toast, setOpen, initAccordions, initFilter };
})();
