<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_perfil'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$perfil = $_SESSION['usuario_perfil'];

$materia = $_GET['materia'] ?? null;
$curso_id = $_GET['curso_id'] ?? null;

try {
    $pdo = getPDOConnection();

    $sql = "SELECT q.*, c.nome as curso_nome
            FROM questoes q
            JOIN cursos c ON c.id = q.curso_id
            WHERE 1 = 1";
    $params = [];

    // Filtro por perfil
    if ($perfil === 'professor') {
        $sql .= " AND q.professor_id = ?";
        $params[] = $usuario_id;

    } elseif ($perfil === 'aluno') {
        // Aluno só vê questões dos cursos liberados
        $sql .= " AND q.curso_id IN (
                    SELECT curso_id FROM aluno_cursos 
                    WHERE aluno_id = ? AND ativo = 1
                 )";
        $params[] = $usuario_id;
    }

    // Filtro por curso
    if (!empty($curso_id)) {
        $sql .= " AND q.curso_id = ?";
        $params[] = $curso_id;
    }

    // Filtro por matéria
    if (!empty($materia)) {
        $sql .= " AND q.materia = ?";
        $params[] = $materia;
    }

    $sql .= " ORDER BY q.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($questoes);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao carregar questões: ' . $e->getMessage()]);
}
