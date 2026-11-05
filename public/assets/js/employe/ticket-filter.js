document.addEventListener('DOMContentLoaded', () => {
  const rows = [...document.querySelectorAll('[data-ticket-row]')];
  const search = document.querySelector('[data-ticket-search]');
  const priority = document.querySelector('[data-ticket-priority]');
  const category = document.querySelector('[data-ticket-category]');
  const tabs = [...document.querySelectorAll('[data-status-filter]')];
  const previous = document.querySelector('[data-ticket-prev]');
  const next = document.querySelector('[data-ticket-next]');
  const pages = document.querySelector('[data-ticket-pages]');
  const range = document.querySelector('[data-ticket-range]');
  const noResults = document.querySelector('[data-ticket-no-results]');
  const pageSize = 6;
  let activeStatus = 'all';
  let currentPage = 1;

  const render = () => {
    const query = search.value.trim().toLocaleLowerCase('fr');
    const selectedPriority = priority.value;
    const selectedCategory = category.value;
    const filteredRows = rows.filter(row => {
      const statusMatches = activeStatus === 'all' || row.dataset.statusGroup === activeStatus;
      const priorityMatches = !selectedPriority || row.dataset.priority === selectedPriority;
      const categoryMatches = !selectedCategory || row.dataset.category === selectedCategory;
      return statusMatches && priorityMatches && categoryMatches && row.dataset.search.includes(query);
    });
    const pageCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
    currentPage = Math.min(currentPage, pageCount);
    const firstIndex = (currentPage - 1) * pageSize;
    const pageRows = filteredRows.slice(firstIndex, firstIndex + pageSize);
    rows.forEach(row => row.hidden = !pageRows.includes(row));
    noResults.hidden = filteredRows.length !== 0;
    range.textContent = filteredRows.length
      ? `Affichage de ${firstIndex + 1} à ${Math.min(firstIndex + pageSize, filteredRows.length)} sur ${filteredRows.length} ticket${filteredRows.length === 1 ? '' : 's'}`
      : 'Aucun ticket à afficher';
    previous.disabled = currentPage <= 1;
    next.disabled = currentPage >= pageCount;
    pages.replaceChildren();
    for (let pageNumber = 1; pageNumber <= pageCount; pageNumber++) {
      const pageButton = document.createElement('button');
      pageButton.type = 'button';
      pageButton.textContent = String(pageNumber);
      pageButton.setAttribute('aria-label', `Page ${pageNumber}`);
      if (pageNumber === currentPage) {
        pageButton.className = 'is-current';
        pageButton.setAttribute('aria-current', 'page');
      }
      pageButton.addEventListener('click', () => {
        currentPage = pageNumber;
        render();
      });
      pages.append(pageButton);
    }
  };

  tabs.forEach(tab => tab.addEventListener('click', () => {
    activeStatus = tab.dataset.statusFilter;
    currentPage = 1;
    tabs.forEach(item => {
      const active = item === tab;
      item.classList.toggle('is-active', active);
      item.setAttribute('aria-pressed', String(active));
    });
    render();
  }));
  search.addEventListener('input', () => {
    currentPage = 1;
    render();
  });
  priority.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  category.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  previous.addEventListener('click', () => {
    currentPage--;
    render();
  });
  next.addEventListener('click', () => {
    currentPage++;
    render();
  });

  render();
});
