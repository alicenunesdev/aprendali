<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_perfil'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$pdo = getPDOConnection();
$usuario_id = $_SESSION['usuario_id'];
$perfil = $_SESSION['usuario_perfil'];

$filtros = [
    'materias' => [],
    'cursos' => []
];

try {
    if ($perfil === 'admin') {
        // Admin: pega todos os cursos e todas as matérias
        $stmtCursos = $pdo->query("SELECT id, nome FROM cursos WHERE ativo = 1");
        $filtros['cursos'] = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

        $stmtMaterias = $pdo->query("SELECT DISTINCT nome FROM materias");
        $filtros['materias'] = array_column($stmtMaterias->fetchAll(PDO::FETCH_ASSOC), 'nome');

    } elseif ($perfil === 'professor') {
        // Professor: somente cursos e matérias que ele criou
        $stmtCursos = $pdo->prepare("SELECT id, nome FROM cursos WHERE ativo = 1 AND professor_id = ?");
        $stmtCursos->execute([$usuario_id]);
        $filtros['cursos'] = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

        $stmtMaterias = $pdo->prepare("SELECT DISTINCT nome FROM materias WHERE professor_id = ?");
        $stmtMaterias->execute([$usuario_id]);
        $filtros['materias'] = array_column($stmtMaterias->fetchAll(PDO::FETCH_ASSOC), 'nome');

    } elseif ($perfil === 'aluno') {
        // Aluno: pega os cursos liberados para ele e suas matérias
        $stmtCursos = $pdo->prepare("
            SELECT c.id, c.nome 
            FROM aluno_cursos ac
            JOIN cursos c ON c.id = ac.curso_id
            WHERE ac.aluno_id = ? AND ac.ativo = 1 AND c.ativo = 1
        ");
        $stmtCursos->execute([$usuario_id]);
        $filtros['cursos'] = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

        // Pega matérias dos cursos liberados
        $cursoIds = array_column($filtros['cursos'], 'id');
        if (count($cursoIds)) {
            $in = str_repeat('?,', count($cursoIds) - 1) . '?';
            $stmtMaterias = $pdo->prepare("SELECT DISTINCT nome FROM materias WHERE curso_id IN ($in)");
            $stmtMaterias->execute($cursoIds);
            $filtros['materias'] = array_column($stmtMaterias->fetchAll(PDO::FETCH_ASSOC), 'nome');
        }
    } else {
        echo json_encode(['error' => 'Perfil inválido.']);
        exit;
    }

    echo json_encode($filtros);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao carregar filtros: ' . $e->getMessage()]);
}
