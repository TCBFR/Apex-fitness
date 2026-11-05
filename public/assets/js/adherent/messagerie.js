(() => {
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];

    const list = $('#conv-list');
    const empty = $('#empty');
    const search = $('#search');
    const messenger = $('#messenger');
    const input = $('#composer-input');
    let activeId = $('.conv.is-active')?.dataset.id;

    const activeThread = () => $(`.chat[data-thread="${activeId}"] .chat__thread`);
    const scrollBottom = () => { const t = activeThread(); if (t) t.scrollTop = t.scrollHeight; };

    function openConversation(id) {
        activeId = id;
        $$('.conv').forEach(c => c.classList.toggle('is-active', c.dataset.id === id));
        $$('.chat').forEach(c => { c.hidden = c.dataset.thread !== id; });
        $(`.conv[data-id="${id}"] .badge`)?.remove();
        messenger.classList.add('show-chat');
        scrollBottom();
        if (window.matchMedia('(min-width: 861px)').matches) input.focus();
    }

    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        let visible = 0;
        $$('.conv').forEach(c => {
            const ok = c.dataset.name.includes(q);
            c.hidden = !ok;
            if (ok) visible++;
        });
        empty.hidden = visible > 0;
    }

    list.addEventListener('click', e => {
        const item = e.target.closest('.conv');
        if (item) openConversation(item.dataset.id);
    });
    list.addEventListener('keydown', e => {
        const item = e.target.closest('.conv');
        if (item && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openConversation(item.dataset.id); }
    });

    search.addEventListener('input', applyFilters);

    $$('.chat__back').forEach(b => b.addEventListener('click', () => messenger.classList.remove('show-chat')));

    function nowTime() {
        const d = new Date();
        return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text || !activeId) return;
        const conversation = $(`.conv[data-id="${activeId}"]`);
        const recipient = Number(conversation?.dataset.recipient || 0);
        try {
            await window.ADH_API('messages', recipient ? {message:text, recipient} : {message:text});
        } catch (error) {
            alert(error.message);
            return;
        }
        const thread = activeThread();
        const msg = document.createElement('div');
        msg.className = 'msg msg--me is-new';
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        const p = document.createElement('p');
        p.textContent = text;
        const meta = document.createElement('span');
        meta.className = 'bubble__meta';
        meta.textContent = nowTime();
        meta.appendChild($('#tpl-ticks').content.cloneNode(true));
        bubble.append(p, meta);
        msg.appendChild(bubble);
        thread.appendChild(msg);

        const item = $(`.conv[data-id="${activeId}"]`);
        const preview = $('.conv__preview', item);
        preview.innerHTML = '<span class="conv__you">Vous : </span>';
        preview.appendChild(document.createTextNode(text));
        $('.conv__time', item).textContent = nowTime();
        list.prepend(item);

        input.value = '';
        autoGrow();
        scrollBottom();
    }

    function autoGrow() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    }

    $('#composer').addEventListener('submit', e => { e.preventDefault(); sendMessage(); });
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    input.addEventListener('input', autoGrow);

    $('[data-testid="emoji-btn"]').addEventListener('click', () => {
        input.value += '💪';
        input.focus();
        autoGrow();
    });

    scrollBottom();
})();
