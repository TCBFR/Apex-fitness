document.addEventListener('DOMContentLoaded', () => {
  const rows = [...document.querySelectorAll('[data-inventory-row]')];
  const search = document.querySelector('[data-inventory-search]');
  const category = document.querySelector('[data-inventory-category]');
  const status = document.querySelector('[data-inventory-status]');
  const reset = document.querySelector('[data-inventory-reset]');
  const previous = document.querySelector('[data-inventory-prev]');
  const next = document.querySelector('[data-inventory-next]');
  const pages = document.querySelector('[data-inventory-pages]');
  const range = document.querySelector('[data-inventory-range]');
  const noResults = document.querySelector('[data-inventory-no-results]');
  const pageSize = 6;
  const selectedRow = rows.find(row => row.classList.contains('is-selected'));
  let currentPage = selectedRow ? Math.floor(rows.indexOf(selectedRow) / pageSize) + 1 : 1;

  const render = () => {
    const query = search.value.trim().toLocaleLowerCase('fr');
    const selectedCategory = category.value;
    const selectedStatus = status.value;
    const filteredRows = rows.filter(row =>
      (!selectedCategory || row.dataset.category === selectedCategory)
      && (!selectedStatus || row.dataset.status === selectedStatus)
      && row.dataset.search.includes(query)
    );
    const pageCount = Math.max(1, Math.ceil(filteredRows.length / pageSize));
    currentPage = Math.min(currentPage, pageCount);
    const firstIndex = (currentPage - 1) * pageSize;
    const pageRows = filteredRows.slice(firstIndex, firstIndex + pageSize);
    rows.forEach(row => row.hidden = !pageRows.includes(row));
    noResults.hidden = filteredRows.length !== 0;
    range.textContent = filteredRows.length
      ? `Affichage de ${firstIndex + 1} à ${Math.min(firstIndex + pageSize, filteredRows.length)} sur ${filteredRows.length} matériel${filteredRows.length === 1 ? '' : 's'}`
      : 'Aucun matériel à afficher';
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

  search.addEventListener('input', () => {
    currentPage = 1;
    render();
  });
  category.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  status.addEventListener('change', () => {
    currentPage = 1;
    render();
  });
  reset.addEventListener('click', () => {
    search.value = '';
    category.value = '';
    status.value = '';
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
