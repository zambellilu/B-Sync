<?php
require_once __DIR__ . '/../includes/protecao.php';
bsyncExigirAdmin();

require_once __DIR__ . '/../includes/conexao.php';

$statusValidos = ['pendente', 'confirmada', 'concluida', 'cancelada'];

$labels = [
    'pendente' => 'Pendente',
    'confirmada' => 'Confirmada',
    'concluida' => 'Concluída',
    'cancelada' => 'Cancelada'
];

function bsyncReservasEscapar($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusFiltro = $_GET['status'] ?? '';

if (!in_array($statusFiltro, $statusValidos, true)) {
    $statusFiltro = '';
}

$mensagem = '';
$erro = '';

/* ATUALIZAR RESERVA */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'reserva_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!bsyncValidarCSRF($token)) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif (!$id || !in_array($status, $statusValidos, true)) {
        $erro = 'Dados inválidos para atualização.';
    } else {
        try {
            $verificar = $conexao->prepare(
                'SELECT id FROM reservas WHERE id = :id'
            );
            $verificar->execute([':id' => $id]);

            if (!$verificar->fetch()) {
                $erro = 'A reserva informada não foi encontrada.';
            } else {
                $atualizar = $conexao->prepare(
                    'UPDATE reservas SET status = :status WHERE id = :id'
                );

                $atualizar->execute([
                    ':status' => $status,
                    ':id' => $id
                ]);

                $url = 'reservas.php?mensagem=atualizado';

                if ($statusFiltro !== '') {
                    $url .= '&status=' . urlencode($statusFiltro);
                }

                header('Location: ' . $url);
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erro = 'Não foi possível atualizar a reserva.';
        }
    }
}

if (($_GET['mensagem'] ?? '') === 'atualizado') {
    $mensagem = 'Status da reserva atualizado com sucesso!';
}

/* CONSULTAR RESERVAS E INDICADORES */

try {
    $sql = "
        SELECT
            r.id,
            r.status,
            r.data_reserva,
            u.nome AS usuario_nome,
            u.email AS usuario_email,
            l.titulo AS livro_titulo,
            l.autor AS livro_autor
        FROM reservas r
        LEFT JOIN usuarios u ON u.id = r.usuario_id
        LEFT JOIN livros l ON l.id = r.livro_id
    ";

    if ($statusFiltro !== '') {
        $sql .= " WHERE r.status = :status";
    }

    $sql .= " ORDER BY r.data_reserva DESC";

    $consulta = $conexao->prepare($sql);

    if ($statusFiltro !== '') {
        $consulta->bindValue(':status', $statusFiltro);
    }

    $consulta->execute();
    $reservas = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $indicadores = $conexao->query("
        SELECT
            COUNT(*) AS total,
            COALESCE(SUM(status = 'pendente'), 0) AS pendentes,
            COALESCE(SUM(status = 'confirmada'), 0) AS confirmadas,
            COALESCE(SUM(status = 'concluida'), 0) AS concluidas
        FROM reservas
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());

    $reservas = [];

    $indicadores = [
        'total' => 0,
        'pendentes' => 0,
        'confirmadas' => 0,
        'concluidas' => 0
    ];

    $erro = 'Não foi possível carregar as reservas.';
}

$nomeAdmin = $_SESSION['usuario_nome'] ?? 'Administrador';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Sync | Reservas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/reservas.css">
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
                <strong><?= bsyncReservasEscapar($nomeAdmin) ?></strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>
            <a href="livros.php"><i class="bi bi-book"></i><span>Livros</span></a>
            <a href="pedidos.php"><i class="bi bi-bag"></i><span>Pedidos</span></a>
            <a href="reservas.php" class="active"><i class="bi bi-calendar-check"></i><span>Reservas</span></a>
            <a href="usuarios.php"><i class="bi bi-people"></i><span>Usuários</span></a>
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
                <?= bsyncReservasEscapar($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <?= bsyncReservasEscapar($erro) ?>
            </div>
        <?php endif; ?>

        <header class="admin-header">
            <div>
                <span class="admin-page-label">GERENCIAMENTO</span>
                <h1>Reservas</h1>
                <p>Acompanhe e gerencie as reservas realizadas pelos usuários.</p>
            </div>
        </header>

        <section class="reservation-stats">
            <?php
            $cards = [
                ['bi-calendar-check', 'Total de reservas', 'total'],
                ['bi-clock-history', 'Pendentes', 'pendentes'],
                ['bi-check-circle', 'Confirmadas', 'confirmadas'],
                ['bi-bookmark-check', 'Concluídas', 'concluidas']
            ];

            foreach ($cards as [$icone, $titulo, $campo]):
            ?>
                <div class="reservation-stat">
                    <div class="reservation-stat-icon">
                        <i class="bi <?= $icone ?>"></i>
                    </div>
                    <div>
                        <span><?= $titulo ?></span>
                        <strong><?= (int)$indicadores[$campo] ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="reservation-toolbar">
            <div>
                <strong>Reservas cadastradas</strong>
                <div class="text-muted small">
                    <?= count($reservas) ?> resultado(s)
                </div>
            </div>

            <form method="GET" class="reservation-filter">
                <label for="filtroStatus">Filtrar:</label>

                <select name="status" id="filtroStatus" onchange="this.form.submit()">
                    <option value="">Todas as reservas</option>

                    <?php foreach ($statusValidos as $status): ?>
                        <option value="<?= $status ?>" <?= $statusFiltro === $status ? 'selected' : '' ?>>
                            <?= $labels[$status] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <section class="reservations-container">
            <div class="reservations-header">
                <h2>Histórico de reservas</h2>
                <p>Consulte os livros reservados, os clientes e atualize o andamento das reservas.</p>
            </div>

            <?php if (empty($reservas)): ?>
                <div class="reservation-empty">
                    <i class="bi bi-calendar-x"></i>
                    <strong>Nenhuma reserva encontrada.</strong>
                    <p class="mb-0">As reservas realizadas aparecerão aqui.</p>
                </div>
            <?php else: ?>

                <?php foreach ($reservas as $reserva): ?>
                    <article class="reservation-card">

                        <div class="reservation-top">
                            <div>
                                <div class="reservation-number">
                                    Reserva #<?= (int)$reserva['id'] ?>
                                </div>

                                <div class="reservation-date">
                                    <i class="bi bi-calendar3"></i>

                                    <?php
                                    $data = !empty($reserva['data_reserva'])
                                        ? strtotime($reserva['data_reserva'])
                                        : false;
                                    ?>

                                    <?= $data ? date('d/m/Y H:i', $data) : 'Data não informada' ?>
                                </div>
                            </div>

                            <div>
                                <div class="reservation-user">
                                    <i class="bi bi-person"></i>
                                    <?= bsyncReservasEscapar($reserva['usuario_nome'] ?: 'Usuário não encontrado') ?>
                                </div>

                                <div class="reservation-email">
                                    <?= bsyncReservasEscapar($reserva['usuario_email'] ?: 'E-mail não disponível') ?>
                                </div>
                            </div>
                        </div>

                        <div class="reservation-book">
                            <i class="bi bi-book"></i>

                            <div>
                                <strong>
                                    <?= bsyncReservasEscapar($reserva['livro_titulo'] ?: 'Livro não encontrado') ?>
                                </strong>

                                <span>
                                    <?= bsyncReservasEscapar($reserva['livro_autor'] ?: 'Autor não informado') ?>
                                </span>
                            </div>
                        </div>

                        <div class="reservation-bottom">
                            <span class="reservation-status <?= bsyncReservasEscapar($reserva['status']) ?>">
                                <?= bsyncReservasEscapar($labels[$reserva['status']] ?? $reserva['status']) ?>
                            </span>

                            <form method="POST" class="reservation-update">
                                <input type="hidden" name="csrf_token" value="<?= bsyncReservasEscapar(bsyncTokenCSRF()) ?>">
                                <input type="hidden" name="reserva_id" value="<?= (int)$reserva['id'] ?>">

                                <select name="status" aria-label="Novo status da reserva">
                                    <?php foreach ($statusValidos as $status): ?>
                                        <option value="<?= $status ?>" <?= $reserva['status'] === $status ? 'selected' : '' ?>>
                                            <?= $labels[$status] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit">
                                    <i class="bi bi-check2"></i> Atualizar
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
