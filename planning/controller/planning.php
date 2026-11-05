<?php
declare(strict_types=1);

require_once __DIR__ . '/../model/planning.php';
require_once __DIR__ . '/PlanningCalendarController.php';
require_once __DIR__ . '/PlanningRoutingController.php';
require_once __DIR__ . '/PlanningBookingController.php';
require_once __DIR__ . '/PlanningCancellationController.php';
require_once __DIR__ . '/PlanningScheduleCreationController.php';
require_once __DIR__ . '/PlanningOccurrencePreparationController.php';
require_once __DIR__ . '/PlanningScheduleController.php';

final class PlanningController
{
    public const TYPES = [
        'cardio' => 'Cardio', 'musculation' => 'Musculation', 'yoga' => 'Yoga et bien-être',
        'cross' => 'Cross Training', 'pilates' => 'Pilates', 'abdos' => 'Abdos et gainage',
        'boxe' => 'Boxe', 'danse' => 'Danse',
    ];
    private const MAX_OCCURRENCES = 52;
    private const ACTIONS = [
        'adherent' => ['reserver', 'annuler'],
        'employe' => ['creer_creneau', 'modifier_creneau', 'supprimer_creneau'],
        'admin' => ['creer_creneau', 'modifier_creneau', 'supprimer_creneau'],
    ];
    public const FC_SCRIPTS = [
        'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js',
        'https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/fr.global.min.js',
    ];
    /** Actions accessibles par URL via le routeur (/planning/events.php). */
    public const ACTIONS_URL = ['events'];
    private Planning $model;

    use PlanningCalendarController;
    use PlanningRoutingController;
    use PlanningBookingController;
    use PlanningCancellationController;
    use PlanningScheduleCreationController;
    use PlanningOccurrencePreparationController;
    use PlanningScheduleController;

    public function __construct(?Planning $model = null)
    {
        $this->model = $model ?? new Planning();
    }

    /**
     * Flux JSON du calendrier : FullCalendar appelle url('planning/events.php')
     * en ajoutant ?start=...&end=... (dates ISO). Répond en JSON puis arrête le script.
     */
    public function events(): array
    {
        $debut = strtotime((string) ($_GET['start'] ?? ''));
        $fin = strtotime((string) ($_GET['end'] ?? ''));
        if ($debut === false) {
            $debut = strtotime('monday this week');
        }
        if ($fin === false || $fin <= $debut) {
            $fin = strtotime('+7 days', $debut);
        }

        // Identifiant de l'utilisateur connecté (0 si visiteur) pour marquer ses réservations
        $numUsers = connecte() ? (int) (utilisateur()['NumUsers'] ?? 0) : 0;

        $evenements = $this->evenements(
            date('Y-m-d H:i:s', $debut),
            date('Y-m-d H:i:s', $fin),
            $numUsers
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($evenements, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}