<?php
require_once __DIR__ . '/../includes/protecao.php';
bsyncExigirAdmin();

require_once __DIR__ . '/../includes/conexao.php';

function bsyncPedidosEscapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function bsyncPedidosValor($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

$statusValidos = ['pendente', 'em_processamento', 'concluido', 'cancelado'];

$labels = [
    'pendente' => 'Pendente',
    'em_processamento' => 'Em processamento',
    'concluido' => 'Concluído',
    'cancelado' => 'Cancelado'
];

$statusFiltro = $_GET['status'] ?? '';
$mensagem = '';
$erro = '';
$tokenCSRF = bsyncTokenCSRF();

if (!in_array($statusFiltro, $statusValidos, true)) {
    $statusFiltro = '';
}

/* ATUALIZAR STATUS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    if (!bsyncValidarCSRF($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (!$id || $id <= 0 || !in_array($status, $statusValidos, true)) {
        $erro = 'Dados inválidos para atualização.';
    } else {
        try {
            $verificar = $conexao->prepare(
                'SELECT id FROM pedidos WHERE id = :id'
            );
            $verificar->execute([':id' => $id]);

            if (!$verificar->fetch()) {
                $erro = 'O pedido informado não foi encontrado.';
            } else {
                $atualizar = $conexao->prepare(
                    'UPDATE pedidos SET status = :status WHERE id = :id'
                );

                $atualizar->execute([
                    ':status' => $status,
                    ':id' => $id
                ]);

                header('Location: pedidos.php?mensagem=atualizado');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Erro ao atualizar pedido: ' . $e->getMessage());
            $erro = 'Não foi possível atualizar o pedido.';
        }
    }
}

if (($_GET['mensagem'] ?? '') === 'atualizado') {
    $mensagem = 'Status do pedido atualizado com sucesso!';
}

/* CARREGAR PEDIDOS */
try {
    $sql = "
        SELECT
            p.id,
            p.status,
            p.data_pedido,
            u.nome AS usuario_nome,
            u.email AS usuario_email,
            COALESCE(SUM(ip.quantidade), 0) AS total_livros,
            COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0) AS valor_total
        FROM pedidos p
        INNER JOIN usuarios u ON u.id = p.usuario_id
        LEFT JOIN itens_pedido ip ON ip.pedido_id = p.id
    ";

    if ($statusFiltro !== '') {
        $sql .= " WHERE p.status = :status";
    }

    $sql .= "
        GROUP BY
            p.id,
            p.status,
            p.data_pedido,
            u.nome,
            u.email
        ORDER BY p.data_pedido DESC
    ";

    $consulta = $conexao->prepare($sql);

    if ($statusFiltro !== '') {
        $consulta->bindValue(':status', $statusFiltro);
    }

    $consulta->execute();
    $pedidos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    /* CARREGAR ITENS DOS PEDIDOS */
    $itensConsulta = $conexao->query("
        SELECT
            ip.pedido_id,
            ip.quantidade,
            ip.preco_unitario,
            COALESCE(l.titulo, 'Livro não encontrado') AS titulo
        FROM itens_pedido ip
        LEFT JOIN livros l ON l.id = ip.livro_id
        ORDER BY l.titulo
    ");

    $itens = [];

    foreach ($itensConsulta->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $itens[$item['pedido_id']][] = $item;
    }

    /* INDICADORES */
    $indicadores = $conexao->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(status = 'pendente'), 0) AS pendentes,
            COALESCE(SUM(status = 'em_processamento'), 0) AS processando,
            COALESCE(SUM(status = 'concluido'), 0) AS concluidos
        FROM pedidos
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Erro ao carregar pedidos: ' . $e->getMessage());

    $pedidos = [];
    $itens = [];

    $indicadores = [
        'total' => 0,
        'pendentes' => 0,
        'processando' => 0,
        'concluidos' => 0
    ];

    $erro = 'Não foi possível carregar os pedidos.';
}

$nomeAdmin = $_SESSION['usuario_nome'] ?? 'Administrador';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Sync | Pedidos</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/pedidos.css">
</head>

<body data-tipo="admin">

<div class="admin-layout">

    <aside class="admin-sidebar">

        <a href="dashboard.php" class="admin-logo">
            <img src="../assets/img/logo.png" alt="B-Sync">
        </a>

        <div class="admin-profile">
            <div class="admin-profile-icon">
                <i class="bi bi-person"></i>
            </div>
            <div>
                <strong><?= bsyncPedidosEscapar($nomeAdmin) ?></strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>

            <a href="livros.php">
                <i class="bi bi-book"></i>
                <span>Livros</span>
            </a>

            <a href="pedidos.php" class="active">
                <i class="bi bi-bag"></i>
                <span>Pedidos</span>
            </a>

            <a href="reservas.php">
                <i class="bi bi-calendar-check"></i>
                <span>Reservas</span>
            </a>

            <a href="usuarios.php">
                <i class="bi bi-people"></i>
                <span>Usuários</span>
            </a>

            <a href="relatorios.php">
                <i class="bi bi-bar-chart"></i>
                <span>Relatórios</span>
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="../index.html">
                <i class="bi bi-house"></i>
                <span>Voltar ao início</span>
            </a>

            <a href="../pages/logout.php">
                <i class="bi bi-box-arrow-left"></i>
                <span>Sair</span>
            </a>
        </div>

    </aside>

    <main class="admin-main">

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-success">
                <?= bsyncPedidosEscapar($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger">
                <?= bsyncPedidosEscapar($erro) ?>
            </div>
        <?php endif; ?>

        <header class="admin-header">
            <div>
                <span class="admin-page-label">GERENCIAMENTO</span>
                <h1>Pedidos</h1>
                <p>Acompanhe e gerencie os pedidos realizados pelos usuários.</p>
            </div>
        </header>

        <section class="order-stats">

            <?php
            $cards = [
                ['bi-bag', 'Total de pedidos', 'total'],
                ['bi-clock-history', 'Pendentes', 'pendentes'],
                ['bi-arrow-repeat', 'Em processamento', 'processando'],
                ['bi-check-circle', 'Concluídos', 'concluidos']
            ];

            foreach ($cards as [$icone, $titulo, $campo]):
            ?>

                <div class="order-stat">
                    <div class="order-stat-icon">
                        <i class="bi <?= $icone ?>"></i>
                    </div>

                    <div>
                        <span><?= $titulo ?></span>
                        <strong><?= (int) $indicadores[$campo] ?></strong>
                    </div>
                </div>

            <?php endforeach; ?>

        </section>

        <div class="order-toolbar">

            <div>
                <strong>Pedidos cadastrados</strong>
                <div class="text-muted small">
                    <?= count($pedidos) ?> resultado(s)
                </div>
            </div>

            <form method="GET" class="order-filter">

                <label for="filtroStatus">Filtrar:</label>

                <select name="status" id="filtroStatus" onchange="this.form.submit()">
                    <option value="">Todos os pedidos</option>

                    <?php foreach ($statusValidos as $status): ?>
                        <option value="<?= bsyncPedidosEscapar($status) ?>"
                            <?= $statusFiltro === $status ? 'selected' : '' ?>>
                            <?= bsyncPedidosEscapar($labels[$status]) ?>
                        </option>
                    <?php endforeach; ?>

                </select>

            </form>

        </div>

        <section class="orders-container">

            <div class="orders-header">
                <h2>Histórico de pedidos</h2>
                <p>Consulte os itens, os clientes e atualize o andamento de cada pedido.</p>
            </div>

            <?php if (empty($pedidos)): ?>

                <div class="order-empty">
                    <i class="bi bi-bag-x"></i>
                    <strong>Nenhum pedido encontrado.</strong>
                    <p class="mb-0">Os pedidos realizados aparecerão aqui.</p>
                </div>

            <?php else: ?>

                <?php foreach ($pedidos as $pedido): ?>

                    <article class="order-card">

                        <div class="order-top">

                            <div>
                                <div class="order-number">
                                    Pedido #<?= (int) $pedido['id'] ?>
                                </div>

                                <div class="order-date">
                                    <i class="bi bi-calendar3"></i>

                                    <?= !empty($pedido['data_pedido'])
                                        ? date('d/m/Y H:i', strtotime($pedido['data_pedido']))
                                        : 'Data não informada' ?>
                                </div>
                            </div>

                            <div>
                                <div class="order-user">
                                    <i class="bi bi-person"></i>
                                    <?= bsyncPedidosEscapar($pedido['usuario_nome']) ?>
                                </div>

                                <div class="order-email">
                                    <?= bsyncPedidosEscapar($pedido['usuario_email']) ?>
                                </div>
                            </div>

                        </div>

                        <div class="order-items">

                            <?php if (!empty($itens[$pedido['id']])): ?>

                                <?php foreach ($itens[$pedido['id']] as $item): ?>

                                    <div class="order-book">

                                        <span>
                                            <?= bsyncPedidosEscapar($item['titulo']) ?>
                                            × <?= (int) $item['quantidade'] ?>
                                        </span>

                                        <strong>
                                            <?= bsyncPedidosValor(
                                                $item['quantidade'] * $item['preco_unitario']
                                            ) ?>
                                        </strong>

                                    </div>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <span class="text-muted small">
                                    Nenhum livro associado a este pedido.
                                </span>

                            <?php endif; ?>

                        </div>

                        <div class="order-total">

                            <span>
                                <?= (int) $pedido['total_livros'] ?> livro(s) no pedido
                            </span>

                            <strong>
                                <?= bsyncPedidosValor($pedido['valor_total']) ?>
                            </strong>

                        </div>

                        <div class="order-bottom">

                            <span class="order-status <?= bsyncPedidosEscapar($pedido['status']) ?>">
                                <?= bsyncPedidosEscapar(
                                    $labels[$pedido['status']] ?? $pedido['status']
                                ) ?>
                            </span>

                            <form method="POST" action="pedidos.php" class="order-update">

                                <input type="hidden" name="csrf_token"
                                       value="<?= bsyncPedidosEscapar($tokenCSRF) ?>">

                                <input type="hidden" name="pedido_id"
                                       value="<?= (int) $pedido['id'] ?>">

                                <select name="status" aria-label="Novo status do pedido">
                                    <?php foreach ($statusValidos as $status): ?>
                                        <option value="<?= bsyncPedidosEscapar($status) ?>"
                                            <?= $pedido['status'] === $status ? 'selected' : '' ?>>
                                            <?= bsyncPedidosEscapar($labels[$status]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit">
                                    <i class="bi bi-check2"></i>
                                    Atualizar
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
