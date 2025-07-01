<?php
session_start();
require_once '../db.php'; // Ajustado para o caminho relativo

if (isset($_SESSION['usuario_id'])) {
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("UPDATE sessoes_ativas SET ativo = 0 WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
    } catch (Exception $e) {
        error_log('Erro ao encerrar sessão ativa: ' . $e->getMessage());
    }
}

session_unset();
session_destroy();

header("Location: ../login.html");
exit();
