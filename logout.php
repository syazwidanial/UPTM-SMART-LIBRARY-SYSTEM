<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    http_response_code(405);
    exit('Invalid request.');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'] ?? '',
        (bool)$params['secure'],
        (bool)$params['httponly']
    );
}

session_destroy();
session_start();
flash('success', 'You have been signed out.');
redirect('login.php');
