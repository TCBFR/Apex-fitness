(() => {
  'use strict';

  const menu = document.querySelector('[data-notification-menu]');
  if (!menu) return;

  const toggle = menu.querySelector('[data-notification-toggle]');
  const popover = menu.querySelector('[data-notification-popover]');
  const list = menu.querySelector('[data-notification-list]');
  const badge = menu.querySelector('[data-notification-count]');
  const headingCount = menu.querySelector('[data-notification-heading-count]');
  let loading = false;

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  })[char]);

  function updateCount(count) {
    const value = Math.max(0, Number(count) || 0);
    badge.textContent = value > 99 ? '99+' : String(value);
    badge.hidden = value === 0;
    headingCount.textContent = value ? `${value} non lue${value > 1 ? 's' : ''}` : 'Tout est lu';
  }

  function dateLabel(value) {
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return new Intl.DateTimeFormat('fr-FR', {
      day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
    }).format(date);
  }

  async function request(url, options = {}) {
    const response = await fetch(url, { credentials: 'same-origin', ...options });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Les notifications sont indisponibles.');
    return result;
  }

  function render(items) {
    if (!items.length) {
      list.innerHTML = '<p class="tb-notification-empty">Aucune notification pour le moment.</p>';
      return;
    }

    list.innerHTML = items.map((item) => {
      const unread = !item.read_at;
      return `<article class="tb-notification-item${unread ? ' is-unread' : ''}" data-notification-id="${Number(item.id)}">
        <div class="tb-notification-copy">
          <strong>${escapeHtml(item.title)}</strong>
          <p>${escapeHtml(item.message)}</p>
          <time datetime="${escapeHtml(item.created_at)}">${escapeHtml(dateLabel(item.created_at))}</time>
        </div>
        ${unread ? `<button type="button" class="tb-notification-read" data-mark-read="${Number(item.id)}">Marquer comme lue</button>` : '<span class="tb-notification-read-label">Lue</span>'}
      </article>`;
    }).join('');
  }

  async function loadNotifications() {
    if (loading) return;
    loading = true;
    list.innerHTML = '<p class="tb-notification-empty">Chargement…</p>';
    try {
      const result = await request(menu.dataset.endpoint);
      updateCount(result.unread_count);
      render(result.items || []);
    } catch (error) {
      list.innerHTML = `<p class="tb-notification-empty">${escapeHtml(error.message)}</p>`;
    } finally {
      loading = false;
    }
  }

  function setOpen(open) {
    popover.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    if (open) {
      positionPopover();
      loadNotifications();
    }
  }

  function positionPopover() {
    const bell = toggle.getBoundingClientRect();
    const sidebar = document.querySelector('#sidebar');
    const sidebarRight = sidebar ? sidebar.getBoundingClientRect().right : 0;
    const minLeft = Math.max(16, sidebarRight + 12);
    const width = Math.max(0, Math.min(420, window.innerWidth - minLeft - 16));
    const left = Math.min(
      Math.max(bell.right - width, minLeft),
      window.innerWidth - width - 16,
    );

    popover.style.left = `${left}px`;
    popover.style.width = `${width}px`;
    popover.style.top = `${Math.min(bell.bottom + 12, window.innerHeight - 120)}px`;
  }

  toggle.addEventListener('click', () => setOpen(popover.hidden));
  document.addEventListener('click', (event) => {
    if (!menu.contains(event.target)) setOpen(false);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOpen(false);
  });
  window.addEventListener('resize', () => {
    if (!popover.hidden) positionPopover();
  });

  list.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-mark-read]');
    if (!button) return;
    button.disabled = true;
    try {
      const result = await request(menu.dataset.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: button.dataset.markRead, csrf: menu.dataset.csrf }),
      });
      updateCount(result.unread_count);
      await loadNotifications();
    } catch (error) {
      button.disabled = false;
      button.title = error.message;
    }
  });
})();
