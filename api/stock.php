<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Date Filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Build Query
$where = "DATE(se.created_at) BETWEEN ? AND ?";
$params = [$start_date, $end_date];

// Get Stock Entries
$stmt = $pdo->prepare("
    SELECT se.*, u.name as user_name 
    FROM stock_entries se 
    LEFT JOIN users u ON se.user_id = u.id 
    WHERE $where 
    ORDER BY se.created_at DESC
");
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Totals
$total_entries = count($entries);
$total_cost = 0;
$total_items = 0;
foreach ($entries as $entry) {
    $total_cost += $entry['total_cost'];
    $total_items += $entry['total_items'];
}

// Fetch categories and products for the modal
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, code, name, stock, cost_price, sell_price FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-white">Gerenciamento de Estoque</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" onclick="openNewEntryModal()">
                <i class="fas fa-plus me-2"></i> Nova Entrada de Estoque
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total de Entradas</h6>
                            <h3 class="mb-0"><?php echo $total_entries; ?></h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total de Itens</h6>
                            <h3 class="mb-0"><?php echo $total_items; ?></h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Custo Total</h6>
                            <h3 class="mb-0">R$ <?php echo number_format($total_cost, 2, ',', '.'); ?></h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
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

    <!-- Entries List -->
    <div class="card">
        <div class="card-header bg-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white">Entradas de Estoque</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-white">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Nota Fiscal</th>
                            <th>Fornecedor</th>
                            <th>Usuário</th>
                            <th class="text-center">Itens</th>
                            <th class="text-end">Custo Total</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($entries) > 0): ?>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td>#<?php echo $entry['id']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($entry['created_at'])); ?>
                                        <br>
                                        <small
                                            class="text-muted"><?php echo date('H:i', strtotime($entry['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo $entry['invoice_number']; ?></span>
                                    </td>
                                    <td><?php echo $entry['supplier'] ?: '-'; ?></td>
                                    <td><?php echo $entry['user_name']; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?php echo $entry['total_items']; ?></span>
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        R$ <?php echo number_format($entry['total_cost'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary"
                                            onclick="viewEntryDetails(<?php echo $entry['id']; ?>)">
                                            <i class="fas fa-eye me-1"></i> Ver Detalhes
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhuma entrada encontrada no período.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- New Entry Modal -->
<div class="modal fade" id="newEntryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Nova Entrada de Estoque</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockEntryForm">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Número da Nota Fiscal *</label>
                            <input type="text" id="invoice_number"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fornecedor</label>
                            <input type="text" id="supplier" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Observações</label>
                            <input type="text" id="notes" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Itens da Entrada</h6>
                        <div>
                            <button type="button" class="btn btn-sm btn-success me-2" onclick="addExistingProduct()">
                                <i class="fas fa-box me-1"></i> Adicionar Produto Existente
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addNewProduct()">
                                <i class="fas fa-plus me-1"></i> Adicionar Novo Produto
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-dark table-striped" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Código</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Preço Custo</th>
                                    <th class="text-end">Preço Venda</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr id="emptyRow">
                                    <td colspan="7" class="text-center text-muted">Nenhum item adicionado</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="5" class="text-end">TOTAL:</td>
                                    <td class="text-end text-success" id="totalCost">R$ 0,00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveStockEntry()">
                    <i class="fas fa-save me-2"></i> Salvar Entrada
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Entry Details Modal -->
<div class="modal fade" id="entryDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Detalhes da Entrada #<span id="detailsEntryId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Nota Fiscal:</strong> <span id="detailsInvoice"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Fornecedor:</strong> <span id="detailsSupplier"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Data:</strong> <span id="detailsDate"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Usuário:</strong> <span id="detailsUser"></span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark table-striped">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Código</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Preço Custo</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detailsItemsBody">
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
    let itemsCounter = 0;
    let productsData = <?php echo json_encode($products); ?>;
    let categoriesData = <?php echo json_encode($categories); ?>;

    function openNewEntryModal() {
        document.getElementById('stockEntryForm').reset();
        document.getElementById('itemsTableBody').innerHTML = '<tr id="emptyRow"><td colspan="7" class="text-center text-muted">Nenhum item adicionado</td></tr>';
        itemsCounter = 0;
        updateTotal();

        const modal = new bootstrap.Modal(document.getElementById('newEntryModal'));
        modal.show();
    }

    function addExistingProduct() {
        const row = document.createElement('tr');
        row.id = `item-${itemsCounter}`;
        row.innerHTML = `
            <td>
                <select class="form-select form-select-sm bg-dark text-white border-secondary" onchange="loadProductData(${itemsCounter})" id="product-select-${itemsCounter}" required>
                    <option value="">Selecione...</option>
                    ${productsData.map(p => `<option value="${p.id}" data-code="${p.code}" data-cost="${p.cost_price}" data-sell="${p.sell_price}">${p.name}</option>`).join('')}
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" id="code-${itemsCounter}" readonly></td>
            <td><input type="number" class="form-control form-control-sm bg-dark text-white border-secondary text-center" id="quantity-${itemsCounter}" value="1" min="1" onchange="updateSubtotal(${itemsCounter})" required></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm bg-dark text-white border-secondary text-end" id="cost-${itemsCounter}" onchange="updateSubtotal(${itemsCounter})" required></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm bg-dark text-white border-secondary text-end" id="sell-${itemsCounter}" readonly></td>
            <td class="text-end fw-bold" id="subtotal-${itemsCounter}">R$ 0,00</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${itemsCounter})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        document.getElementById('emptyRow')?.remove();
        document.getElementById('itemsTableBody').appendChild(row);
        itemsCounter++;
    }

    function addNewProduct() {
        const row = document.createElement('tr');
        row.id = `item-${itemsCounter}`;
        row.dataset.isNew = 'true';
        row.innerHTML = `
            <td><input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" id="name-${itemsCounter}" placeholder="Nome do produto" required></td>
            <td><input type="text" class="form-control form-control-sm bg-dark text-white border-secondary" id="code-${itemsCounter}" placeholder="Código"></td>
            <td><input type="number" class="form-control form-control-sm bg-dark text-white border-secondary text-center" id="quantity-${itemsCounter}" value="1" min="1" onchange="updateSubtotal(${itemsCounter})" required></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm bg-dark text-white border-secondary text-end" id="cost-${itemsCounter}" onchange="updateSubtotal(${itemsCounter})" required></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm bg-dark text-white border-secondary text-end" id="sell-${itemsCounter}" required></td>
            <td class="text-end fw-bold" id="subtotal-${itemsCounter}">R$ 0,00</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${itemsCounter})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        document.getElementById('emptyRow')?.remove();
        document.getElementById('itemsTableBody').appendChild(row);
        itemsCounter++;
    }

    function loadProductData(index) {
        const select = document.getElementById(`product-select-${index}`);
        const option = select.options[select.selectedIndex];

        if (option.value) {
            document.getElementById(`code-${index}`).value = option.dataset.code || '';
            document.getElementById(`cost-${index}`).value = option.dataset.cost || '';
            document.getElementById(`sell-${index}`).value = option.dataset.sell || '';
            updateSubtotal(index);
        }
    }

    function updateSubtotal(index) {
        const quantity = parseFloat(document.getElementById(`quantity-${index}`).value) || 0;
        const cost = parseFloat(document.getElementById(`cost-${index}`).value) || 0;
        const subtotal = quantity * cost;

        document.getElementById(`subtotal-${index}`).textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
        updateTotal();
    }

    function updateTotal() {
        let total = 0;
        const rows = document.querySelectorAll('#itemsTableBody tr:not(#emptyRow)');

        rows.forEach(row => {
            const subtotalText = row.querySelector('[id^="subtotal-"]')?.textContent || 'R$ 0,00';
            const subtotal = parseFloat(subtotalText.replace('R$ ', '').replace(',', '.')) || 0;
            total += subtotal;
        });

        document.getElementById('totalCost').textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    }

    function removeItem(index) {
        document.getElementById(`item-${index}`).remove();

        const rows = document.querySelectorAll('#itemsTableBody tr');
        if (rows.length === 0) {
            document.getElementById('itemsTableBody').innerHTML = '<tr id="emptyRow"><td colspan="7" class="text-center text-muted">Nenhum item adicionado</td></tr>';
        }

        updateTotal();
    }

    function saveStockEntry() {
        const invoice_number = document.getElementById('invoice_number').value;
        const supplier = document.getElementById('supplier').value;
        const notes = document.getElementById('notes').value;

        if (!invoice_number) {
            Swal.fire('Atenção!', 'Informe o número da nota fiscal', 'warning');
            return;
        }

        const items = [];
        const rows = document.querySelectorAll('#itemsTableBody tr:not(#emptyRow)');

        if (rows.length === 0) {
            Swal.fire('Atenção!', 'Adicione pelo menos um item', 'warning');
            return;
        }

        rows.forEach((row, idx) => {
            const isNew = row.dataset.isNew === 'true';
            const rowId = row.id.replace('item-', '');

            const item = {
                is_new_product: isNew,
                quantity: parseInt(document.getElementById(`quantity-${rowId}`).value),
                cost_price: parseFloat(document.getElementById(`cost-${rowId}`).value),
                sell_price: parseFloat(document.getElementById(`sell-${rowId}`).value)
            };

            if (isNew) {
                item.name = document.getElementById(`name-${rowId}`).value;
                item.code = document.getElementById(`code-${rowId}`).value;
                item.category_id = null;
                item.size = 'Unico';
                item.color = '';
                item.gender = 'Unisex';
            } else {
                const select = document.getElementById(`product-select-${rowId}`);
                item.product_id = parseInt(select.value);
            }

            items.push(item);
        });

        const data = {
            invoice_number,
            supplier,
            notes,
            items
        };

        fetch('process_stock_entry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Sucesso!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Erro!', 'Erro ao processar a solicitação', 'error');
            });
    }

    function viewEntryDetails(id) {
        document.getElementById('detailsEntryId').textContent = id;
        document.getElementById('detailsItemsBody').innerHTML = '<tr><td colspan="5" class="text-center">Carregando...</td></tr>';

        const modal = new bootstrap.Modal(document.getElementById('entryDetailsModal'));
        modal.show();

        fetch(`ajax_get_stock_entry.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    Swal.fire('Erro!', data.error, 'error');
                    return;
                }

                document.getElementById('detailsInvoice').textContent = data.entry.invoice_number;
                document.getElementById('detailsSupplier').textContent = data.entry.supplier || '-';
                document.getElementById('detailsDate').textContent = new Date(data.entry.created_at).toLocaleString('pt-BR');
                document.getElementById('detailsUser').textContent = data.entry.user_name;

                const tbody = document.getElementById('detailsItemsBody');
                tbody.innerHTML = '';

                data.items.forEach(item => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${item.product_name || 'Produto Removido'}</td>
                        <td>${item.product_code || '-'}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">R$ ${parseFloat(item.cost_price).toFixed(2).replace('.', ',')}</td>
                        <td class="text-end">R$ ${parseFloat(item.subtotal).toFixed(2).replace('.', ',')}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Erro!', 'Erro ao carregar detalhes', 'error');
            });
    }
</script>

<?php include 'includes/footer.php'; ?>