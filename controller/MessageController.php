<?php

require_once __DIR__ . '/../Model/Message.php';
require_once __DIR__ . '/../Model/MessageSuppression.php';

final class MessageController
{
    private Message $message;
    private MessageSuppression $suppression;

    public function __construct(?Message $message = null)
    {
        $this->message = $message ?? new Message();
        $this->suppression = new MessageSuppression();
    }

    public function getAll(int $userId): array
    {
        return $this->message->getAllFor($userId);
    }

    public function getAllThreads(int $userId): array
    {
        $messages = $this->getAll($userId);
        $byId = array_column($messages, null, 'IdMessage');
        $threads = [];
        foreach ($messages as $message) {
            $root = $message;
            while (!empty($root['IdMessage_parent']) && isset($byId[$root['IdMessage_parent']])) {
                $root = $byId[$root['IdMessage_parent']];
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
        $this->suppression->delete($userId, $messageId);
    }
}
