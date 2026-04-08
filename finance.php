<?php 
session_start();
require_once 'config/db.php';

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
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    WHERE $where 
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$filtered_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get accounts receivable summary
$accounts_pending = $pdo->query("SELECT COALESCE(SUM(remaining_amount), 0) FROM accounts_receivable WHERE status IN ('pending', 'partial', 'overdue')")->fetchColumn();
$accounts_overdue = $pdo->query("SELECT COALESCE(SUM(remaining_amount), 0) FROM accounts_receivable WHERE status = 'overdue'")->fetchColumn();

// Payment method translations
$payment_names = [
    'Cash' => 'Dinheiro',
    'Credit Card' => 'Crédito',
    'Debit Card' => 'Débito',
    'Pix' => 'Pix',
    'Account' => 'Crediário',
    'Mixed' => 'Misto'
];

$payment_icons = [
    'Cash' => '💵',
    'Credit Card' => '💳',
    'Debit Card' => '💳',
    'Pix' => '📱',
    'Account' => '📋',
    'Mixed' => '🔀'
];

include 'includes/header.php';
?>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .card { border: 1px solid #ddd !important; background: white !important; color: black !important; }
    .text-white { color: black !important; }
}

/* Custom scrollbar for sales table */
.card-body::-webkit-scrollbar {
    width: 10px;
}

.card-body::-webkit-scrollbar-track {
    background: #1a1a1a;
    border-radius: 5px;
}

.card-body::-webkit-scrollbar-thumb {
    background: #4CAF50;
    border-radius: 5px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #45a049;
}

/* Sticky table header */
#salesTable thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #212529 !important;
}

/* Smooth scrolling */
.card-body {
    scroll-behavior: smooth;
}
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="fw-bold text-white">💰 Financeiro</h2>
            <div class="no-print">
                <button class="btn btn-success me-2" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </button>
                <a href="generate_finance_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&payment_method=<?php echo $payment_method; ?>"
                   class="btn btn-danger me-2" target="_blank">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </a>
                <button class="btn btn-info" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Vendas Hoje</h6>
                            <h3 class="fw-bold mb-0">R$ <?php echo number_format($total_today, 2, ',', '.'); ?></h3>
                            <small><?php echo $count_today; ?> venda(s)</small>
                        </div>
                        <div>
                            <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Mês Atual</h6>
                            <h3 class="fw-bold mb-0">R$ <?php echo number_format($total_month, 2, ',', '.'); ?></h3>
                            <small><?php echo date('F Y'); ?></small>
                        </div>
                        <div>
                            <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">Período Filtrado</h6>
                            <h3 class="fw-bold mb-0">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></h3>
                            <small><?php echo $filtered_count; ?> venda(s)</small>
                        </div>
                        <div>
                            <i class="fas fa-filter fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>    </div>

    <!-- Filters -->
    <div class="card mb-4 no-print">
        <div class="card-header bg-dark">
            <h5 class="mb-0 text-white"><i class="fas fa-filter me-2"></i>Filtros de Pesquisa</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="finance.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-white">Data In�cio</label>
                    <input type="date" name="start_date" class="form-control bg-dark text-white border-secondary" 
                           value="<?php echo $start_date; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-white">Data Fim</label>
                    <input type="date" name="end_date" class="form-control bg-dark text-white border-secondary" 
                           value="<?php echo $end_date; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-white">Forma de Pagamento</label>
                    <select name="payment_method" class="form-select bg-dark text-white border-secondary">
                        <option value="">Todas as Formas</option>
                        <option value="Cash" <?php echo $payment_method == 'Cash' ? 'selected' : ''; ?>>?? Dinheiro</option>
                        <option value="Credit Card" <?php echo $payment_method == 'Credit Card' ? 'selected' : ''; ?>>?? Cart�o Cr�dito</option>
                        <option value="Debit Card" <?php echo $payment_method == 'Debit Card' ? 'selected' : ''; ?>>?? Cart�o D�bito</option>
                        <option value="Pix" <?php echo $payment_method == 'Pix' ? 'selected' : ''; ?>>?? Pix</option>
                        <option value="Account" <?php echo $payment_method == 'Account' ? 'selected' : ''; ?>>?? Credi�rio</option>
                        <option value="Mixed" <?php echo $payment_method == 'Mixed' ? 'selected' : ''; ?>>?? Misto</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                </div>
            </form>
            <div class="mt-3">
                <a href="finance.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Limpar
                </a>
                <button class="btn btn-sm btn-outline-info" onclick="setToday()">
                    <i class="fas fa-calendar-day me-1"></i>Hoje
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="setThisWeek()">
                    <i class="fas fa-calendar-week me-1"></i>Esta Semana
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="setThisMonth()">
                    <i class="fas fa-calendar-alt me-1"></i>Este M�s
                </button>
                <button class="btn btn-sm btn-outline-info" onclick="setLastMonth()">
                    <i class="fas fa-calendar me-1"></i>M�s Passado
                </button>
            </div>
        </div>
    </div>

    <!-- Payment Stats and Sales Details -->
    <div class="row g-4">
        <!-- Payment Methods Chart -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-dark">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-chart-pie me-2"></i>Vendas por Forma de Pagamento
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($payment_stats) > 0): ?>
                        <table class="table table-hover text-white mb-0">
                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($payment_stats as $stat):
                                    $icon = $payment_icons[$stat['payment_method']] ?? '💰';
                                    $payment_name = $payment_names[$stat['payment_method']] ?? $stat['payment_method'];
                                    $percentage = $filtered_total > 0 ? ($stat['total'] / $filtered_total) * 100 : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo $icon; ?> <?php echo $payment_name; ?>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $stat['count']; ?></span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            R$ <?php echo number_format($stat['total'], 2, ',', '.'); ?>
                                            <br><small class="text-muted"><?php echo number_format($percentage, 1); ?>%</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <th>TOTAL</th>
                                    <th class="text-center"><?php echo $filtered_count; ?></th>
                                    <th class="text-end text-success">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-pie fa-3x mb-3"></i>
                            <p>Nenhuma venda no período</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sales Details -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-list me-2"></i>Detalhamento de Vendas
                        <small class="ms-2">(<?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?>)</small>
                    </h5>
                    <span class="badge bg-primary">
                        <?php echo count($filtered_sales); ?> venda(s)
                    </span>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm text-white align-middle" id="salesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Data/Hora</th>
                                    <th>Vendedor</th>
                                    <th>Pagamento</th>
                                    <th class="text-end">Desconto</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($filtered_sales) > 0): ?>
                                    <?php foreach ($filtered_sales as $sale):
                                        $icon = $payment_icons[$sale['payment_method']] ?? '💰';
                                        $payment_name = $payment_names[$sale['payment_method']] ?? $sale['payment_method'];
                                        $badge_color = $sale['payment_method'] === 'Account' ? 'bg-warning text-dark' : 'bg-info text-dark';
                                    ?>
                                        <tr>
                                            <td><strong>#<?php echo $sale['id']; ?></strong></td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($sale['created_at'])); ?></small><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($sale['created_at'])); ?></small>
                                            </td>
                                            <td><?php echo $sale['user_name']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $badge_color; ?>">
                                                    <?php echo $icon; ?> <?php echo $payment_name; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($sale['discount'] > 0): ?>
                                                    <span class="text-warning">-R$ <?php echo number_format($sale['discount'], 2, ',', '.'); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end fw-bold text-success">
                                                R$ <?php echo number_format($sale['total_amount'], 2, ',', '.'); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            Nenhuma venda encontrada no período selecionado
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if (count($filtered_sales) > 0): ?>
                            <tfoot class="table-dark">
                                <tr>
                                    <th colspan="5" class="text-end">TOTAL DO PERÍODO:</th>
                                    <th class="text-end text-success">R$ <?php echo number_format($filtered_total, 2, ',', '.'); ?></th>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" class="btn btn-primary" style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;">
        <i class="fas fa-arrow-up"></i>
    </button>
</div>

<script>
// Scroll to top button
const scrollTopBtn = document.getElementById('scrollTopBtn');
const salesTableBody = document.querySelector('.card-body[style*="max-height"]');

if (salesTableBody) {
    salesTableBody.addEventListener('scroll', function() {
        if (this.scrollTop > 200) {
            scrollTopBtn.style.display = 'block';
        } else {
            scrollTopBtn.style.display = 'none';
        }
    });
}

scrollTopBtn.addEventListener('click', function() {
    if (salesTableBody) {
        salesTableBody.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
});

function setToday() {
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('input[name="start_date"]').value = today;
    document.querySelector('input[name="end_date"]').value = today;
}

function setThisWeek() {
    const today = new Date();
    const firstDay = new Date(today.setDate(today.getDate() - today.getDay()));
    const lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 6));

    document.querySelector('input[name="start_date"]').value = firstDay.toISOString().split('T')[0];
    document.querySelector('input[name="end_date"]').value = lastDay.toISOString().split('T')[0];
}

function setThisMonth() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    document.querySelector('input[name="start_date"]').value = firstDay.toISOString().split('T')[0];
    document.querySelector('input[name="end_date"]').value = lastDay.toISOString().split('T')[0];
}

function setLastMonth() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);

    document.querySelector('input[name="start_date"]').value = firstDay.toISOString().split('T')[0];
    document.querySelector('input[name="end_date"]').value = lastDay.toISOString().split('T')[0];
}

function exportToExcel() {
    // Get table
    var table = document.getElementById('salesTable');
    var html = table.outerHTML;

    // Create download link
    var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'relatorio_financeiro_' + new Date().toISOString().split('T')[0] + '.xls';
    link.click();
}
</script>

<?php include 'includes/footer.php'; ?>
