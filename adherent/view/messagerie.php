<?php
require_once __DIR__ . '/../../config/function.php';
exige_role('ADHERENT');
require_once dirname(__DIR__) . '/includes/messagerie/data/conversations.php';
require_once dirname(__DIR__) . '/includes/messagerie/icons.php';
$activeId = $conversations[0]['id'];
$tabs = ['all' => 'Tous', 'coach' => 'Coach', 'accueil' => 'Accueil', 'admin' => 'Administration'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Messages — ATHLETIX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- <link rel="stylesheet" href="assets/css/style.css"> (CSS déjà inclus en bas de page) -->
</head>
<body>
<div class="app">
    <?php $activeNav = 'messages'; include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include dirname(__DIR__) . '/includes/topbar.php'; ?>
        <div class="page-title">
            <h1 class="topbar__title">Messages</h1>
            <p class="topbar__sub">Échangez avec vos coachs et l'équipe Athletix</p>
        </div>

        <section class="messenger" id="messenger" data-testid="messenger">
            <div class="list-pane">
                <label class="search">
                    <?= icon('search') ?>
                    <input type="search" id="search" placeholder="Rechercher une conversation…" autocomplete="off" data-testid="conversation-search-input">
                </label>
                <div class="tabs" role="tablist">
                    <?php foreach ($tabs as $key => $label): ?>
                        <button class="tab<?= $key === 'all' ? ' is-active' : '' ?>" data-filter="<?= $key ?>" role="tab" data-testid="tab-<?= $key ?>"><?= $label ?></button>
                    <?php endforeach; ?>
                </div>
                <ul class="conv-list" id="conv-list" data-testid="conversation-list">
                    <?php foreach ($conversations as $conv): $isActive = $conv['id'] === $activeId; include dirname(__DIR__) . '/includes/messagerie/conversation-item.php'; endforeach; ?>
                </ul>
                <div class="empty" id="empty" hidden data-testid="empty-state">
                    <?= icon('inbox') ?>
                    <strong>Aucune conversation</strong>
                    <span>Essayez un autre filtre ou une autre recherche.</span>
                </div>
            </div>

            <div class="chat-pane" data-testid="chat-pane">
                <?php foreach ($conversations as $conv): ?>
                    <div class="chat" data-thread="<?= e($conv['id']) ?>" <?= $conv['id'] === $activeId ? '' : 'hidden' ?> data-testid="chat-<?= e($conv['id']) ?>">
                        <header class="chat__head">
                            <button class="icon-btn chat__back" data-testid="chat-back-btn-<?= e($conv['id']) ?>" aria-label="Retour"><?= icon('back') ?></button>
                            <span class="avatar" style="--c: <?= $conv['color'] ?>"><?= e($conv['initials']) ?><?php if ($conv['online']): ?><i class="avatar__online"></i><?php endif; ?></span>
                            <div class="chat__who">
                                <strong data-testid="chat-name"><?= e($conv['name']) ?></strong>
                                <small class="<?= $conv['online'] ? 'is-online' : '' ?>"><?= $conv['online'] ? 'En ligne' : 'Hors ligne' ?> · <?= e($conv['roleLabel']) ?></small>
                            </div>
                            <button class="icon-btn" aria-label="Options" data-testid="chat-options-btn-<?= e($conv['id']) ?>"><?= icon('more') ?></button>
                        </header>
                        <div class="chat__thread" data-testid="chat-thread-<?= e($conv['id']) ?>">
                            <?php foreach ($conv['messages'] as $msg): ?>
                                <?php if (isset($msg['date'])): ?>
                                    <div class="date-sep"><span><?= e($msg['date']) ?></span></div>
                                <?php else: $isMe = ($msg['from'] ?? 'them') === 'me'; ?>
                                    <div class="msg <?= $isMe ? 'msg--me' : 'msg--them' ?>">
                                        <div class="bubble">
                                            <p><?= e($msg['text'] ?? '') ?></p>
                                            <span class="bubble__meta"><?= e($msg['time'] ?? '') ?><?= $isMe ? readTicks(!empty($msg['read'])) : '' ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <form class="composer" id="composer" data-testid="composer-form" autocomplete="off">
                    <button type="button" class="icon-btn" aria-label="Emoji" data-testid="emoji-btn"><?= icon('smile') ?></button>
                    <textarea id="composer-input" rows="1" placeholder="Écrire un message…" data-testid="composer-input"></textarea>
                    <button type="submit" class="send-btn" aria-label="Envoyer" data-testid="composer-send-btn"><?= icon('send') ?></button>
                </form>
            </div>
        </section>
    </main>
</div>
<template id="tpl-ticks"><?= readTicks(false) ?></template>
<script>
    (() => {
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];

    const list = $('#conv-list');
    const empty = $('#empty');
    const search = $('#search');
    const messenger = $('#messenger');
    const input = $('#composer-input');
    let filter = 'all';
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
            const ok = (filter === 'all' || c.dataset.role === filter) && c.dataset.name.includes(q);
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

    $$('.tab').forEach(tab => tab.addEventListener('click', () => {
        $$('.tab').forEach(t => t.classList.toggle('is-active', t === tab));
        filter = tab.dataset.filter;
        applyFilters();
    }));
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

</script>

<style>
    :root {
    --bg: #0a0d0a;
    --surface: #111411;
    --surface-2: #171b17;
    --surface-3: #1e231e;
    --border: #232923;
    --text: #f2f5f0;
    --muted: #8a938a;
    --lime: #c6f24e;
    --lime-soft: rgba(198, 242, 78, 0.12);
    --lime-line: rgba(198, 242, 78, 0.35);
    --bubble-in: #1f241f;
    --online: #3ddc6b;
    --radius: 18px;
    --sidebar-w: 250px;
    --ease: cubic-bezier(.2, .8, .2, 1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
    -webkit-font-smoothing: antialiased;
    overflow: hidden;
}
::selection { background: var(--lime); color: #0a0d0a; }
button, input, textarea { font: inherit; color: inherit; }
button { background: none; border: 0; cursor: pointer; }
a { color: inherit; text-decoration: none; }
[hidden] { display: none !important; }

.icon { width: 20px; height: 20px; flex-shrink: 0; }
.icon-btn {
    width: 40px; height: 40px; display: grid; place-items: center;
    border-radius: 12px; color: var(--muted);
    transition: background-color .2s, color .2s, transform .15s;
}
.icon-btn:hover { background: var(--surface-3); color: var(--text); }
.icon-btn:active { transform: scale(.94); }

/* Layout */
.app { display: flex; height: 100vh; height: 100dvh; }

.ico { flex-shrink: 0; }

/* Avatar */
.avatar {
    --c: var(--lime);
    position: relative; flex-shrink: 0;
    width: 46px; height: 46px; border-radius: 50%;
    display: grid; place-items: center;
    font-weight: 700; font-size: 14px; letter-spacing: .02em;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 18%, #0f120f);
    border: 1px solid color-mix(in srgb, var(--c) 35%, transparent);
}
.avatar--sm { width: 36px; height: 36px; font-size: 12px; }
.avatar__online {
    position: absolute; right: 0; bottom: 0;
    width: 12px; height: 12px; border-radius: 50%;
    background: var(--online); border: 2px solid var(--surface);
}

/* Main */
.main { flex: 1; min-width: 0; margin-left: var(--sidebar-w); display: flex; flex-direction: column; padding: 28px 32px 28px; gap: 22px; }
.topbar__title { font-size: 30px; font-weight: 800; letter-spacing: -.02em; }
.topbar__sub { color: var(--muted); margin-top: 4px; }

.messenger {
    flex: 1; min-height: 0;
    display: grid; grid-template-columns: 360px 1fr;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 22px; overflow: hidden;
}

/* List pane */
.list-pane { display: flex; flex-direction: column; min-height: 0; border-right: 1px solid var(--border); padding: 20px 14px 0; }
.search {
    display: flex; align-items: center; gap: 10px;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 14px; padding: 0 14px; margin: 0 6px; color: var(--muted);
    transition: border-color .2s;
}
.search:focus-within { border-color: var(--lime-line); }
.search .icon { width: 18px; height: 18px; }
.search input { flex: 1; min-width: 0; background: none; border: 0; outline: 0; height: 44px; color: var(--text); }
.search input::placeholder { color: var(--muted); }
.search input::-webkit-search-cancel-button { filter: invert(1) opacity(.5); }

.tabs { display: flex; gap: 6px; margin: 16px 6px 12px; overflow-x: auto; scrollbar-width: none; }
.tabs::-webkit-scrollbar { display: none; }
.tab {
    padding: 8px 14px; border-radius: 999px; white-space: nowrap;
    color: var(--muted); font-weight: 600; font-size: 13px;
    background: var(--surface-2); border: 1px solid var(--border);
    transition: background-color .2s, color .2s, border-color .2s;
}
.tab:hover { color: var(--text); }
.tab.is-active { background: var(--lime); color: #0a0d0a; border-color: var(--lime); }

.conv-list { list-style: none; overflow-y: auto; flex: 1; padding: 0 0 16px; }
.conv {
    display: flex; align-items: center; gap: 12px;
    padding: 12px; margin: 2px 0; border-radius: 14px; cursor: pointer;
    border: 1px solid transparent;
    transition: background-color .2s, border-color .2s;
    animation: fadeUp .35s var(--ease) both;
}
.conv:hover { background: var(--surface-2); }
.conv:focus-visible { outline: 2px solid var(--lime-line); outline-offset: -2px; }
.conv.is-active { background: var(--lime-soft); border-color: var(--lime-line); }
.conv.is-active .conv__name { color: var(--lime); }
.conv .avatar__online { border-color: var(--surface); }
.conv__body { flex: 1; min-width: 0; }
.conv__top, .conv__bottom { display: flex; align-items: center; gap: 8px; }
.conv__bottom { margin-top: 4px; }
.conv__name { flex: 1; min-width: 0; font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .2s; }
.conv__time { color: var(--muted); font-size: 12px; flex-shrink: 0; }
.conv__preview { flex: 1; min-width: 0; color: var(--muted); font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv__you { color: var(--text); opacity: .7; }
.badge {
    flex-shrink: 0; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px;
    background: var(--lime); color: #0a0d0a; font-size: 11px; font-weight: 700;
    display: grid; place-items: center;
}

.empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; color: var(--muted); text-align: center; padding: 40px 20px; }
.empty .icon { width: 36px; height: 36px; color: var(--lime); opacity: .7; margin-bottom: 6px; }
.empty strong { color: var(--text); font-size: 15px; }

/* Chat pane */
.chat-pane { display: flex; flex-direction: column; min-height: 0; min-width: 0; background: var(--bg); }
.chat { flex: 1; min-height: 0; display: flex; flex-direction: column; }
.chat__head {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 22px; border-bottom: 1px solid var(--border); background: var(--surface);
}
.chat__head .avatar__online { border-color: var(--surface); }
.chat__back { display: none; }
.chat__who { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
.chat__who strong { font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chat__who small { color: var(--muted); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.chat__who small.is-online { color: var(--online); }

.chat__thread { flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 10px; }
.chat:not([hidden]) .chat__thread > * { animation: fadeUp .3s var(--ease) both; }

.date-sep { display: flex; align-items: center; gap: 14px; margin: 14px 0 6px; color: var(--muted); font-size: 12px; font-weight: 500; }
.date-sep::before, .date-sep::after { content: ''; flex: 1; height: 1px; background: var(--border); }
.date-sep span { background: var(--surface-2); border: 1px solid var(--border); padding: 4px 12px; border-radius: 999px; }

.msg { display: flex; }
.msg--me { justify-content: flex-end; }
.bubble {
    max-width: min(68%, 520px);
    padding: 10px 14px 8px; border-radius: var(--radius);
    overflow-wrap: anywhere; word-break: break-word;
    line-height: 1.5;
}
.msg--them .bubble { background: var(--bubble-in); border-bottom-left-radius: 6px; color: var(--text); }
.msg--me .bubble { background: var(--lime); color: #0a0d0a; border-bottom-right-radius: 6px; }
.bubble p { white-space: pre-wrap; }
.bubble__meta { display: flex; align-items: center; justify-content: flex-end; gap: 4px; font-size: 11px; margin-top: 4px; opacity: .6; }
.msg--me .bubble__meta { opacity: .75; }
.ticks { width: 18px; height: 12px; }
.ticks.is-read { color: #1f6b2d; }
.msg.is-new .bubble { animation: pop .35s var(--ease); }

.composer {
    display: flex; align-items: flex-end; gap: 10px;
    padding: 14px 18px; border-top: 1px solid var(--border); background: var(--surface);
}
.composer textarea {
    flex: 1; min-width: 0; resize: none; outline: 0;
    background: var(--surface-2); border: 1px solid var(--border); border-radius: 14px;
    padding: 11px 14px; line-height: 1.4; max-height: 140px; color: var(--text);
    transition: border-color .2s;
}
.composer textarea:focus { border-color: var(--lime-line); }
.composer textarea::placeholder { color: var(--muted); }
.send-btn {
    width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
    background: var(--lime); color: #0a0d0a; display: grid; place-items: center;
    transition: transform .15s, box-shadow .2s;
}
.send-btn:hover { box-shadow: 0 0 0 4px var(--lime-soft); }
.send-btn:active { transform: scale(.92); }

/* Scrollbars */
.conv-list, .chat__thread, .sidebar__nav { scrollbar-width: thin; scrollbar-color: var(--surface-3) transparent; }
.conv-list::-webkit-scrollbar, .chat__thread::-webkit-scrollbar { width: 6px; }
.conv-list::-webkit-scrollbar-thumb, .chat__thread::-webkit-scrollbar-thumb { background: var(--surface-3); border-radius: 3px; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
@keyframes pop { from { opacity: 0; transform: translateY(8px) scale(.96); } to { opacity: 1; transform: none; } }

/* Tablet */
@media (max-width: 1180px) {
    .messenger { grid-template-columns: 300px 1fr; }
    .main { padding: 22px; }
}

@media (max-width: 860px) {
    .main { margin-left: 0; padding: 14px; gap: 14px; }
    .topbar__title { font-size: 24px; }
    .topbar__sub { display: none; }
    .messenger { grid-template-columns: 1fr; border-radius: 18px; }
    .list-pane { border-right: 0; }
    .chat-pane { display: none; }
    .messenger.show-chat .list-pane { display: none; }
    .messenger.show-chat .chat-pane { display: flex; }
    .chat__back { display: grid; margin-left: -8px; }
    .chat__head { padding: 12px 14px; }
    .chat__thread { padding: 18px 14px; }
    .bubble { max-width: 82%; }
    .composer { padding: 10px 12px; }
}

</style>
</body>
</html>
