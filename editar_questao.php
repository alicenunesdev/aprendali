<?php
session_start();
include 'db.php';
include 'menu.php';

// Verificar se ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerenciar_questoes.php");
    exit;
}

$id = (int)$_GET['id'];

// Verificar se a coluna materia_id existe na tabela questoes
$has_materia_column = false;
$check_column = $conn->query("SHOW COLUMNS FROM questoes LIKE 'materia_id'");
if ($check_column && $check_column->num_rows > 0) {
    $has_materia_column = true;
}

// Montar a query dinamicamente
if ($has_materia_column) {
    $query = "SELECT q.*, c.nome AS curso_nome, m.nome AS materia_nome
              FROM questoes q
              LEFT JOIN cursos c ON q.curso_id = c.id
              LEFT JOIN materias m ON q.materia_id = m.id
              WHERE q.id = ?";
} else {
    $query = "SELECT q.*, c.nome AS curso_nome
              FROM questoes q
              LEFT JOIN cursos c ON q.curso_id = c.id
              WHERE q.id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensagem'] = 'Questão não encontrada.';
    $_SESSION['tipo_mensagem'] = 'error';
    header("Location: gerenciar_questoes.php");
    exit;
}

$questao = $result->fetch_assoc();


// Continue com o HTML/formulário aqui
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Questão</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body {
            background: rgb(231, 234, 247);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: rgba(255, 255, 255, 0.95);
            padding: 25px 35px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header-section h1 {
            margin: 0;
            color: #2d3748;
            font-size: 2.2em;
            font-weight: 700;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-voltar {
            background: linear-gradient(45deg, #718096, #4a5568);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-voltar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(113, 128, 150, 0.4);
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        select, input[type="text"], textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
            box-sizing: border-box;
        }

        select:focus, input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .alternativas-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }

        .radio-option:hover {
            background-color: #f8f9fa;
        }

        .radio-option input[type="radio"] {
            margin: 0;
            accent-color: #667eea;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }

        .btn-salvar {
            background: linear-gradient(45deg, #48bb78, #38a169);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-salvar:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(72, 187, 120, 0.3);
        }

        .btn-cancelar {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cancelar:hover {
            transform: translateY(-2px);
            background: #cbd5e0;
        }

        .info-box {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }

        .info-box i {
            margin-right: 8px;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-warning {
            background: linear-gradient(45deg, #ed8936, #dd6b20);
            color: white;
            box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }

            .header-section {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }

            .header-section h1 {
                font-size: 1.8em;
            }

            .form-container {
                padding: 25px;
            }

            .alternativas-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .radio-group {
                justify-content: center;
            }
        }

        .form-container {
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <main class="main-content">
        <div class="header-section">
            <h1><i class="fas fa-edit"></i> Editar Questão</h1>
            <a href="gerenciar_questoes.php" class="btn-voltar">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="form-container">
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                Editando questão ID: <?php echo $questao['id']; ?> - Criada em: <?php echo date('d/m/Y H:i', strtotime($questao['criado_em'])); ?>
            </div>

            <?php if (!$has_materia_column): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                A tabela de questões não possui o campo matéria. O campo matéria será ignorado nesta edição.
            </div>
            <?php endif; ?>

            <form method="POST" action="salvar_edicao_questao.php">
                <input type="hidden" name="id" value="<?php echo $questao['id']; ?>">

                <div class="form-group">
                    <label>Curso:</label>
                    <select name="curso_id" required>
                        <option value="">Selecione um curso</option>
                        <?php
                        $cursos = $conn->query("SELECT id, nome FROM cursos ORDER BY nome");
                        while ($curso = $cursos->fetch_assoc()) {
                            $selected = ($curso['id'] == $questao['curso_id']) ? 'selected' : '';
                            echo "<option value='{$curso['id']}' {$selected}>{$curso['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <?php if ($has_materia_column): ?>
                <div class="form-group">
                    <label>Matéria:</label>
                    <select name="materia_id">
                        <option value="">Selecione uma matéria (opcional)</option>
                        <?php
                        $materias = $conn->query("SELECT id, nome FROM materias ORDER BY nome");
                        while ($materia = $materias->fetch_assoc()) {
                            $selected = ($materia['id'] == ($questao['materia_id'] ?? '')) ? 'selected' : '';
                            echo "<option value='{$materia['id']}' {$selected}>{$materia['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Pergunta:</label>
                    <textarea name="pergunta" placeholder="Digite sua pergunta aqui..." required><?php echo htmlspecialchars($questao['pergunta']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Alternativas:</label>
                    <div class="alternativas-grid">
                        <input type="text" name="resposta_A" placeholder="A) Digite a alternativa A" 
                               value="<?php echo htmlspecialchars($questao['resposta_A']); ?>" required>
                        <input type="text" name="resposta_B" placeholder="B) Digite a alternativa B" 
                               value="<?php echo htmlspecialchars($questao['resposta_B']); ?>" required>
                        <input type="text" name="resposta_C" placeholder="C) Digite a alternativa C" 
                               value="<?php echo htmlspecialchars($questao['resposta_C']); ?>" required>
                        <input type="text" name="resposta_D" placeholder="D) Digite a alternativa D" 
                               value="<?php echo htmlspecialchars($questao['resposta_D']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Resposta Correta:</label>
                    <div class="radio-group">
                        <?php foreach (['A', 'B', 'C', 'D'] as $letra): ?>
                            <label class="radio-option">
                                <input type="radio" name="resposta_correta" value="<?php echo $letra; ?>" 
                                       <?php echo ($questao['resposta_correta'] == $letra) ? 'checked' : ''; ?> required>
                                <span><?php echo $letra; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Explicação da Resposta:</label>
                    <textarea name="descricao" rows="4" placeholder="Explique por que essa é a resposta correta (opcional)"><?php echo htmlspecialchars($questao['descricao'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Dificuldade:</label>
                    <select name="dificuldade">
                        <option value="Fácil" <?php echo ($questao['dificuldade'] == 'Fácil') ? 'selected' : ''; ?>>Fácil</option>
                        <option value="Média" <?php echo ($questao['dificuldade'] == 'Média') ? 'selected' : ''; ?>>Média</option>
                        <option value="Difícil" <?php echo ($questao['dificuldade'] == 'Difícil') ? 'selected' : ''; ?>>Difícil</option>
                    </select>
                </div>            
                <div class="form-actions">
                    <button type="submit" class="btn-salvar">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="gerenciar_questoes.php" class="btn-cancelar">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>