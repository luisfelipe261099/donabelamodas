<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        throw new Exception('Dados inválidos');
    }

    $invoice_number = $data['invoice_number'] ?? '';
    $supplier = $data['supplier'] ?? '';
    $notes = $data['notes'] ?? '';
    $items = $data['items'] ?? [];

    if (empty($invoice_number)) {
        throw new Exception('Número da nota fiscal é obrigatório');
    }

    if (empty($items)) {
        throw new Exception('Adicione pelo menos um item');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Calculate totals
    $total_items = count($items);
    $total_cost = 0;

    foreach ($items as $item) {
        $total_cost += ($item['quantity'] * $item['cost_price']);
    }

    // Insert stock entry
    $stmt = $pdo->prepare("
        INSERT INTO stock_entries (invoice_number, supplier, total_items, total_cost, notes, user_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $invoice_number,
        $supplier,
        $total_items,
        $total_cost,
        $notes,
        $_SESSION['user_id']
    ]);

    $stock_entry_id = $pdo->lastInsertId();

    // Process each item
    foreach ($items as $item) {
        $product_id = null;

        // Check if it's a new product or existing
        if (isset($item['is_new_product']) && $item['is_new_product']) {
            // Create new product
            $stmt = $pdo->prepare("
                INSERT INTO products (code, name, category_id, size, color, gender, cost_price, sell_price, stock, image, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $item['code'] ?? '',
                $item['name'],
                $item['category_id'] ?? null,
                $item['size'] ?? 'Unico',
                $item['color'] ?? '',
                $item['gender'] ?? 'Unisex',
                $item['cost_price'],
                $item['sell_price'],
                $item['quantity'], // Initial stock
                $item['image'] ?? ''
            ]);

            $product_id = $pdo->lastInsertId();
        } else {
            // Update existing product stock
            $product_id = $item['product_id'];

            $stmt = $pdo->prepare("
                UPDATE products 
                SET stock = stock + ?, 
                    cost_price = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $item['quantity'],
                $item['cost_price'],
                $product_id
            ]);
        }

        // Insert stock entry item
        $subtotal = $item['quantity'] * $item['cost_price'];

        $stmt = $pdo->prepare("
            INSERT INTO stock_entry_items (stock_entry_id, product_id, quantity, cost_price, subtotal, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $stock_entry_id,
            $product_id,
            $item['quantity'],
            $item['cost_price'],
            $subtotal
        ]);
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Entrada de estoque registrada com sucesso!',
        'stock_entry_id' => $stock_entry_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
