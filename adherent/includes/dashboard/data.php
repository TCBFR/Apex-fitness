<?php
require_once dirname(__DIR__, 2) . '/controller/AdherentDashboardController.php';

$memberId = (int) utilisateur()['NumUsers'];
$dashboardController = new AdherentDashboardController();
$dashboardData = $dashboardController->donnees($memberId);
$user = $dashboardData['user'];
$upcomingReservations = $dashboardData['upcomingReservations'];
$stats = $dashboardData['stats'];
$discover = $dashboardData['discover'];
$activityIcons = $dashboardData['activityIcons'];
$heroImg = $dashboardData['heroImg'];
$navHref = static fn(string $page): string => $dashboardController->lienPage($page);
$active = $_GET['page'] ?? 'dashboard';
