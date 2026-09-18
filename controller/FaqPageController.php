<?php

require_once __DIR__ . '/FaqController.php';

final class FaqPageController
{
    private FaqController $faq;

    public function __construct(?FaqController $faq = null)
    {
        $this->faq = $faq ?? new FaqController();
    }

    public function getAll(): array
    {
        return $this->faq->index();
    }

    public function set(int $userId, string $question): void
    {
        $this->faq->creer($userId, $question);
    }

    public function getAllByCategory(array $questions): array
    {
        $groups = [];
        foreach ($questions as $question) $groups[$question['categorie']][] = $question;
        return $groups;
    }
}
