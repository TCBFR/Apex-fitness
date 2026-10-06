<?php
require_once __DIR__ . '/../config/auth.php';
exige_role('ADMIN');
require_once __DIR__ . '/../controller/EntityController.php';
require_once __DIR__ . '/model/faq.php';
$crud = (new EntityController(Faq::class))->traiter();
require __DIR__ . '/view/faq.php';