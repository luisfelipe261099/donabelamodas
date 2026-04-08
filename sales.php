<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Date Filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build Query
$where = "DATE(s.created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];

// Get Sales
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE $where 
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Totals for the period
$total_sales = 0;
foreach ($sales as $sale) {
    $total_sales += $sale['total_amount'];
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-white">Histórico de Vendas</h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-white">Data Inicial</label>
                    <input type="date" name="start_date" class="form-control bg-dark text-white border-secondary"
                        value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-white">Data Final</label>
                    <input type="date" name="end_date" class="form-control bg-dark text-white border-secondary"
                        value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales List -->
    <div class="card">
        <div class="card-header bg-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white">Vendas do Período</h5>
            <span class="badge bg-success fs-6">Total: R$ <?php echo number_format($total_sales, 2, ',', '.'); ?></span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-white">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Vendedor</th>
                            <th>Pagamento</th>
                            <th class="text-end">Desconto</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($sales) > 0): ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td>#<?php echo $sale['id']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($sale['created_at'])); ?>
                                        <br>
                                        <small
                                            class="text-muted"><?php echo date('H:i', strtotime($sale['created_at'])); ?></small>
                                    </td>
                                    <td><?php echo $sale['user_name']; ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo $sale['payment_method']; ?></span>
                                    </td>
                                    <td class="text-end text-warning">
                                        <?php echo $sale['discount'] > 0 ? '-R$ ' . number_format($sale['discount'], 2, ',', '.') : '-'; ?>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        R$ <?php echo number_format($sale['total_amount'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="text-end" style="white-space: nowrap;">
                                        <button class="btn btn-sm btn-primary"
                                            onclick="viewSaleItems(<?php echo $sale['id']; ?>)">
                                            <i class="fas fa-eye me-1"></i> Ver Itens
                                        </button>
                                        <button class="btn btn-sm btn-danger ms-1"
                                            onclick="deleteSale(<?php echo $sale['id']; ?>)">
                                            <i class="fas fa-trash-alt me-1"></i> Excluir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Nenhuma venda encontrada no período.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Sale Items Modal -->
<div class="modal fade" id="saleItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Detalhes da Venda #<span id="modalSaleId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Preço Unit.</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="saleItemsTableBody">
                            <!-- Items will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewSaleItems(saleId) {
        // Set modal title
        document.getElementById('modalSaleId').textContent = saleId;

        // Clear previous items
        const tbody = document.getElementById('saleItemsTableBody');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Carregando...</td></tr>';

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('saleItemsModal'));
        modal.show();

        // Fetch items
        fetch(`ajax_get_sale_items.php?id=${saleId}`)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum item encontrado.</td></tr>';
                    return;
                }

                data.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                    <td>${item.product_name || 'Produto Removido'}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">R$ ${parseFloat(item.unit_price).toFixed(2).replace('.', ',')}</td>
                    <td class="text-end">R$ ${parseFloat(item.subtotal).toFixed(2).replace('.', ',')}</td>
                `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Erro ao carregar itens.</td></tr>';
            });
    }

    function deleteSale(saleId) {
        if (confirm('Tem certeza que deseja excluir esta venda? O estoque será restaurado e esta ação não pode ser desfeita.')) {
            fetch('cancel_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sale_id: saleId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Venda excluída com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir venda: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao processar a solicitação.');
                });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>