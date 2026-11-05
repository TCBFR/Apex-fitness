<?php

require_once __DIR__ . '/../../admin/controller/ControleurGenerique.php';
require_once __DIR__ . '/../model/AvisDomain.php';

/** Coordonne les opérations sur les avis et leur affichage public. */
final class AvisController
{
    private Avis $avis;

    public function __construct(?Avis $avis = null)
    {
        $this->avis = $avis ?? new Avis();
    }

    /**
     * Point d'entrée appelé par le routeur (public/index.php).
     * Reprend la logique de l'ancien avis/public.php : traitement du formulaire,
     * puis préparation des données pour view/public.php.
     */
    public function index(): array
    {
        $erreur = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verifie_csrf();
            if (!connecte() || !a_role('ADHERENT') || a_role('EMPLOYE')) {
                http_response_code(403);
                $erreur = 'Seuls les adhérents non employés peuvent donner un avis.';
            } else {
                try {
                    $this->creer(
                        (int) utilisateur()['NumUsers'],
                        (int) ($_POST['note'] ?? 0),
                        (string) ($_POST['commentaire'] ?? '')
                    );
                    journalise('AVIS_SALLE', 'Apex Fitness');
                    flash('succes', 'Merci pour votre avis.');
                    redirige('avis/index.php');
                } catch (Throwable $exception) {
                    $erreur = $exception->getMessage();
                }
            }
        }

        $avis = $this->avis->publies();
        $moyenne = $avis ? array_sum(array_column($avis, 'noteavis')) / count($avis) : 0;
        $noteFiltre = isset($_GET['note']) ? max(1, min(5, (int) $_GET['note'])) : null;

        $compteurs = array_fill(1, 5, 0);
        foreach ($avis as $item) {
            $compteurs[(int) $item['noteavis']]++;
        }
        $maxCompteur = max($compteurs) ?: 1;

        return [
            'titrePage' => 'Avis des adhérents',
            'pageActive' => 'avis',
            'erreur' => $erreur,
            'avis' => $avis,
            'moyenne' => $moyenne,
            'noteFiltre' => $noteFiltre,
            'compteurs' => $compteurs,
            'maxCompteur' => $maxCompteur,
            'avisAffiches' => $this->filtrerParNote($avis, $noteFiltre),
        ];
    }

    public function avisDeAdherent(int $numUtilisateur): ?array
    {
        return $this->avis->avisDeAdherent($numUtilisateur);
    }

    public function creer(int $numUtilisateur, int $note, string $commentaire): int
    {
        return $this->avis->creer($numUtilisateur, $note, trim($commentaire));
    }

    public function repondre(int $numInteraction, string $reponse): void
    {
        $this->avis->repondre($numInteraction, trim($reponse));
    }

    public function filtrerParNote(array $avis, ?int $note): array
    {
        if ($note === null) {
            return $avis;
        }

        return array_values(array_filter(
            $avis,
            static fn(array $item): bool => (int) $item['noteavis'] === $note
        ));
    }

    public function traiterCrud(): array
    {
        return (new ControleurGenerique(Avis::class))->traiter();
    }
}