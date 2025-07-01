<?php
session_start();
include 'db.php'; // Certifique-se de que este arquivo conecta ao seu banco de dados

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Inicializa $result como null. Será um objeto mysqli_result se a consulta for bem-sucedida.
// Caso contrário, será tratado para ter .num_rows = 0.
$result = null; 

// Consulta para buscar cursos baseado no perfil do usuário
if ($_SESSION['usuario_perfil'] === 'admin') {
    // Admin vê todos os cursos ativos
    $sql = "SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome";
    $result = $conn->query($sql);
} elseif ($_SESSION['usuario_perfil'] === 'professor') {
    // Professor vê apenas os cursos que ministra e estão ativos
    $professor_id = $_SESSION['usuario_id'];
    $sql = "SELECT id, nome FROM cursos WHERE ativo = 1 AND professor_id = ? ORDER BY nome";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $professor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    }
} elseif ($_SESSION['usuario_perfil'] === 'aluno') {
    // Aluno vê apenas os cursos com o mesmo professor_id que ele
    $aluno_id = $_SESSION['usuario_id'];

    // Primeiro, obtenha o professor_id do aluno logado
    $sql_aluno_professor = "SELECT professor_id FROM usuario WHERE id = ?"; // Tabela 'usuario' em vez de 'usuarios'
    $stmt_aluno_professor = $conn->prepare($sql_aluno_professor);
    if ($stmt_aluno_professor) {
        $stmt_aluno_professor->bind_param("i", $aluno_id);
        $stmt_aluno_professor->execute();
        $result_aluno_professor = $stmt_aluno_professor->get_result();
        $aluno_data = $result_aluno_professor->fetch_assoc();
        $stmt_aluno_professor->close();

        if ($aluno_data && !is_null($aluno_data['professor_id'])) {
            $professor_do_aluno_id = $aluno_data['professor_id'];
            // Agora, busque os cursos associados a este professor
            $sql = "SELECT id, nome FROM cursos WHERE ativo = 1 AND professor_id = ? ORDER BY nome";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $professor_do_aluno_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();
            }
        }
    }
} 
// Contagem de cursos para a barra de estatísticas
// Garante que $result é um objeto com num_rows ou que num_cursos_disponiveis seja 0
$num_cursos_disponiveis = ($result instanceof mysqli_result) ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Cursos - AprendaAli</title>
    <link rel="stylesheet" href="assets/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #2c3e50;
            line-height: 1.6;
            margin: 0;
            min-height: 100vh;
        }

        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        /* Header Section */
        .page-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 15px 0;
            letter-spacing: -0.5px;
        }

        .page-header p {
            font-size: 1.1rem;
            color: #6c757d;
            margin: 0;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Stats Bar */
        .stats-bar {
            background: linear-gradient(135deg,rgb(176, 184, 194) 0%,rgb(106, 110, 114) 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.2);
        }

        .stats-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-label {
            font-size: 0.95rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .add-course-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }

        .add-course-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        /* Courses Grid */
        .cursos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .curso-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            padding: 30px 25px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
            min-height: 180px;
        }

        .curso-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #6f42c1, #5a32a3);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .curso-card:hover::before {
            transform: scaleX(1);
        }

        .curso-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
            background: linear-gradient(135deg, #ffffff 0%, #ffffff 100%);
        }

        .curso-titulo {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            line-height: 1.4;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .curso-botoes {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .curso-botoes button {
            font-size: 0.9rem;
            font-weight: 500;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }

        .curso-botoes button:first-child {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 3px 12px rgba(40, 167, 69, 0.3);
        }

        .curso-botoes button:first-child:hover {
            background: linear-gradient(135deg, #218838 0%, #1c7430 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(40, 167, 69, 0.4);
        }

        .curso-botoes button:last-child {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
            box-shadow: 0 3px 12px rgba(111, 66, 193, 0.3);
        }

        .curso-botoes button:last-child:hover {
            background: linear-gradient(135deg, #5a32a3 0%, #4c2a85 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(111, 66, 193, 0.4);
        }

        .curso-botoes button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .curso-botoes button:hover::before {
            left: 100%;
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 40px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            border: 2px dashed #dee2e6;
            color: #6c757d;
        }

        .empty-state h3 {
            font-size: 1.4rem;
            color: #495057;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-state p {
            font-size: 1rem;
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }

            .page-header {
                padding: 25px 20px;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .stats-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }

            .cursos-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .curso-card {
                padding: 25px 20px;
            }

            .empty-state {
                padding: 40px 20px;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 1.6rem;
            }

            .curso-titulo {
                font-size: 1.1rem;
            }

            .curso-botoes button {
                font-size: 0.85rem;
                padding: 10px 16px;
            }
        }

        /* Loading Animation */
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

        .curso-card {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .curso-card:nth-child(1) { animation-delay: 0.1s; }
        .curso-card:nth-child(2) { animation-delay: 0.2s; }
        .curso-card:nth-child(3) { animation-delay: 0.3s; }
        .curso-card:nth-child(4) { animation-delay: 0.4s; }
        .curso-card:nth-child(5) { animation-delay: 0.5s; }
        .curso-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>

    <main class="main-content">
        
        <div class="stats-bar">
            <div class="stats-info">
                <div class="stats-number"><?= $num_cursos_disponiveis ?></div>
                <div class="stats-label">
                    <?= $num_cursos_disponiveis == 1 ? 'Curso Disponível' : 'Cursos Disponíveis' ?>
                </div>
            </div>
        </div>

        <div class="cursos-container">
            <?php if ($num_cursos_disponiveis > 0): // Use a variável segura ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="curso-card">
                        <div class="curso-titulo"><?= htmlspecialchars($row['nome']) ?></div>
                        <div class="curso-botoes">
                            <button onclick="location.href='ver_materias.php?curso=<?= $row['id'] ?>'">
                                Matérias
                            </button>
                            <button onclick="location.href='ver_questoes.php?curso=<?= $row['id'] ?>'">
                                Questões
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Nenhum Curso Cadastrado</h3>
                    <p>Comece criando seu primeiro curso para organizar o conteúdo educacional da plataforma.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
