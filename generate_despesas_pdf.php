<?php
require_once 'config/db.php';
session_start();

// Filtros (mesmos da pagina de despesas)
$f_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$f_category = isset($_GET['category']) ? $_GET['category'] : '';
$f_status = isset($_GET['status']) ? $_GET['status'] : '';
$f_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$f_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = "1=1";
$params = [];
if ($f_search !== '') {
    $where .= " AND (description LIKE ? OR notes LIKE ?)";
    $params[] = "%$f_search%";
    $params[] = "%$f_search%";
}
if ($f_category !== '') {
    $where .= " AND category = ?";
    $params[] = $f_category;
}
if ($f_status == 'Pago') {
    $where .= " AND status = 'Pago'";
} elseif ($f_status == 'Pendente') {
    $where .= " AND COALESCE(status, 'Pendente') != 'Pago'";
}
if ($f_start !== '') {
    $where .= " AND expense_date >= ?";
    $params[] = $f_start;
}
if ($f_end !== '') {
    $where .= " AND expense_date <= ?";
    $params[] = $f_end;
}

// Despesas filtradas
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE $where ORDER BY expense_date DESC, id DESC");
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totais
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $where");
$stmt->execute($params);
$total_expenses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $where AND COALESCE(status, 'Pendente') != 'Pago'");
$stmt->execute($params);
$total_pending = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $where AND status = 'Pago'");
$stmt->execute($params);
$total_paid = $stmt->fetchColumn();

// Totais por categoria
$stmt = $pdo->prepare("SELECT category, COUNT(*) as qty, SUM(amount) as total FROM expenses WHERE $where GROUP BY category ORDER BY total DESC");
$stmt->execute($params);
$by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Relatório de Despesas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { margin: 20px 0; text-align: center; }
        .summary-box { display: inline-block; width: 22%; padding: 15px; margin: 5px; background: #f0f0f0; border-radius: 5px; text-align: center; }
        .summary-box h3 { margin: 0; font-size: 14px; color: #666; }
        .summary-box .value { font-size: 22px; font-weight: bold; color: #333; margin: 10px 0; }
        h2 { color: #333; font-size: 16px; border-bottom: 2px solid #D4574A; padding-bottom: 5px; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background-color: #D4574A; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #D4574A !important; color: white; font-weight: bold; }
        .badge-pago { background: #28a745; color: white; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
        .badge-pendente { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 3px; font-size: 11px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .no-print { text-align: center; margin: 20px 0; }
        .no-print button { padding: 10px 30px; font-size: 16px; cursor: pointer; background: #D4574A; color: white; border: none; border-radius: 5px; }
        @media print {
            .no-print { display: none !important; }
            * {
                font-weight: bold !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            th, .total-row { color: #fff !important; }
            .badge-pago, .badge-pendente { color: #000 !important; }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>RELATÓRIO DE DESPESAS</h1>
        <?php if ($f_start || $f_end): ?>
            <p>Período:
                <?php echo $f_start ? date('d/m/Y', strtotime($f_start)) : 'início'; ?>
                a
                <?php echo $f_end ? date('d/m/Y', strtotime($f_end)) : 'hoje'; ?>
            </p>
        <?php else: ?>
            <p>Período: todas as datas</p>
        <?php endif; ?>
        <?php if ($f_category): ?>
            <p>Categoria: <?php echo htmlspecialchars($f_category); ?></p>
        <?php endif; ?>
        <?php if ($f_status): ?>
            <p>Situação: <?php echo htmlspecialchars($f_status); ?></p>
        <?php endif; ?>
        <?php if ($f_search): ?>
            <p>Busca: "<?php echo htmlspecialchars($f_search); ?>"</p>
        <?php endif; ?>
        <p>Gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="summary">
        <div class="summary-box">
            <h3>Total de Despesas</h3>
            <div class="value">R$ <?php echo number_format($total_expenses, 2, ',', '.'); ?></div>
            <small><?php echo count($expenses); ?> despesa(s)</small>
        </div>
        <div class="summary-box">
            <h3>Pendentes</h3>
            <div class="value">R$ <?php echo number_format($total_pending, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-box">
            <h3>Pagas</h3>
            <div class="value">R$ <?php echo number_format($total_paid, 2, ',', '.'); ?></div>
        </div>
    </div>

    <?php if (count($by_category) > 0): ?>
        <h2>Totais por Categoria</h2>
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th class="text-center">Quantidade</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">% do Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($by_category as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['category']); ?></td>
                        <td class="text-center"><?php echo $cat['qty']; ?></td>
                        <td class="text-right">R$ <?php echo number_format($cat['total'], 2, ',', '.'); ?></td>
                        <td class="text-right">
                            <?php echo $total_expenses > 0 ? number_format(($cat['total'] / $total_expenses) * 100, 1, ',', '.') : '0'; ?>%
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="text-center"><?php echo count($expenses); ?></td>
                    <td class="text-right">R$ <?php echo number_format($total_expenses, 2, ',', '.'); ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Despesas Detalhadas</h2>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th class="text-right">Valor</th>
                <th class="text-center">Situação</th>
                <th>Pago em</th>
                <th>Observações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expenses as $expense): ?>
                <?php $is_paid = ($expense['status'] ?? 'Pendente') == 'Pago'; ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                    <td><?php echo htmlspecialchars($expense['category']); ?></td>
                    <td class="text-right">R$ <?php echo number_format($expense['amount'], 2, ',', '.'); ?></td>
                    <td class="text-center">
                        <span class="<?php echo $is_paid ? 'badge-pago' : 'badge-pendente'; ?>">
                            <?php echo $is_paid ? 'Pago' : 'Pendente'; ?>
                        </span>
                    </td>
                    <td><?php echo !empty($expense['paid_at']) ? date('d/m/Y', strtotime($expense['paid_at'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($expense['notes'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($expenses) == 0): ?>
                <tr>
                    <td colspan="7" class="text-center">Nenhuma despesa encontrada.</td>
                </tr>
            <?php else: ?>
                <tr class="total-row">
                    <td colspan="3">TOTAL</td>
                    <td class="text-right">R$ <?php echo number_format($total_expenses, 2, ',', '.'); ?></td>
                    <td colspan="3"></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Dona Bela Modas - Relatório de Despesas</p>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Imprimir / Salvar PDF</button>
    </div>

    <script>
        window.print();
    </script>
</body>

</html>
