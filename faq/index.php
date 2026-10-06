<?php
require_once __DIR__ . '/../config/function.php';
exige_role('ADMIN');
require_once __DIR__ . '/controller/FaqController.php';
$crud = (new FaqController())->traiterCrud();
require __DIR__ . '/view/faq.php';