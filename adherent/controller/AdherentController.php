<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/model/AdherentEspace.php';
require_once dirname(__DIR__) . '/service/StockagePhotoProfil.php';
require_once dirname(__DIR__, 2) . '/admin/controller/ControleurGenerique.php';
require_once dirname(__DIR__) . '/model/adherent.php';

final class AdherentController extends ControleurGenerique
{
    /** Actions accessibles par URL (index.php?route=adherent/<action>). */
    public const ACTIONS = [
        'espace', 'planning', 'reservations', 'abonnement', 'profil', 'avis', 'messagerie', 'offres',
    ];

    public function __construct(private ?AdherentEspace $space = null)
    {
        parent::__construct(Adherent::class);
        $this->space ??= new AdherentEspace();
    }

    /** Page d'arrivée après la connexion (/adherent/espace). */
    public function espace(): array
    {
        return $this->donneesPage('Mon espace', 'espace');
    }

    public function planning(): array
    {
        return $this->donneesPage('Planning', 'planning');
    }

    public function reservations(): array
    {
        return $this->donneesPage('Réservations', 'reservations');
    }

    public function abonnement(): array
    {
        return $this->donneesPage('Mon abonnement', 'abonnement');
    }

    public function offres(): array
    {
        return $this->donneesPage('Offres', 'abonnement');
    }

    public function profil(): array
    {
        return $this->donneesPage('Mon profil', 'profil');
    }

    public function avis(): array
    {
        return $this->donneesPage('Mon avis', 'avis');
    }

    public function messagerie(): array
    {
        return $this->donneesPage('Messagerie', 'messagerie');
    }

    /** Données communes aux pages de l'espace adhérent. */
    private function donneesPage(string $titre, string $pageActive): array
    {
        return [
            'titrePage'  => $titre,
            'pageActive' => $pageActive,
            'layout'     => 'aucun',
        ];
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
        $storage = new StockagePhotoProfil();
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

        $safe = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $facture = $invoice;
        ob_start();
        require dirname(__DIR__, 2) . '/facture/templates/facture.php';
        $html = (string) ob_get_clean();

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
}