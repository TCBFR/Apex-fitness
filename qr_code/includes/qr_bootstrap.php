<?php

declare(strict_types=1);

/**
 * Chargement manuel du module qr_code + helpers de rendu des vues.
 * Réutilise les helpers du module profil (profil_json, profil_csrf_ok, profil_contexte, profil_dispatch).
 * À inclure APRÈS config/function.php.
 */

require_once dirname(__DIR__, 2) . '/adherent/includes/profil_bootstrap.php';
require_once dirname(__DIR__) . '/controller/QrCodeController.php';

/** Exécute qr_code/view/<vue>.php avec $data et renvoie ce qu'il affiche ou retourne. */
function qr_executer(string $vue, array $data): mixed
{
    $fichier = dirname(__DIR__) . '/view/' . $vue . '.php';
    $rendre = static function (string $__file, array $__data): mixed {
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            $retour = require $__file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $sortie = (string) ob_get_clean();
        return $retour === 1 ? $sortie : $retour; // `return` explicite dans la vue, sinon sa sortie
    };
    return $rendre($fichier, $data);
}

/** Page HTML : qr_code/view/<vue>.php dans qr_code/view/layout.php */
function qr_vue(string $vue, array $data = [], int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    $content = (string) qr_executer($vue, $data);
    echo (string) qr_executer('layout', $data + ['content' => $content]);
}

/** Image PNG d'une valeur (vue image.php). */
function qr_image(string $valeur): void
{
    $png = (string) qr_executer('image', ['valeur' => $valeur]);
    header('Content-Type: image/png');
    header('Cache-Control: private, no-store');
    header('Content-Length: ' . strlen($png));
    echo $png;
}

/** Données JSON du contrôle (vue verification/resultat.php). */
function qr_resultat(string $statut, ?array $membre = null, ?string $formule = null): array
{
    return qr_executer('verification/resultat', compact('statut', 'membre', 'formule'));
}