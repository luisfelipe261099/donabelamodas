<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Garantir colunas de baixa (status e data de pagamento) na tabela expenses
try {
    $col = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'status'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE expenses ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Pendente'");
        $pdo->exec("ALTER TABLE expenses ADD COLUMN paid_at DATE NULL");
    }
} catch (PDOException $e) {
    // Coluna já existe ou sem permissão de ALTER - segue o fluxo normal
}

// Converte valor formatado (1.234,56) para decimal (1234.56)
function parse_money($value)
{
    $value = trim(str_replace(['R$', ' ', '.'], '', $value));
    return str_replace(',', '.', $value);
}

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $description = $_POST['description'];
    $amount = parse_money($_POST['amount']);
    $expense_date = $_POST['expense_date'];
    $category = $_POST['category'];
    $notes = $_POST['notes'];
    $status = ($_POST['status'] ?? 'Pendente') == 'Pago' ? 'Pago' : 'Pendente';
    $paid_at = $status == 'Pago' ? date('Y-m-d') : null;

    if (!empty($description) && !empty($amount) && !empty($expense_date)) {
        $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, expense_date, category, notes, status, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$description, $amount, $expense_date, $category, $notes, $status, $paid_at]);
        echo "<script>window.location.href='despesas.php';</script>";
    }
}

// Filtros de busca
$f_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$f_category = isset($_GET['category']) ? $_GET['category'] : '';
$f_status = isset($_GET['status']) ? $_GET['status'] : '';
$f_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$f_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Query string dos filtros para preservar nos links de ação e redirects
$filter_qs = http_build_query(array_filter([
    'search' => $f_search,
    'category' => $f_category,
    'status' => $f_status,
    'start_date' => $f_start,
    'end_date' => $f_end,
]));
$redirect_url = 'despesas.php' . ($filter_qs ? '?' . $filter_qs : '');

// Handle Baixa (marcar como paga)
if (isset($_GET['baixa'])) {
    $stmt = $pdo->prepare("UPDATE expenses SET status = 'Pago', paid_at = ? WHERE id = ?");
    $stmt->execute([date('Y-m-d'), $_GET['baixa']]);
    echo "<script>window.location.href='" . $redirect_url . "';</script>";
}

// Handle Reabrir (desfazer baixa)
if (isset($_GET['reabrir'])) {
    $stmt = $pdo->prepare("UPDATE expenses SET status = 'Pendente', paid_at = NULL WHERE id = ?");
    $stmt->execute([$_GET['reabrir']]);
    echo "<script>window.location.href='" . $redirect_url . "';</script>";
}

// Handle Delete Expense
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='" . $redirect_url . "';</script>";
}

$categories_list = ['Água', 'Luz', 'Internet', 'Aluguel', 'Funcionários', 'Manutenção', 'Fornecedores', 'Outros'];

// Monta WHERE dos filtros
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

$has_filters = ($filter_qs !== '');

// Get Expenses (filtradas)
$stmt = $pdo->prepare("SELECT * FROM expenses WHERE $where ORDER BY expense_date DESC, id DESC");
$stmt->execute($params);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Totals (respeitando os filtros)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $where");
$stmt->execute($params);
$total_expenses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $where AND COALESCE(status, 'Pendente') != 'Pago'");
$stmt->execute($params);
$total_pending = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE $where AND status = 'Pago'");
$stmt->execute($params);
$total_paid = $stmt->fetchColumn();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-white">Despesas</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="generate_despesas_pdf.php<?php echo $filter_qs ? '?' . $filter_qs : ''; ?>" target="_blank"
                class="btn btn-outline-light me-2">
                <i class="fas fa-file-pdf me-2"></i> Relatório
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus me-2"></i> Nova Despesa
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control" placeholder="Descrição ou observações"
                        value="<?php echo htmlspecialchars($f_search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Categoria</label>
                    <select name="category" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($categories_list as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $f_category == $cat ? 'selected' : ''; ?>>
                                <?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Situação</label>
                    <select name="status" class="form-select">
                        <option value="">Todas</option>
                        <option value="Pendente" <?php echo $f_status == 'Pendente' ? 'selected' : ''; ?>>Pendente
                        </option>
                        <option value="Pago" <?php echo $f_status == 'Pago' ? 'selected' : ''; ?>>Pago</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">De</label>
                    <input type="date" name="start_date" class="form-control"
                        value="<?php echo htmlspecialchars($f_start); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Até</label>
                    <input type="date" name="end_date" class="form-control"
                        value="<?php echo htmlspecialchars($f_end); ?>">
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" title="Filtrar">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if ($has_filters): ?>
                        <a href="despesas.php" class="btn btn-secondary" title="Limpar filtros">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Total<?php echo $has_filters ? ' (filtrado)' : ' de Despesas'; ?></h5>
                    <h3 class="fw-bold">R$ <?php echo number_format($total_expenses, 2, ',', '.'); ?></h3>
                    <small><?php echo count($expenses); ?> despesa(s)</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Pendentes</h5>
                    <h3 class="fw-bold">R$ <?php echo number_format($total_pending, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Pagas</h5>
                    <h3 class="fw-bold">R$ <?php echo number_format($total_paid, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Situação</th>
                            <th>Observações</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <?php $is_paid = ($expense['status'] ?? 'Pendente') == 'Pago'; ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                                <td><?php echo $expense['description']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $expense['category']; ?></span></td>
                                <td class="text-danger fw-bold">R$
                                    <?php echo number_format($expense['amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($is_paid): ?>
                                        <span class="badge bg-success">Pago</span>
                                        <?php if (!empty($expense['paid_at'])): ?>
                                            <small class="text-muted d-block"><?php echo date('d/m/Y', strtotime($expense['paid_at'])); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $expense['notes']; ?></td>
                                <td class="text-end">
                                    <?php $qs_suffix = $filter_qs ? '&' . $filter_qs : ''; ?>
                                    <?php if (!$is_paid): ?>
                                        <a href="despesas.php?baixa=<?php echo $expense['id'] . $qs_suffix; ?>"
                                            class="btn btn-sm btn-success" title="Dar baixa (marcar como paga)"
                                            onclick="return confirm('Confirmar a baixa desta despesa?')">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="despesas.php?reabrir=<?php echo $expense['id'] . $qs_suffix; ?>"
                                            class="btn btn-sm btn-outline-warning" title="Desfazer baixa"
                                            onclick="return confirm('Desfazer a baixa desta despesa?')">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="despesas.php?delete=<?php echo $expense['id'] . $qs_suffix; ?>"
                                        class="btn btn-sm btn-danger" title="Excluir"
                                        onclick="return confirm('Tem certeza que deseja excluir esta despesa?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($expenses) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <?php echo $has_filters ? 'Nenhuma despesa encontrada com os filtros aplicados.' : 'Nenhuma despesa registrada.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Adicionar Despesa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="description" class="form-control" placeholder="Ex: Conta de Luz"
                            required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Valor (R$)</label>
                            <input type="text" inputmode="numeric" name="amount" class="form-control money-mask"
                                placeholder="0,00" autocomplete="off" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data</label>
                            <input type="date" name="expense_date" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Categoria</label>
                            <select name="category" class="form-select">
                                <option value="Água">Água</option>
                                <option value="Luz">Luz</option>
                                <option value="Internet">Internet</option>
                                <option value="Aluguel">Aluguel</option>
                                <option value="Funcionários">Funcionários</option>
                                <option value="Manutenção">Manutenção</option>
                                <option value="Fornecedores">Fornecedores</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Situação</label>
                            <select name="status" class="form-select">
                                <option value="Pendente">Pendente</option>
                                <option value="Pago">Pago</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="add_expense" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Máscara de moeda: formata enquanto digita (1.234,56)
    document.querySelectorAll('.money-mask').forEach(function (input) {
        input.addEventListener('input', function () {
            var digits = this.value.replace(/\D/g, '').slice(0, 12);
            if (!digits) {
                this.value = '';
                return;
            }
            var number = parseInt(digits, 10) / 100;
            this.value = number.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
