<?php
/** @var array $conv @var bool $isActive */
$last = lastMessage($conv);
?>
<li class="conv<?= $isActive ? ' is-active' : '' ?>" data-id="<?= e($conv['id']) ?>" data-recipient="<?= (int)$conv['recipient'] ?>" data-role="<?= e($conv['role']) ?>" data-name="<?= e(mb_strtolower($conv['name'])) ?>" data-testid="conversation-item-<?= e($conv['id']) ?>" tabindex="0" role="button">
    <span class="avatar" style="--c: <?= $conv['color'] ?>">
        <?= e($conv['initials']) ?>
        <?php if ($conv['online']): ?><i class="avatar__online"></i><?php endif; ?>
    </span>
    <div class="conv__body">
        <div class="conv__top">
            <strong class="conv__name"><?= e($conv['name']) ?></strong>
            <time class="conv__time"><?= e($conv['time']) ?></time>
        </div>
        <div class="conv__bottom">
            <p class="conv__preview" data-testid="conversation-preview-<?= e($conv['id']) ?>"><?= $last['from'] === 'me' ? '<span class="conv__you">Vous : </span>' : '' ?><?= e($last['text']) ?></p>
            <?php if ($conv['unread'] > 0): ?><span class="badge" data-testid="unread-badge-<?= e($conv['id']) ?>"><?= $conv['unread'] ?></span><?php endif; ?>
        </div>
    </div>
</li>
