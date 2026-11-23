<?php
require_once __DIR__ . '/../includes/protecao.php';
bsyncExigirAdmin();

require_once __DIR__ . '/../includes/conexao.php';

$tokenCSRF = bsyncTokenCSRF();
$mensagem = '';
$erro = '';

function bsyncEscapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!bsyncValidarCSRF($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Solicitação inválida. Atualize a página e tente novamente.');
    }

    $acao = $_POST['acao'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    try {
        if ($acao === 'excluir') {
            if (!$id || $id <= 0) {
                $erro = 'Livro inválido.';
            } else {
                $stmt = $conexao->prepare('DELETE FROM livros WHERE id = :id');
                $stmt->execute([':id' => $id]);

                if ($stmt->rowCount() > 0) {
                    header('Location: livros.php?mensagem=' . urlencode('Livro excluído com sucesso!'));
                    exit;
                }

                $erro = 'Livro não encontrado.';
            }
        } elseif ($acao === 'cadastrar' || $acao === 'editar') {
            $titulo = trim($_POST['titulo'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $preco = trim($_POST['preco'] ?? '');
            $disponibilidade = $_POST['disponibilidade'] ?? '';

            $precoValido = preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $preco);

            if (
                $titulo === '' ||
                mb_strlen($titulo) > 200 ||
                $autor === '' ||
                mb_strlen($autor) > 150 ||
                $categoria === '' ||
                mb_strlen($categoria) > 100 ||
                !$precoValido ||
                !in_array($disponibilidade, ['disponivel', 'indisponivel'], true)
            ) {
                $erro = 'Preencha os campos corretamente. Confira os limites de caracteres e o preço.';
            } elseif ($acao === 'editar' && (!$id || $id <= 0)) {
                $erro = 'Livro inválido.';
            } else {
                if ($acao === 'editar') {
                    $verificar = $conexao->prepare('SELECT id FROM livros WHERE id = :id');
                    $verificar->execute([':id' => $id]);

                    if (!$verificar->fetch()) {
                        $erro = 'O livro que você tentou editar não foi encontrado.';
                    }
                }

                if ($erro === '') {
                    $dados = [
                        ':titulo' => $titulo,
                        ':autor' => $autor,
                        ':categoria' => $categoria,
                        ':preco' => number_format((float) $preco, 2, '.', ''),
                        ':disponibilidade' => $disponibilidade
                    ];

                    if ($acao === 'cadastrar') {
                        $sql = 'INSERT INTO livros
                                (titulo, autor, categoria, preco, disponibilidade)
                                VALUES
                                (:titulo, :autor, :categoria, :preco, :disponibilidade)';

                        $mensagemSucesso = 'Livro cadastrado com sucesso!';
                    } else {
                        $sql = 'UPDATE livros
                                SET titulo = :titulo,
                                    autor = :autor,
                                    categoria = :categoria,
                                    preco = :preco,
                                    disponibilidade = :disponibilidade
                                WHERE id = :id';

                        $dados[':id'] = $id;
                        $mensagemSucesso = 'Livro atualizado com sucesso!';
                    }

                    $stmt = $conexao->prepare($sql);
                    $stmt->execute($dados);

                    header('Location: livros.php?mensagem=' . urlencode($mensagemSucesso));
                    exit;
                }
            }
        } else {
            $erro = 'Ação inválida.';
        }
    } catch (PDOException $e) {
        error_log('Erro ao gerenciar livros: ' . $e->getMessage());

        if ($acao === 'excluir') {
            $erro = 'Não foi possível excluir este livro. Ele pode estar vinculado a pedidos ou reservas.';
        } else {
            $erro = 'Não foi possível salvar o livro. Tente novamente.';
        }
    }
}

if (isset($_GET['mensagem'])) {
    $mensagem = trim($_GET['mensagem']);
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
                <strong><?= bsyncEscapar($_SESSION['usuario_nome'] ?? 'Administrador') ?></strong>
                <small>Área administrativa</small>
            </div>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>

            <a href="livros.php" class="active">
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

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-success">
                <?= bsyncEscapar($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger">
                <?= bsyncEscapar($erro) ?>
            </div>
        <?php endif; ?>

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

        <section class="book-search">
            <i class="bi bi-search"></i>
            <input type="text" id="buscaLivros" placeholder="Buscar por título ou autor...">
        </section>

        <section class="books-container">

            <div class="books-header">
                <div>
                    <h2>Livros cadastrados</h2>
                    <p>Consulte os livros disponíveis no catálogo.</p>
                </div>
                <span><?= count($livros) ?> livros</span>
            </div>

            <?php if (empty($livros)): ?>

                <div class="empty-books">
                    <i class="bi bi-book"></i>
                    <p>Nenhum livro cadastrado.</p>
                    <span>Adicione um livro para começar a organizar seu catálogo.</span>
                </div>

            <?php else: ?>

                <div id="listaLivros">

                    <?php foreach ($livros as $livro): ?>

                        <article class="book-item">

                            <div class="book-cover">
                                <i class="bi bi-book"></i>
                            </div>

                            <div class="book-info">
                                <h3><?= bsyncEscapar($livro['titulo']) ?></h3>
                                <p><?= bsyncEscapar($livro['autor']) ?></p>
                                <span class="book-category">
                                    <?= bsyncEscapar($livro['categoria']) ?>
                                </span>
                            </div>

                            <div class="book-price">
                                R$ <?= number_format((float) $livro['preco'], 2, ',', '.') ?>
                            </div>

                            <span class="book-status <?= $livro['disponibilidade'] === 'disponivel' ? 'available' : 'unavailable' ?>">
                                <?= $livro['disponibilidade'] === 'disponivel' ? 'Disponível' : 'Indisponível' ?>
                            </span>

                            <div class="book-actions">

                                <button type="button"
                                        class="btn btn-sm btn-outline-primary btn-editar"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditar"
                                        data-id="<?= (int) $livro['id'] ?>"
                                        data-titulo="<?= bsyncEscapar($livro['titulo']) ?>"
                                        data-autor="<?= bsyncEscapar($livro['autor']) ?>"
                                        data-categoria="<?= bsyncEscapar($livro['categoria']) ?>"
                                        data-preco="<?= bsyncEscapar($livro['preco']) ?>"
                                        data-disponibilidade="<?= bsyncEscapar($livro['disponibilidade']) ?>">
                                    <i class="bi bi-pencil"></i>
                                    Editar
                                </button>

                                <form method="POST" action="livros.php"
                                      onsubmit="return confirm('Tem certeza de que deseja excluir este livro?');">

                                    <input type="hidden" name="csrf_token" value="<?= bsyncEscapar($tokenCSRF) ?>">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="<?= (int) $livro['id'] ?>">

                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                        Excluir
                                    </button>
                                </form>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

                <p id="nenhumResultado" class="text-center mt-4" hidden>
                    Nenhum livro encontrado.
                </p>

            <?php endif; ?>

        </section>

    </main>
</div>

<!-- MODAL CADASTRAR LIVRO -->
<div class="modal fade" id="modalLivro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Adicionar livro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="livros.php">

                <input type="hidden" name="csrf_token" value="<?= bsyncEscapar($tokenCSRF) ?>">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="titulo" class="form-control" maxlength="200" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor</label>
                        <input type="text" name="autor" class="form-control" maxlength="150" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <input type="text" name="categoria" class="form-control" maxlength="100" list="categoriasLivros" placeholder="Digite a categoria" required>
                        <datalist id="categoriasLivros">
                            <option value="Literatura">
                            <option value="Clássico">
                            <option value="Fantasia">
                            <option value="Romance">
                            <option value="Suspense">
                            <option value="Ficção científica">
                            <option value="Biografia">
                            <option value="Infantil">
                            <option value="História">
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Preço</label>
                        <input type="number" name="preco" class="form-control" step="0.01" min="0" max="99999999.99" required>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
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

<!-- MODAL EDITAR LIVRO -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Editar livro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="livros.php">

                <input type="hidden" name="csrf_token" value="<?= bsyncEscapar($tokenCSRF) ?>">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="editarId">

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="titulo" id="editarTitulo" class="form-control" maxlength="200" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Autor</label>
                        <input type="text" name="autor" id="editarAutor" class="form-control" maxlength="150" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <input type="text" name="categoria" id="editarCategoria" class="form-control" maxlength="100" list="categoriasLivros" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Preço</label>
                        <input type="number" name="preco" id="editarPreco" class="form-control" step="0.01" min="0" max="99999999.99" required>
                    </div>

                    <div>
                        <label class="form-label">Disponibilidade</label>
                        <select name="disponibilidade" id="editarDisponibilidade" class="form-select" required>
                            <option value="disponivel">Disponível</option>
                            <option value="indisponivel">Indisponível</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Salvar alterações
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/script.js"></script>

<script>
document.querySelectorAll('.btn-editar').forEach(botao => {
    botao.addEventListener('click', function () {
        document.getElementById('editarId').value = this.dataset.id;
        document.getElementById('editarTitulo').value = this.dataset.titulo;
        document.getElementById('editarAutor').value = this.dataset.autor;
        document.getElementById('editarCategoria').value = this.dataset.categoria;
        document.getElementById('editarPreco').value = this.dataset.preco;
        document.getElementById('editarDisponibilidade').value = this.dataset.disponibilidade;
    });
});

const busca = document.getElementById('buscaLivros');
const lista = document.getElementById('listaLivros');
const nenhumResultado = document.getElementById('nenhumResultado');

busca.addEventListener('input', function () {
    const termo = this.value.trim().toLocaleLowerCase('pt-BR');
    let encontrados = 0;

    if (lista) {
        lista.querySelectorAll('.book-item').forEach(livro => {
            const corresponde = livro.textContent.toLocaleLowerCase('pt-BR').includes(termo);
            livro.style.display = corresponde ? '' : 'none';

            if (corresponde) {
                encontrados++;
            }
        });
    }

    if (nenhumResultado) {
        nenhumResultado.hidden = encontrados > 0;
    }
});
</script>

</body>
</html>
