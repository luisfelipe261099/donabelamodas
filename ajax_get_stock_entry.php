<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

$id = $_GET['id'];

try {
    // Get stock entry details
    $stmt = $pdo->prepare("
        SELECT se.*, u.name as user_name
        FROM stock_entries se
        LEFT JOIN users u ON se.user_id = u.id
        WHERE se.id = ?
    ");
    $stmt->execute([$id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$entry) {
        echo json_encode(['error' => 'Entrada não encontrada']);
        exit;
    }

    // Get items
    $stmt = $pdo->prepare("
        SELECT sei.*, p.name as product_name, p.code as product_code
        FROM stock_entry_items sei
        LEFT JOIN products p ON sei.product_id = p.id
        WHERE sei.stock_entry_id = ?
        ORDER BY sei.id
    ");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'entry' => $entry,
        'items' => $items
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
