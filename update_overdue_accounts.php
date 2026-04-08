<?php
require_once 'config/db.php';
session_start();

// Update overdue accounts
$stmt = $pdo->prepare("
    UPDATE accounts_receivable 
    SET status = 'overdue' 
    WHERE due_date < CURDATE() 
    AND status IN ('pending', 'partial')
");
$stmt->execute();

echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
?>

