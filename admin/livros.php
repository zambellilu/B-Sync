

<?php
require_once __DIR__ . '/../includes/conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $autor = trim($_POST['autor'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $preco = $_POST['preco'] ?? '';
    $disponibilidade = $_POST['disponibilidade'] ?? 'disponivel';

    if (
        $titulo === '' ||
        $autor === '' ||
        $categoria === '' ||
        !is_numeric($preco) ||
        (float) $preco < 0 ||
        !in_array($disponibilidade, ['disponivel', 'indisponivel'], true)
    ) {
        $erro = 'Preencha os campos corretamente.';
    } else {
        $sql = 'INSERT INTO livros
                (titulo, autor, categoria, preco, disponibilidade)
                VALUES (:titulo, :autor, :categoria, :preco, :disponibilidade)';

        $stmt = $conexao->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':autor' => $autor,
            ':categoria' => $categoria,
            ':preco' => $preco,
            ':disponibilidade' => $disponibilidade
        ]);

        header('Location: livros.php?sucesso=1');
        exit;
    }
}

if (isset($_GET['sucesso'])) {
    $mensagem = 'Livro cadastrado com sucesso!';
}

$consulta = $conexao->query(
    'SELECT id, titulo, autor, categoria, preco, disponibilidade
     FROM livros
     ORDER BY titulo'
);

$livros = $consulta->fetchAll();
?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Sync | Livros</title>

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body data-tipo="admin">

<div class="admin-layout">

    <!-- =========================
         MENU LATERAL
    ========================== -->
    <aside class="admin-sidebar">

        <a href="dashboard.html" class="admin-logo">
            <img src="../assets/img/logo.png" alt="B-Sync">
        </a>

        <div class="admin-profile">
            <div class="admin-profile-icon">
                <i class="bi bi-person"></i>
            </div>
            <div>
                <strong>Administrador</strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.html">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>

            <a href="livros.html" class="active">
                <i class="bi bi-book"></i>
                <span>Livros</span>
            </a>

            <a href="pedidos.html">
                <i class="bi bi-bag"></i>
                <span>Pedidos</span>
            </a>

            <a href="reservas.html">
                <i class="bi bi-calendar-check"></i>
                <span>Reservas</span>
            </a>

            <a href="usuarios.html">
                <i class="bi bi-people"></i>
                <span>Usuários</span>
            </a>

            <a href="relatorios.html">
                <i class="bi bi-bar-chart"></i>
                <span>Relatórios</span>
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="../index.html">
                <i class="bi bi-house"></i>
                <span>Voltar ao início</span>
            </a>

            <a href="../pages/login.html">
                <i class="bi bi-box-arrow-left"></i>
                <span>Sair</span>
            </a>
        </div>

    </aside>


    <!-- =========================
         CONTEÚDO PRINCIPAL
    ========================== -->
    <main class="admin-main">

    
<?php if ($mensagem !== ''): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($mensagem) ?>
    </div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>


        <!-- CABEÇALHO -->
        <header class="admin-header">
            <div>
                <span class="admin-page-label">GERENCIAMENTO</span>
                <h1>Livros</h1>
                <p>Gerencie o catálogo da sua livraria.</p>
            </div>

            <button class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#modalLivro">
                <i class="bi bi-plus-lg"></i>
                Adicionar livro
            </button>
        </header>


        <!-- BUSCA -->
        <section class="book-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Buscar por título ou autor...">
        </section>


        <!-- LIVROS CADASTRADOS -->
        

<section class="books-container">
    <div class="books-header">
        <div>
            <h2>Livros cadastrados</h2>
            <p>Consulte os livros disponíveis no catálogo.</p>
        </div>
        <span><?= count($livros) ?> livros</span>
    </div>

    <?php if (empty($livros)): ?>
        <p>Nenhum livro cadastrado.</p>
    <?php else: ?>
        <?php foreach ($livros as $livro): ?>
            <article class="book-item">
                <div class="book-cover">
                    <i class="bi bi-book"></i>
                </div>

                <div class="book-info">
                    <h3><?= htmlspecialchars($livro['titulo']) ?></h3>
                    <p><?= htmlspecialchars($livro['autor']) ?></p>
                    <span class="book-category">
                        <?= htmlspecialchars($livro['categoria']) ?>
                    </span>
                </div>

                <div class="book-price">
                    R$ <?= number_format((float) $livro['preco'], 2, ',', '.') ?>
                </div>

                <span class="book-status <?= $livro['disponibilidade'] === 'disponivel' ? 'available' : 'unavailable' ?>">
                    <?= $livro['disponibilidade'] === 'disponivel' ? 'Disponível' : 'Indisponível' ?>
                </span>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

    </main>
</div>


<!-- =========================
     MODAL | ADICIONAR LIVRO
========================= -->
<div class="modal fade" id="modalLivro" tabindex="-1">

    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Adicionar livro</h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>

            <form method="POST" action="livros.php">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="titulo" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor</label>
                        <input type="text" name="autor" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        

                        <select name="categoria" class="form-select" required>
                            <option value="">Selecione</option>
                            <option>Literatura</option>
                            <option>Clássico</option>
                            <option>Fantasia</option>
                            <option>Romance</option>
                            <option>Suspense</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Preço</label>
                        <input type="number" name="preco" class="form-control" step="0.01" min="0" required>
                    </div>

                    <div>
                        <label class="form-label">Disponibilidade</label>

                        <select name="disponibilidade" class="form-select" required>
                            <option value="disponivel">Disponível</option>
                            <option value="indisponivel">Indisponível</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Cadastrar livro
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>


<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>

</body>
</html>