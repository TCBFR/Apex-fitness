<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/Notification.php';

final class NotificationController
{
    public function __construct(private ?Notification $notifications = null)
    {
        $this->notifications ??= new Notification();
    }

    public function menu(int $adherentId): array
    {
        return [
            'items' => $this->notifications->pourAdherent($adherentId),
            'unread_count' => $this->notifications->compterNonLues($adherentId),
        ];
    }

    public function nombreNonLues(int $adherentId): int
    {
        $this->notifications->preparerRappels();
        return $this->notifications->compterNonLues($adherentId);
    }

    public function marquerCommeLue(int $adherentId, int $notificationId): array
    {
        $this->notifications->marquerCommeLue($adherentId, $notificationId);
        return ['unread_count' => $this->notifications->compterNonLues($adherentId)];
    }
}
