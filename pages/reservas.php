<?php
require_once __DIR__ . '/../includes/protecao.php';
bsyncExigirUsuario();
require_once __DIR__ . '/../includes/conexao.php';

$usuarioId = (int) $_SESSION['usuario_id'];
$tokenCSRF = bsyncTokenCSRF();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $reservaId = filter_input(INPUT_POST, 'reserva_id', FILTER_VALIDATE_INT);

    if (!bsyncValidarCSRF($token)) {
        $erro = 'Solicitação inválida. Atualize a página e tente novamente.';
    } elseif (!$reservaId || $reservaId < 1) {
        $erro = 'Reserva inválida.';
    } else {
        try {
            $sql = "UPDATE reservas
                    SET status = 'cancelada'
                    WHERE id = :id
                    AND usuario_id = :usuario_id
                    AND status IN ('pendente', 'confirmada')";

            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                ':id' => $reservaId,
                ':usuario_id' => $usuarioId
            ]);

            if ($stmt->rowCount() > 0) {
                header('Location: reservas.php?resultado=cancelada');
                exit;
            }

            $erro = 'Não foi possível cancelar esta reserva. Ela pode já ter sido finalizada.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $erro = 'Não foi possível cancelar a reserva.';
        }
    }
}

if (($_GET['resultado'] ?? '') === 'cancelada') {
    $mensagem = 'Reserva cancelada com sucesso.';
} else {
    $mensagem = '';
}

try {
    $sql = "SELECT
                r.id,
                r.status,
                r.data_reserva,
                l.titulo,
                l.autor
            FROM reservas r
            INNER JOIN livros l ON l.id = r.livro_id
            WHERE r.usuario_id = :usuario_id
            ORDER BY r.data_reserva DESC";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([':usuario_id' => $usuarioId]);
    $reservas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $reservas = [];
    $erro = 'Não foi possível carregar suas reservas.';
}

$ativas = 0;
$aguardando = 0;
$concluidas = 0;

foreach ($reservas as $reserva) {
    if ($reserva['status'] === 'confirmada') {
        $ativas++;
    } elseif ($reserva['status'] === 'pendente') {
        $aguardando++;
    } elseif ($reserva['status'] === 'concluida') {
        $concluidas++;
    }
}

function bsyncEscaparReserva($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$nomesStatus = [
    'pendente' => 'Aguardando',
    'confirmada' => 'Confirmada',
    'concluida' => 'Concluída',
    'cancelada' => 'Cancelada'
];

$classesStatus = [
    'pendente' => 'status-pending',
    'confirmada' => 'status-active',
    'concluida' => 'status-finished',
    'cancelada' => 'status-pending'
];
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
    <link rel="stylesheet" href="../assets/css/usuario.css">
</head>

<body class="user-page">

    <nav class="navbar navbar-expand-lg user-navbar">
        <div class="container">

            <a class="navbar-brand" href="../index.html">
                <img src="../assets/img/logo.png" alt="B-Sync">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto align-items-lg-center">

                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Início</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="reservas.php">Reservas</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="pedidos.php">Pedidos</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="perfil.php">Perfil</a>
                    </li>

                    <li class="nav-item ms-lg-3">
                        <a href="logout.php" class="btn btn-primary">Sair</a>
                    </li>

                </ul>
            </div>

        </div>
    </nav>

    <main class="user-content">
        <div class="container">

            <div class="user-title">
                <span>MINHAS RESERVAS</span>
                <h1>Livros reservados</h1>
                <p>Acompanhe suas reservas e consulte o status de cada livro.</p>
            </div>

            <?php if ($mensagem): ?>
                <div class="alert alert-success" role="alert">
                    <?= bsyncEscaparReserva($mensagem) ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert alert-danger" role="alert">
                    <?= bsyncEscaparReserva($erro) ?>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">

                <div class="col-md-4">
                    <div class="user-card">
                        <div class="user-icon">
                            <i class="bi bi-bookmark"></i>
                        </div>
                        <h3>Reservas ativas</h3>
                        <p><?= $ativas ?> <?= $ativas === 1 ? 'reserva confirmada' : 'reservas confirmadas' ?>.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="user-card">
                        <div class="user-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h3>Aguardando</h3>
                        <p><?= $aguardando ?> <?= $aguardando === 1 ? 'reserva aguardando' : 'reservas aguardando' ?>.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="user-card">
                        <div class="user-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3>Concluídas</h3>
                        <p><?= $concluidas ?> <?= $concluidas === 1 ? 'reserva finalizada' : 'reservas finalizadas' ?>.</p>
                    </div>
                </div>

            </div>

            <div class="user-table">
                <div class="p-4">

                    <h2 class="mb-3" style="font: 700 1.4rem 'Playfair Display', serif; color: #5A3E85;">
                        Minhas reservas
                    </h2>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Livro</th>
                                    <th>Data da reserva</th>
                                    <th>Status</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (empty($reservas)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            Você ainda não possui reservas.
                                            <a href="catalogo.php">Explorar catálogo</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservas as $reserva): ?>
                                        <?php
                                        $status = $reserva['status'];
                                        $nomeStatus = $nomesStatus[$status] ?? ucfirst($status);
                                        $classeStatus = $classesStatus[$status] ?? 'status-pending';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= bsyncEscaparReserva($reserva['titulo']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= bsyncEscaparReserva($reserva['autor']) ?></small>
                                            </td>

                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($reserva['data_reserva'])) ?>
                                            </td>

                                            <td>
                                                <span class="status <?= $classeStatus ?>">
                                                    <?= bsyncEscaparReserva($nomeStatus) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if (in_array($status, ['pendente', 'confirmada'], true)): ?>
                                                    <form method="POST" onsubmit="return confirm('Deseja realmente cancelar esta reserva?');">
                                                        <input type="hidden" name="csrf_token" value="<?= bsyncEscaparReserva($tokenCSRF) ?>">
                                                        <input type="hidden" name="reserva_id" value="<?= (int) $reserva['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Cancelar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        </div>
    </main>

    <footer class="user-footer">
        © 2026 B-Sync • Sistema de Gerenciamento para Livrarias
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
