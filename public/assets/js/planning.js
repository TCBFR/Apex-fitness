(() => {
  const calendarElement = document.getElementById('planning');
  const detailElement = document.getElementById('planning-detail');

  if (!calendarElement || !window.FullCalendar) return;

  const endpoint = new URL(calendarElement.dataset.source, window.location.href);
  const colors = {
    cardio: '#ff4d5e',
    musculation: '#4f7dff',
    yoga: '#a266ff',
    boxe: '#ff9f1c',
    danse: '#d45bff',
  };

  const calendar = new FullCalendar.Calendar(calendarElement, {
    locale: 'fr',
    initialView: 'timeGridWeek',
    firstDay: 1,
    allDaySlot: false,
    height: 'auto',
    slotMinTime: '07:00:00',
    slotMaxTime: '22:00:00',
    events: async (range, success, failure) => {
      const url = new URL(endpoint);
      url.searchParams.set('start', range.startStr);
      url.searchParams.set('end', range.endStr);

      try {
        const response = await fetch(url);
        if (!response.ok) throw new Error('Le planning est indisponible.');
        success(await response.json());
      } catch (error) {
        failure(error);
      }
    },
    eventDidMount: ({ event, el }) => {
      el.style.setProperty('--event-color', colors[event.extendedProps.type] || '#4f7dff');
    },
    eventClick: ({ event }) => showDetails(event),
  });

  function showDetails(event) {
    if (!detailElement) return;

    const props = event.extendedProps;
    const rows = [
      ['Activité', event.title],
      ['Type', props.typeLibelle],
      ['Coach', props.coach],
      ['Salle', props.salle],
      ['Places', `${props.prises} / ${props.capacite}`],
    ];

    detailElement.replaceChildren();
    const title = document.createElement('h3');
    title.textContent = 'Détail de la séance';
    detailElement.append(title);

    const list = document.createElement('dl');
    rows.forEach(([label, value]) => {
      if (!value) return;
      const term = document.createElement('dt');
      const description = document.createElement('dd');
      term.textContent = label;
      description.textContent = value;
      list.append(term, description);
    });
    detailElement.append(list);
    detailElement.hidden = false;
  }

  calendar.render();
})();
