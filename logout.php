<?php
require_once __DIR__ . '/includes/auth.php';

deconnexion();
session_start();
flash('info', 'Vous êtes déconnecté.');
redirige('index.php');
