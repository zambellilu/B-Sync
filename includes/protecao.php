<?php
$basePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

if (session_status() === PHP_SESSION_NONE) {
    session_name('BSyncSession');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $basePath . '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function bsyncExigirLogin()
{
    global $basePath;

    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . $basePath . '/pages/login.php');
        exit;
    }
}

function bsyncExigirAdmin()
{
    global $basePath;

    bsyncExigirLogin();

    if (($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
        header('Location: ' . $basePath . '/pages/dashboard.php');
        exit;
    }
}

function bsyncExigirUsuario()
{
    global $basePath;

    bsyncExigirLogin();

    if (($_SESSION['usuario_tipo'] ?? '') !== 'usuario') {
        header('Location: ' . $basePath . '/admin/dashboard.php');
        exit;
    }
}

function bsyncTokenCSRF()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function bsyncValidarCSRF($token)
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
