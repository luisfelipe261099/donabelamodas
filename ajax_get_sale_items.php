<?php
require_once 'config/db.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT si.quantity, si.unit_price, si.subtotal, p.name as product_name 
        FROM sale_items si 
        LEFT JOIN products p ON si.product_id = p.id 
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>