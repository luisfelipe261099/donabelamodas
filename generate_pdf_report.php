<?php
require_once 'config/db.php';

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// Build WHERE clause
$where = "DATE(created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($payment_method) {
    $where .= " AND payment_method = ?";
    $params[] = $payment_method;
}

// Filtered total
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE $where");
$stmt->execute($params);
$filtered_total = $stmt->fetchColumn() ?: 0;

// Sales by Payment Method (filtered)
$stmt = $pdo->prepare("SELECT payment_method, SUM(total_amount) as total, COUNT(*) as count FROM sales WHERE $where GROUP BY payment_method");
$stmt->execute($params);
$payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtered sales details
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    WHERE $where 
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$filtered_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro - PDV Dona Bela</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
        }
        .info-box {
            background: #f0f0f0;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h3 {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #333;
            color: white;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .summary-box {
            flex: 1;
            background: #e8f4f8;
            padding: 15px;
            margin: 0 5px;
            border-radius: 5px;
            text-align: center;
        }
        .summary-box h4 {
            margin: 0 0 10px 0;
            color: #666;
        }
        .summary-box .value {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }
        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PDV DONA BELA</h1>
        <p>Relatório Financeiro</p>
        <p>Período: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
        <?php if ($payment_method): ?>
            <p>Forma de Pagamento: <?php echo $payment_method; ?></p>
        <?php endif; ?>
        <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="summary">
        <div class="summary-box">
            <h4>Total do Período</h4>
            <div class="value">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-box">
            <h4>Quantidade de Vendas</h4>
            <div class="value"><?php echo count($filtered_sales); ?></div>
        </div>
        <div class="summary-box">
            <h4>Ticket Médio</h4>
            <div class="value">R$ <?php echo count($filtered_sales) > 0 ? number_format($filtered_total / count($filtered_sales), 2, ',', '.') : '0,00'; ?></div>
        </div>
    </div>

    <h3>Vendas por Forma de Pagamento</h3>
    <table>
        <thead>
            <tr>
                <th>Método de Pagamento</th>
                <th class="text-center">Quantidade</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payment_stats as $stat): ?>
                <tr>
                    <td><?php echo $stat['payment_method']; ?></td>
                    <td class="text-center"><?php echo $stat['count']; ?></td>
                    <td class="text-end">R$ <?php echo number_format($stat['total'], 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Detalhamento de Vendas</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data/Hora</th>
                <th>Vendedor</th>
                <th>Pagamento</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>

            <?php if (count($filtered_sales) > 0): ?>
                <?php foreach ($filtered_sales as $sale): ?>
                    <tr>
                        <td>#<?php echo $sale['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
                        <td><?php echo $sale['user_name']; ?></td>
                        <td><?php echo $sale['payment_method']; ?></td>
                        <td class="text-end">R$ <?php echo number_format($sale['total_amount'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Nenhuma venda encontrada no período</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <th colspan="4" class="text-end">TOTAL:</th>
                <th class="text-end">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></th>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 40px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer;">
            Imprimir / Salvar como PDF
        </button>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>