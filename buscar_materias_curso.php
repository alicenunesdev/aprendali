<?php
include 'db.php';

$curso_id = $_GET['curso_id'] ?? null;
if (!$curso_id) {
    echo json_encode(['materias' => []]);
    exit;
}

$pdo = getPDOConnection();
$stmt = $pdo->prepare("SELECT materias FROM cursos WHERE id = ?");
$stmt->execute([$curso_id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$materias = [];

if ($row && !empty($row['materias'])) {
    $materias = json_decode($row['materias'], true);
}

echo json_encode(['materias' => $materias]);
