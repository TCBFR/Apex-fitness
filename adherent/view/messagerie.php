<?php
require_once __DIR__ . '/../../config/function.php';
exige_role('ADHERENT');
require_once dirname(__DIR__) . '/includes/messagerie/data/conversations.php';
require_once dirname(__DIR__) . '/includes/messagerie/icons.php';
$activeId = $conversations[0]['id'];
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
    <link rel="stylesheet" href="<?= e(url('assets/css/pages/adherent-messagerie.css')) ?>">
</head>
<body>
<div class="app">
    <?php $activeNav = 'messages'; include dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <main class="main">
        <?php include dirname(__DIR__) . '/includes/topbar.php'; ?>
        <div class="page-title">
            <span class="page-eyebrow">SERVICE ADHÉRENTS</span>
            <h1 class="topbar__title">Messages</h1>
            <p class="topbar__sub">Une question ? Écrivez directement à l’équipe d’accueil ATHLETIX.</p>
        </div>

        <section class="messenger" id="messenger" data-testid="messenger">
            <div class="list-pane">
                <div class="list-heading">
                    <div><span>VOTRE ESPACE</span><h2>Conversations</h2></div>
                    <span class="support-chip"><i></i> Accueil</span>
                </div>
                <label class="search">
                    <?= icon('search') ?>
                    <input type="search" id="search" placeholder="Rechercher…" autocomplete="off" aria-label="Rechercher une conversation" data-testid="conversation-search-input">
                </label>
                <ul class="conv-list" id="conv-list" data-testid="conversation-list">
                    <?php foreach ($conversations as $conv): $isActive = $conv['id'] === $activeId; include dirname(__DIR__) . '/includes/messagerie/conversation-item.php'; endforeach; ?>
                </ul>
                <div class="empty" id="empty" hidden data-testid="empty-state">
                    <?= icon('inbox') ?>
                    <strong>Aucun résultat</strong>
                    <span>Essayez avec un autre nom de conversation.</span>
                </div>
            </div>

            <div class="chat-pane" data-testid="chat-pane">
                <?php foreach ($conversations as $conv): ?>
                    <div class="chat" data-thread="<?= e($conv['id']) ?>" <?= $conv['id'] === $activeId ? '' : 'hidden' ?> data-testid="chat-<?= e($conv['id']) ?>">
                        <header class="chat__head">
                            <button class="icon-btn chat__back" data-testid="chat-back-btn-<?= e($conv['id']) ?>" aria-label="Retour"><?= icon('back') ?></button>
                            <span class="avatar" style="--c: <?= $conv['color'] ?>"><?= e($conv['initials']) ?></span>
                            <div class="chat__who">
                                <strong data-testid="chat-name"><?= e($conv['name']) ?></strong>
                                <small>Service adhérents · <?= e($conv['roleLabel']) ?></small>
                            </div>
                        </header>
                        <div class="chat__thread" data-testid="chat-thread-<?= e($conv['id']) ?>">
                            <?php if (!$conv['messages']): ?>
                                <div class="thread-welcome">
                                    <span class="thread-welcome-icon"><?= icon('inbox') ?></span>
                                    <strong>Bienvenue dans votre conversation</strong>
                                    <p>Écrivez à l’équipe d’accueil. Votre échange restera disponible ici.</p>
                                </div>
                            <?php endif; ?>
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
                    <div class="composer-field">
                        <textarea id="composer-input" rows="1" placeholder="Écrire à l’accueil…" aria-label="Votre message à l’accueil" data-testid="composer-input"></textarea>
                        <small>Entrée pour envoyer · Maj + Entrée pour aller à la ligne</small>
                    </div>
                    <button type="submit" class="send-btn" aria-label="Envoyer" data-testid="composer-send-btn"><?= icon('send') ?></button>
                </form>
            </div>
        </section>
    </main>
</div>
<template id="tpl-ticks"><?= readTicks(false) ?></template>
<script src="<?= e(url('assets/js/adherent/messagerie.js')) ?>"></script>


</body>
</html>
