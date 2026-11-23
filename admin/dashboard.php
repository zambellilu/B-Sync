<?php
require_once __DIR__ . '/../includes/protecao.php';
bsyncExigirAdmin();

require_once __DIR__ . '/../includes/conexao.php';

$nomeAdmin = $_SESSION['usuario_nome'] ?? 'Administrador';

$totalLivros = 0;
$totalPedidos = 0;
$totalReservas = 0;
$totalUsuarios = 0;
$atividades = [];
$erroBanco = false;

try {
    $totalLivros = (int) $conexao->query(
        "SELECT COUNT(*) FROM livros"
    )->fetchColumn();

    $totalPedidos = (int) $conexao->query(
        "SELECT COUNT(*) FROM pedidos"
    )->fetchColumn();

    $totalReservas = (int) $conexao->query(
        "SELECT COUNT(*) FROM reservas"
    )->fetchColumn();

    $totalUsuarios = (int) $conexao->query(
        "SELECT COUNT(*) FROM usuarios"
    )->fetchColumn();

    $sqlAtividades = "
        SELECT *
        FROM (
            SELECT
                'pedido' AS tipo,
                'Novo pedido realizado' AS titulo,
                CONCAT('Pedido #', p.id, ' · ', u.nome) AS descricao,
                p.data_pedido AS data_atividade
            FROM pedidos p
            INNER JOIN usuarios u ON u.id = p.usuario_id

            UNION ALL

            SELECT
                'reserva' AS tipo,
                'Nova reserva realizada' AS titulo,
                CONCAT(l.titulo, ' · ', u.nome) AS descricao,
                r.data_reserva AS data_atividade
            FROM reservas r
            INNER JOIN usuarios u ON u.id = r.usuario_id
            INNER JOIN livros l ON l.id = r.livro_id

            UNION ALL

            SELECT
                'usuario' AS tipo,
                'Novo usuário cadastrado' AS titulo,
                CONCAT(u.nome, ' realizou o cadastro') AS descricao,
                u.criado_em AS data_atividade
            FROM usuarios u
            WHERE u.tipo = 'usuario'
        ) AS atividades
        ORDER BY data_atividade DESC
        LIMIT 6
    ";

    $atividades = $conexao->query($sqlAtividades)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Erro ao carregar dashboard administrativo: ' . $e->getMessage());
    $erroBanco = true;
}

function bsyncTempoDecorrido($data)
{
    $timestamp = strtotime($data);

    if (!$timestamp) {
        return '';
    }

    $diferenca = max(0, time() - $timestamp);

    if ($diferenca < 60) {
        return 'Agora mesmo';
    }

    if ($diferenca < 3600) {
        $minutos = (int) floor($diferenca / 60);
        return 'Há ' . $minutos . ' min';
    }

    if ($diferenca < 86400) {
        $horas = (int) floor($diferenca / 3600);
        return 'Há ' . $horas . ($horas === 1 ? ' hora' : ' horas');
    }

    if ($diferenca < 604800) {
        $dias = (int) floor($diferenca / 86400);
        return 'Há ' . $dias . ($dias === 1 ? ' dia' : ' dias');
    }

    return date('d/m/Y', $timestamp);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>B-Sync | Dashboard Administrativo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
                <strong><?= htmlspecialchars($nomeAdmin, ENT_QUOTES, 'UTF-8') ?></strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <nav class="admin-menu">

            <a href="dashboard.php" class="active">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>

            <a href="livros.php">
                <i class="bi bi-book"></i>
                <span>Livros</span>
            </a>

            <a href="pedidos.php">
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

        <header class="admin-header">
            <div>
                <span class="admin-page-label">PAINEL ADMINISTRATIVO</span>
                <h1>Dashboard</h1>
                <p>Acompanhe o que está acontecendo na sua livraria.</p>
            </div>
        </header>

        <?php if ($erroBanco): ?>
            <div class="alert alert-danger">
                Não foi possível carregar todos os dados do painel. Verifique a conexão com o banco de dados.
            </div>
        <?php endif; ?>

        <section class="admin-stats">

            <div class="admin-stat-card">
                <div class="stat-icon purple">
                    <i class="bi bi-book"></i>
                </div>
                <div>
                    <span>Livros cadastrados</span>
                    <strong><?= $totalLivros ?></strong>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="stat-icon red">
                    <i class="bi bi-bag"></i>
                </div>
                <div>
                    <span>Pedidos</span>
                    <strong><?= $totalPedidos ?></strong>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="stat-icon purple">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <span>Reservas</span>
                    <strong><?= $totalReservas ?></strong>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="stat-icon red">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <span>Usuários</span>
                    <strong><?= $totalUsuarios ?></strong>
                </div>
            </div>

        </section>

        <section class="admin-dashboard-grid">

            <div class="admin-panel">

                <div class="panel-header">
                    <div>
                        <h2>Atividades recentes</h2>
                        <p>Últimas movimentações do sistema.</p>
                    </div>
                    <i class="bi bi-clock-history"></i>
                </div>

                <div class="activity-list">

                    <?php if (empty($atividades)): ?>

                        <div class="activity-item">
                            <div class="activity-icon purple">
                                <i class="bi bi-clock-history"></i>
                            </div>

                            <div>
                                <strong>Nenhuma atividade registrada</strong>
                                <span>As movimentações aparecerão aqui.</span>
                            </div>
                        </div>

                    <?php else: ?>

                        <?php foreach ($atividades as $atividade): ?>

                            <?php
                            $icone = 'bi-person-plus';
                            $cor = 'purple';

                            if ($atividade['tipo'] === 'pedido') {
                                $icone = 'bi-bag-check';
                            } elseif ($atividade['tipo'] === 'reserva') {
                                $icone = 'bi-calendar-plus';
                                $cor = 'red';
                            }
                            ?>

                            <div class="activity-item">

                                <div class="activity-icon <?= $cor ?>">
                                    <i class="bi <?= $icone ?>"></i>
                                </div>

                                <div>
                                    <strong>
                                        <?= htmlspecialchars($atividade['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                    </strong>

                                    <span>
                                        <?= htmlspecialchars($atividade['descricao'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>

                                <small>
                                    <?= htmlspecialchars(bsyncTempoDecorrido($atividade['data_atividade']), ENT_QUOTES, 'UTF-8') ?>
                                </small>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

            <div class="admin-panel">

                <div class="panel-header">
                    <div>
                        <h2>Acesso rápido</h2>
                        <p>Gerencie as principais áreas.</p>
                    </div>
                    <i class="bi bi-lightning"></i>
                </div>

                <div class="quick-actions">

                    <a href="livros.php" class="quick-action">
                        <i class="bi bi-book"></i>
                        <div>
                            <strong>Gerenciar livros</strong>
                            <span>Adicionar ou editar livros</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>

                    <a href="pedidos.php" class="quick-action">
                        <i class="bi bi-bag"></i>
                        <div>
                            <strong>Pedidos</strong>
                            <span>Visualizar pedidos</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>

                    <a href="reservas.php" class="quick-action">
                        <i class="bi bi-calendar-check"></i>
                        <div>
                            <strong>Reservas</strong>
                            <span>Acompanhar reservas</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>

                    <a href="usuarios.php" class="quick-action">
                        <i class="bi bi-people"></i>
                        <div>
                            <strong>Usuários</strong>
                            <span>Gerenciar usuários</span>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

<script src="../assets/js/script.js"></script>

</body>
</html>
