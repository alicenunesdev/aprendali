<?php
include 'db.php';
session_start();

// Exemplo: usuário fixo user_id = 1
$user_id = 1;

if (isset($_POST['materia_id'])) {
    $materia_id = intval($_POST['materia_id']);
    $conn->query("INSERT INTO materias_concluidas (user_id, materia_id) VALUES ($user_id, $materia_id)");
    echo "ok";
} else {
    echo "erro";
}
?>
