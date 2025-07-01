<?php
session_start();
include 'db.php';

$msg = '';
$error = '';

// Buscar cursos para o select
$cursos = $conn->query("SELECT id, nome FROM cursos");

// ====================================
// 1. ADICIONAR NOVA MATÉRIA
// ====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['adicionar'])) {
    $nome = $_POST['nome'];
    $topico = $_POST['topico'];
    $tipo = $_POST['tipo'];
    $curso_id = $_POST['curso_id'];

    if (empty($nome) || empty($topico) || empty($curso_id)) {
        $error = "Todos os campos são obrigatórios!";
    } else {
        if ($tipo === 'pdf') {
            $conteudo = $_POST['link_drive'];
        } elseif ($tipo === 'video') {
            $conteudo = $_POST['link'];
        }

        $stmt = $conn->prepare("INSERT INTO materias (nome, topico, tipo, conteudo, curso_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $nome, $topico, $tipo, $conteudo, $curso_id);
        
        if ($stmt->execute()) {
            // ✅ REDIRECIONAR para evitar duplicação ao recarregar
            header("Location: adicionar_materia.php?sucesso=1");
            exit;
        } else {
            $error = "Erro ao adicionar matéria: " . $stmt->error;
        }
    }
}

// ====================================
// 2. LISTAR MATÉRIAS (para exibir na tabela)
// ====================================
$materias = [];
$result = $conn->query("
    SELECT m.*, c.nome as curso_nome 
    FROM materias m 
    LEFT JOIN cursos c ON m.curso_id = c.id 
    ORDER BY m.id DESC
");

if ($result) {
    $materias = $result->fetch_all(MYSQLI_ASSOC);
}

// ====================================
// 3. BUSCAR MATÉRIA PARA EDIÇÃO (via AJAX)
// ====================================
if (isset($_GET['acao']) && $_GET['acao'] == 'buscar_materia') {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM materias WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $materia = $result->fetch_assoc();
    
    if ($materia) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $materia]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Matéria não encontrada!']);
    }
    exit;
}

// ====================================
// 4. ATUALIZAR MATÉRIA (via AJAX)
// ====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao']) && $_POST['acao'] == 'editar') {
    $id = intval($_POST['id']);
    $nome = $_POST['nome'];
    $topico = $_POST['topico'];
    $tipo = $_POST['tipo'];
    $curso_id = intval($_POST['curso_id']);
    
    if ($tipo === 'pdf') {
        $conteudo = $_POST['link_drive'];
    } elseif ($tipo === 'video') {
        $conteudo = $_POST['link'];
    }

    $stmt = $conn->prepare("UPDATE materias SET nome = ?, topico = ?, tipo = ?, conteudo = ?, curso_id = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $nome, $topico, $tipo, $conteudo, $curso_id, $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Matéria atualizada com sucesso!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar matéria: ' . $stmt->error]);
    }
    exit;
}

// ====================================
// 5. EXCLUIR MATÉRIA (via AJAX)
// ====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['acao']) && $_POST['acao'] == 'excluir') {
    $id = intval($_POST['id']);

    // Verificar se há materiais concluídas relacionadas
    $stmtVerifica = $conn->prepare("SELECT COUNT(*) FROM materias_concluidas WHERE materia_id = ?");
    $stmtVerifica->bind_param("i", $id);
    $stmtVerifica->execute();
    $stmtVerifica->bind_result($count);
    $stmtVerifica->fetch();
    $stmtVerifica->close();

    if ($count > 0) {
        // Se houver registros relacionados, exclua-os primeiro
        $stmtDeleteRelated = $conn->prepare("DELETE FROM materias_concluidas WHERE materia_id = ?");
        $stmtDeleteRelated->bind_param("i", $id);
        $stmtDeleteRelated->execute();
        $stmtDeleteRelated->close();
    }

    // Agora excluir a matéria
    $stmt = $conn->prepare("DELETE FROM materias WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Matéria excluída com sucesso!']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Erro ao excluir matéria: ' . $stmt->error]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Gerenciar Matérias</title>
  <link rel="stylesheet" href="assets/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      background: #f1f3f8;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .main-content {
      padding: 2rem 1rem;
    }

    .form-wrapper, .table-wrapper {
      background: #ffffff;
      padding: 2rem;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      max-width: 1200px;
      margin: 0 auto 2rem auto;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(79, 70, 229, 0.1);
    }

    .form-wrapper::before, .table-wrapper::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #6366f1, #8b5cf6);
    }

    .form-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .form-header h2, .table-header h2 {
      color: #4f46e5;
      font-size: 1.75rem;
      font-weight: 600;
      margin: 0 0 0.5rem 0;
    }

    .form-header p, .table-header p {
      color: #6b7280;
      margin: 0;
      font-size: 1.1rem;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .form-group {
      position: relative;
    }

    label {
      display: block;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    label i {
      color: #6366f1;
      width: 16px;
    }

    input[type="text"],
    input[type="url"],
    select {
      width: 100%;
      padding: 1rem;
      border-radius: 12px;
      border: 2px solid #e5e7eb;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fff;
      position: relative;
    }

    input[type="text"]:focus,
    input[type="url"]:focus,
    select:focus {
      outline: none;
      border-color: #6366f1;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
      transform: translateY(-1px);
    }

    select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 0.75rem center;
      background-repeat: no-repeat;
      background-size: 1.5em 1.5em;
      padding-right: 2.5rem;
    }

    .tipo-toggle {
      display: flex;
      background: #f3f4f6;
      border-radius: 12px;
      padding: 4px;
      position: relative;
    }

    .tipo-option {
      flex: 1;
      padding: 0.75rem 1rem;
      text-align: center;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
      position: relative;
      z-index: 2;
    }

    .tipo-option.active {
      background: #6366f1;
      color: white;
      box-shadow: 0 2px 8px rgba(99, 102, 241, 0.25);
    }

    .tipo-option:not(.active) {
      color: #6b7280;
    }

    .campo-condicional {
      margin-top: 1rem;
      padding: 1.5rem;
      background: #f8fafc;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Estilos especiais para o campo Google Drive */
    .drive-instructions {
      background: #e8f4f8;
      border: 1px solid #bee5eb;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
      color: #0c5460;
    }

    .drive-instructions h4 {
      margin: 0 0 0.5rem 0;
      color: #0c5460;
      font-size: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .drive-instructions ol {
      margin: 0.5rem 0 0 0;
      padding-left: 1.2rem;
    }

    .drive-instructions li {
      margin-bottom: 0.3rem;
      line-height: 1.4;
    }

    .drive-url-example {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      padding: 0.5rem;
      margin: 0.5rem 0;
      font-family: monospace;
      font-size: 0.85rem;
      word-break: break-all;
      color: #495057;
    }

    button, .btn {
      padding: 0.75rem 1.5rem;
      background: #6366f1;
      color: white;
      border: none;
      border-radius: 12px;
      font-weight: 500;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
      overflow: hidden;
      margin-top: 1rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    button:hover, .btn:hover {
      background: #4f46e5;
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
    }

    button:active, .btn:active {
      transform: translateY(0);
    }

    .btn-secondary {
      background: #6b7280;
    }

    .btn-secondary:hover {
      background: #4b5563;
    }

    .btn-danger {
      background: #ef4444;
    }

    .btn-danger:hover {
      background: #dc2626;
    }

    .success, .error {
      background: #ecfdf5;
      color: #059669;
      border: 1px solid #a7f3d0;
      padding: 1.25rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      text-align: center;
      font-weight: 500;
      animation: slideIn 0.5s ease;
    }

    .error {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
    }

    .success i {
      margin-right: 0.5rem;
      font-size: 1.2rem;
    }

    .error i {
      margin-right: 0.5rem;
      font-size: 1.2rem;
    }

    datalist {
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    /* Estilos da tabela */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1.5rem;
    }

    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }

    th {
      background-color: #f9fafb;
      color: #374151;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      letter-spacing: 0.05em;
    }

    tr:hover {
      background-color: #f9fafb;
    }

    .badge {
      display: inline-block;
      padding: 0.35rem 0.65rem;
      font-size: 0.75rem;
      font-weight: 600;
      line-height: 1;
      text-align: center;
      white-space: nowrap;
      vertical-align: baseline;
      border-radius: 0.375rem;
    }

    .badge-pdf {
      color: #fff;
      background-color: #ef4444;
    }

    .badge-video {
      color: #fff;
      background-color: #3b82f6;
    }

    .actions {
      display: flex;
      gap: 0.5rem;
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      overflow: auto;
    }

    .modal-content {
      background-color: #fff;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      width: 90%;
      max-width: 600px;
      position: relative;
      animation: modalFadeIn 0.3s ease;
    }

    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .close {
      position: absolute;
      top: 1rem;
      right: 1.5rem;
      font-size: 1.5rem;
      color: #6b7280;
      cursor: pointer;
      transition: color 0.2s ease;
    }

    .close:hover {
      color: #1f2937;
    }

    .modal-header {
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e5e7eb;
    }

    .modal-header h3 {
      margin: 0;
      color: #4f46e5;
      font-size: 1.5rem;
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .form-wrapper, .table-wrapper {
        padding: 1.5rem;
        margin: 1rem auto;
        border-radius: 20px;
      }

      .form-header h2, .table-header h2 {
        font-size: 1.5rem;
      }

      .tipo-toggle {
        flex-direction: column;
        gap: 4px;
      }

      .tipo-option {
        padding: 1rem;
      }

      table {
        display: block;
        overflow-x: auto;
      }

      .modal-content {
        margin: 10% auto;
        width: 95%;
      }
    }

    /* Animações de entrada */
    .form-group {
      animation: fadeInUp 0.6s ease forwards;
      opacity: 0;
    }

    .form-group:nth-child(1) { animation-delay: 0.1s; }
    .form-group:nth-child(2) { animation-delay: 0.2s; }
    .form-group:nth-child(3) { animation-delay: 0.3s; }
    .form-group:nth-child(4) { animation-delay: 0.4s; }
    .form-group:nth-child(5) { animation-delay: 0.5s; }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>
  <?php include 'menu.php'; ?>

  <main class="main-content">
    <!-- Formulário para adicionar nova matéria -->
    <div class="form-wrapper">
      <div class="form-header">
        <h2><i class="fas fa-plus-circle"></i> Nova Matéria</h2>
        <p>Adicione conteúdo educacional ao seu curso</p>
      </div>

      <?php if ($msg): ?>
        <div class="success">
          <i class="fas fa-check-circle"></i>
          <?= $msg ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="error">
          <i class="fas fa-exclamation-circle"></i>
          <?= $error ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="form-materia">
        <input type="hidden" name="adicionar" value="1">
        <div class="form-group">
          <label><i class="fas fa-graduation-cap"></i> Curso:</label>
          <select name="curso_id" id="curso_id" required onchange="buscarMateriasExistentes()">
            <option value="">Selecione um curso</option>
            <?php while ($curso = $cursos->fetch_assoc()): ?>
              <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label><i class="fas fa-book"></i> Nome da Matéria:</label>
          <input type="text" name="nome" id="nome" list="materias-existentes" required autocomplete="off" placeholder="Ex: Matemática Básica">
          <datalist id="materias-existentes">
            <!-- preenchido dinamicamente via JS -->
          </datalist>
        </div>

        <div class="form-group">
          <label><i class="fas fa-tag"></i> Tópico:</label>
          <input type="text" name="topico" placeholder="Ex: Gramática, Morfologia, Álgebra..." required>
        </div>

        <div class="form-group">
          <label><i class="fas fa-file-alt"></i> Tipo de Conteúdo:</label>
          <div class="tipo-toggle">
            <div class="tipo-option active" onclick="selecionarTipo('pdf')">
              <i class="fas fa-file-pdf"></i> PDF
            </div>
            <div class="tipo-option" onclick="selecionarTipo('video')">
              <i class="fas fa-video"></i> Vídeo
            </div>
          </div>
          <select name="tipo" id="tipo" style="display: none;">
            <option value="pdf">PDF</option>
            <option value="video">Vídeo</option>
          </select>
        </div>

        <div id="campo-pdf" class="campo-condicional">
          <div class="drive-instructions">
            <h4><i class="fab fa-google-drive"></i> Como usar o Google Drive:</h4>
            <ol>
              <li>Faça upload do seu PDF para o Google Drive</li>
              <li>Clique com o botão direito no arquivo → "Compartilhar"</li>
              <li>Altere as permissões para "Qualquer pessoa com o link pode visualizar"</li>
              <li>Copie o link de compartilhamento e cole abaixo</li>
            </ol>
            <small><strong>Exemplo de link:</strong></small>
            <div class="drive-url-example">
              https://drive.google.com/file/d/1ABC123xyz/view?usp=sharing
            </div>
          </div>
          <label><i class="fab fa-google-drive"></i> Link do Google Drive:</label>
          <input type="url" name="link_drive" placeholder="https://drive.google.com/file/d/..." />
        </div>

        <div id="campo-video" class="campo-condicional" style="display:none;">
          <label><i class="fas fa-link"></i> Link do Vídeo (YouTube):</label>
          <input type="url" name="link" placeholder="https://youtube.com/watch?v=..." />
        </div>

        <button type="submit">
          <i class="fas fa-save"></i> Salvar Matéria
        </button>
      </form>
    </div>

    <!-- Tabela de matérias existentes -->
    <div class="table-wrapper">
      <div class="table-header">
        <h2><i class="fas fa-list"></i> Matérias Cadastradas</h2>
        <p>Lista de todas as matérias disponíveis no sistema</p>
      </div>

      <table id="tabela-materias" class="display">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Tópico</th>
            <th>Tipo</th>
            <th>Curso</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($materias as $materia): ?>
            <tr>
              <td><?= $materia['id'] ?></td>
              <td><?= htmlspecialchars($materia['nome']) ?></td>
              <td><?= htmlspecialchars($materia['topico']) ?></td>
              <td>
                <span class="badge <?= $materia['tipo'] === 'pdf' ? 'badge-pdf' : 'badge-video' ?>">
                  <?= strtoupper($materia['tipo']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($materia['curso_nome'] ?? 'N/A') ?></td>
              <td class="actions">
                <button class="btn btn-secondary btn-editar" data-id="<?= $materia['id'] ?>">
                  <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-danger btn-excluir" data-id="<?= $materia['id'] ?>">
                  <i class="fas fa-trash"></i> Excluir
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Modal para edição -->
    <div id="modal-editar" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
          <h3><i class="fas fa-edit"></i> Editar Matéria</h3>
        </div>
        <form id="form-editar" method="POST">
          <input type="hidden" name="acao" value="editar">
          <input type="hidden" name="id" id="editar-id">
          
          <div class="form-group">
            <label><i class="fas fa-graduation-cap"></i> Curso:</label>
            <select name="curso_id" id="editar-curso_id" required>
              <option value="">Selecione um curso</option>
              <?php 
              $cursos = $conn->query("SELECT id, nome FROM cursos");
              while ($curso = $cursos->fetch_assoc()): ?>
                <option value="<?= $curso['id'] ?>"><?= htmlspecialchars($curso['nome']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label><i class="fas fa-book"></i> Nome da Matéria:</label>
            <input type="text" name="nome" id="editar-nome" required>
          </div>

          <div class="form-group">
            <label><i class="fas fa-tag"></i> Tópico:</label>
            <input type="text" name="topico" id="editar-topico" required>
          </div>

          <div class="form-group">
            <label><i class="fas fa-file-alt"></i> Tipo de Conteúdo:</label>
            <div class="tipo-toggle">
              <div class="tipo-option" id="editar-tipo-pdf" onclick="selecionarTipoModal('pdf')">
                <i class="fas fa-file-pdf"></i> PDF
              </div>
              <div class="tipo-option" id="editar-tipo-video" onclick="selecionarTipoModal('video')">
                <i class="fas fa-video"></i> Vídeo
              </div>
            </div>
            <select name="tipo" id="editar-tipo" style="display: none;">
              <option value="pdf">PDF</option>
              <option value="video">Vídeo</option>
            </select>
          </div>

          <div id="editar-campo-pdf" class="campo-condicional">
            <label><i class="fab fa-google-drive"></i> Link do Google Drive:</label>
            <input type="url" name="link_drive" id="editar-link_drive" placeholder="https://drive.google.com/file/d/...">
          </div>

          <div id="editar-campo-video" class="campo-condicional" style="display:none;">
            <label><i class="fas fa-link"></i> Link do Vídeo (YouTube):</label>
            <input type="url" name="link" id="editar-link" placeholder="https://youtube.com/watch?v=...">
          </div>

          <button type="submit" class="btn">
            <i class="fas fa-save"></i> Atualizar Matéria
          </button>
        </form>
      </div>
    </div>

    <!-- Modal para confirmação de exclusão -->
    <div id="modal-excluir" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
          <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão</h3>
        </div>
        <p>Tem certeza que deseja excluir esta matéria? Esta ação não pode ser desfeita.</p>
        <form id="form-excluir" method="POST">
          <input type="hidden" name="acao" value="excluir">
          <input type="hidden" name="id" id="excluir-id">
          
          <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="button" class="btn btn-secondary" onclick="fecharModal('modal-excluir')">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="submit" class="btn btn-danger">
              <i class="fas fa-trash"></i> Confirmar Exclusão
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script>
    // Inicializar DataTable
    $(document).ready(function() {
      $('#tabela-materias').DataTable({
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
        },
        responsive: true
      });
    });

    // Funções para o formulário principal
    function selecionarTipo(tipo) {
      // Atualizar visual dos botões
      document.querySelectorAll('.tipo-option').forEach(opt => {
        opt.classList.remove('active');
      });
      event.target.closest('.tipo-option').classList.add('active');
      
      // Atualizar select oculto
      document.getElementById('tipo').value = tipo;
      
      // Mostrar campo apropriado
      mostrarCampo();
    }

    function mostrarCampo() {
      const tipo = document.getElementById('tipo').value;
      const campoPdf = document.getElementById('campo-pdf');
      const campoVideo = document.getElementById('campo-video');
      
      campoPdf.style.display = tipo === 'pdf' ? 'block' : 'none';
      campoVideo.style.display = tipo === 'video' ? 'block' : 'none';
    }

    function buscarMateriasExistentes() {
      const cursoId = document.getElementById('curso_id').value;
      const datalist = document.getElementById('materias-existentes');

      datalist.innerHTML = '';

      if (cursoId) {
        fetch(`materias_do_curso.php?curso_id=${cursoId}`)
          .then(res => res.json())
          .then(materias => {
            materias.forEach(m => {
              const opt = document.createElement('option');
              opt.value = m.nome;
              datalist.appendChild(opt);
            });
          });
      }
    }

    // Funções para o modal de edição
    function selecionarTipoModal(tipo) {
      // Atualizar visual dos botões
      document.querySelectorAll('#modal-editar .tipo-option').forEach(opt => {
        opt.classList.remove('active');
      });
      
      if (tipo === 'pdf') {
        document.getElementById('editar-tipo-pdf').classList.add('active');
      } else {
        document.getElementById('editar-tipo-video').classList.add('active');
      }
      
      // Atualizar select oculto
      document.getElementById('editar-tipo').value = tipo;
      
      // Mostrar campo apropriado
      mostrarCampoModal();
    }

    function mostrarCampoModal() {
      const tipo = document.getElementById('editar-tipo').value;
      const campoPdf = document.getElementById('editar-campo-pdf');
      const campoVideo = document.getElementById('editar-campo-video');
      
      campoPdf.style.display = tipo === 'pdf' ? 'block' : 'none';
      campoVideo.style.display = tipo === 'video' ? 'block' : 'none';
    }

    // Manipulação dos modais
    function abrirModal(id) {
      document.getElementById(id).style.display = 'block';
    }

    function fecharModal(id) {
      document.getElementById(id).style.display = 'none';
    }

    // Fechar modal ao clicar no X ou fora do conteúdo
    window.onclick = function(event) {
      if (event.target.className === 'modal') {
        event.target.style.display = 'none';
      }
    }

    // Event listeners para os botões de edição
    document.querySelectorAll('.btn-editar').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        
        // Buscar dados da matéria via AJAX
        fetch(`?acao=buscar_materia&id=${id}`)
          .then(res => res.json())
          .then(data => {
            if (data.status === 'success') {
              const materia = data.data;
              
              // Preencher formulário de edição
              document.getElementById('editar-id').value = materia.id;
              document.getElementById('editar-curso_id').value = materia.curso_id;
              document.getElementById('editar-nome').value = materia.nome;
              document.getElementById('editar-topico').value = materia.topico;
              document.getElementById('editar-tipo').value = materia.tipo;
              
              // Configurar tipo de conteúdo
              selecionarTipoModal(materia.tipo);
              
              // Preencher conteúdo com base no tipo
              if (materia.tipo === 'pdf') {
                document.getElementById('editar-link_drive').value = materia.conteudo;
              } else {
                document.getElementById('editar-link').value = materia.conteudo;
              }
              
              // Abrir modal
              abrirModal('modal-editar');
            } else {
              alert(data.message);
            }
          });
      });
    });

    // Event listeners para os botões de exclusão
    document.querySelectorAll('.btn-excluir').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        document.getElementById('excluir-id').value = id;
        abrirModal('modal-excluir');
      });
    });

    // Fechar modais ao clicar no X
    document.querySelectorAll('.modal .close').forEach(close => {
      close.addEventListener('click', function() {
        fecharModal(this.closest('.modal').id);
      });
    });

    // Enviar formulário de edição via AJAX
    document.getElementById('form-editar').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          alert(data.message);
          location.reload();
        } else {
          alert(data.message);
        }
      });
    });

    // Enviar formulário de exclusão via AJAX
    document.getElementById('form-excluir').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          alert(data.message);
          location.reload();
        } else {
          alert(data.message);
        }
      });
    });

    // Inicializar na carga da página
    window.onload = function() {
      mostrarCampo();
      
      // Definir tipo inicial como PDF
      document.getElementById('tipo').value = 'pdf';
      document.querySelector('.tipo-option').classList.add('active');
    };
  </script>
</body>
</html>