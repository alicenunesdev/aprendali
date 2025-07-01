<?php
/**
 * ===============================================
 * CONFIGURAÇÃO DO BANCO DE DADOS - APRENDALI
 * ===============================================
 */

$host = 'localhost';
$user = 'root';  
$pass = 'cpd@sorento';
$db   = 'aprendali';

// Conexão MySQLi (mantida para compatibilidade)
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Erro MySQLi: " . $conn->connect_error);
    die("Erro na conexão: " . $conn->connect_error);
}

// Configurar charset
$conn->set_charset("utf8mb4");

// Função para obter conexão PDO
function getPDOConnection() {
    global $host, $user, $pass, $db;
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro PDO: " . $e->getMessage());
        throw new Exception("Erro na conexão com o banco de dados");
    }
}

// Configurações globais
define('DB_HOST', $host);
define('DB_USER', $user);
define('DB_PASS', $pass);
define('DB_NAME', $db);
?>