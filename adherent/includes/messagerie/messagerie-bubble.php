<?php
/**
 * Affiche un élément d'une conversation : séparateur de date ou bulle de message.
 * Variable attendue : $msg (tableau issu de conversations.php)
 *   - ['date' => 'Hier']                                            → séparateur de date
 *   - ['from' => 'me'|'them', 'text' => '…', 'time' => '10:42', 'read' => bool] → bulle
 */
if (isset($msg['date'])): ?>
    <div class="date-sep"><span><?= e($msg['date']) ?></span></div>
<?php else:
    $isMe = ($msg['from'] ?? 'them') === 'me';
    $isRead = !empty($msg['read']);
    ?>
    <div class="msg <?= $isMe ? 'msg--me' : 'msg--them' ?>">
        <div class="bubble">
            <p><?= e($msg['text'] ?? '') ?></p>
            <span class="bubble__meta">
                <?= e($msg['time'] ?? '') ?>
                <?php if ($isMe): ?>
                    <?php if (function_exists('readTicks')): ?>
                        <?= readTicks($isRead) ?>
                    <?php else: ?>
                        <svg class="ticks<?= $isRead ? ' is-read' : '' ?>" viewBox="0 0 18 12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m1 6.5 3.5 3.5L11 2.5M7 10l.5.5L14 2.5"/></svg>
                    <?php endif; ?>
                <?php endif; ?>
            </span>
        </div>
    </div>
<?php endif; ?>