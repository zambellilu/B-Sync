

<?php
require_once __DIR__ . '/../includes/conexao.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: catalogo.php');
    exit;
}

$stmt = $conexao->prepare(
    'SELECT id, titulo, autor, categoria, preco, disponibilidade
     FROM livros
     WHERE id = :id'
);

$stmt->execute([':id' => $id]);
$livro = $stmt->fetch();

if (!$livro) {
    http_response_code(404);
}

function escapar($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$categoria = $livro['categoria'] ?? '';
$disponibilidade = $livro['disponibilidade'] ?? '';

$disponivel = in_array(
    mb_strtolower(trim($disponibilidade), 'UTF-8'),
    ['disponível', 'disponivel'],
    true
);

$preco = number_format((float) ($livro['preco'] ?? 0), 2, ',', '.');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $livro ? escapar($livro['titulo']) . ' | B-Sync' : 'Livro não encontrado | B-Sync' ?>
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/detalhes_livro.css">
</head>

<body class="detalhes-page">

    <!-- CABEÇALHO -->

    <nav class="navbar">
        <div class="container">

            <a class="navbar-brand" href="../index.html">
                <img src="../assets/img/logo.png" alt="B-Sync">
            </a>

            <div class="page-breadcrumb">
                <a href="../index.html">Início</a>
                <i class="bi bi-chevron-right"></i>
                <a href="catalogo.php">Catálogo</a>
                <i class="bi bi-chevron-right"></i>
                <span>Detalhes do livro</span>
            </div>

        </div>
    </nav>

    <!-- CONTEÚDO -->

    <main class="detalhes-content">

        <div class="container">

            <?php if (!$livro): ?>

                <section class="not-found">

                    <div class="not-found-icon">
                        <i class="bi bi-book"></i>
                    </div>

                    <span class="details-label">CATÁLOGO B-SYNC</span>

                    <h1>Livro não encontrado</h1>

                    <p>
                        Este livro pode ter sido removido ou não está mais disponível no catálogo.
                    </p>

                    <a href="catalogo.php" class="reserve-btn">
                        <i class="bi bi-arrow-left"></i>
                        Voltar ao catálogo
                    </a>

                </section>

            <?php else: ?>

                <!-- INTRODUÇÃO -->

                <section class="details-heading">

                    <span class="details-label">CATÁLOGO B-SYNC</span>

                    <h1>Conheça esta obra</h1>

                    <p>
                        Confira as informações e a disponibilidade deste livro.
                    </p>

                </section>

                <!-- DETALHES DO LIVRO -->

                <section class="book-panel">

                    <!-- CAPA -->

                    <div class="book-cover-area">

                        <div class="book-cover">

                            <div class="cover-decoration">
                                <i class="bi bi-book-half"></i>
                            </div>

                            <span>B-SYNC</span>

                        </div>

                        <span class="cover-caption">
                            Explore novas histórias
                        </span>

                    </div>

                    <!-- INFORMAÇÕES -->

                    <div class="book-details">

                        <div class="book-category">
                            <i class="bi bi-bookmark"></i>
                            <?= escapar($categoria) ?>
                        </div>

                        <h2>
                            <?= escapar($livro['titulo']) ?>
                        </h2>

                        <p class="book-author">
                            por <?= escapar($livro['autor']) ?>
                        </p>

                        <div class="book-description">
                            <p>
                                Confira as informações desta obra e sua disponibilidade no catálogo da B-Sync.
                            </p>
                        </div>

                        <div class="details-divider"></div>

                        <div class="book-data">

                            <div class="data-item">
                                <span>Categoria</span>
                                <strong><?= escapar($categoria) ?></strong>
                            </div>

                            <div class="data-item">
                                <span>Disponibilidade</span>

                                <strong class="status <?= $disponivel ? 'available' : 'unavailable' ?>">
                                    <i class="bi bi-circle-fill"></i>
                                    <?= escapar($disponibilidade) ?>
                                </strong>
                            </div>

                            <div class="data-item">
                                <span>Preço</span>
                                <strong>R$ <?= $preco ?></strong>
                            </div>

                        </div>

                        <div class="book-actions">

                            <?php if ($disponivel): ?>

                                <button class="reserve-btn" type="button">
                                    <i class="bi bi-bookmark-check"></i>
                                    Reservar livro
                                </button>

                            <?php else: ?>

                                <button class="reserve-btn" type="button" disabled>
                                    <i class="bi bi-bookmark-check"></i>
                                    Livro indisponível
                                </button>

                            <?php endif; ?>

                            <a href="catalogo.php" class="catalog-link">
                                Continuar explorando
                            </a>

                        </div>

                    </div>

                </section>

            <?php endif; ?>

        </div>

    </main>

    <!-- RODAPÉ -->

    <footer class="detalhes-footer">

        <div class="container">

            <p>
                © 2026 B-Sync • Sistema de Gerenciamento para Livrarias
            </p>

        </div>

    </footer>

</body>
</html>
