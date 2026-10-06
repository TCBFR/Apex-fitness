<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'Fitness';
const DB_USER = 'root';
const DB_PASS = 'root';
const DB_PORT = 8889;

const APP_NOM = 'Fitness';
const APP_BASE = '';

ini_set('display_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Paris');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
