<?php
// config.php - Configurações do sistema
require_once 'db.php'; // Incluir sua conexão existente

// Configurações de segurança
define('ENCRYPTION_KEY', 'sua_chave_secreta_aqui_mude_em_producao');
define('SESSION_TIMEOUT', 1800); // 30 minutos em segundos
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos em segundos

// Configurações do sistema
define('SITE_URL', 'http://localhost/aprendali');
define('ADMIN_EMAIL', 'admin@aprendali.com');

// Fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Classe adaptada para usar sua conexão mysqli
class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        global $conn; // Usar sua conexão global
        $this->conn = $conn;
        
        if ($this->conn->connect_error) {
            error_log("Erro de conexão com banco: " . $this->conn->connect_error);
            die("Erro interno do servidor. Tente novamente mais tarde.");
        }
        
        // Configurar charset
        $this->conn->set_charset("utf8mb4");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Método para executar queries preparadas
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação da query: " . $this->conn->error);
            }
            
            if (!empty($params)) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                }
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            return $stmt;
        } catch (Exception $e) {
            error_log("Erro na query: " . $e->getMessage());
            throw new Exception("Erro interno do servidor");
        }
    }
    
    // Método para buscar um registro
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Método para buscar múltiplos registros
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Método para inserir e retornar o ID
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $this->conn->insert_id;
    }
    
    // Método para contar registros
    public function count($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        $row = $result->fetch_row();
        return $row[0];
    }
    
    // Método para executar query simples
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->affected_rows;
    }
}

// Função para sanitizar dados de entrada
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para gerar token seguro
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Função para hash de senha
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Função para verificar senha
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Função para obter IP do cliente
function getClientIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Função para obter User Agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
}

// Função para redirecionar
function redirect($url) {
    header("Location: $url");
    exit();
}

// Função para exibir mensagens JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Configurações de sessão segura
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID da sessão periodicamente para segurança
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>