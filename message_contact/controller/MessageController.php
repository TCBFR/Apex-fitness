<?php

declare(strict_types=1);

require_once __DIR__ . '/../model/Message.php';
require_once __DIR__ . '/MessageSuppressionController.php';

final class MessageController
{
    public function __construct(
        private ?Message $message = null,
        private ?MessageSuppressionController $suppression = null
    ) {
        $this->message ??= new Message();
        $this->suppression ??= new MessageSuppressionController();
    }

    public function getAll(int $userId): array
    {
        return $this->message->getAllFor($userId);
    }

    public function getAllThreads(int $userId): array
    {
        $messages = $this->getAll($userId);
        $messagesById = array_column($messages, null, 'IdMessage');
        $threads = [];
        foreach ($messages as $message) {
            $root = $message;
            while (!empty($root['IdMessage_parent']) && isset($messagesById[$root['IdMessage_parent']])) {
                $root = $messagesById[$root['IdMessage_parent']];
            }
            $threads[$root['IdMessage']][] = $message;
        }
        return array_values($threads);
    }

    public function set(int $userId, int $parentId, string $text): void
    {
        $this->message->setReply($userId, $parentId, $text);
    }

    public function setInitial(int $userId, string $text): void
    {
        $this->message->setInitial($userId, $text);
    }

    public function delete(int $userId, int $messageId): void
    {
        $this->suppression->supprimer($userId, $messageId);
    }
}
