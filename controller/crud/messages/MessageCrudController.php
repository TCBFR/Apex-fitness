<?php

require_once __DIR__ . '/../../MessageController.php';

final class MessageCrudController
{
    private MessageController $messages;

    public function __construct(?MessageController $messages = null)
    {
        $this->messages = $messages ?? new MessageController();
    }

    public function getAll(int $userId): array
    {
        return $this->messages->getAll($userId);
    }

    public function getThreads(int $userId): array
    {
        return $this->messages->getAllThreads($userId);
    }

    public function create(int $userId, string $text): void
    {
        $this->messages->setInitial($userId, $text);
    }

    public function update(int $userId, int $parentId, string $text): void
    {
        $this->messages->set($userId, $parentId, $text);
    }

    public function delete(int $userId, int $messageId): void
    {
        $this->messages->delete($userId, $messageId);
    }
}
