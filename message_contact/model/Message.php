<?php

require_once __DIR__ . '/../../config/function.php';
require_once __DIR__ . '/MessageConversationRead.php';
require_once __DIR__ . '/MessageConversationWrite.php';
require_once __DIR__ . '/MessageConversationSchema.php';

final class Message
{
    use MessageConversationRead;
    use MessageConversationWrite;
    use MessageConversationSchema;

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }
}
