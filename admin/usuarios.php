<?php
require_once __DIR__ . '/../includes/protecao.php';
bsyncExigirAdmin();

require_once __DIR__ . '/../includes/conexao.php';

$tiposValidos = ['usuario', 'admin'];
$tipoFiltro = $_GET['tipo'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$mensagem = '';
$erro = '';

if (!in_array($tipoFiltro, $tiposValidos, true)) {
    $tipoFiltro = '';
}

$adminId = (int)($_SESSION['usuario_id'] ?? 0);

function bsyncUsuariosEscapar($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/* ALTERAR TIPO DE ACESSO */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioId = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
    $novoTipo = $_POST['tipo'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!bsyncValidarCSRF($token)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (!$usuarioId || !in_array($novoTipo, $tiposValidos, true)) {
        $erro = 'Dados inválidos para atualização.';
    } elseif ($usuarioId === $adminId) {
        $erro = 'Você não pode alterar seu próprio nível de acesso.';
    } else {
        try {
            $conexao->beginTransaction();

            $consultaUsuario = $conexao->prepare(
                'SELECT id, tipo FROM usuarios WHERE id = ? FOR UPDATE'
            );
            $consultaUsuario->execute([$usuarioId]);

            $usuarioAlvo = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

            if (!$usuarioAlvo) {
                $erro = 'Usuário não encontrado.';
            } elseif ($usuarioAlvo['tipo'] === $novoTipo) {
                $erro = 'O usuário já possui esse nível de acesso.';
            } else {
                if ($usuarioAlvo['tipo'] === 'admin' && $novoTipo === 'usuario') {
                    $totalAdmins = (int)$conexao->query(
                        "SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin' FOR UPDATE"
                    )->fetchColumn();

                    if ($totalAdmins <= 1) {
                        $erro = 'Não é possível remover o acesso do único administrador.';
                    }
                }

                if ($erro === '') {
                    $atualizar = $conexao->prepare(
                        'UPDATE usuarios SET tipo = ? WHERE id = ?'
                    );
                    $atualizar->execute([$novoTipo, $usuarioId]);

                    $conexao->commit();

                    $url = 'usuarios.php?mensagem=atualizado';

                    if ($tipoFiltro !== '') {
                        $url .= '&tipo=' . urlencode($tipoFiltro);
                    }

                    if ($busca !== '') {
                        $url .= '&busca=' . urlencode($busca);
                    }

                    header('Location: ' . $url);
                    exit;
                }
            }

            $conexao->rollBack();

        } catch (PDOException $e) {
            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            error_log($e->getMessage());
            $erro = 'Não foi possível atualizar o usuário.';
        }
    }
}

if (($_GET['mensagem'] ?? '') === 'atualizado') {
    $mensagem = 'Nível de acesso atualizado com sucesso!';
}

/* CONSULTAR USUÁRIOS */

try {
    $condicoes = [];
    $parametros = [];

    if ($tipoFiltro !== '') {
        $condicoes[] = 'tipo = :tipo';
        $parametros[':tipo'] = $tipoFiltro;
    }

    if ($busca !== '') {
        $condicoes[] = '(nome LIKE :busca OR email LIKE :busca)';
        $parametros[':busca'] = '%' . $busca . '%';
    }

    $sql = "
        SELECT id, nome, email, tipo, criado_em
        FROM usuarios
    ";

    if ($condicoes) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }

    $sql .= ' ORDER BY criado_em DESC';

    $consulta = $conexao->prepare($sql);
    $consulta->execute($parametros);

    $usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $indicadores = $conexao->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(tipo = 'usuario'), 0) AS usuarios,
            COALESCE(SUM(tipo = 'admin'), 0) AS administradores
        FROM usuarios
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());

    $usuarios = [];

    $indicadores = [
        'total' => 0,
        'usuarios' => 0,
        'administradores' => 0
    ];

    $erro = 'Não foi possível carregar os usuários.';
}

$nomeAdmin = $_SESSION['usuario_nome'] ?? 'Administrador';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Sync | Usuários</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/usuarios.css">
</head>

<body data-tipo="admin">
<div class="admin-layout">

    <aside class="admin-sidebar">
        <a href="dashboard.php" class="admin-logo">
            <img src="../assets/img/logo.png" alt="B-Sync">
        </a>

        <div class="admin-profile">
            <div class="admin-profile-icon"><i class="bi bi-person"></i></div>
            <div>
                <strong><?= bsyncUsuariosEscapar($nomeAdmin) ?></strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
            <a href="livros.php"><i class="bi bi-book"></i><span>Livros</span></a>
            <a href="pedidos.php"><i class="bi bi-bag"></i><span>Pedidos</span></a>
            <a href="reservas.php"><i class="bi bi-calendar-check"></i><span>Reservas</span></a>
            <a href="usuarios.php" class="active"><i class="bi bi-people"></i><span>Usuários</span></a>
            <a href="relatorios.php"><i class="bi bi-bar-chart"></i><span>Relatórios</span></a>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="../index.html"><i class="bi bi-house"></i><span>Voltar ao início</span></a>
            <a href="../pages/logout.php"><i class="bi bi-box-arrow-left"></i><span>Sair</span></a>
        </div>
    </aside>

    <main class="admin-main">

        <?php if ($mensagem): ?>
            <div class="alert alert-success">
                <?= bsyncUsuariosEscapar($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <?= bsyncUsuariosEscapar($erro) ?>
            </div>
        <?php endif; ?>

        <header class="admin-header">
            <div>
                <span class="admin-page-label">GERENCIAMENTO</span>
                <h1>Usuários</h1>
                <p>Consulte os cadastros e gerencie os níveis de acesso.</p>
            </div>
        </header>

        <section class="user-stats">
            <?php
            $cards = [
                ['bi-people', 'Total de cadastros', 'total'],
                ['bi-person', 'Usuários', 'usuarios'],
                ['bi-shield-check', 'Administradores', 'administradores']
            ];

            foreach ($cards as [$icone, $titulo, $campo]):
            ?>
                <div class="user-stat">
                    <div class="user-stat-icon">
                        <i class="bi <?= $icone ?>"></i>
                    </div>
                    <div>
                        <span><?= $titulo ?></span>
                        <strong><?= (int)$indicadores[$campo] ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="user-toolbar">
            <div>
                <strong>Usuários cadastrados</strong>
                <div class="text-muted small">
                    <?= count($usuarios) ?> resultado(s)
                </div>
            </div>

            <form method="GET" class="user-filters">
                <input
                    type="search"
                    name="busca"
                    placeholder="Buscar nome ou e-mail"
                    value="<?= bsyncUsuariosEscapar($busca) ?>"
                    aria-label="Buscar usuário"
                >

                <select name="tipo" aria-label="Filtrar por tipo">
                    <option value="">Todos os acessos</option>
                    <option value="usuario" <?= $tipoFiltro === 'usuario' ? 'selected' : '' ?>>Usuários</option>
                    <option value="admin" <?= $tipoFiltro === 'admin' ? 'selected' : '' ?>>Administradores</option>
                </select>

                <button type="submit">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </form>
        </div>

        <section class="users-container">
            <div class="users-header">
                <h2>Lista de usuários</h2>
                <p>Consulte os dados dos usuários e seus níveis de acesso.</p>
            </div>

            <?php if (empty($usuarios)): ?>
                <div class="user-empty">
                    <i class="bi bi-person-x"></i>
                    <strong>Nenhum usuário encontrado.</strong>
                    <p class="mb-0">Tente alterar os filtros da pesquisa.</p>
                </div>
            <?php else: ?>

                <div class="users-table-wrapper">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>E-mail</th>
                                <th>Cadastro</th>
                                <th>Acesso</th>
                                <th>Gerenciar</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <strong><?= bsyncUsuariosEscapar($usuario['nome']) ?></strong>
                                        <small>#<?= (int)$usuario['id'] ?></small>
                                    </td>

                                    <td><?= bsyncUsuariosEscapar($usuario['email']) ?></td>

                                    <td>
                                        <?php
                                        $dataCadastro = !empty($usuario['criado_em'])
                                            ? strtotime($usuario['criado_em'])
                                            : false;
                                        ?>
                                        <?= $dataCadastro ? date('d/m/Y', $dataCadastro) : '—' ?>
                                    </td>

                                    <td>
                                        <span class="user-role <?= $usuario['tipo'] === 'admin' ? 'admin' : 'usuario' ?>">
                                            <?= $usuario['tipo'] === 'admin' ? 'Administrador' : 'Usuário' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ((int)$usuario['id'] === $adminId): ?>
                                            <span class="user-self">Sua conta</span>
                                        <?php else: ?>
                                            <form method="POST" class="user-update">
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= bsyncUsuariosEscapar(bsyncTokenCSRF()) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="usuario_id"
                                                    value="<?= (int)$usuario['id'] ?>"
                                                >

                                                <select
                                                    name="tipo"
                                                    aria-label="Nível de acesso de <?= bsyncUsuariosEscapar($usuario['nome']) ?>"
                                                >
                                                    <option value="usuario" <?= $usuario['tipo'] === 'usuario' ? 'selected' : '' ?>>
                                                        Usuário
                                                    </option>
                                                    <option value="admin" <?= $usuario['tipo'] === 'admin' ? 'selected' : '' ?>>
                                                        Administrador
                                                    </option>
                                                </select>

                                                <button type="submit" title="Salvar alteração">
                                                    <i class="bi bi-check2"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </section>

    </main>
</div>
</body>
</html>
