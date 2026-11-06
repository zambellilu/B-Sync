
<?php
require_once __DIR__ . '/../includes/conexao.php';

$consulta = $conexao->query(
    'SELECT id, titulo, autor, categoria, preco, disponibilidade
     FROM livros
     ORDER BY titulo'
);

$livros = $consulta->fetchAll();

function normalizarCategoria($categoria) {
    $categoria = strtolower(trim($categoria));

    return strtr($categoria, [
        'á' => 'a',
        'à' => 'a',
        'ã' => 'a',
        'â' => 'a',
        'é' => 'e',
        'ê' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ô' => 'o',
        'õ' => 'o',
        'ú' => 'u',
        'ç' => 'c'
    ]);
}

$categorias = [];

foreach ($livros as $livro) {
    $categorias[$livro['categoria']] = normalizarCategoria($livro['categoria']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B-Sync | Catálogo</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/catalogo.css">
</head>

<body class="catalogo-page">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg shadow-sm sticky-top">
        <div class="container">

            <a class="navbar-brand" href="../index.html">
                <img src="../assets/img/logo.png" alt="B-Sync">
            </a>

            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menu"
                aria-controls="menu"
                aria-expanded="false"
                aria-label="Abrir menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto align-items-lg-center">

                    <li class="nav-item">
                        <a class="nav-link" href="../index.html">Início</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="catalogo.php">Catálogo</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../index.html#sobre">Sobre</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../index.html#contato">Contato</a>
                    </li>

                    <li class="nav-item ms-lg-3">
                        <a href="login.html" class="btn btn-primary">Entrar</a>
                    </li>

                </ul>
            </div>

        </div>
    </nav>

    <!-- CABEÇALHO -->
    <section class="catalogo-header">
        <div class="container">

            <span class="catalogo-label">NOSSO CATÁLOGO</span>

            <h1>Encontre sua próxima história</h1>

            <p>
                Explore nosso catálogo e encontre livros para
                diferentes momentos, estilos e interesses.
            </p>

        </div>
    </section>

    <!-- CATÁLOGO -->
    <main class="catalogo-content">
        <div class="container">

            <!-- BUSCA E FILTRO -->
            <div class="catalogo-tools">

                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        id="searchBook"
                        placeholder="Buscar por título ou autor...">
                </div>

                <div class="category-box">
                    <select id="categoryFilter">
                        <option value="todos">Todas as categorias</option>

                        <?php foreach ($categorias as $nome => $categoria): ?>
                            <option value="<?= htmlspecialchars($categoria) ?>">
                                <?= htmlspecialchars($nome) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

            <!-- LIVROS -->
            <div class="row g-4 catalogo-grid" id="listaLivros">

                <?php if (empty($livros)): ?>

                    <div class="col-12">
                        <div class="no-results" style="display: block;">
                            <i class="bi bi-book"></i>
                            <p>Nenhum livro cadastrado no catálogo.</p>
                        </div>
                    </div>

                <?php else: ?>

                    <?php foreach ($livros as $livro): ?>

                        <div class="col-md-6 col-lg-4 book-item"
                            data-category="<?= htmlspecialchars(normalizarCategoria($livro['categoria'])) ?>"
                            data-title="<?= htmlspecialchars($livro['titulo']) ?>"
                            data-author="<?= htmlspecialchars($livro['autor']) ?>">

                            <div class="catalogo-card">

                                <div class="catalogo-cover">
                                    <span><?= htmlspecialchars($livro['categoria']) ?></span>
                                </div>

                                <div class="catalogo-card-body">

                                    <h2><?= htmlspecialchars($livro['titulo']) ?></h2>

                                    <p class="author">
                                        <?= htmlspecialchars($livro['autor']) ?>
                                    </p>

                                    <p class="description">
                                        Categoria: <?= htmlspecialchars($livro['categoria']) ?>
                                    </p>

                                    <div class="book-info">

                                        <div>
                                            <strong>
                                                R$ <?= number_format((float) $livro['preco'], 2, ',', '.') ?>
                                            </strong>

                                            <small class="d-block">
                                                <?= $livro['disponibilidade'] === 'disponivel' ? 'Disponível' : 'Indisponível' ?>
                                            </small>
                                        </div>

                                        <a href="detalhes-livro.php?id=<?= (int) $livro['id'] ?>"
                                            class="btn btn-primary">
                                            Ver detalhes
                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

            <!-- NENHUM RESULTADO -->
            <?php if (!empty($livros)): ?>
                <div id="noResults" class="no-results" style="display: none;">
                    <i class="bi bi-search"></i>
                    <p>Nenhum livro encontrado.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- RODAPÉ -->
    <footer class="catalogo-footer">
        <div class="container text-center">
            <p>© 2026 B-Sync • Sistema de Gerenciamento para Livrarias</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <!-- BUSCA E FILTRO -->
    <script>
        const searchInput = document.getElementById("searchBook");
        const categoryFilter = document.getElementById("categoryFilter");
        const books = document.querySelectorAll(".book-item");
        const noResults = document.getElementById("noResults");

        function filterBooks() {
            const search = searchInput.value.toLowerCase();
            const category = categoryFilter.value;
            let visibleBooks = 0;

            books.forEach(book => {
                const title = book.dataset.title.toLowerCase();
                const author = book.dataset.author.toLowerCase();
                const bookCategory = book.dataset.category;

                const matchesSearch =
                    title.includes(search) || author.includes(search);

                const matchesCategory =
                    category === "todos" || bookCategory === category;

                const visible = matchesSearch && matchesCategory;

                book.style.display = visible ? "" : "none";

                if (visible) visibleBooks++;
            });

            if (noResults) {
                noResults.style.display =
                    visibleBooks === 0 ? "block" : "none";
            }
        }

        searchInput.addEventListener("input", filterBooks);
        categoryFilter.addEventListener("change", filterBooks);
    </script>

</body>
</html>
