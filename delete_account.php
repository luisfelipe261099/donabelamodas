<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $account_id = $data['account_id'] ?? null;

    if (!$account_id) {
        echo json_encode(['success' => false, 'message' => 'ID da conta não fornecido']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Buscar informações da conta
        $stmt = $pdo->prepare("SELECT * FROM accounts_receivable WHERE id = ?");
        $stmt->execute([$account_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception('Conta não encontrada');
        }

        // 2. Se tiver venda relacionada, excluir a venda também
        if ($account['sale_id']) {
            $sale_id = $account['sale_id'];

            // Buscar itens da venda para devolver ao estoque
            $stmt = $pdo->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
            $stmt->execute([$sale_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Devolver produtos ao estoque
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            foreach ($items as $item) {
                $stmt_stock->execute([$item['quantity'], $item['product_id']]);
            }

            // Excluir itens da venda
            $pdo->prepare("DELETE FROM sale_items WHERE sale_id = ?")->execute([$sale_id]);

            // Excluir a venda
            $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sale_id]);
        }

        // 3. Excluir pagamentos relacionados
        $pdo->prepare("DELETE FROM account_payments WHERE account_id = ?")->execute([$account_id]);

        // 4. Excluir a conta a receber
        $pdo->prepare("DELETE FROM accounts_receivable WHERE id = ?")->execute([$account_id]);

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Conta excluída com sucesso!' . ($account['sale_id'] ? ' A venda relacionada também foi excluída.' : ''),
            'account_id' => $account_id
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>

