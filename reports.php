<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Date Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// 1. Financial Summary (Filtered by Date)
// Total Sales
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_sales = $stmt->fetchColumn() ?: 0;

// Total Cost (Approximate based on current cost)
$stmt = $pdo->prepare("
    SELECT SUM(si.quantity * p.cost_price) 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    JOIN sales s ON si.sale_id = s.id 
    WHERE DATE(s.created_at) BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$total_cost = $stmt->fetchColumn() ?: 0;

$net_revenue = $total_sales - $total_cost;

// 2. Inventory Value (Current Snapshot)
$stock_cost = $pdo->query("SELECT SUM(stock * cost_price) FROM products")->fetchColumn() ?: 0;
$stock_sell = $pdo->query("SELECT SUM(stock * sell_price) FROM products")->fetchColumn() ?: 0;
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
$total_items = $pdo->query("SELECT SUM(stock) FROM products")->fetchColumn() ?: 0;

?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-white">Relatórios Gerenciais</h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Data Inicial</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Final</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Faturamento Bruto</h5>
                    <h2 class="fw-bold">R$ <?php echo number_format($total_sales, 2, ',', '.'); ?></h2>
                    <small>No período selecionado</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Custo Produtos Vendidos</h5>
                    <h2 class="fw-bold">R$ <?php echo number_format($total_cost, 2, ',', '.'); ?></h2>
                    <small>Baseado no custo atual</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Faturamento Líquido</h5>
                    <h2 class="fw-bold">R$ <?php echo number_format($net_revenue, 2, ',', '.'); ?></h2>
                    <small>Lucro Bruto</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Cards -->
    <h4 class="text-white mb-3">Valor de Estoque (Atual)</h4>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Valor em Custo</h5>
                    <h3>R$ <?php echo number_format($stock_cost, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Valor em Venda</h5>
                    <h3>R$ <?php echo number_format($stock_sell, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-secondary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Produtos</h5>
                    <h3><?php echo $total_products; ?> SKUs</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark border-secondary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Itens</h5>
                    <h3><?php echo $total_items; ?> un</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>