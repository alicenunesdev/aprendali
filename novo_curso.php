<?php include 'verificar_sessao.php'; ?>
<?php
include 'db.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $ativo = 1;

    // Recebe as matérias enviadas (array)
    $materias = isset($_POST['materias']) ? $_POST['materias'] : [];

    // Limpa matérias vazias
    $materias = array_filter(array_map('trim', $materias), fn($m) => $m !== '');

    if (empty($nome)) {
        $mensagem = 'O nome do curso é obrigatório.';
    } elseif (empty($materias)) {
        $mensagem = 'Informe ao menos uma matéria.';
    } else {
        // Converte array em JSON para salvar no banco
        $materias_json = json_encode(array_values($materias), JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("INSERT INTO cursos (nome, ativo, materias) VALUES (?, ?, ?)");
        if ($stmt === false) {
            $mensagem = 'Erro na preparação da query: ' . $conn->error;
        } else {
            $stmt->bind_param("sis", $nome, $ativo, $materias_json);

            if ($stmt->execute()) {
                $mensagem = 'Curso criado com sucesso!';
            } else {
                $mensagem = 'Erro ao criar curso: ' . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Novo Curso - Aprendali</title>
    <link rel="stylesheet" href="assets/style.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Ajuste para inputs inline do campo matéria */
        .materia-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            align-items: center;
        }
        .materia-group input[type="text"] {
            flex-grow: 1;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .btn-add {
            padding: 0.4rem 0.8rem;
            font-size: 1.2rem;
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            height: 36px;
            user-select: none;
        }
        .btn-remove {
            background: #e74c3c;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            height: 36px;
            padding: 0 0.6rem;
            user-select: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
<header class="top-nav">
    <div class="logo">Aprendali</div>
    <div class="menu-toggle" id="menu-toggle">&#9776;</div>
    <nav class="nav-links" id="nav-links">
        <a href="index.php">Cursos</a>
        <a href="novo_curso.php">Novo Curso</a>
    </nav>
</header>

<main class="main-content">
    <section class="form-section">
        <h1>Criar Novo Curso</h1>

        <?php if ($mensagem): ?>
            <p style="margin-bottom:1rem; color: <?php echo strpos($mensagem, 'sucesso') !== false ? 'green' : 'red'; ?>;">
                <?php echo htmlspecialchars($mensagem); ?>
            </p>
        <?php endif; ?>

        <form action="novo_curso.php" method="post" id="form-curso">
            <label for="nome">Nome do Curso:</label><br />
            <input type="text" id="nome" name="nome" required /><br /><br />

            <label>Matérias:</label><br />

            <div id="materias-container">
                <div class="materia-group">
                    <input type="text" name="materias[]" placeholder="Digite a matéria" required />
                    <button type="button" class="btn-remove" onclick="removerMateria(this)">×</button>
                </div>
            </div>

            <button type="button" class="btn-add" id="btn-add-materia">+</button><br /><br />

            <button type="submit">Criar Curso</button>
        </form>

        <br />
        <a href="index.php" style="color:#4F46E5; text-decoration:none;">← Voltar para Cursos</a>
    </section>
</main>

<script>
    const menuToggle = document.getElementById('menu-toggle');
    const navLinks = document.getElementById('nav-links');

    menuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('show');
    });

    const btnAddMateria = document.getElementById('btn-add-materia');
    const materiasContainer = document.getElementById('materias-container');

    btnAddMateria.addEventListener('click', () => {
        const novaMateria = document.createElement('div');
        novaMateria.classList.add('materia-group');
        novaMateria.innerHTML = `
            <input type="text" name="materias[]" placeholder="Digite a matéria" required />
            <button type="button" class="btn-remove" onclick="removerMateria(this)">×</button>
        `;
        materiasContainer.appendChild(novaMateria);
    });

    function removerMateria(botao) {
        const grupo = botao.parentNode;
        if (materiasContainer.children.length > 1) {
            grupo.remove();
        } else {
            alert('Deve haver ao menos uma matéria.');
        }
    }
</script>

</body>
</html>
