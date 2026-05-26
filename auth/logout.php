<?php
require_once '../config/app.php';

// Destroy the session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Restart a fresh session so we can set the flash
session_start();
setFlash('info', 'You have been logged out. See you soon!');

redirect(BASE_URL . '/auth/login.php');
