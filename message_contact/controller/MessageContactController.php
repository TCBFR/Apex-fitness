<?php

require_once __DIR__ . '/../model/Message.php';

/** Prépare et valide les demandes envoyées depuis le formulaire de contact. */
final class ContactController
{
    public function get(): array
    {
        $user = utilisateur();
        $connecte = connecte();

        return [
            'nom' => $connecte ? $user['prenomUsers'] . ' ' . $user['nomUsers'] : '',
            'mail' => $connecte ? $user['mailUsers'] : '',
            'sujet' => $_GET['sujet'] ?? '',
            'message' => '',
        ];
    }

    public function set(array $data): array
    {
        $errors = [];

        if (mb_strlen($data['nom']) < 2) {
            $errors['nom'] = 'Indiquez votre nom.';
        }
        if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
            $errors['mail'] = 'Adresse e-mail invalide.';
        }
        if (mb_strlen($data['sujet']) < 3) {
            $errors['sujet'] = 'Résumez votre demande.';
        }
        if (mb_strlen($data['message']) < 15) {
            $errors['message'] = '15 caractères minimum.';
        }
        if ($errors) {
            return $errors;
        }

        $pdo = db();
        $query = $pdo->prepare(
            'INSERT INTO message_contact (nom, mail, sujet, message) VALUES (?, ?, ?, ?)'
        );
        $query->execute([$data['nom'], $data['mail'], $data['sujet'], $data['message']]);
        journalise('MESSAGE_CONTACT', 'Nummessage=' . $pdo->lastInsertId());

        return [];
    }
}

/** Coordonne la lecture, la réponse et la suppression des conversations. */
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
        $messagesById = array_column($messages, null, 'IdMessage');
        $threads = [];

        foreach ($messages as $message) {
            $root = $message;
            while (
                !empty($root['IdMessage_parent'])
                && isset($messagesById[$root['IdMessage_parent']])
            ) {
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
        $this->suppression->delete($userId, $messageId);
    }
}
