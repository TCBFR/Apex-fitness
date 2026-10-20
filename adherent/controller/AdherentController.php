<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/model/AdherentEspace.php';
require_once dirname(__DIR__) . '/service/ProfilePhotoStorage.php';
require_once dirname(__DIR__, 2) . '/admin/controller/EntityController.php';
require_once dirname(__DIR__) . '/model/adherent.php';

final class AdherentController extends EntityController
{
    public function __construct(private ?AdherentEspace $space = null)
    {
        parent::__construct(Adherent::class);
        $this->space ??= new AdherentEspace();
    }

    public function historiqueReservations(int $memberId): array
    {
        return $this->space->historiqueReservations($memberId);
    }

    public function paiements(int $memberId): array
    {
        return $this->space->paiements($memberId);
    }

    public function facture(int $memberId, int $paymentId): ?array
    {
        return $this->space->paiement($memberId, $paymentId);
    }

    public function messagesNonLus(int $memberId): int
    {
        return $this->space->messagesNonLus($memberId);
    }

    public function changerPhotoProfil(int $memberId, array $upload): string
    {
        $storage = new ProfilePhotoStorage();
        $newPath = $storage->store($upload);
        try {
            $oldPath = $this->space->updateProfilePhoto($memberId, $newPath);
            $storage->remove($oldPath);
            return $newPath;
        } catch (Throwable $exception) {
            $storage->remove($newPath);
            throw $exception;
        }
    }

    public function telechargerFacture(int $memberId, int $paymentId): void
    {
        $invoice = $this->facture($memberId, $paymentId);
        if (!$invoice) {
            http_response_code(404);
            exit('Facture introuvable.');
        }
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $safe = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $facture = $invoice;
        ob_start();
        require dirname(__DIR__, 2) . '/facture/templates/facture.php';
        $html = (string)ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream('facture-Athletix.pdf', ['Attachment' => true]);
        exit;
    }

    public function objectifs(int $memberId, int $months = 3): array
    {
        $today = date('Y-m-d');
        $done = array_column($this->space->objectifsDuJour($memberId, $today), 'objectif');
        return [
            'today' => $today,
            'available' => ['Séance sportive', 'Boire 2 litres d’eau', 'Dormir 8 heures', '10 000 pas'],
            'done' => $done,
            'history' => $this->space->historiqueObjectifs($memberId, $months),
        ];
    }

    public function marquerObjectif(int $memberId, array $input): void
    {
        $this->space->marquerObjectif(
            $memberId,
            (string)($input['date'] ?? ''),
            (string)($input['objectif'] ?? ''),
            filter_var($input['atteint'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );
    }
}
