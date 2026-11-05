<?php
declare(strict_types=1);

require_once __DIR__ . '/PlanningTransactions.php';
require_once __DIR__ . '/PlanningCalendarQueries.php';
require_once __DIR__ . '/PlanningMemberQueries.php';
require_once __DIR__ . '/PlanningReservationQueries.php';
require_once __DIR__ . '/PlanningScheduleQueries.php';

final class Planning
{
    private PDO $pdo;

    use PlanningTransactions;
    use PlanningCalendarQueries;
    use PlanningMemberQueries;
    use PlanningReservationQueries;
    use PlanningScheduleQueries;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? db();
    }
}
