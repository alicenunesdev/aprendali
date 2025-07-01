<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['usuario_id'])) {
  $_SESSION['mensagem'] = "Sessão expirada.";
  $_SESSION['tipo_mensagem'] = "error";
  header("Location: gerenciar_questoes.php");
  exit;
}

$professor_id = $_SESSION['usuario_id'];

$curso_id = $_POST['curso_id'];
$materia = $_POST['materia'];
$dificuldade = $_POST['dificuldade'];
$pergunta = $_POST['pergunta'];
$resposta_A = $_POST['resposta_A'];
$resposta_B = $_POST['resposta_B'];
$resposta_C = $_POST['resposta_C'];
$resposta_D = $_POST['resposta_D'];
$resposta_correta = $_POST['resposta_correta'];
$descricao = $_POST['descricao'] ?? null;

try {
  $pdo = getPDOConnection();

  $stmt = $pdo->prepare("INSERT INTO questoes (curso_id, materia, dificuldade, pergunta, resposta_A, resposta_B, resposta_C, resposta_D, resposta_correta, descricao, professor_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([
    $curso_id, $materia, $dificuldade, $pergunta,
    $resposta_A, $resposta_B, $resposta_C, $resposta_D,
    $resposta_correta, $descricao, $professor_id
  ]);

  $_SESSION['mensagem'] = "Questão cadastrada com sucesso!";
  $_SESSION['tipo_mensagem'] = "success";

} catch (Exception $e) {
  $_SESSION['mensagem'] = "Erro ao salvar: " . $e->getMessage();
  $_SESSION['tipo_mensagem'] = "error";
}

header("Location: gerenciar_questoes.php");
exit;
