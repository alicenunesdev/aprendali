<?php
/**
 * ===============================================
 * AUTH/LOGIN.PHP - SISTEMA APRENDALI
 * ===============================================
 */

session_start();

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

require_once '../db.php'; // ajuste o caminho se necessário

try {
    $data = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data) {
            $data = $_POST;
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $data = $_GET;
    }

    if (!$data || (!isset($data['email']) && !isset($data['usuario'])) || !isset($data['senha'])) {
        echo json_encode(['success' => false, 'message' => 'Dados de login inválidos']);
        exit();
    }

    $login = trim($data['email'] ?? $data['usuario'] ?? '');
    $senha = trim($data['senha']);

    $pdo = getPDOConnection();

    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE (email = ? OR usuario = ?) AND ativo = 1");
    $stmt->execute([$login, $login]);
    $usuario_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_data) {
        echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        exit();
    }

    if ($senha !== $usuario_data['senha']) {
        echo json_encode(['success' => false, 'message' => 'Credenciais inválidas.']);
        exit();
    }

    // LOGIN OK
    session_regenerate_id(true);
    $session_id = session_id();

    $_SESSION['logado'] = true;
    $_SESSION['usuario_id'] = $usuario_data['id'];
    $_SESSION['usuario_nome'] = $usuario_data['nome'];
    $_SESSION['usuario_email'] = $usuario_data['email'];
    $_SESSION['usuario_perfil'] = $usuario_data['perfil'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Finalizar sessões anteriores
    $stmt = $pdo->prepare("UPDATE sessoes_ativas SET ativo = 0 WHERE usuario_id = ?");
    $stmt->execute([$usuario_data['id']]);

    // Registrar nova sessão
    $stmt = $pdo->prepare("
        INSERT INTO sessoes_ativas 
        (session_id, usuario_id, usuario_nome, usuario_login, usuario_email, usuario_perfil, browser_fingerprint, ip_address, user_agente, data_login, data_ultima_atividade, ativo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 1)
    ");
    $stmt->execute([
        $session_id,
        $usuario_data['id'],
        $usuario_data['nome'],
        $usuario_data['usuario'],
        $usuario_data['email'],
        $usuario_data['perfil'],
        hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'usuario' => [
            'id' => (int)$usuario_data['id'],
            'nome' => $usuario_data['nome'],
            'email' => $usuario_data['email'],
            'perfil' => $usuario_data['perfil']
        ],
        'sessao' => [
            'session_id' => $session_id,
            'login_time' => time()
        ],
        'redirect' => 'index.php'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Erro no login: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor.'
    ], JSON_UNESCAPED_UNICODE);
}
