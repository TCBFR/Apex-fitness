/* Vue FAQ : accordéons de catégories et recherche instantanée. */
(function () {
  'use strict';
  const { $, $$, setOpen, initAccordions } = window.Athletix;
  initAccordions();
  const search = $('#faq-search');
  if (!search) return;
  const cats = $$('.faq-cat');
  const empty = $('#faq-empty');
  const normalize = (value) => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

  search.addEventListener('input', () => {
    const query = normalize(search.value.trim());
    let visibleCategories = 0;
    cats.forEach((category) => {
      const questions = $$('.faq-q', category);
      let visibleInCategory = 0;
      questions.forEach((question) => {
        const matches = !query || normalize(question.textContent).includes(query);
        question.hidden = !matches;
        if (matches) visibleInCategory++;
        if (!query) setOpen(question, false);
      });
      category.hidden = Boolean(query) && visibleInCategory === 0;
      const count = query ? visibleInCategory : questions.length;
      const countElement = $('.cat-count', category);
      if (countElement) countElement.textContent = String(count);
      setOpen(category, Boolean(query) && visibleInCategory > 0);
      if (visibleInCategory > 0) visibleCategories++;
    });
    if (empty) empty.hidden = visibleCategories > 0;
  });
})();
