(() => {
      const rows = [...document.querySelectorAll('[data-reservation-row]')];
      const activityFilter = document.getElementById('filtre-reservation-activite');
      const statusFilter = document.getElementById('filtre-reservation-statut');
      const search = document.getElementById('recherche-reservation');
      const empty = document.getElementById('reservations-filtre-vide');
      const filterRows = () => {
        const activity = activityFilter.value;
        const status = statusFilter.value;
        const query = search.value.trim().toLocaleLowerCase('fr');
        let visibleCount = 0;
        rows.forEach(row => {
          const visible = (!activity || row.dataset.type === activity)
            && (!status || row.dataset.status === status)
            && row.dataset.search.includes(query);
          row.hidden = !visible;
          if (visible) visibleCount++;
        });
        empty.hidden = visibleCount > 0;
      };
      activityFilter.addEventListener('change', filterRows);
      statusFilter.addEventListener('change', filterRows);
      search.addEventListener('input', filterRows);

      const dialog = document.getElementById('reservation-dialogue');
      const reservationId = document.getElementById('dialog-reservation-id');
      const courseSelect = document.getElementById('dialog-reservation-course');
      document.querySelectorAll('[data-reservation-edit]').forEach(button => {
        button.addEventListener('click', () => {
          reservationId.value = button.dataset.id;
          courseSelect.value = '';
          courseSelect.querySelectorAll('option[data-complet]').forEach(option => {
            option.disabled = option.dataset.complet === '1';
          });
          const currentCourse = courseSelect.querySelector(`[value="${button.dataset.currentCourse}"]`);
          if (currentCourse) currentCourse.disabled = false;
          dialog.showModal();
        });
      });
      dialog.querySelector('.reservation-dialog-close').addEventListener('click', () => dialog.close());
    })();
