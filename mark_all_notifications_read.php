<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    
    echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
}
?>

