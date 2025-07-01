<?php
session_start();

include 'db.php';
include 'menu.php';

$pdo = getPDOConnection();

$curso_id = $_GET['curso_id'] ?? 1;

$stmt = $pdo->prepare("SELECT * FROM materias WHERE curso_id = ?");
$stmt->execute([$curso_id]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
function extrairIDYoutube($url) {
    preg_match('/(?:v=|\/embed\/|\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches);
    return $matches[1] ?? '';
}

$cursoSelecionado = isset($_GET['curso']) ? intval($_GET['curso']) : null;
$user_id = 1;
$concluidas = [];

$result = $conn->query("SELECT materia_id FROM materias_concluidas WHERE user_id = $user_id");
while ($row = $result->fetch_assoc()) {
    $concluidas[] = $row['materia_id'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Ver Matérias</title>
<link rel="stylesheet" href="assets/style.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* Reset e Base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    color: #1e293b;
    line-height: 1.6;
    min-height: 100vh;
}

/* Menu Lateral */
.menu-toggle {
    position: fixed;
    top: 24px;
    left: 24px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px;
    cursor: pointer;
    z-index: 1002;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.12), 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
}

.menu-toggle:hover {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-color: #3b82f6;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.2), 0 4px 12px rgba(0, 0, 0, 0.08);
}

.hamburger-lines {
    width: 22px;
    height: 18px;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.hamburger-lines span {
    display: block;
    height: 2.5px;
    width: 100%;
    background: currentColor;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-menu {
    position: fixed;
    top: 0;
    left: -380px;
    width: 380px;
    height: 100vh;
    background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%);
    box-shadow: 4px 0 32px rgba(59, 130, 246, 0.08);
    z-index: 1001;
    transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    backdrop-filter: blur(20px);
}

.sidebar-menu.show {
    left: 0;
}

.sidebar-header {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 24px 28px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    position: relative;
}

.sidebar-header::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, #3b82f6 50%, transparent 100%);
}

.menu-close {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    border-radius: 12px;
    padding: 10px 16px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.menu-close:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.35);
    transform: scale(1.05);
}

.sidebar-content {
    padding: 28px;
}

.menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(2px);
}

.menu-overlay.show {
    opacity: 1;
    visibility: visible;
}

/* Container Principal */
.container-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 90px 24px 40px;
    transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.container-wrapper.menu-open {
    margin-left: 380px;
}

/* Header do Curso */
.course-header {
    text-align: center;
    margin-bottom: 64px;
    position: relative;
}

.course-header::after {
    content: '';
    position: absolute;
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 2px;
}

.course-header h2 {
    font-size: 2.75rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 12px;
    letter-spacing: -0.025em;
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.course-subtitle {
    color: #64748b;
    font-size: 1.15rem;
    font-weight: 400;
    letter-spacing: 0.01em;
}

/* Grid de Matérias */
.materias-grid {
    display: grid;
    gap: 36px;
}

/* Card de Matéria */
.materia-section {
    background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.08), 0 1px 4px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.materia-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.materia-section:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(59, 130, 246, 0.15), 0 4px 16px rgba(0, 0, 0, 0.08);
}

.materia-section:hover::before {
    opacity: 1;
}

/* Header da Matéria */
.materia-header {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 28px 32px;
    position: relative;
    overflow: hidden;
}

.materia-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.6;
}

.materia-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
}

.materia-titulo {
    font-size: 1.35rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    margin: 0;
    position: relative;
    z-index: 1;
}

.materia-stats {
    margin-top: 10px;
    font-size: 0.9rem;
    opacity: 0.9;
    font-weight: 400;
    position: relative;
    z-index: 1;
}

/* Lista de Tópicos */
.topicos-lista {
    padding: 0;
}

.topico-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.topico-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    transform: scaleY(0);
    transition: transform 0.2s ease;
}

.topico-item:last-child {
    border-bottom: none;
}

.topico-item:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.topico-item:hover::before {
    transform: scaleY(1);
}

/* Lado Esquerdo do Tópico */
.topico-info {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
}

.topico-numero {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #64748b;
    font-weight: 600;
    font-size: 0.9rem;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s ease;
}

.topico-item:hover .topico-numero {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-color: #3b82f6;
    transform: scale(1.05);
}

.topico-detalhes {
    flex: 1;
}

.topico-nome {
    font-weight: 500;
    color: #1e293b;
    font-size: 1rem;
    line-height: 1.4;
    margin: 0;
    transition: color 0.2s ease;
}

.topico-item:hover .topico-nome {
    color: #3b82f6;
}

.topico-tipo {
    font-size: 0.82rem;
    color: #64748b;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 500;
}

/* Ações do Tópico */
.topico-acoes {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-acao {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: #64748b;
    position: relative;
    overflow: hidden;
}

.btn-acao::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.btn-acao:hover {
    border-color: #3b82f6;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
}

.btn-acao:hover::before {
    opacity: 1;
}

.btn-acao span {
    position: relative;
    z-index: 1;
}

.btn-video span,
.btn-pdf span {
    font-size: 1.1rem;
}

.btn-concluido {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    border-color: #86efac;
    color: #166534;
}

.btn-concluido::before {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.btn-marcar {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-color: #7dd3fc;
    color: #0284c7;
}

.btn-marcar::before {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
}

/* Estado Vazio */
.empty-state {
    text-align: center;
    padding: 100px 40px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    border: 2px dashed #cbd5e1;
    color: #64748b;
    position: relative;
    overflow: hidden;
}

.empty-state::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%23cbd5e1" opacity="0.3"/></pattern></defs><rect width="200" height="200" fill="url(%23dots)"/></svg>');
    opacity: 0.5;
}

.empty-state h2 {
    font-size: 1.6rem;
    color: #475569;
    margin-bottom: 12px;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

.empty-state p {
    font-size: 1.05rem;
    line-height: 1.6;
    max-width: 420px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 2000;
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%);
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 32px 80px rgba(0, 0, 0, 0.4);
    max-width: 95%;
    max-height: 95%;
    width: auto;
    height: auto;
    border: 1px solid #e2e8f0;
    animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.modal-close {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.modal-close:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
}

.modal iframe {
    width: 800px;
    height: 450px;
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.modal embed {
    width: 800px;
    height: 600px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Notas Sidebar */
.sidebar-notas {
    position: fixed;
    right: -340px;
    top: 0;
    width: 340px;
    height: 100vh;
    background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%);
    box-shadow: -4px 0 32px rgba(59, 130, 246, 0.08);
    border-left: 1px solid #e2e8f0;
    z-index: 1001;
    transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow-y: auto;
    backdrop-filter: blur(20px);
}

.sidebar-notas.show {
    right: 0;
}

.notes-container {
    padding: 28px;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.notes-container h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 12px;
}

.notes-container h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 2px;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 1px;
}

.notes-container textarea {
    flex: 1;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    resize: none;
    font-family: inherit;
    font-size: 0.95rem;
    line-height: 1.6;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transition: all 0.3s ease;
    color: #1e293b;
}

.notes-container textarea:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 4px 12px rgba(59, 130, 246, 0.05);
}

.notes-container textarea::placeholder {
    color: #94a3b8;
    font-style: italic;
}

/* Botão de Notas */
.notes-toggle {
    position: fixed;
    top: 24px;
    right: 24px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 14px;
    cursor: pointer;
    z-index: 1002;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.12), 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 1.2rem;
    backdrop-filter: blur(10px);
}

.notes-toggle:hover {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-color: #3b82f6;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.2), 0 4px 12px rgba(0, 0, 0, 0.08);
}

/* Responsividade */
@media (max-width: 1024px) {
    .container-wrapper.menu-open {
        margin-left: 0;
    }
    
    .modal iframe,
    .modal embed {
        width: 100%;
        height: 60vh;
    }
    
    .sidebar-notas {
        display: none;
    }
    
    .notes-toggle {
        display: none;
    }
    
    .sidebar-menu {
        width: 100%;
        left: -100%;
    }
}

@media (max-width: 768px) {
    .container-wrapper {
        padding: 80px 20px 40px;
    }
    
    .course-header h2 {
        font-size: 2.2rem;
    }
    
    .materia-header {
        padding: 24px 24px;
    }
    
    .topico-item {
        padding: 20px 24px;
    }
    
    .modal-content {
        margin: 20px;
        padding: 24px;
    }
    
    .modal iframe,
    .modal embed {
        width: 100%;
        height: 50vh;
    }
    
    .topico-info {
        gap: 16px;
    }
    
    .btn-acao {
        width: 38px;
        height: 38px;
        gap: 8px;
    }
}

/* Animações */
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

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.materia-section {
    animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.topico-item {
    animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.materia-section:nth-child(1) { animation-delay: 0.1s; }
.materia-section:nth-child(2) { animation-delay: 0.2s; }
.materia-section:nth-child(3) { animation-delay: 0.3s; }
.materia-section:nth-child(4) { animation-delay: 0.4s; }

.topico-item:nth-child(1) { animation-delay: 0.1s; }
.topico-item:nth-child(2) { animation-delay: 0.15s; }
.topico-item:nth-child(3) { animation-delay: 0.2s; }
.topico-item:nth-child(4) { animation-delay: 0.25s; }
.topico-item:nth-child(5) { animation-delay: 0.3s; }
</style>
</head>
<body>

<!-- Botão Menu -->
<button class="menu-toggle" id="menu-toggle" aria-label="Abrir menu">
    <div class="hamburger-lines">
        <span></span>
        <span></span>
        <span></span>
    </div>
</button>

<!-- Botão Notas -->
<button class="notes-toggle" id="notes-toggle" aria-label="Abrir notas">
    📝
</button>

<!-- Menu Lateral -->
<div id="sidebar-menu" class="sidebar-menu">
    <div class="sidebar-header">
        <button id="menu-close" class="menu-close" aria-label="Fechar menu">✕</button>
    </div>
    <div class="sidebar-content">
        <?php include 'menu.php'; ?>
    </div>
</div>

<!-- Notas Sidebar -->
<div id="sidebar-notas" class="sidebar-notas">
    <div class="notes-container">
        <h3>Minhas Anotações</h3>
        <textarea placeholder="Escreva suas anotações aqui..." id="notes-textarea"></textarea>
    </div>
</div>

<!-- Overlay -->
<div id="menu-overlay" class="menu-overlay"></div>

<!-- Conteúdo Principal -->
<div class="container-wrapper" id="container-wrapper">
    <?php if ($cursoSelecionado): ?>
        <?php
        // Buscar informações do curso
        $curso = $conn->query("SELECT nome FROM cursos WHERE id = $cursoSelecionado")->fetch_assoc();
        
        // Buscar matérias agrupadas por nome
        $materiasQuery = $conn->query("SELECT * FROM materias WHERE curso_id = $cursoSelecionado ORDER BY nome, id");
        $materiasPorNome = [];
        
        while ($m = $materiasQuery->fetch_assoc()) {
            $materiasPorNome[$m['nome']][] = $m;
        }
        ?>

        <!-- Header do Curso -->
        <div class="course-header">
            <h2><?= htmlspecialchars($curso['nome']) ?></h2>
            <p class="course-subtitle"><?= count($materiasPorNome) ?> matéria(s) disponível(is)</p>
        </div>

        <!-- Grid de Matérias -->
        <div class="materias-grid">
            <?php foreach ($materiasPorNome as $nomeMateria => $topicos): ?>
                <div class="materia-section">
                    <!-- Header da Matéria -->
                    <div class="materia-header">
                        <h3 class="materia-titulo"><?= htmlspecialchars($nomeMateria) ?></h3>
                        <div class="materia-stats">
                            <?= count($topicos) ?> tópico(s) • 
                            <?= count(array_filter($topicos, function($t) use ($concluidas) { return in_array($t['id'], $concluidas); })) ?> concluído(s)
                        </div>
                    </div>

                    <!-- Lista de Tópicos -->
                    <div class="topicos-lista">
                        <?php foreach ($topicos as $index => $topico): ?>
                            <div class="topico-item">
                                <!-- Info do Tópico -->
                                <div class="topico-info">
                                    <div class="topico-numero"><?= str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></div>
                                    <div class="topico-detalhes">
                                        <h4 class="topico-nome"><?= htmlspecialchars($topico['topico']) ?></h4>
                                        <div class="topico-tipo"><?= $topico['tipo'] === 'pdf' ? 'PDF' : 'Vídeo' ?></div>
                                    </div>
                                </div>

                                <!-- Ações do Tópico -->
                                <div class="topico-acoes">
                                    <?php if ($topico['tipo'] === 'pdf'): ?>
                                        <button class="btn-acao btn-pdf" onclick="abrirPDF('<?= $topico['conteudo'] ?>')" title="Visualizar PDF">
                                            📄
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-acao btn-video" onclick="abrirVideo('<?= extrairIDYoutube($topico['conteudo']) ?>')" title="Assistir Vídeo">
                                            ▶️
                                        </button>
                                    <?php endif; ?>

                                    <?php if (in_array($topico['id'], $concluidas)): ?>
                                        <div class="btn-acao btn-concluido" title="Concluído">
                                            ✅
                                        </div>
                                    <?php else: ?>
                                        <button class="btn-acao btn-marcar" onclick="marcarVisto(<?= $topico['id'] ?>, this)" title="Marcar como Concluído">
                                            ☐
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Estado Vazio -->
        <div class="empty-state">
            <h2>Selecione um Curso</h2>
            <p>Escolha um curso no menu para visualizar as matérias e tópicos disponíveis.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Vídeo -->
<div id="videoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Assistir Vídeo</h3>
            <button class="modal-close" onclick="fecharModal()">Fechar</button>
        </div>
        <iframe id="videoFrame" src="" allowfullscreen></iframe>
    </div>
</div>

<!-- Modal de PDF -->
<div id="pdfModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Visualizar PDF</h3>
            <button class="modal-close" onclick="fecharModalPDF()">Fechar</button>
        </div>
        <embed id="pdfFrame" src="" type="application/pdf">
    </div>
</div>

<script>
// Funções do Modal de Vídeo
function abrirVideo(videoId) {
    document.getElementById('videoFrame').src = 'https://www.youtube.com/embed/' + videoId;
    document.getElementById('videoModal').style.display = 'block';
}

function fecharModal() {
    document.getElementById('videoModal').style.display = 'none';
    document.getElementById('videoFrame').src = '';
}

// Funções do Modal de PDF
// Funções do Modal de PDF
function abrirPDF(pdfUrl) {
    const visualizacaoURL = 'https://docs.google.com/gview?url=' + encodeURIComponent(pdfUrl) + '&embedded=true';
    document.getElementById('pdfFrame').src = visualizacaoURL;
    document.getElementById('pdfModal').style.display = 'block';
}


function fecharModalPDF() {
    document.getElementById('pdfModal').style.display = 'none';
    document.getElementById('pdfFrame').src = '';
}

// Marcar como visto
function marcarVisto(materiaId, botao) {
    fetch('marcar_visto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'materia_id=' + materiaId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            botao.innerHTML = '✅';
            botao.className = 'btn-acao btn-concluido';
            botao.onclick = null;
            botao.title = 'Concluído';
        }
    })
    .catch(() => {
        // Fallback para versão antiga
        botao.innerHTML = '✅';
        botao.className = 'btn-acao btn-concluido';
        botao.onclick = null;
        botao.title = 'Concluído';
    });
}

// Controle do Menu
document.addEventListener("DOMContentLoaded", function() {
    const menuToggle = document.getElementById("menu-toggle");
    const menuClose = document.getElementById("menu-close");
    const sidebarMenu = document.getElementById("sidebar-menu");
    const menuOverlay = document.getElementById("menu-overlay");
    const containerWrapper = document.getElementById("container-wrapper");
    
    // Controle das Notas
    const notesToggle = document.getElementById("notes-toggle");
    const sidebarNotas = document.getElementById("sidebar-notas");

    // Menu Functions
    function openMenu() {
        sidebarMenu.classList.add("show");
        menuOverlay.classList.add("show");
        if (window.innerWidth > 1024) {
            containerWrapper.classList.add("menu-open");
        }
    }

    function closeMenu() {
        sidebarMenu.classList.remove("show");
        menuOverlay.classList.remove("show");
        containerWrapper.classList.remove("menu-open");
    }

    // Notes Functions
    function toggleNotes() {
        sidebarNotas.classList.toggle("show");
    }

    // Event Listeners
    menuToggle?.addEventListener("click", openMenu);
    menuClose?.addEventListener("click", closeMenu);
    menuOverlay?.addEventListener("click", closeMenu);
    notesToggle?.addEventListener("click", toggleNotes);

    // Fechar com ESC
    document.addEventListener("keydown", function(e) {
        if (e.key === "Escape") {
            closeMenu();
            sidebarNotas.classList.remove("show");
            fecharModal();
            fecharModalPDF();
        }
    });

    // Responsive behavior
    window.addEventListener("resize", function() {
        if (window.innerWidth <= 1024) {
            containerWrapper.classList.remove("menu-open");
        } else if (sidebarMenu.classList.contains("show")) {
            containerWrapper.classList.add("menu-open");
        }
    });

    // Fechar menu ao clicar em links
    const menuLinks = sidebarMenu.querySelectorAll('a');
    menuLinks.forEach(link => {
        link.addEventListener('click', () => setTimeout(closeMenu, 300));
    });

    // Salvar notas automaticamente
    const notesTextarea = document.getElementById('notes-textarea');
    if (notesTextarea) {
        // Carregar notas salvas
        const savedNotes = localStorage.getItem('course-notes-<?= $cursoSelecionado ?>');
        if (savedNotes) {
            notesTextarea.value = savedNotes;
        }

        // Salvar notas automaticamente
        notesTextarea.addEventListener('input', function() {
            localStorage.setItem('course-notes-<?= $cursoSelecionado ?>', this.value);
        });
    }
});

// Fechar modals clicando fora
document.getElementById('videoModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});

document.getElementById('pdfModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalPDF();
    }
});
</script>

</body>
</html>