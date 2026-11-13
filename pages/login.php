
<?php
$cookieName = 'bsync_remember';
$cookiePath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
$secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_name('BSyncSession');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookiePath,
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../includes/conexao.php';

function bsyncDefinirSessao($usuario)
{
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
}

function bsyncRedirecionar($tipo)
{
    if ($tipo === 'admin') {
        header('Location: ../admin/dashboard.html');
    } else {
        header('Location: dashboard.html');
    }

    exit;
}

function bsyncLimparLembrar($conexao, $cookieName, $cookiePath, $secureCookie)
{
    if (!empty($_COOKIE[$cookieName])) {
        $partes = explode(':', $_COOKIE[$cookieName], 2);

        if (count($partes) === 2) {
            $stmt = $conexao->prepare(
                'DELETE FROM lembrar_login WHERE selector = :selector'
            );
            $stmt->execute([':selector' => $partes[0]]);
        }
    }

    setcookie($cookieName, '', [
        'expires' => time() - 3600,
        'path' => $cookiePath,
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    unset($_COOKIE[$cookieName]);
}

function bsyncCriarLembrar($conexao, $usuarioId, $cookieName, $cookiePath, $secureCookie)
{
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $validator);
    $expiracao = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

    $stmt = $conexao->prepare(
        'INSERT INTO lembrar_login (usuario_id, selector, token_hash, expira_em)
         VALUES (:usuario_id, :selector, :token_hash, :expira_em)'
    );

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':selector' => $selector,
        ':token_hash' => $tokenHash,
        ':expira_em' => $expiracao
    ]);

    setcookie($cookieName, $selector . ':' . $validator, [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => $cookiePath,
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Se já estiver autenticado, encaminha para o painel.
if (!empty($_SESSION['usuario_id'])) {
    bsyncRedirecionar($_SESSION['usuario_tipo']);
}

// Recupera a autenticação pelo cookie, se existir.
if (!empty($_COOKIE[$cookieName])) {
    $partes = explode(':', $_COOKIE[$cookieName], 2);

    if (
        count($partes) === 2 &&
        preg_match('/^[a-f0-9]{24}$/', $partes[0]) &&
        preg_match('/^[a-f0-9]{64}$/', $partes[1])
    ) {
        $stmt = $conexao->prepare(
            'SELECT l.selector, l.token_hash, u.id, u.nome, u.email, u.tipo
             FROM lembrar_login l
             INNER JOIN usuarios u ON u.id = l.usuario_id
             WHERE l.selector = :selector
             AND l.expira_em > NOW()
             LIMIT 1'
        );

        $stmt->execute([':selector' => $partes[0]]);
        $lembrado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $lembrado &&
            hash_equals($lembrado['token_hash'], hash('sha256', $partes[1]))
        ) {
            bsyncDefinirSessao($lembrado);

            // Renova o token para evitar reutilização do anterior.
            bsyncLimparLembrar(
                $conexao,
                $cookieName,
                $cookiePath,
                $secureCookie
            );

            bsyncCriarLembrar(
                $conexao,
                $lembrado['id'],
                $cookieName,
                $cookiePath,
                $secureCookie
            );

            bsyncRedirecionar($lembrado['tipo']);
        }
    }

    bsyncLimparLembrar(
        $conexao,
        $cookieName,
        $cookiePath,
        $secureCookie
    );
}

$erro = '';
$email = '';

if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['csrf_login'], $csrf)
    ) {
        $erro = 'Sua solicitação expirou. Atualize a página e tente novamente.';
    } elseif ($email === '' || $senha === '') {
        $erro = 'Preencha o e-mail e a senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail ou senha incorretos.';
    } else {
        $stmt = $conexao->prepare(
            'SELECT id, nome, email, senha, tipo
             FROM usuarios
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $erro = 'E-mail ou senha incorretos.';
        } else {
            bsyncDefinirSessao($usuario);

            if (!empty($_POST['lembrar'])) {
                bsyncLimparLembrar(
                    $conexao,
                    $cookieName,
                    $cookiePath,
                    $secureCookie
                );

                bsyncCriarLembrar(
                    $conexao,
                    $usuario['id'],
                    $cookieName,
                    $cookiePath,
                    $secureCookie
                );
            } else {
                bsyncLimparLembrar(
                    $conexao,
                    $cookieName,
                    $cookiePath,
                    $secureCookie
                );
            }

            bsyncRedirecionar($usuario['tipo']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>B-Sync | Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body class="login-page">

    <main class="login-screen">

        <section class="login-intro">

            <div class="intro-text">
                Organize Histórias<br>
                Conecte Pessoas
            </div>

            <img
                src="../assets/img/login-livraria.png"
                alt="Interior da livraria"
                class="login-image"
            >

        </section>

        <section class="login-area">

            <div class="login-content">

                <div class="login-header">

                    <img
                        src="../assets/img/logo.png"
                        alt="B-Sync"
                        class="login-logo-right"
                    >

                    <h2>Bem-vindo de volta!</h2>

                    <p>Faça login para acessar sua conta.</p>

                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="login.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars($_SESSION['csrf_login'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <div class="form-group">

                        <label for="email">E-mail</label>

                        <div class="input-wrapper">

                            <i class="bi bi-envelope"></i>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Digite seu e-mail"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                required
                            >

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="senha">Senha</label>

                        <div class="input-wrapper">

                            <i class="bi bi-lock"></i>

                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                placeholder="Digite sua senha"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                aria-label="Mostrar senha"
                            >
                                <i class="bi bi-eye"></i>
                            </button>

                        </div>

                    </div>

                    <div class="login-options">

                        <label class="remember-me">

                            <input
                                type="checkbox"
                                name="lembrar"
                                value="1"
                            >

                            <span>Lembrar de mim</span>

                        </label>

                        <a href="#" class="forgot-password">
                            Esqueci minha senha
                        </a>

                    </div>

                    <button type="submit" class="login-button">
                        Entrar
                    </button>

                </form>

                <div class="login-divider">
                    <span>ou</span>
                </div>

                <p class="register-text">
                    Ainda não possui uma conta?
                    <a href="cadastro.php">Cadastre-se</a>
                </p>

                <a href="../index.html" class="back-home">
                    <i class="bi bi-arrow-left"></i>
                    Voltar para o início
                </a>

            </div>

        </section>

    </main>

    <script src="../assets/js/login.js"></script>

</body>
</html>
