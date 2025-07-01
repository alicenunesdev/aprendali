<?php
session_start();
include 'db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: auth/login.php');
    exit();
}

$pdo = getPDOConnection();
$success_message = '';
$error_message = '';
$cursos = [];

// Buscar cursos se admin ou professor
if (in_array($_SESSION['usuario_perfil'], ['admin', 'professor'])) {
    try {
        $stmt_cursos = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome");
        $cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erro ao carregar cursos: " . $e->getMessage();
    }
}

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if ($new_password !== $confirm_password) throw new Exception('As senhas não coincidem.');
            if (strlen($new_password) < 6) throw new Exception('A nova senha deve ter pelo menos 6 caracteres.');

            $stmt = $pdo->prepare("SELECT senha FROM usuario WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $user = $stmt->fetch();

            if ($user['senha'] !== $current_password) throw new Exception('Senha atual incorreta.');

            $stmt = $pdo->prepare("UPDATE usuario SET senha = ? WHERE id = ?");
            $stmt->execute([$new_password, $_SESSION['usuario_id']]);
            $success_message = 'Senha alterada com sucesso!';
        }

        if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
            if (!in_array($_SESSION['usuario_perfil'], ['admin', 'professor'])) {
                throw new Exception('Você não tem permissão para criar usuários.');
            }

            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $usuario = trim($_POST['usuario']);
            $senha = $_POST['senha'];
            $perfil = $_POST['perfil'];
            $curso_id = (!empty($_POST['curso_id'])) ? $_POST['curso_id'] : null;

            if (empty($nome) || empty($email) || empty($usuario) || empty($senha)) {
                throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
            }
            if (strlen($senha) < 6) throw new Exception('A senha deve ter pelo menos 6 caracteres.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Email inválido.');

            if ($_SESSION['usuario_perfil'] === 'professor' && $perfil !== 'aluno') {
                throw new Exception('Professores só podem criar contas de alunos.');
            }

            if ($perfil === 'aluno' && empty($curso_id)) {
                throw new Exception('Para o perfil Aluno, selecione um curso.');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE email = ? OR usuario = ?");
            $stmt->execute([$email, $usuario]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Email ou nome de usuário já existe.');
            }

            $professor_id = ($_SESSION['usuario_perfil'] === 'professor') ? $_SESSION['usuario_id'] : null;

            $stmt = $pdo->prepare("INSERT INTO usuario (nome, email, usuario, senha, perfil, curso_id, professor_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $usuario, $senha, $perfil, $curso_id, $professor_id]);

            $success_message = 'Usuário criado com sucesso!';
        }

        if (isset($_POST['action']) && $_SESSION['usuario_perfil'] === 'admin') {
            $user_id = $_POST['user_id'] ?? null;
            if (!$user_id) throw new Exception('ID do usuário não fornecido.');

            if ($_POST['action'] === 'delete_user') {
                if ($user_id == $_SESSION['usuario_id']) throw new Exception('Você não pode excluir sua própria conta.');
                $stmt = $pdo->prepare("DELETE FROM usuario WHERE id = ?");
                $stmt->execute([$user_id]);
                $success_message = 'Usuário excluído com sucesso!';
            }

            if ($_POST['action'] === 'toggle_user_status') {
                if ($user_id == $_SESSION['usuario_id']) throw new Exception('Você não pode alterar o próprio status.');
                $new_status = $_POST['current_status'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE usuario SET ativo = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                $success_message = 'Status alterado com sucesso!';
            }

            if ($_POST['action'] === 'edit_user') {
                $edit_nome = trim($_POST['edit_nome']);
                $edit_email = trim($_POST['edit_email']);
                $edit_usuario = trim($_POST['edit_usuario']);
                $edit_perfil = $_POST['edit_perfil'];
                $edit_curso_id = (!empty($_POST['edit_curso_id'])) ? $_POST['edit_curso_id'] : null;

                if (empty($edit_nome) || empty($edit_email) || empty($edit_usuario)) {
                    throw new Exception('Todos os campos obrigatórios devem ser preenchidos para edição.');
                }

                if (!filter_var($edit_email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email inválido.');
                }

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE (email = ? OR usuario = ?) AND id != ?");
                $stmt->execute([$edit_email, $edit_usuario, $user_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Email ou nome de usuário já existe.');
                }

                if ($edit_perfil === 'aluno' && empty($edit_curso_id)) {
                    throw new Exception('Curso obrigatório para aluno.');
                }

                $stmt = $pdo->prepare("UPDATE usuario SET nome = ?, email = ?, usuario = ?, perfil = ?, curso_id = ? WHERE id = ?");
                $stmt->execute([$edit_nome, $edit_email, $edit_usuario, $edit_perfil, $edit_curso_id, $user_id]);
                $success_message = 'Usuário editado com sucesso!';
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Buscar dados do usuário atual
$stmt = $pdo->prepare("SELECT * FROM usuario WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$current_user = $stmt->fetch();

// Buscar lista de usuários (admin)
$users_list = [];
if ($_SESSION['usuario_perfil'] === 'admin') {
    $stmt = $pdo->prepare("SELECT u.id, u.nome, u.email, u.usuario, u.perfil, u.ativo, u.data_criacao, u.curso_id, c.nome AS curso_nome FROM usuario u LEFT JOIN cursos c ON u.curso_id = c.id ORDER BY u.nome");
    $stmt->execute();
    $users_list = $stmt->fetchAll();
}

include 'menu.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - AprendaAli</title>
    <style>
        .main-container {
            min-height: calc(100vh - 80px);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 40px 20px;
        }
        
        .profile-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
        }
        
        .profile-info h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .profile-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .badge-professor {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        
        .badge-aluno {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 32px;
            margin-bottom: 32px;
        }
        
        .card {
            background: linear-gradient(135deg, #ffffff 0%, #fafbfc 100%);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 
                0 4px 20px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.6);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 12px 32px rgba(0, 0, 0, 0.08),
                0 4px 12px rgba(0, 0, 0, 0.04);
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .card-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
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
        
        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }
        
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(6, 182, 212, 0.4);
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
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }
        
        .users-table th,
        .users-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        .users-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table tr:hover {
            background: rgba(37, 99, 235, 0.02);
        }
        
        .status-active {
            color: #059669;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #dc2626;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                padding: 24px;
            }
            
            .card {
                padding: 24px;
            }
            
            .users-table {
                font-size: 0.85rem;
            }
            
            .users-table th,
            .users-table td {
                padding: 12px 8px;
            }
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2563eb;
            display: block;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .actions-cell {
            white-space: nowrap; /* Evita que os botões quebrem a linha */
        }

        .actions-cell form {
            display: inline-block; /* Alinha os formulários horizontalmente */
            margin-right: 8px; /* Espaço entre os botões */
        }

        /* Estilos para o modal */
        .modal {
            display: none; /* INICIALMENTE ESCONDIDO */
            position: fixed;
            z-index: 1000; /* Garante que fique acima de outros elementos */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5); /* Fundo semi-transparente */
            justify-content: center; /* Para centralizar o conteúdo quando visível */
            align-items: center; /* Para centralizar o conteúdo quando visível */
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative; /* Para posicionar o botão de fechar */
        }

        .close-button {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            line-height: 1;
        }

        .close-button:hover,
        .close-button:focus {
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="profile-wrapper">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($current_user['nome'], 0, 1)) ?>
                </div>
                <div class="profile-info">
                    <h1><?= htmlspecialchars($current_user['nome']) ?></h1>
                    <span class="profile-badge badge-<?= $current_user['perfil'] ?>">
                        <?= ucfirst($current_user['perfil']) ?>
                    </span>
                    <p style="color: #64748b; margin-top: 8px; font-size: 0.95rem;">
                        <?= htmlspecialchars($current_user['email']) ?> | 
                        Usuário: <?= htmlspecialchars($current_user['usuario']) ?>
                    </p>
                    <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 4px;">
                        Membro desde <?= date('d/m/Y', strtotime($current_user['data_criacao'])) ?>
                    </p>
                </div>

                <?php if ($_SESSION['usuario_perfil'] === 'admin'): ?>
                <div class="user-stats">
                    <?php
                    $stmt = $pdo->prepare("SELECT perfil, COUNT(*) as total FROM usuario GROUP BY perfil");
                    $stmt->execute();
                    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    ?>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['admin'] ?? 0 ?></span>
                        <span class="stat-label">Admins</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['professor'] ?? 0 ?></span>
                        <span class="stat-label">Professores</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?= $stats['aluno'] ?? 0 ?></span>
                        <span class="stat-label">Alunos</span>
                    </div>
                </div>
                <?php endif; ?>
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

            <div class="cards-grid">
                <div class="card">
                    <h2 class="card-title">
                        <span class="card-icon">🔒</span>
                        Alterar Senha
                    </h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label class="form-label">Senha Atual</label>
                            <input type="password" name="current_password" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" name="new_password" class="form-input" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirmar Nova Senha</label>
                            <input type="password" name="confirm_password" class="form-input" required minlength="6">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            Alterar Senha
                        </button>
                    </form>
                </div>

              <?php if (in_array($_SESSION['usuario_perfil'], ['admin', 'professor'])): ?>
                <div class="card">
                    <h2 class="card-title">
                        <span class="card-icon">👤</span>
                        Criar Novo Usuário
                    </h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div class="form-group">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nome de Usuário</label>
                            <input type="text" name="usuario" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-input" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Perfil</label>
                            <select name="perfil" id="perfil_select" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php if ($_SESSION['usuario_perfil'] === 'admin'): ?>
                                    <option value="admin">Administrador</option>
                                    <option value="professor">Professor</option>
                                <?php endif; ?>
                                <option value="aluno">Aluno</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="curso_select_group" style="display: none;">
                            <label class="form-label">Curso</label>
                            <select name="curso_id" id="curso_id" class="form-select">
                                <option value="">Selecione um curso (opcional)</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?= htmlspecialchars($curso['id']) ?>">
                                        <?= htmlspecialchars($curso['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            Criar Usuário
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($_SESSION['usuario_perfil'] === 'admin' && !empty($users_list)): ?>
            <div class="card" style="grid-column: 1 / -1;">
                <h2 class="card-title">
                    <span class="card-icon">👥</span>
                    Gerenciar Usuários
                </h2>
                
                <div style="overflow-x: auto;">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Usuário</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Curso</th> 
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_list as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['nome']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['usuario']) ?></td>
                                <td>
                                    <span class="profile-badge badge-<?= $user['perfil'] ?>">
                                        <?= ucfirst($user['perfil']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?= $user['ativo'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $user['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($user['curso_nome'] ?: 'N/A') ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($user['data_criacao'])) ?></td>
                                <td class="actions-cell">
                                    <button type="button" class="btn btn-warning btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>, <?= htmlspecialchars(json_encode($cursos)) ?>)">
                                        Editar
                                    </button>

                                    <form method="POST" style="display:inline-block;">
                                        <input type="hidden" name="action" value="toggle_user_status">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $user['ativo'] ?>">
                                        <button type="submit" class="btn <?= $user['ativo'] ? 'btn-info' : 'btn-primary' ?> btn-sm">
                                            <?= $user['ativo'] ? 'Desabilitar' : 'Habilitar' ?>
                                        </button>
                                    </form>

                                    <?php if ($user['id'] !== $_SESSION['usuario_id']): // Previne autoexclusão ?>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?= htmlspecialchars($user['nome']) ?>? Esta ação é irreversível.');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Excluir
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <button class="close-button" onclick="closeEditModal()">&times;</button>
            <h3 style="margin-top:0; color:#1e293b;">Editar Usuário</h3>
            <form id="editUserForm" method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="edit_nome" id="edit_nome" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="edit_email" id="edit_email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nome de Usuário</label>
                    <input type="text" name="edit_usuario" id="edit_usuario" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Perfil</label>
                    <select name="edit_perfil" id="edit_perfil" class="form-select" required onchange="toggleEditCursoSelect()">
                        <option value="">Selecione...</option>
                        <?php if ($_SESSION['usuario_perfil'] === 'admin'): ?>
                            <option value="admin">Administrador</option>
                            <option value="professor">Professor</option>
                        <?php endif; ?>
                        <option value="aluno">Aluno</option>
                    </select>
                </div>
                
                <div class="form-group" id="edit_curso_select_group" style="display: none;">
                    <label class="form-label">Curso</label>
                    <select name="edit_curso_id" id="edit_curso_id" class="form-select">
                        <option value="">Selecione um curso (opcional)</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?= htmlspecialchars($curso['id']) ?>">
                                <?= htmlspecialchars($curso['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn" style="background-color: #64748b; color: white;" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para abrir o modal de edição
        function openEditModal(user, allCursos) {
            const modal = document.getElementById('editUserModal');
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_nome').value = user.nome;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_usuario').value = user.usuario;
            document.getElementById('edit_perfil').value = user.perfil;

            // Preencher o select de cursos
            const editCursoSelect = document.getElementById('edit_curso_id');
            editCursoSelect.innerHTML = '<option value="">Selecione um curso (opcional)</option>';
            allCursos.forEach(curso => {
                const option = document.createElement('option');
                option.value = curso.id;
                option.textContent = curso.nome;
                if (user.curso_id == curso.id) { // Usar == para comparação de tipo se curso.id for string e user.curso_id for number (ou vice-versa)
                    option.selected = true;
                }
                editCursoSelect.appendChild(option);
            });

            toggleEditCursoSelect(); // Chama para exibir/ocultar o campo curso conforme o perfil
            
            modal.style.display = 'flex'; // Torna a modal visível
        }

        // Função para fechar o modal de edição
        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Função para controlar a visibilidade do campo "Curso" no modal de edição
        function toggleEditCursoSelect() {
            const perfilSelect = document.getElementById('edit_perfil');
            const cursoGroup = document.getElementById('edit_curso_select_group');
            if (perfilSelect.value === 'aluno') {
                cursoGroup.style.display = 'block';
            } else {
                cursoGroup.style.display = 'none';
                document.getElementById('edit_curso_id').value = ''; // Limpa o valor se não for aluno
            }
        }

        // Função para controlar a visibilidade do campo "Curso" no formulário de criação
        function toggleCreateCursoSelect() {
            const perfilSelect = document.getElementById('perfil_select');
            const cursoGroup = document.getElementById('curso_select_group');
            if (perfilSelect.value === 'aluno') {
                cursoGroup.style.display = 'block';
            } else {
                cursoGroup.style.display = 'none';
                document.getElementById('curso_id').value = ''; // Limpa o valor se não for aluno
            }
        }

        // Adiciona listeners quando o DOM estiver completamente carregado
        document.addEventListener('DOMContentLoaded', function() {
            // Listener para o select de perfil no formulário de criação
            const perfilCreateSelect = document.getElementById('perfil_select');
            if (perfilCreateSelect) {
                perfilCreateSelect.addEventListener('change', toggleCreateCursoSelect);
                // Chama a função uma vez no carregamento para definir o estado inicial
                toggleCreateCursoSelect(); 
            }

            // Fechar modal ao clicar fora dela
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('editUserModal');
                if (event.target === modal) {
                    closeEditModal();
                }
            });
        });
    </script>
</body>
</html>