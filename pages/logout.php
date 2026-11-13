
<?php
session_name('BSyncSession');

$cookiePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookiePath,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once __DIR__ . '/../includes/conexao.php';

// Remove o token de login persistente
if (!empty($_COOKIE['bsync_remember'])) {
    $partes = explode(':', $_COOKIE['bsync_remember']);

    if (count($partes) === 2) {
        $stmt = $conexao->prepare(
            'DELETE FROM lembrar_login WHERE selector = :selector'
        );
        $stmt->execute([':selector' => $partes[0]]);
    }
}

// Apaga o cookie de lembrar login
setcookie('bsync_remember', '', [
    'expires' => time() - 3600,
    'path' => $cookiePath,
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax'
]);

// Encerra a sessão
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookiePath,
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
}

session_destroy();

// Volta para o login
header('Location: login.php');
exit;
