
<?php
require_once __DIR__ . '/../includes/conexao.php';

$erro = '';
$sucesso = '';
$nome = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar-senha'] ?? '';

    if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } else {
        try {
            $consulta = $conexao->prepare(
                'SELECT id FROM usuarios WHERE email = :email'
            );

            $consulta->execute([':email' => $email]);

            if ($consulta->fetch()) {
                $erro = 'Este e-mail já está cadastrado.';
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $conexao->prepare(
                    'INSERT INTO usuarios (nome, email, senha)
                     VALUES (:nome, :email, :senha)'
                );

                $stmt->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => $senhaHash
                ]);

                $sucesso = 'Conta criada com sucesso! Você já pode fazer login.';
                $nome = '';
                $email = '';
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $erro = 'Este e-mail já está cadastrado.';
            } else {
                $erro = 'Não foi possível criar sua conta. Tente novamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>B-Sync | Cadastro</title>

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
    <link rel="stylesheet" href="../assets/css/cadastro.css">
</head>

<body class="cadastro-page">

    <main class="cadastro-screen">

        <section class="cadastro-intro">

            <img
                src="../assets/img/login-livraria.png"
                alt="Interior da livraria"
                class="cadastro-image"
            >

            <div class="cadastro-intro-text">
                Organize Histórias<br>
                Conecte Pessoas
            </div>

        </section>

        <section class="cadastro-area">

            <div class="cadastro-content">

                <div class="cadastro-header">

                    <img
                        src="../assets/img/logo.png"
                        alt="B-Sync"
                        class="cadastro-logo"
                    >

                    <h2>Crie sua conta</h2>

                    <p>Preencha seus dados para começar.</p>

                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success" role="alert">
                        <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="cadastro-form" method="POST" action="cadastro.php">

                    <div class="form-group">

                        <label for="nome">Nome</label>

                        <div class="input-wrapper">

                            <i class="bi bi-person"></i>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                placeholder="Digite seu nome"
                                value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                                maxlength="100"
                                required
                            >

                        </div>

                    </div>

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
                                maxlength="150"
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
                                minlength="6"
                                required
                            >

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="confirmar-senha">Confirmar senha</label>

                        <div class="input-wrapper">

                            <i class="bi bi-lock"></i>

                            <input
                                type="password"
                                id="confirmar-senha"
                                name="confirmar-senha"
                                placeholder="Confirme sua senha"
                                minlength="6"
                                required
                            >

                        </div>

                    </div>

                    <button type="submit" class="cadastro-button">
                        Criar conta
                    </button>

                </form>

                <p class="login-link">
                    Já possui uma conta?
                    <a href="login.php">Faça login</a>
                </p>

                <a href="../index.html" class="back-home">
                    <i class="bi bi-arrow-left"></i>
                    Voltar para o início
                </a>

            </div>

        </section>

    </main>

</body>
</html>
