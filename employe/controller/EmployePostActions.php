<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifie_csrf();
    $action = $_POST['action'] ?? '';

  require __DIR__ . '/controller/actions/EmployeActionDevenirAdherent.php';
  $actionsTechnicien = [
    'avancer_maintenance', 'prendre_ticket', 'detacher_ticket', 'modifier_materiel',
    'declarer_utilisation_materiel', 'commander_materiel', 'commenter_ticket',
  ];
  require __DIR__ . '/controller/actions/EmployeActionAction02.php';  require __DIR__ . '/controller/actions/EmployeActionAction03.php';
  require __DIR__ . '/controller/actions/EmployeActionFermerTicket.php';
  require __DIR__ . '/controller/actions/EmployeActionCommenterTicket.php';
  require __DIR__ . '/controller/actions/EmployeActionDeclarerUtilisationMateriel.php';
  require __DIR__ . '/controller/actions/EmployeActionCommanderMateriel.php';
  require __DIR__ . '/controller/actions/EmployeActionModifierMateriel.php';
  require __DIR__ . '/controller/actions/EmployeActionRepondreMessage.php';
  require __DIR__ . '/controller/actions/EmployeActionAccueilAnnulerReservation.php';
  require __DIR__ . '/controller/actions/EmployeActionRepondreMessage2.php';
  require __DIR__ . '/controller/actions/EmployeActionSupprimerMessage.php';
    /* ---- planning : creer / supprimer un creneau ------------------- */
    require __DIR__ . '/controller/actions/EmployeActionAction13.php';
    /* ---- declarer une panne ---------------------------------------- */
    require __DIR__ . '/controller/actions/EmployeActionDeclarerPanne.php';
    require __DIR__ . '/controller/actions/EmployeActionFermerTicket2.php';
    require __DIR__ . '/controller/actions/EmployeActionPrendreTicket.php';
    require __DIR__ . '/controller/actions/EmployeActionDetacherTicket.php';
    /* ---- faire avancer une demande de maintenance ------------------- */
    require __DIR__ . '/controller/actions/EmployeActionAvancerMaintenance.php';
    /* ---- repondre a une question de FAQ ----------------------------- */
    require __DIR__ . '/controller/actions/EmployeActionRepondreFaq.php';
    require __DIR__ . '/controller/actions/EmployeActionSupprimerFaq.php';
}

/* =====================================================================
   DONNEES
   ===================================================================== */
