<?php

declare(strict_types=1);

/**
 * CONTRÔLEUR — module qr_code. Il ne contient ni SQL, ni règle métier, ni mise en forme :
 * il lit la requête, interroge les modèles (GenerateurQrCode, Abonnement) et choisit la vue.
 *  - image()    : l'adhérent affiche son QR (vue image.php)
 *  - index()    : page de contrôle de l'accueil (vue verification/index.php)
 *  - verifier() : l'accueil envoie un code scanné (vue verification/resultat.php, en JSON)
 */
final class QrCodeController
{
    /** GET : QR actif de l'adhérent connecté (abonnement actif exigé). */
    public function image(): void
    {
        $ctx = profil_contexte(true);
        if ($ctx === null) {
            return;
        }
        $qr = (new GenerateurQrCode())->actifPourAdherent(db(), (int) $ctx['user']['NumUsers']);
        if ($qr === null) {
            profil_json(['ok' => false, 'message' => 'Aucun QR code actif.'], 404);
            return;
        }
        qr_image((string) $qr['code_qr']);
    }

    /** GET : page de contrôle (accueil). */
    public function index(): void
    {
        qr_vue('verification/index', ['title' => 'Contrôle d\'accès · ATHLETIX']);
    }

    /** POST (AJAX) : contrôle une valeur scannée. */
    public function verifier(): void
    {
        if (!profil_csrf_ok()) {
            return;
        }
        $valeur = trim((string) ($_POST['valeur'] ?? ''));
        if ($valeur === '' || strlen($valeur) > 150) {
            profil_json(qr_resultat('INVALIDE'), 422);
            return;
        }

        $res = (new GenerateurQrCode())->controler(db(), $valeur);
        $statut = $res['statut'];
        $formule = null;

        // Un QR valide ne suffit pas : l'abonnement doit l'être aussi (autre modèle)
        if ($statut === 'VALIDE') {
            $abonnement = (new Abonnement())->courantPourAdherent((int) $res['numUsers']);
            if (Abonnement::estActif($abonnement)) {
                $formule = $abonnement['libelle_abo'] ?? 'Adhérent';
            } else {
                $statut = 'ABONNEMENT_EXPIRE';
            }
        }

        journalise($statut === 'VALIDE' ? 'QR_VALIDE' : 'QR_REFUSE', 'Statut ' . $statut
            . ($res['numUsers'] !== null ? ', adhérent ' . $res['numUsers'] : ''));

        profil_json(qr_resultat($statut, $res['membre'], $formule));
    }
}