<?php
require_once __DIR__ . '/../config/function.php';

deconnexion();
session_start();
flash('info', 'Vous êtes déconnecté.');
redirige('accueil/index.php');
