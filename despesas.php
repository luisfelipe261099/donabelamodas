<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $description = $_POST['description'];
    $amount = str_replace(',', '.', $_POST['amount']); // Handle comma as decimal separator
    $expense_date = $_POST['expense_date'];
    $category = $_POST['category'];
    $notes = $_POST['notes'];

    if (!empty($description) && !empty($amount) && !empty($expense_date)) {
        $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, expense_date, category, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$description, $amount, $expense_date, $category, $notes]);
        echo "<script>window.location.href='despesas.php';</script>";
    }
}

// Handle Delete Expense
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='despesas.php';</script>";
}

// Get Expenses
$expenses = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate Total
$total_expenses = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses")->fetchColumn();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold text-white">Despesas</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus me-2"></i> Nova Despesa
            </button>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Despesas</h5>
                    <h3 class="fw-bold">R$ <?php echo number_format($total_expenses, 2, ',', '.'); ?></h3>
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
                            <th>Observações</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                                <td><?php echo $expense['description']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $expense['category']; ?></span></td>
                                <td class="text-danger fw-bold">R$
                                    <?php echo number_format($expense['amount'], 2, ',', '.'); ?></td>
                                <td><?php echo $expense['notes']; ?></td>
                                <td class="text-end">
                                    <a href="despesas.php?delete=<?php echo $expense['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Tem certeza que deseja excluir esta despesa?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($expenses) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Nenhuma despesa registrada.</td>
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
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00"
                                required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Data</label>
                            <input type="date" name="expense_date" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
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

<?php include 'includes/footer.php'; ?>