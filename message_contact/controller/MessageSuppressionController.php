<?php

declare(strict_types=1);

require_once __DIR__ . '/../model/MessageSuppression.php';

final class MessageSuppressionController
{
    public function __construct(private ?MessageSuppression $suppression = null)
    {
        $this->suppression ??= new MessageSuppression();
    }

    public function supprimer(int $userId, int $messageId): void
    {
        $this->suppression->delete($userId, $messageId);
    }
}
