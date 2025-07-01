<?php
session_start();
include 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = 'ID da questão não fornecido.';
    $_SESSION['tipo_mensagem'] = 'error';
    header("Location: gerenciar_questoes.php");
    exit;
}

$id = (int) $_GET['id'];

if ($id <= 0) {
    $_SESSION['mensagem'] = 'ID da questão inválido.';
    $_SESSION['tipo_mensagem'] = 'error';
    header("Location: gerenciar_questoes.php");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id FROM questoes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['mensagem'] = 'Questão não encontrada.';
        $_SESSION['tipo_mensagem'] = 'error';
        header("Location: gerenciar_questoes.php");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM questoes WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Questão excluída com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        throw new Exception('Erro ao excluir: ' . $stmt->error);
    }

} catch (Exception $e) {
    $_SESSION['mensagem'] = 'Erro: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'error';
}

header("Location: gerenciar_questoes.php");
exit;
?>
