<?php
session_start();
include 'db.php';

// Verificar se o usuário está logado e é professor ou admin
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_perfil'], ['admin', 'professor'])) {
    header('Location: auth/login.php');
    exit();
}

$pdo = getPDOConnection();
$success_message = '';
$error_message = '';
$curso_filtro_id = $_GET['curso_id'] ?? '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Excluir aluno (EXISTENTE)
        if (isset($_POST['action']) && $_POST['action'] === 'delete_student') {
            $aluno_id = (int)$_POST['aluno_id'];
            
            // Verificar se é aluno do professor atual (ou se é admin)
            if ($_SESSION['usuario_perfil'] === 'professor') {
                $stmt = $pdo->prepare("SELECT id FROM usuario WHERE id = ? AND professor_id = ? AND perfil = 'aluno'");
                $stmt->execute([$aluno_id, $_SESSION['usuario_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception('Você não tem permissão para excluir este aluno.');
                }
            }
            
            // Excluir aluno (cascade irá remover relacionamentos)
            $stmt = $pdo->prepare("DELETE FROM usuario WHERE id = ? AND perfil = 'aluno'");
            $stmt->execute([$aluno_id]);
            
            $success_message = 'Aluno excluído com sucesso!';
        }
        
        // Desabilitar/Habilitar aluno (NOVO)
        if (isset($_POST['action']) && in_array($_POST['action'], ['disable_student', 'enable_student'])) {
            $aluno_id = (int)$_POST['aluno_id'];
            $new_status = ($_POST['action'] === 'disable_student') ? 0 : 1;
            $action_word = ($new_status === 0) ? 'desabilitado' : 'habilitado';

            // Verificar se é aluno do professor atual (ou se é admin)
            if ($_SESSION['usuario_perfil'] === 'professor') {
                $stmt = $pdo->prepare("SELECT id FROM usuario WHERE id = ? AND professor_id = ? AND perfil = 'aluno'");
                $stmt->execute([$aluno_id, $_SESSION['usuario_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception("Você não tem permissão para {$action_word} este aluno.");
                }
            }

            $stmt = $pdo->prepare("UPDATE usuario SET ativo = ? WHERE id = ? AND perfil = 'aluno'");
            $stmt->execute([$new_status, $aluno_id]);
            
            $success_message = "Aluno {$action_word} com sucesso!";
        }

        // Atualizar cursos do aluno (EXISTENTE)
        if (isset($_POST['action']) && $_POST['action'] === 'update_student_courses') {
            $aluno_id = (int)$_POST['aluno_id'];
            $cursos_selecionados = $_POST['cursos'] ?? [];
            
            // Verificar se é aluno do professor atual (ou se é admin)
            if ($_SESSION['usuario_perfil'] === 'professor') {
                $stmt = $pdo->prepare("SELECT id FROM usuario WHERE id = ? AND professor_id = ? AND perfil = 'aluno'");
                $stmt->execute([$aluno_id, $_SESSION['usuario_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception('Você não tem permissão para editar este aluno.');
                }
            }
            
            // Remover todos os cursos atuais do aluno (e manter apenas os ativos, ou todos se a coluna 'ativo' em aluno_cursos não for usada para desabilitar)
            // Se você quiser que o status ativo do aluno também afete os cursos, você pode mudar esta query.
            // Por simplicidade, vou manter a lógica de sobrescrever os cursos, o que significa que o 'ativo' na tabela `usuario` será o principal controle.
            $stmt = $pdo->prepare("DELETE FROM aluno_cursos WHERE aluno_id = ?");
            $stmt->execute([$aluno_id]);
            
            // Adicionar novos cursos
            if (!empty($cursos_selecionados)) {
                $stmt = $pdo->prepare("INSERT INTO aluno_cursos (aluno_id, curso_id) VALUES (?, ?)");
                foreach ($cursos_selecionados as $curso_id) {
                    $stmt->execute([$aluno_id, (int)$curso_id]);
                }
            }
            
            $success_message = 'Cursos do aluno atualizados com sucesso!';
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Buscar alunos
// Definir a variável do filtro de curso antes de usá-la
$curso_filtro_id = $_GET['curso_id'] ?? '';

// Buscar alunos
$alunos = [];

if ($_SESSION['usuario_perfil'] === 'admin') {
    // Admin vê todos os alunos (ativos e inativos)
    $query = "
        SELECT u.*, p.nome as professor_nome,
               GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') as cursos_nomes,
               COUNT(DISTINCT ac.curso_id) as total_cursos
        FROM usuario u
        LEFT JOIN usuario p ON u.professor_id = p.id
        LEFT JOIN aluno_cursos ac ON u.id = ac.aluno_id AND ac.ativo = 1
        LEFT JOIN cursos c ON ac.curso_id = c.id AND c.ativo = 1
        WHERE u.perfil = 'aluno'
    ";

    $params = [];

    if (!empty($curso_filtro_id)) {
        $query .= " AND u.id IN (
            SELECT aluno_id FROM aluno_cursos WHERE curso_id = ?
        )";
        $params[] = $curso_filtro_id;
    }

    $query .= " GROUP BY u.id ORDER BY u.nome";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll();

} else {
    // Professor vê apenas seus alunos (ativos e inativos)
    $query = "
        SELECT u.*, p.nome as professor_nome,
               GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') as cursos_nomes,
               COUNT(DISTINCT ac.curso_id) as total_cursos
        FROM usuario u
        LEFT JOIN usuario p ON u.professor_id = p.id
        LEFT JOIN aluno_cursos ac ON u.id = ac.aluno_id AND ac.ativo = 1
        LEFT JOIN cursos c ON ac.curso_id = c.id AND c.ativo = 1
        WHERE u.perfil = 'aluno' AND u.professor_id = ?
    ";

    $params = [$_SESSION['usuario_id']];

    if (!empty($curso_filtro_id)) {
        $query .= " AND u.id IN (
            SELECT aluno_id FROM aluno_cursos WHERE curso_id = ?
        )";
        $params[] = $curso_filtro_id;
    }

    $query .= " GROUP BY u.id ORDER BY u.nome";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll();
}

// Buscar apenas os cursos vinculados ao professor, se não for admin
if ($_SESSION['usuario_perfil'] === 'professor') {
    $stmt = $pdo->prepare("SELECT id, nome FROM cursos WHERE ativo = 1 AND professor_id = ? ORDER BY nome");
    $stmt->execute([$_SESSION['usuario_id']]);
} else {
    // Admin vê todos os cursos
    $stmt = $pdo->prepare("SELECT id, nome FROM cursos WHERE ativo = 1 ORDER BY nome");
    $stmt->execute();
}
$cursos_disponiveis = $stmt->fetchAll();


// Estatísticas
$stats = [
    'total_alunos' => count($alunos),
    'alunos_ativos' => 0, // Adicionado para contar alunos ativos
    'alunos_inativos' => 0, // Adicionado para contar alunos inativos
    'alunos_com_cursos' => 0,
    'total_cursos_ativos' => count($cursos_disponiveis)
];

foreach ($alunos as $aluno) {
    if ($aluno['ativo'] == 1) {
        $stats['alunos_ativos']++;
    } else {
        $stats['alunos_inativos']++;
    }
    if ($aluno['total_cursos'] > 0) {
        $stats['alunos_com_cursos']++;
    }
}

// Incluir o header
include 'menu.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Alunos - AprendaAli</title>
    <style>
        /* Mantenha o CSS existente e adicione ou modifique conforme necessário */
        .main-container {
            min-height: calc(100vh - 80px);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 40px 20px;
        }
        
        .students-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .page-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .page-subtitle {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 24px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2563eb;
            display: block;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .students-section {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }
        
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }
        
        .student-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 
                0 2px 12px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; /* Necessário para o badge */
        }
        
        .student-card.inactive {
            opacity: 0.7; /* Para indicar que está inativo */
            border: 1px dashed rgba(220, 38, 38, 0.4);
            background: #fffafa; /* Um leve tom de vermelho para inativos */
        }

        .status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background-color: #d1fae5;
            color: #047857;
        }

        .status-inactive {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .student-card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 12px 32px rgba(0, 0, 0, 0.08),
                0 4px 12px rgba(0, 0, 0, 0.04);
        }
        
        .student-avatar {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 16px;
        }
        
        .student-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .student-email {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }
        
        .student-info {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }
        
        .student-courses {
            margin-bottom: 20px;
        }
        
        .courses-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .courses-list {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .no-courses {
            color: #94a3b8;
            font-style: italic;
        }
        
        .student-actions {
            display: flex;
            flex-wrap: wrap; /* Para acomodar mais botões */
            gap: 8px;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }

        .btn-warning { /* Novo estilo para o botão de desabilitar/habilitar */
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #047857;
            border: 1px solid rgba(5, 150, 105, 0.2);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: white;
            border-radius: 20px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .modal-overlay.active .modal {
            transform: scale(1) translateY(0);
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            max-height: 200px;
            overflow-y: auto;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
        }
        
        .checkbox-item label {
            font-size: 0.9rem;
            color: #374151;
            cursor: pointer;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .empty-state {
            text-align: center;
            padding: 64px 32px;
            color: #64748b;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .empty-description {
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .students-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .page-header {
                padding: 24px;
            }
            
            .students-section {
                padding: 24px;
            }
            
            .student-card {
                padding: 20px;
            }
            
            .modal {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="students-wrapper">
            <div class="page-header">
                <div class="page-title">
                    <span class="page-icon">👨‍🎓</span>
                    Área de Alunos
                </div>
                <div class="page-subtitle">
                    Gerencie seus alunos e controle o acesso aos cursos
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['total_alunos'] ?></span>
                        <span class="stat-label">Total de Alunos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['alunos_ativos'] ?></span>
                        <span class="stat-label">Alunos Ativos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['alunos_inativos'] ?></span>
                        <span class="stat-label">Alunos Inativos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['alunos_com_cursos'] ?></span>
                        <span class="stat-label">Com Cursos Ativos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['total_cursos_ativos'] ?></span>
                        <span class="stat-label">Cursos Disponíveis</span>
                    </div>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✓ <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ✗ <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="students-section">
                <h2 class="section-title">
                    <span class="section-icon">📚</span>
                    Meus Alunos
                </h2>
                <form method="GET" style="margin-bottom: 24px;">
    <label for="curso_id" style="font-weight: 600; color: #374151; margin-right: 8px;">
        Filtrar por curso:
    </label>
    <select name="curso_id" id="curso_id" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
        <option value="">Todos os cursos</option>
        <?php foreach ($cursos_disponiveis as $curso): ?>
            <option value="<?= $curso['id'] ?>" <?= $curso_filtro_id == $curso['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($curso['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
                <?php if (empty($alunos)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👨‍🎓</div>
                        <div class="empty-title">Nenhum aluno encontrado</div>
                        <div class="empty-description">
                            Você ainda não possui alunos cadastrados.<br>
                            Crie novos alunos através da seção "Perfil" para começar.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="students-grid">
                        <?php foreach ($alunos as $aluno): ?>
                            <div class="student-card <?= $aluno['ativo'] == 0 ? 'inactive' : '' ?>">
                                <div class="status-badge <?= $aluno['ativo'] == 1 ? 'status-active' : 'status-inactive' ?>">
                                    <?= $aluno['ativo'] == 1 ? 'Ativo' : 'Inativo' ?>
                                </div>

                                <div class="student-avatar">
                                    <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                                </div>
                                
                                <div class="student-name">
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </div>
                                
                                <div class="student-email">
                                    <?= htmlspecialchars($aluno['email']) ?>
                                </div>
                                
                                <div class="student-info">
                                    Usuário: <?= htmlspecialchars($aluno['usuario']) ?><br>
                                    Criado em: <?= date('d/m/Y', strtotime($aluno['data_criacao'])) ?>
                                    <?php if ($aluno['professor_nome']): ?>
                                        <br>Professor: <?= htmlspecialchars($aluno['professor_nome']) ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="student-courses">
                                    <div class="courses-label">Cursos Ativos</div>
                                    <div class="courses-list">
                                        <?php if ($aluno['cursos_nomes']): ?>
                                            <?= htmlspecialchars($aluno['cursos_nomes']) ?>
                                        <?php else: ?>
                                            <span class="no-courses">Nenhum curso ativo</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="student-actions">
                                    <button class="btn btn-primary btn-sm" onclick="editStudentCourses(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome']) ?>')">
                                        ✏️ Editar Cursos
                                    </button>
                                    <?php if ($aluno['ativo'] == 1): ?>
                                        <button class="btn btn-warning btn-sm" onclick="confirmDisableEnableStudent(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome']) ?>', 'disable')">
                                            🚫 Desabilitar
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-sm" onclick="confirmDisableEnableStudent(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome']) ?>', 'enable')">
                                            ✅ Habilitar
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDeleteStudent(<?= $aluno['id'] ?>, '<?= htmlspecialchars($aluno['nome']) ?>')">
                                        🗑️ Excluir
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="editCoursesModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title">Editar Cursos do Aluno</div>
            <form id="editCoursesForm" method="POST">
                <input type="hidden" name="action" value="update_student_courses">
                <input type="hidden" name="aluno_id" id="edit_aluno_id">
                
                <div class="form-group">
                    <label class="form-label">Aluno: <span id="edit_aluno_nome"></span></label>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Selecione os cursos:</label>
                    <div class="checkbox-group" id="cursosCheckboxes">
                        <?php foreach ($cursos_disponiveis as $curso): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="cursos[]" value="<?= $curso['id'] ?>" id="curso_<?= $curso['id'] ?>">
                                <label for="curso_<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editCoursesModal')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="disableEnableStudentModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title" id="disable_enable_modal_title"></div>
            <p>Tem certeza que deseja <strong id="action_verb"></strong> o aluno <strong id="disable_enable_aluno_nome"></strong>?</p>
            <p style="color: #f59e0b; font-size: 0.9rem; margin-top: 16px;" id="disable_enable_warning">
                ⚠️ Ao desabilitar, o aluno perderá acesso ao sistema. Você poderá reativá-lo a qualquer momento.
            </p>
            
            <form id="disableEnableStudentForm" method="POST">
                <input type="hidden" name="action" id="disable_enable_action">
                <input type="hidden" name="aluno_id" id="disable_enable_aluno_id">
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('disableEnableStudentModal')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning" id="disable_enable_submit_btn">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteStudentModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title">Confirmar Exclusão</div>
            <p>Tem certeza que deseja excluir o aluno <strong id="delete_aluno_nome"></strong>?</p>
            <p style="color: #dc2626; font-size: 0.9rem; margin-top: 16px;">
                ⚠️ Esta ação não pode ser desfeita. Todos os dados do aluno serão removidos permanentemente.
            </p>
            
            <form id="deleteStudentForm" method="POST">
                <input type="hidden" name="action" value="delete_student">
                <input type="hidden" name="aluno_id" id="delete_aluno_id">
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteStudentModal')">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        Confirmar Exclusão
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editStudentCourses(alunoId, alunoNome) {
            document.getElementById('edit_aluno_id').value = alunoId;
            document.getElementById('edit_aluno_nome').textContent = alunoNome;
            
            const checkboxes = document.querySelectorAll('#cursosCheckboxes input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);
            
            fetch('get_student_courses.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'aluno_id=' + alunoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.cursos.forEach(cursoId => {
                        const checkbox = document.getElementById('curso_' + cursoId);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao buscar cursos do aluno:', error);
            });
            
            openModal('editCoursesModal');
        }
        
        function confirmDeleteStudent(alunoId, alunoNome) {
            document.getElementById('delete_aluno_id').value = alunoId;
            document.getElementById('delete_aluno_nome').textContent = alunoNome;
            openModal('deleteStudentModal');
        }

        // NOVO: Função para confirmar desabilitar/habilitar aluno
        function confirmDisableEnableStudent(alunoId, alunoNome, actionType) {
            const modalTitle = document.getElementById('disable_enable_modal_title');
            const actionVerb = document.getElementById('action_verb');
            const warningText = document.getElementById('disable_enable_warning');
            const submitBtn = document.getElementById('disable_enable_submit_btn');

            document.getElementById('disable_enable_aluno_id').value = alunoId;
            document.getElementById('disable_enable_aluno_nome').textContent = alunoNome;

            if (actionType === 'disable') {
                modalTitle.textContent = 'Confirmar Desabilitação';
                actionVerb.textContent = 'desabilitar';
                warningText.innerHTML = '⚠️ Ao desabilitar, o aluno perderá acesso ao sistema. Você poderá reativá-lo a qualquer momento.';
                submitBtn.className = 'btn btn-warning'; // Botão laranja para desabilitar
                document.getElementById('disable_enable_action').value = 'disable_student';
            } else { // actionType === 'enable'
                modalTitle.textContent = 'Confirmar Habilitação';
                actionVerb.textContent = 'habilitar';
                warningText.innerHTML = '✅ Ao habilitar, o aluno terá novamente acesso ao sistema.';
                submitBtn.className = 'btn btn-primary'; // Botão azul para habilitar (ou crie um 'btn-success' se preferir verde)
                document.getElementById('disable_enable_action').value = 'enable_student';
            }
            openModal('disableEnableStudentModal');
        }
        
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Fechar modal ao clicar fora dele
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
        
        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) {
                    activeModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
        
        // Confirmação adicional antes de excluir
        document.getElementById('deleteStudentForm').addEventListener('submit', function(e) {
            const alunoNome = document.getElementById('delete_aluno_nome').textContent;
            if (!confirm(`Você tem CERTEZA ABSOLUTA que deseja excluir o aluno "${alunoNome}"?\n\nEsta ação é IRREVERSÍVEL!`)) {
                e.preventDefault();
            }
        });
        
        // Auto-hide alerts após 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Animação suave para os cards de alunos
        document.addEventListener('DOMContentLoaded', function() {
            const studentCards = document.querySelectorAll('.student-card');
            studentCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>