<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

// Get unread notifications count
$stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$unread_count = $stmt->fetchColumn();

// Get recent notifications (last 10)
$stmt = $pdo->query("
    SELECT * FROM notifications 
    ORDER BY created_at DESC 
    LIMIT 10
");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for accounts due today and tomorrow
$stmt = $pdo->query("
    SELECT ar.*, c.name as customer_name 
    FROM accounts_receivable ar
    JOIN customers c ON ar.customer_id = c.id
    WHERE ar.status IN ('pending', 'partial')
    AND ar.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ORDER BY ar.due_date
");
$accounts_due_soon = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create notifications for accounts due soon if not already created
foreach ($accounts_due_soon as $account) {
    $days_until_due = floor((strtotime($account['due_date']) - time()) / 86400);
    
    if ($days_until_due == 0) {
        $title = "Conta vence hoje!";
        $message = "Conta #{$account['id']} de {$account['customer_name']} vence hoje. Valor: R$ " . number_format($account['remaining_amount'], 2, ',', '.');
    } else {
        $title = "Conta vence amanhã";
        $message = "Conta #{$account['id']} de {$account['customer_name']} vence amanhã. Valor: R$ " . number_format($account['remaining_amount'], 2, ',', '.');
    }
    
    // Check if notification already exists for today
    $stmt = $pdo->prepare("
        SELECT id FROM notifications 
        WHERE type = 'account_due' 
        AND related_id = ? 
        AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$account['id']]);
    
    if (!$stmt->fetch()) {
        // Create notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (type, title, message, related_id, related_type) 
            VALUES ('account_due', ?, ?, ?, 'account')
        ");
        $stmt->execute([$title, $message, $account['id']]);
    }
}

// Get updated notifications
$stmt = $pdo->query("
    SELECT * FROM notifications 
    ORDER BY created_at DESC 
    LIMIT 10
");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$unread_count = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);
?>

