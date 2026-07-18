<?php
require_once 'config/db.php';
session_start();

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// Build WHERE clause
$where = "DATE(s.created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];

if ($payment_method) {
    $where .= " AND s.payment_method = ?";
    $params[] = $payment_method;
}

// Totals
$total_today = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$total_month = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
$count_today = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Filtered total
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM sales s WHERE $where");
$stmt->execute($params);
$filtered_total = $stmt->fetchColumn();

// Count filtered sales
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sales s WHERE $where");
$stmt->execute($params);
$filtered_count = $stmt->fetchColumn();

// Average ticket
$avg_ticket = $filtered_count > 0 ? $filtered_total / $filtered_count : 0;

// Sales by Payment Method (filtered)
$stmt = $pdo->prepare("SELECT s.payment_method, SUM(s.total_amount) as total, COUNT(*) as count FROM sales s WHERE $where GROUP BY s.payment_method");
$stmt->execute($params);
$payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtered sales details
// Usa custo historico (sale_items.cost_price) se existir, senao custo atual do produto
$saleItemsHasCost = false;
try {
    $saleItemsHasCost = (bool) $pdo->query("SHOW COLUMNS FROM sale_items LIKE 'cost_price'")->fetch();
} catch (Exception $e) {
    $saleItemsHasCost = false;
}
$costExpr = $saleItemsHasCost
    ? "si.quantity * COALESCE(NULLIF(si.cost_price, 0), p.cost_price)"
    : "si.quantity * p.cost_price";

$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name,
        (SELECT COALESCE(SUM($costExpr), 0)
         FROM sale_items si
         JOIN products p ON si.product_id = p.id
         WHERE si.sale_id = s.id) as total_cost
    FROM sales s
    JOIN users u ON s.user_id = u.id
    WHERE $where
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$filtered_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Period totals: cost, profit and margin %
$period_cost = 0;
foreach ($filtered_sales as $s) {
    $period_cost += $s['total_cost'];
}
$period_profit = $filtered_total - $period_cost;
$period_margin = $filtered_total > 0 ? ($period_profit / $filtered_total) * 100 : 0;

// Despesas do periodo e lucro liquido
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$period_expenses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT category, COUNT(*) as qty, SUM(amount) as total FROM expenses WHERE expense_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC");
$stmt->execute([$start_date, $end_date]);
$expenses_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

$net_profit = $period_profit - $period_expenses;
$net_margin = $filtered_total > 0 ? ($net_profit / $filtered_total) * 100 : 0;

// Payment method translations
$payment_names = [
    'Cash' => 'Dinheiro',
    'Credit Card' => 'Credito',
    'Debit Card' => 'Debito',
    'Pix' => 'Pix',
    'Account' => 'Crediario',
    'Mixed' => 'Misto'
];

// Start HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório Financeiro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { margin: 20px 0; }
        .summary-box { display: inline-block; width: 23%; padding: 15px; margin: 5px; background: #f0f0f0; border-radius: 5px; text-align: center; }
        .summary-box h3 { margin: 0; font-size: 14px; color: #666; }
        .summary-box .value { font-size: 24px; font-weight: bold; color: #333; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #4CAF50 !important; color: white; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        /* Impressao em negrito e preto forte para sair mais escuro */
        @media print {
            * {
                font-weight: bold !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            /* Mantem legibilidade do texto sobre fundos coloridos */
            th, .total-row { color: #fff !important; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RELATÓRIO FINANCEIRO</h1>
        <p>Período: <?php echo date('d/m/Y', strtotime($start_date)); ?> a <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
        <?php if ($payment_method): ?>
            <p>Forma de Pagamento: <?php echo $payment_names[$payment_method] ?? $payment_method; ?></p>
        <?php endif; ?>
        <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="summary">
        <div class="summary-box">
            <h3>Vendas Hoje</h3>
            <div class="value">R$ <?php echo number_format($total_today, 2, ',', '.'); ?></div>
            <small><?php echo $count_today; ?> venda(s)</small>
        </div>
        <div class="summary-box">
            <h3>Mês Atual</h3>
            <div class="value">R$ <?php echo number_format($total_month, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-box">
            <h3>Período Filtrado</h3>
            <div class="value">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></div>
            <small><?php echo $filtered_count; ?> venda(s)</small>
        </div>
        <div class="summary-box">
            <h3>Ticket Médio</h3>
            <div class="value">R$ <?php echo number_format($avg_ticket, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-box">
            <h3>Custo (Gastei)</h3>
            <div class="value">R$ <?php echo number_format($period_cost, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-box">
            <h3>Lucro Bruto</h3>
            <div class="value">R$ <?php echo number_format($period_profit, 2, ',', '.'); ?></div>
            <small>Margem: <?php echo number_format($period_margin, 1, ',', '.'); ?>%</small>
        </div>
        <div class="summary-box">
            <h3>Despesas do Período</h3>
            <div class="value">R$ <?php echo number_format($period_expenses, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-box">
            <h3>Lucro Líquido</h3>
            <div class="value">R$ <?php echo number_format($net_profit, 2, ',', '.'); ?></div>
            <small>Margem: <?php echo number_format($net_margin, 1, ',', '.'); ?>% (Lucro Bruto − Despesas)</small>
        </div>
    </div>

    <h2>Vendas por Forma de Pagamento</h2>
    <table>
        <thead>
            <tr>
                <th>Forma de Pagamento</th>
                <th class="text-center">Quantidade</th>
                <th class="text-right">Total</th>
                <th class="text-right">%</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payment_stats as $stat): 
                $percentage = $filtered_total > 0 ? ($stat['total'] / $filtered_total) * 100 : 0;
                $payment_name = $payment_names[$stat['payment_method']] ?? $stat['payment_method'];
            ?>
                <tr>
                    <td><?php echo $payment_name; ?></td>
                    <td class="text-center"><?php echo $stat['count']; ?></td>
                    <td class="text-right">R$ <?php echo number_format($stat['total'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($percentage, 1); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="text-center"><?php echo $filtered_count; ?></td>
                <td class="text-right">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></td>
                <td class="text-right">100%</td>
            </tr>
        </tfoot>
    </table>

    <?php if (count($expenses_by_category) > 0): ?>
        <h2>Despesas do Período por Categoria</h2>
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th class="text-center">Quantidade</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses_by_category as $cat):
                    $percentage = $period_expenses > 0 ? ($cat['total'] / $period_expenses) * 100 : 0;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['category']); ?></td>
                        <td class="text-center"><?php echo $cat['qty']; ?></td>
                        <td class="text-right">R$ <?php echo number_format($cat['total'], 2, ',', '.'); ?></td>
                        <td class="text-right"><?php echo number_format($percentage, 1); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>TOTAL DE DESPESAS</td>
                    <td></td>
                    <td class="text-right">R$ <?php echo number_format($period_expenses, 2, ',', '.'); ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <h2>Resultado do Período</h2>
        <table>
            <tbody>
                <tr>
                    <td>Faturamento (Vendas)</td>
                    <td class="text-right">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>(−) Custo dos Produtos Vendidos</td>
                    <td class="text-right">R$ <?php echo number_format($period_cost, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>(=) Lucro Bruto</td>
                    <td class="text-right">R$ <?php echo number_format($period_profit, 2, ',', '.'); ?></td>
                </tr>
                <tr>
                    <td>(−) Despesas do Período</td>
                    <td class="text-right">R$ <?php echo number_format($period_expenses, 2, ',', '.'); ?></td>
                </tr>
                <tr class="total-row">
                    <td>(=) LUCRO LÍQUIDO</td>
                    <td class="text-right">R$ <?php echo number_format($net_profit, 2, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

    <h2>Detalhamento de Vendas</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data/Hora</th>
                <th>Vendedor</th>
                <th>Pagamento</th>
                <th class="text-right">Valor Venda</th>
                <th class="text-right">Desconto</th>
                <th class="text-right">Total</th>
                <th class="text-right">Custo</th>
                <th class="text-right">Lucro</th>
                <th class="text-right">Margem %</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($filtered_sales) > 0): ?>
                <?php foreach ($filtered_sales as $sale):
                    $payment_name = $payment_names[$sale['payment_method']] ?? $sale['payment_method'];
                ?>
                    <tr>
                        <td>#<?php echo $sale['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></td>
                        <td><?php echo $sale['user_name']; ?></td>
                        <td><?php echo $payment_name; ?></td>
                        <td class="text-right">R$ <?php echo number_format($sale['total_amount'] + $sale['discount'], 2, ',', '.'); ?></td>
                        <td class="text-right">
                            <?php if ($sale['discount'] > 0): ?>
                                R$ <?php echo number_format($sale['discount'], 2, ',', '.'); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-right">R$ <?php echo number_format($sale['total_amount'], 2, ',', '.'); ?></td>
                        <td class="text-right">R$ <?php echo number_format($sale['total_cost'], 2, ',', '.'); ?></td>
                        <?php
                            $sale_profit = $sale['total_amount'] - $sale['total_cost'];
                            $sale_margin = $sale['total_amount'] > 0 ? ($sale_profit / $sale['total_amount']) * 100 : 0;
                        ?>
                        <td class="text-right">R$ <?php echo number_format($sale_profit, 2, ',', '.'); ?></td>
                        <td class="text-right"><?php echo number_format($sale_margin, 1, ',', '.'); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">Nenhuma venda encontrada no período</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" class="text-right">TOTAL DO PERÍODO:</td>
                <td class="text-right">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></td>
                <td class="text-right">R$ <?php echo number_format($period_cost, 2, ',', '.'); ?></td>
                <td class="text-right">R$ <?php echo number_format($period_profit, 2, ',', '.'); ?></td>
                <td class="text-right"><?php echo number_format($period_margin, 1, ',', '.'); ?>%</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Sistema PDV - Relatório gerado automaticamente</p>
        <p><?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

