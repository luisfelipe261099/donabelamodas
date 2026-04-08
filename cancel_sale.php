<?php
require_once 'config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sale_id = $data['sale_id'] ?? null;

    if (!$sale_id) {
        echo json_encode(['success' => false, 'message' => 'ID da venda não fornecido']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Buscar informações da venda
        $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sale) {
            throw new Exception('Venda não encontrada');
        }

        // 2. Buscar itens da venda para devolver ao estoque
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Devolver produtos ao estoque
        $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        foreach ($items as $item) {
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        // 4. Se for crediário, excluir da conta a receber
        if ($sale['payment_method'] === 'Account') {
            // Buscar conta a receber relacionada
            $stmt = $pdo->prepare("SELECT id FROM accounts_receivable WHERE sale_id = ?");
            $stmt->execute([$sale_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($account) {
                // Excluir pagamentos relacionados
                $pdo->prepare("DELETE FROM account_payments WHERE account_id = ?")->execute([$account['id']]);
                
                // Excluir conta a receber
                $pdo->prepare("DELETE FROM accounts_receivable WHERE id = ?")->execute([$account['id']]);
            }
        }

        // 5. Excluir itens da venda
        $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$sale_id]);

        // 6. Excluir a venda
        $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sale_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Venda cancelada com sucesso!',
            'sale_id' => $sale_id
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>

