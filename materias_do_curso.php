<?php include 'verificar_sessao.php'; ?>
<?php
include 'db.php';

$curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;
$result = $conn->query("SELECT DISTINCT nome FROM materias WHERE curso_id = $curso_id");

$materias = [];
while ($row = $result->fetch_assoc()) {
  $materias[] = ['nome' => $row['nome']];
}

header('Content-Type: application/json');
echo json_encode($materias);
