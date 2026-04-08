<?php
// Set Brazil timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações TiDB Cloud (donabela)
$host = 'gateway01.us-east-1.prod.aws.tidbcloud.com';
$port = 4000;
$db_name = 'donabela';
$username = '2TTR5UU5w3Zdwys.root';
$password = 'LbPeOxZuD5HSMvkI';

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
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>