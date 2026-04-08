<?php
// Set Brazil timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações via variáveis de ambiente (Vercel) com fallback local
$host = getenv('DB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com';
$port = getenv('DB_PORT') ?: 4000;
$db_name = getenv('DB_NAME') ?: 'donabela';
$username = getenv('DB_USER') ?: '2TTR5UU5w3Zdwys.root';
$password = getenv('DB_PASS') ?: 'LbPeOxZuD5HSMvkI';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
    ];

    // No Windows, usar o certificado do sistema
    if (PHP_OS_FAMILY === 'Windows') {
        // TiDB Cloud usa certificados públicos (ISRG Root) que o PHP/OpenSSL reconhece
        $options[PDO::MYSQL_ATTR_SSL_CA] = '';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("set names utf8mb4");
    // Set MySQL timezone to Brazil
    $pdo->exec("SET time_zone = '-03:00'");

    // Configurar sessão via banco de dados (necessário para Vercel/serverless)
    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/sessions.php';
        init_db_session($pdo);
    }
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>