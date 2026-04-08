<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Handle Receive Payment
if (isset($_POST['receive_payment'])) {
    $account_id = $_POST['account_id'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $payment_date = $_POST['payment_date'];
    $notes = $_POST['notes'];

    try {
        $pdo->beginTransaction();

        // Insert payment
        $stmt = $pdo->prepare("INSERT INTO account_payments (account_id, amount, payment_method, payment_date, received_by, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$account_id, $amount, $payment_method, $payment_date, $_SESSION['user_id'], $notes]);

        // Update account
        $stmt = $pdo->prepare("UPDATE accounts_receivable SET paid_amount = paid_amount + ?, remaining_amount = remaining_amount - ? WHERE id = ?");
        $stmt->execute([$amount, $amount, $account_id]);

        // Check if fully paid
        $stmt = $pdo->prepare("SELECT remaining_amount FROM accounts_receivable WHERE id = ?");
        $stmt->execute([$account_id]);
        $remaining = $stmt->fetchColumn();

        if ($remaining <= 0) {
            $stmt = $pdo->prepare("UPDATE accounts_receivable SET status = 'paid', payment_date = ? WHERE id = ?");
            $stmt->execute([$payment_date, $account_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE accounts_receivable SET status = 'partial' WHERE id = ?");
            $stmt->execute([$account_id]);
        }

        $pdo->commit();
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Sucesso!', 'Pagamento recebido com sucesso!', 'success')
                .then(() => { window.location.href='accounts.php'; });
            });
        </script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Erro!', 'Erro ao processar pagamento.', 'error');
            });
        </script>";
    }
}

// Get filter parameters
$customer_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build WHERE clause
$where = "1=1";
$params = [];

if ($customer_id) {
    $where .= " AND ar.customer_id = ?";
    $params[] = $customer_id;
}

if ($status) {
    $where .= " AND ar.status = ?";
    $params[] = $status;
}

// Get accounts
$stmt = $pdo->prepare("
    SELECT ar.*, c.name as customer_name, c.phone as customer_phone, u.name as created_by_name
    FROM accounts_receivable ar
    JOIN customers c ON ar.customer_id = c.id
    JOIN users u ON ar.created_by = u.id
    WHERE $where
    ORDER BY ar.due_date ASC, ar.created_at DESC
");
$stmt->execute($params);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customers for filter
$customers = $pdo->query("SELECT id, name FROM customers WHERE active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_pending = 0;
$total_overdue = 0;
$total_paid_today = 0;

foreach ($accounts as $account) {
    if ($account['status'] == 'pending' || $account['status'] == 'partial') {
        $total_pending += $account['remaining_amount'];
    }
    if ($account['status'] == 'overdue') {
        $total_overdue += $account['remaining_amount'];
    }
}

$stmt = $pdo->query("SELECT SUM(amount) FROM account_payments WHERE DATE(payment_date) = CURDATE()");
$total_paid_today = $stmt->fetchColumn() ?: 0;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-white">Contas a Receber</h2>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h6 class="card-title">Contas Pendentes</h6>
                    <h3 class="fw-bold">R$ <?php echo number_format($total_pending, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Contas Vencidas</h6>
                    <h3 class="fw-bold">R$ <?php echo number_format($total_overdue, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Recebido Hoje</h6>
                    <h3 class="fw-bold">R$ <?php echo number_format($total_paid_today, 2, ',', '.'); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h6 class="card-title">Total de Contas</h6>
                    <h3 class="fw-bold"><?php echo count($accounts); ?></h3>
                </div>
            </div>
        </div>
    </div>
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="accounts.php" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label text-white">Cliente</label>
                    <select name="customer_id" class="form-select bg-dark text-white border-secondary">
                        <option value="">Todos os Clientes</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $customer_id == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-white">Status</label>
                    <select name="status" class="form-select bg-dark text-white border-secondary">
                        <option value="">Todos</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="partial" <?php echo $status == 'partial' ? 'selected' : ''; ?>>Parcial</option>
                        <option value="overdue" <?php echo $status == 'overdue' ? 'selected' : ''; ?>>Vencida</option>
                        <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paga</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="accounts.php" class="btn btn-secondary w-100">
                        <i class="fas fa-times me-2"></i>Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-white">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Descrição</th>
                            <th>Vencimento</th>
                            <th class="text-end">Valor Total</th>
                            <th class="text-end">Pago</th>
                            <th class="text-end">Restante</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($accounts) > 0): ?>
                            <?php foreach ($accounts as $account): 
                                $is_overdue = (strtotime($account['due_date']) < time() && $account['status'] != 'paid');
                                $status_class = [
                                    'pending' => 'warning',
                                    'partial' => 'info',
                                    'paid' => 'success',
                                    'overdue' => 'danger',
                                    'cancelled' => 'secondary'
                                ];
                                $status_text = [
                                    'pending' => 'Pendente',
                                    'partial' => 'Parcial',
                                    'paid' => 'Paga',
                                    'overdue' => 'Vencida',
                                    'cancelled' => 'Cancelada'
                                ];
                            ?>
                                <tr class="<?php echo $is_overdue ? 'table-danger' : ''; ?>">
                                    <td>#<?php echo $account['id']; ?></td>
                                    <td>
                                        <strong><?php echo $account['customer_name']; ?></strong><br>
                                        <small class="text-muted"><?php echo $account['customer_phone']; ?></small>
                                    </td>
                                    <td><?php echo $account['description'] ?: 'Venda #' . $account['sale_id']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($account['due_date'])); ?>
                                        <?php if ($is_overdue): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <?php 
                                                $days = floor((time() - strtotime($account['due_date'])) / 86400);
                                                echo $days . ' dia(s) atrasado';
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">R$ <?php echo number_format($account['total_amount'], 2, ',', '.'); ?></td>
                                    <td class="text-end text-success">R$ <?php echo number_format($account['paid_amount'], 2, ',', '.'); ?></td>
                                    <td class="text-end fw-bold <?php echo $account['remaining_amount'] > 0 ? 'text-warning' : 'text-success'; ?>">
                                        R$ <?php echo number_format($account['remaining_amount'], 2, ',', '.'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_class[$account['status']]; ?>">
                                            <?php echo $status_text[$account['status']]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($account['status'] != 'paid' && $account['status'] != 'cancelled'): ?>
                                            <button class="btn btn-sm btn-success" onclick="receivePayment(<?php echo htmlspecialchars(json_encode($account)); ?>)">
                                                <i class="fas fa-dollar-sign"></i> Receber
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info" onclick="viewDetails(<?php echo $account['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteAccount(<?php echo $account['id']; ?>, <?php echo $account['sale_id'] ? 'true' : 'false'; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Nenhuma conta encontrada</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <form method="POST">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Receber Pagamento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="account_id" id="payment-account-id">
                    
                    <div class="alert alert-info">
                        <strong>Cliente:</strong> <span id="payment-customer"></span><br>
                        <strong>Valor Restante:</strong> <span id="payment-remaining" class="fs-5 fw-bold"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Valor Recebido *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-white">R$</span>
                            <input type="number" name="amount" id="payment-amount" 
                                   class="form-control bg-dark text-white border-secondary" 
                                   step="0.01" min="0.01" required>
                        </div>
                        <small class="text-muted">Digite o valor que está recebendo</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Forma de Pagamento *</label>
                        <select name="payment_method" class="form-select bg-dark text-white border-secondary" required>
                            <option value="Cash">Dinheiro</option>
                            <option value="Credit Card">Cartão de Crédito</option>
                            <option value="Debit Card">Cartão de Débito</option>
                            <option value="Pix">Pix</option>
                            <option value="Transfer">Transferência</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Data do Pagamento *</label>
                        <input type="date" name="payment_date" class="form-control bg-dark text-white border-secondary" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control bg-dark text-white border-secondary" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="receive_payment" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Confirmar Recebimento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function receivePayment(account) {
    $('#payment-account-id').val(account.id);
    $('#payment-customer').text(account.customer_name);
    $('#payment-remaining').text('R$ ' + parseFloat(account.remaining_amount).toFixed(2).replace('.', ','));
    $('#payment-amount').val(parseFloat(account.remaining_amount).toFixed(2));
    $('#payment-amount').attr('max', parseFloat(account.remaining_amount).toFixed(2));
    
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}

function viewDetails(accountId) {
    // TODO: Implement view details modal with payment history
    Swal.fire({
        title: 'Detalhes da Conta #' + accountId,
        html: '<p>Funcionalidade em desenvolvimento...</p>',
        icon: 'info'
    });
}

function deleteAccount(accountId, hasSale) {
    let warningText = 'Esta ação não pode ser desfeita!';
    if (hasSale) {
        warningText = 'Esta conta possui uma VENDA relacionada. A venda também será excluída e os produtos retornarão ao estoque!';
    }

    Swal.fire({
        title: 'Excluir Conta #' + accountId + '?',
        text: warningText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Excluindo conta...',
                text: 'Aguarde',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send request
            fetch('delete_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ account_id: accountId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Excluída!',
                        text: data.message,
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erro!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Erro!', 'Erro ao excluir conta: ' + error, 'error');
            });
        }
    });
}

// Update overdue accounts status
$(document).ready(function() {
    // Check for overdue accounts and update status
    $.post('update_overdue_accounts.php');
});
</script>