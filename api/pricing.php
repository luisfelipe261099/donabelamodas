<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Fetch products with margin calculation
$products = $pdo->query("
    SELECT 
        p.*, 
        c.name as category_name,
        (p.sell_price - p.cost_price) as profit_margin,
        ((p.sell_price - p.cost_price) / p.sell_price * 100) as margin_percent
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-white">Precificação e Margens</h2>
            <p class="text-muted">Analise a lucratividade e simule descontos.</p>
        </div>
    </div>

    <!-- Pricing Calculator -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 text-white"><i class="fas fa-calculator me-2"></i>Calculadora de Precificação</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="calcCost" class="form-label text-white">Custo (R$)</label>
                    <input type="number" class="form-control" id="calcCost" step="0.01" placeholder="0,00">
                </div>
                <div class="col-md-4">
                    <label for="calcMargin" class="form-label text-white">Margem Desejada (%)</label>
                    <input type="number" class="form-control" id="calcMargin" step="0.1" placeholder="0">
                </div>
                <div class="col-md-4">
                    <label for="calcPrice" class="form-label text-white">Preço de Venda Sugerido</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-white border-secondary">R$</span>
                        <input type="text" class="form-control bg-dark text-white" id="calcPrice" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const costInput = document.getElementById('calcCost');
            const marginInput = document.getElementById('calcMargin');
            const priceInput = document.getElementById('calcPrice');

            function calculatePrice() {
                const cost = parseFloat(costInput.value) || 0;
                const margin = parseFloat(marginInput.value) || 0;

                if (cost > 0 && margin < 100) {
                    // Formula: Price = Cost / (1 - (Margin / 100))
                    const price = cost / (1 - (margin / 100));
                    priceInput.value = price.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                } else {
                    priceInput.value = '---';
                }
            }

            costInput.addEventListener('input', calculatePrice);
            marginInput.addEventListener('input', calculatePrice);
        });
    </script>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Custo</th>
                            <th>Venda</th>
                            <th>Margem R$</th>
                            <th>Margem %</th>
                            <th>Desconto Máx. Sugerido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <?php
                            // Logic for max suggested discount (e.g., leaving at least 10% margin)
                            $safe_margin_percent = 10;
                            $min_sell_price = $prod['cost_price'] / (1 - ($safe_margin_percent / 100));
                            $max_discount = $prod['sell_price'] - $min_sell_price;
                            $max_discount = max(0, $max_discount); // No negative discount
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo $prod['name']; ?></div>
                                    <small class="text-muted"><?php echo $prod['category_name']; ?></small>
                                </td>
                                <td>R$ <?php echo number_format($prod['cost_price'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($prod['sell_price'], 2, ',', '.'); ?></td>
                                <td
                                    class="fw-bold <?php echo $prod['profit_margin'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    R$ <?php echo number_format($prod['profit_margin'], 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?php echo $prod['margin_percent'] > 30 ? 'bg-success' : ($prod['margin_percent'] > 15 ? 'bg-warning' : 'bg-danger'); ?>"
                                            role="progressbar"
                                            style="width: <?php echo min(100, max(0, $prod['margin_percent'])); ?>%">
                                            <?php echo number_format($prod['margin_percent'], 1, ',', '.'); ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark">
                                        Até R$ <?php echo number_format($max_discount, 2, ',', '.'); ?>
                                    </span>
                                    <small class="d-block text-muted">Para manter <?php echo $safe_margin_percent; ?>% de
                                        margem</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>