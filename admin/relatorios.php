<?php
require_once __DIR__ . '/../includes/protecao.php';
bsyncExigirAdmin();

require_once __DIR__ . '/../includes/conexao.php';

$nomeAdmin = $_SESSION['usuario_nome'] ?? 'Administrador';
$erro = '';

$indicadores = [
    'livros' => 0,
    'disponiveis' => 0,
    'usuarios' => 0,
    'administradores' => 0,
    'pedidos' => 0,
    'reservas' => 0,
    'concluidos' => 0,
    'faturamento' => 0
];

$labelsStatus = [
    'pendente' => 'Pendentes',
    'em_processamento' => 'Em processamento',
    'concluido' => 'Concluídos',
    'cancelado' => 'Cancelados'
];

$pedidosStatus = array_fill_keys(array_keys($labelsStatus), 0);
$categorias = [];

function bsyncRelatoriosEscapar($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function relatorioPercentual($valor, $total) {
    if ($total <= 0) {
        return 0;
    }

    return round(($valor / $total) * 100);
}

function relatorioMoeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

try {
    $contar = function ($sql) use ($conexao) {
        return (int)$conexao->query($sql)->fetchColumn();
    };

    /* INDICADORES GERAIS */

    $indicadores['livros'] = $contar(
        "SELECT COUNT(*) FROM livros"
    );

    $indicadores['disponiveis'] = $contar(
        "SELECT COUNT(*) FROM livros WHERE disponibilidade = 'disponivel'"
    );

    $indicadores['usuarios'] = $contar(
        "SELECT COUNT(*) FROM usuarios WHERE tipo = 'usuario'"
    );

    $indicadores['administradores'] = $contar(
        "SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin'"
    );

    $indicadores['pedidos'] = $contar(
        "SELECT COUNT(*) FROM pedidos"
    );

    $indicadores['reservas'] = $contar(
        "SELECT COUNT(*) FROM reservas"
    );

    $indicadores['concluidos'] = $contar(
        "SELECT COUNT(*) FROM pedidos WHERE status = 'concluido'"
    );

    /* FATURAMENTO DOS PEDIDOS CONCLUÍDOS */

    $stmt = $conexao->query("
        SELECT COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0)
        FROM itens_pedido ip
        INNER JOIN pedidos p ON p.id = ip.pedido_id
        WHERE p.status = 'concluido'
    ");

    $indicadores['faturamento'] = (float)$stmt->fetchColumn();

    /* PEDIDOS POR STATUS */

    $stmt = $conexao->query("
        SELECT status, COUNT(*) AS quantidade
        FROM pedidos
        GROUP BY status
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        if (isset($pedidosStatus[$linha['status']])) {
            $pedidosStatus[$linha['status']] = (int)$linha['quantidade'];
        }
    }

    /* LIVROS POR CATEGORIA */

    $stmt = $conexao->query("
        SELECT
            COALESCE(NULLIF(categoria, ''), 'Sem categoria') AS categoria,
            COUNT(*) AS quantidade
        FROM livros
        GROUP BY categoria
        ORDER BY quantidade DESC, categoria ASC
    ");

    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());

    $erro = 'Não foi possível carregar os relatórios. Verifique a conexão com o banco de dados.';
}

$totalPedidosStatus = array_sum($pedidosStatus);

$totalLivrosCategorias = array_sum(array_map(
    fn($categoria) => (int)$categoria['quantidade'],
    $categorias
));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Sync | Relatórios</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/relatorios.css">
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
                <strong><?= bsyncRelatoriosEscapar($nomeAdmin) ?></strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
            <a href="livros.php"><i class="bi bi-book"></i><span>Livros</span></a>
            <a href="pedidos.php"><i class="bi bi-bag"></i><span>Pedidos</span></a>
            <a href="reservas.php"><i class="bi bi-calendar-check"></i><span>Reservas</span></a>
            <a href="usuarios.php"><i class="bi bi-people"></i><span>Usuários</span></a>
            <a href="relatorios.php" class="active"><i class="bi bi-bar-chart"></i><span>Relatórios</span></a>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="../index.html"><i class="bi bi-house"></i><span>Voltar ao início</span></a>
            <a href="../pages/logout.php"><i class="bi bi-box-arrow-left"></i><span>Sair</span></a>
        </div>
    </aside>

    <main class="admin-main">

        <header class="admin-header">
            <div>
                <span class="admin-page-label">VISÃO GERAL</span>
                <h1>Relatórios</h1>
                <p>Acompanhe os dados e indicadores gerais da biblioteca.</p>
            </div>
        </header>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <?= bsyncRelatoriosEscapar($erro) ?>
            </div>
        <?php else: ?>

            <section class="report-stats">

                <article class="report-stat">
                    <div class="report-stat-icon purple">
                        <i class="bi bi-book"></i>
                    </div>
                    <div>
                        <span>Livros cadastrados</span>
                        <strong><?= $indicadores['livros'] ?></strong>
                        <small><?= $indicadores['disponiveis'] ?> disponíveis</small>
                    </div>
                </article>

                <article class="report-stat">
                    <div class="report-stat-icon red">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <span>Usuários cadastrados</span>
                        <strong><?= $indicadores['usuarios'] ?></strong>
                        <small><?= $indicadores['administradores'] ?> administrador(es)</small>
                    </div>
                </article>

                <article class="report-stat">
                    <div class="report-stat-icon blue">
                        <i class="bi bi-bag"></i>
                    </div>
                    <div>
                        <span>Total de pedidos</span>
                        <strong><?= $indicadores['pedidos'] ?></strong>
                        <small><?= $indicadores['concluidos'] ?> concluídos</small>
                    </div>
                </article>

                <article class="report-stat">
                    <div class="report-stat-icon green">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <span>Faturamento concluído</span>
                        <strong><?= relatorioMoeda($indicadores['faturamento']) ?></strong>
                        <small>Pedidos concluídos</small>
                    </div>
                </article>

            </section>

            <section class="report-panels">

                <article class="report-panel">
                    <div class="report-panel-header">
                        <div>
                            <h2>Pedidos por status</h2>
                            <p>Distribuição dos pedidos cadastrados.</p>
                        </div>
                        <i class="bi bi-clipboard-data"></i>
                    </div>

                    <div class="report-bars">
                        <?php if ($totalPedidosStatus > 0): ?>

                            <?php foreach ($labelsStatus as $status => $label): ?>
                                <?php
                                $quantidade = $pedidosStatus[$status];
                                $percentual = relatorioPercentual(
                                    $quantidade,
                                    $totalPedidosStatus
                                );
                                ?>

                                <div class="report-bar-item">
                                    <div class="report-bar-info">
                                        <span><?= $label ?></span>
                                        <strong><?= $quantidade ?></strong>
                                    </div>

                                    <div class="report-bar-track">
                                        <div
                                            class="report-bar-fill <?= $status ?>"
                                            style="width: <?= $percentual ?>%"
                                        ></div>
                                    </div>

                                    <small><?= $percentual ?>%</small>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="report-empty">
                                <i class="bi bi-inbox"></i>
                                <p>Ainda não existem pedidos cadastrados.</p>
                            </div>

                        <?php endif; ?>
                    </div>
                </article>

                <article class="report-panel">
                    <div class="report-panel-header">
                        <div>
                            <h2>Livros por categoria</h2>
                            <p>Quantidade de livros em cada categoria.</p>
                        </div>
                        <i class="bi bi-bookmarks"></i>
                    </div>

                    <?php if ($categorias): ?>

                        <div class="report-bars">
                            <?php foreach ($categorias as $categoria): ?>
                                <?php
                                $quantidade = (int)$categoria['quantidade'];
                                $percentual = relatorioPercentual(
                                    $quantidade,
                                    $totalLivrosCategorias
                                );
                                ?>

                                <div class="report-bar-item">
                                    <div class="report-bar-info">
                                        <span><?= bsyncRelatoriosEscapar($categoria['categoria']) ?></span>
                                        <strong><?= $quantidade ?></strong>
                                    </div>

                                    <div class="report-bar-track">
                                        <div
                                            class="report-bar-fill categoria"
                                            style="width: <?= $percentual ?>%"
                                        ></div>
                                    </div>

                                    <small><?= $percentual ?>%</small>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>

                        <div class="report-empty">
                            <i class="bi bi-book"></i>
                            <p>Ainda não existem livros cadastrados.</p>
                        </div>

                    <?php endif; ?>
                </article>

            </section>

            <section class="report-summary">
                <div>
                    <i class="bi bi-calendar-check"></i>
                    <span>Total de reservas</span>
                    <strong><?= $indicadores['reservas'] ?></strong>
                </div>

                <div>
                    <i class="bi bi-check-circle"></i>
                    <span>Pedidos concluídos</span>
                    <strong><?= $indicadores['concluidos'] ?></strong>
                </div>

                <div>
                    <i class="bi bi-book-half"></i>
                    <span>Livros disponíveis</span>
                    <strong><?= $indicadores['disponiveis'] ?></strong>
                </div>
            </section>

        <?php endif; ?>

    </main>
</div>
</body>
</html>
