<?php
require_once 'config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Carrinho vazio']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Get current open cash register
        $stmt = $pdo->prepare("SELECT id FROM cash_register WHERE user_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $cash_register = $stmt->fetch(PDO::FETCH_ASSOC);
        $cash_register_id = $cash_register ? $cash_register['id'] : null;

        // Calculate total from items
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Apply discount
        $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
        $total_amount = $subtotal - $discount;

        // Handle payment method
        $payment_method = $data['payment_method'];
        
        // Handle customer_id for Account payment
        $customer_id = null;
        if ($payment_method === 'Account' && isset($data['customer_id'])) {
            $customer_id = $data['customer_id'];
        }

        // Create Sale - IMPORTANT: total_amount is the final total (subtotal - discount)
        $stmt = $pdo->prepare("INSERT INTO sales (user_id, total_amount, discount, payment_method, cash_register_id, customer_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total_amount, $discount, $payment_method, $cash_register_id, $customer_id]);
        $sale_id = $pdo->lastInsertId();

        // Add Items and Update Stock
        $stmt_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($data['items'] as $item) {
            $unit_price = floatval($item['price']);
            $quantity = intval($item['quantity']);
            $item_subtotal = $unit_price * $quantity;

            $stmt_item->execute([
                $sale_id,
                $item['id'],
                $quantity,
                $unit_price,
                $item_subtotal
            ]);

            $stmt_stock->execute([$quantity, $item['id']]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'sale_id' => $sale_id]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
