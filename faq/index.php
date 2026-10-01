<?php
require_once __DIR__ . '/../includes/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/faq.php';
$crud = (new FaqTableController())->traiter();
require __DIR__ . '/view/faq.php';