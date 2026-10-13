<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/planning.php';

/**
 * CONTROLLER du module planning (FullCalendar) : regles metier et actions.
 * Le SQL est dans planning/model/planning.php, l'affichage dans planning/view/planning.php.
 *
 * Ce controller n'etend pas EntityController : le planning n'est pas un CRUD
 * d'administration, il a ses propres regles (reservation, quota, conflits).
 *
 * Utilisation dans une page (employe/espace.php) :
 *   $planning = new PlanningController();
 *   if (POST && PlanningController::estAction($mode, $action)) {
 *       // verifie_csrf() deja appele par la page
 *       $planning->traiter($mode, $moi, 'employe/espace.php?onglet=creneaux');
 *   }
 *   $planningDonnees = $planning->donneesEmploye($moi);   // pour la view
 */
final class PlanningController
{
    public const TYPES = [
        'cardio'      => 'Cardio',
        'musculation' => 'Musculation',
        'yoga'        => 'Yoga et bien-être',
        'boxe'        => 'Boxe',
        'danse'       => 'Danse',
    ];

    private const MAX_OCCURRENCES = 52;

    /** Actions POST gerees par ce controller, selon l'espace. */
    private const ACTIONS = [
        'adherent' => ['reserver', 'annuler'],
        'employe'  => ['creer_creneau', 'supprimer_creneau'],
    ];

    /** Bibliothèque FullCalendar à charger avant assets/js/planning.js. */
    public const FC_SCRIPTS = [
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
        'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/fr.global.min.js',
    ];

    /** @var Planning */
    private $model;

    public function __construct(?Planning $model = null)
    {
        $this->model = $model ?? new Planning();
    }

    public static function estAction(string $mode, string $action): bool
    {
        return in_array($action, self::ACTIONS[$mode] ?? [], true);
    }

    /* =====================================================================
       FLUX JSON POUR FULLCALENDAR (planning/events.php)
       ===================================================================== */

    /** Evenements au format FullCalendar pour la plage [debut, fin[. */
    public function evenements(string $debut, string $fin, int $numUsers = 0): array
    {
        $out = [];
        foreach ($this->model->evenements($debut, $fin, $numUsers) as $r) {
            $type      = isset(self::TYPES[$r['type_activite']]) ? $r['type_activite'] : 'cardio';
            $capacite  = (int) $r['capacite_max'];
            $prises    = (int) $r['places_prises'];
            $restantes = max(0, $capacite - $prises);
            $complet   = $restantes === 0;
            $tsDebut   = strtotime($r['date_heure_debut']);

            $out[] = [
                'id'            => (int) $r['Numactivite'],
                'title'         => $r['titre'],
                'start'         => date('Y-m-d\TH:i:s', $tsDebut),
                'end'           => date('Y-m-d\TH:i:s', strtotime($r['date_heure_fin'])),
                'classNames'    => ['act-' . $type, $complet ? 'act-complet' : 'act-ouvert'],
                'extendedProps' => [
                    'type'        => $type,
                    'typeLibelle' => self::TYPES[$type],
                    'salle'       => $r['nomsalle'],
                    'coach'       => $r['prenomUsers'],
                    'capacite'    => $capacite,
                    'prises'      => $prises,
                    'restantes'   => $restantes,
                    'complet'     => $complet,
                    'reserve'     => (int) $r['reserve'] > 0,
                    'serie'       => $r['id_serie'] !== null,
                    'passe'       => $tsDebut < time(),
                ],
            ];
        }
        return $out;
    }

    /* =====================================================================
       DONNEES POUR LA VIEW
       ===================================================================== */

    /** Données nécessaires à l'affichage du planning employé. */
    public function donneesEmploye(int $moi): array
    {
        $creneaux = [];
        foreach ($this->model->derniersCreneaux() as $c) {
            $capacite = (int) $c['capacite_max'];
            $c['pct'] = $capacite > 0 ? (int) round((int) $c['places_prises'] / $capacite * 100) : 0;
            $creneaux[] = $c;
        }
        return [
            'moi'      => $moi,
            'creneaux' => $creneaux,
            'titres'   => $this->model->titresExistants(),
            'salles'   => $this->model->salles(),
            'coachs'   => $this->model->coachs(),
        ];
    }

    /* =====================================================================
       ACTIONS POST (le jeton CSRF est verifie par la page avant l'appel)
       ===================================================================== */

    /**
     * Execute l'action demandee puis redirige. Ne fait rien si l'action n'est pas la sienne.
     * @param string      $retour           page de retour (ex. 'employe/espace.php?onglet=creneaux')
     * @param string|null $retourAnnulation page de retour apres une annulation (par defaut $retour)
     */
    public function traiter(string $mode, int $moi, string $retour, ?string $retourAnnulation = null): void
    {
        $action = (string) ($_POST['action'] ?? '');
        if (!self::estAction($mode, $action)) {
            return;
        }

        try {
            switch ($action) {
                case 'reserver':
                    $num   = (int) ($_POST['creneau'] ?? 0);
                    $titre = $this->reserver($moi, $num);
                    journalise('RESERVATION', 'Numactivite=' . $num);
                    flash('succes', 'Réservation enregistrée pour ' . $titre . '.');
                    break;

                case 'annuler':
                    $numRes = (int) ($_POST['reservation'] ?? 0);
                    $this->annuler($moi, $numRes);
                    journalise('ANNULATION', 'Numres=' . $numRes);
                    flash('succes', 'Réservation annulée, la place est de nouveau disponible.');
                    $retour = $retourAnnulation ?? $retour;
                    break;

                case 'creer_creneau':
                    $res = $this->creer($_POST);
                    journalise('CREATION_CRENEAU', $res['crees'] . ' creneau(x)');
                    flash('succes', $res['crees'] > 1
                        ? $res['crees'] . ' créneaux ajoutés au planning.'
                        : 'Créneau ajouté au planning.');
                    if ($res['ignores']) {
                        flash('info', 'Ignorés (salle ou coach déjà pris) : ' . implode(', ', $res['ignores']) . '.');
                    }
                    break;

                case 'supprimer_creneau':
                    $num = (int) ($_POST['creneau'] ?? 0);
                    $n   = $this->supprimer($num, ($_POST['portee'] ?? 'un') === 'serie');
                    journalise('SUPPRESSION_CRENEAU', 'Numactivite=' . $num . ' (' . $n . ')');
                    flash('succes', $n > 1 ? "$n créneaux supprimés." : 'Créneau supprimé.');
                    break;
            }
        } catch (RuntimeException $ex) {
            flash('erreur', $ex->getMessage());
        }

        redirige($retour);
        exit;
    }

    /* =====================================================================
       ADHERENT : RESERVER / ANNULER
       ===================================================================== */

    /**
     * Reserve un creneau. Regles verifiees cote serveur, dans une transaction :
     * 1 adherent actif, 2 creneau a venir, 3 pas complet, 4 pas deja reserve,
     * 5 quota mensuel de l'offre Freemium.
     * @return string titre de l'activite reservee
     * @throws RuntimeException message affichable a l'adherent
     */
    public function reserver(int $numUsers, int $numActivite): string
    {
        $fiche = $this->model->ficheAdherent($numUsers);
        if ($fiche === null || $fiche['libelle_statut'] !== 'Actif') {
            throw new RuntimeException(
                'Votre compte est ' . mb_strtolower($fiche['libelle_statut'] ?? 'inconnu')
                . '. Régularisez votre abonnement à l\'accueil avant de réserver.'
            );
        }

        try {
            $this->model->begin();

            // Verrou sur le creneau : deux reservations simultanees ne depassent pas la capacite.
            $creneau = $this->model->verrouillerCreneau($numActivite);
            if ($creneau === null) {
                throw new RuntimeException('Ce créneau n\'existe plus.');
            }
            if (strtotime($creneau['date_heure_debut']) < time()) {
                throw new RuntimeException('Ce créneau est déjà passé.');
            }

            if ($this->model->placesPrises($numActivite) >= (int) $creneau['capacite_max']) {
                throw new RuntimeException('Le créneau est complet (' . (int) $creneau['capacite_max'] . ' places).');
            }

            $existante = $this->model->reservationExistante($numUsers, $numActivite);
            if ($existante && $existante['statut_reservation'] !== 'Annulee') {
                throw new RuntimeException('Vous avez déjà une réservation sur ce créneau.');
            }

            if ($fiche['type_abo'] === 'FREEMIUM') {
                $quota = (int) $fiche['nb_reservations_max_mois'];
                if ($this->model->reservationsDuMois($numUsers, $creneau['date_heure_debut']) >= $quota) {
                    throw new RuntimeException("L'offre Freemium est limitée à $quota réservations par mois.");
                }
            }

            if ($existante) {   // une annulation precedente est reactivee
                $this->model->reactiverReservation((int) $existante['Numres']);
            } else {
                $this->model->creerReservation($numUsers, $numActivite);
            }

            $this->model->commit();
            return (string) $creneau['titre'];
        } catch (RuntimeException $e) {
            $this->model->rollback();
            throw $e;
        } catch (Throwable $e) {
            $this->model->rollback();
            throw new RuntimeException('La réservation a échoué. Réessayez dans un instant.');
        }
    }

    /**
     * Annule une reservation de l'adherent (jusqu'a 2 h avant le cours).
     * @throws RuntimeException
     */
    public function annuler(int $numUsers, int $numRes): void
    {
        $res = $this->model->reservationDeAdherent($numRes, $numUsers);
        if ($res === null) {
            throw new RuntimeException('Réservation introuvable.');
        }
        if ($res['statut_reservation'] === 'Annulee') {
            throw new RuntimeException('Cette réservation est déjà annulée.');
        }
        if (strtotime($res['date_heure_debut']) - time() < 2 * 3600) {
            throw new RuntimeException('L\'annulation n\'est plus possible moins de 2 heures avant le cours.');
        }
        $this->model->annulerReservation($numRes);
    }

    /* =====================================================================
       EMPLOYE : CREER / SUPPRIMER
       ===================================================================== */

    /**
     * Cree un creneau, ou une serie hebdomadaire si $d['repeter'] est vrai.
     * @return array{crees:int, ignores:string[]}
     * @throws RuntimeException message affichable a l'employe
     */
    public function creer(array $d): array
    {
        $titre = trim((string) ($d['titre'] ?? ''));
        $type  = (string) ($d['type'] ?? '');
        $salle = (int) ($d['salle'] ?? 0);
        $duree = min(480, max(15, (int) ($d['duree'] ?? 60)));
        $cap   = max(1, (int) ($d['capacite'] ?? 1));
        $coach = (int) ($d['coach'] ?? 0);

        if ($titre === '' || mb_strlen($titre) > 100) {
            throw new RuntimeException('Le titre est obligatoire (100 caractères maximum).');
        }
        if (!isset(self::TYPES[$type])) {
            throw new RuntimeException('Type d\'activité invalide.');
        }
        if ($salle <= 0) {
            throw new RuntimeException('Choisissez une salle.');
        }
        if ($coach <= 0) {
            throw new RuntimeException('Choisissez un coach.');
        }

        $debutTexte = trim((string) ($d['debut'] ?? ''));
        try {
            if ($debutTexte === '') {
                throw new Exception('vide');
            }
            $premier = new DateTimeImmutable($debutTexte);
        } catch (Exception $e) {
            throw new RuntimeException('Date de début invalide.');
        }
        if ($premier->getTimestamp() < time()) {
            throw new RuntimeException('La date de début doit être dans le futur.');
        }

        $repeter = !empty($d['repeter']);
        $limite  = $premier;
        if ($repeter) {
            $finTexte = trim((string) ($d['jusqu_au'] ?? ''));
            try {
                if ($finTexte === '') {
                    throw new Exception('vide');
                }
                $limite = new DateTimeImmutable($finTexte . ' 23:59:59');
            } catch (Exception $e) {
                throw new RuntimeException('Indiquez la date de fin de la répétition.');
            }
            if ($limite < $premier) {
                throw new RuntimeException('La date de fin de répétition précède le début.');
            }
        }

        // Occurrences : "+N weeks" conserve l'heure locale a travers le changement d'heure.
        $occurrences = [];
        for ($i = 0; ; $i++) {
            $deb = $i === 0 ? $premier : $premier->modify("+$i weeks");
            if ($deb > $limite) {
                break;
            }
            if ($i >= self::MAX_OCCURRENCES) {
                throw new RuntimeException('Répétition limitée à ' . self::MAX_OCCURRENCES . ' semaines.');
            }
            $occurrences[] = [$deb, $deb->modify("+$duree minutes")];
            if (!$repeter) {
                break;
            }
        }

        $idSerie = ($repeter && count($occurrences) > 1) ? bin2hex(random_bytes(16)) : null;
        $fmt = 'Y-m-d H:i:s';

        try {
            $this->model->begin();

            $capSalle = $this->model->capaciteSalleVerrouillee($salle);
            if ($capSalle === null) {
                throw new RuntimeException('Salle introuvable.');
            }
            if ($cap > $capSalle) {
                throw new RuntimeException("La salle accueille au maximum $capSalle personnes.");
            }
            if (!$this->model->coachValide($coach)) {
                throw new RuntimeException('Coach introuvable.');
            }

            $crees = 0;
            $ignores = [];
            foreach ($occurrences as [$deb, $fin]) {
                if ($this->model->conflit($salle, $coach, $deb->format($fmt), $fin->format($fmt))) {
                    if (!$repeter) {
                        throw new RuntimeException('La salle ou le coach est déjà pris sur ce créneau.');
                    }
                    $ignores[] = $deb->format('d/m/Y H\hi');
                    continue;
                }
                $this->model->insererCreneau($titre, $type, $deb->format($fmt), $fin->format($fmt),
                                             $cap, $salle, $coach, $idSerie);
                $crees++;
            }
            if ($crees === 0) {
                throw new RuntimeException('Aucun créneau créé : salle ou coach déjà pris sur toutes les dates.');
            }

            $this->model->commit();
            return ['crees' => $crees, 'ignores' => $ignores];
        } catch (Throwable $e) {
            $this->model->rollback();
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException('Création impossible : ' . ($e instanceof PDOException ? 'conflit en base.' : 'erreur interne.'));
        }
    }

    /**
     * Supprime un creneau, ou toutes ses occurrences a venir si $serie.
     * Les reservations associees sont supprimees en cascade par la base.
     * @return int nombre de creneaux supprimes
     * @throws RuntimeException
     */
    public function supprimer(int $numActivite, bool $serie): int
    {
        $creneau = $this->model->creneau($numActivite);
        if ($creneau === null) {
            throw new RuntimeException('Créneau introuvable.');
        }

        if ($serie && $creneau['id_serie'] !== null) {
            return $this->model->supprimerSerieAVenir((string) $creneau['id_serie']);
        }
        return $this->model->supprimerCreneau($numActivite);
    }
}
