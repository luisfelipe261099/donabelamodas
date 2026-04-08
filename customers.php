<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Handle Add/Edit Customer
if (isset($_POST['save_customer'])) {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'];
    $cpf = $_POST['cpf'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $credit_limit = $_POST['credit_limit'];
    $notes = $_POST['notes'];
    $active = isset($_POST['active']) ? 1 : 0;

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE customers SET name=?, cpf=?, phone=?, email=?, address=?, credit_limit=?, notes=?, active=? WHERE id=?");
            $stmt->execute([$name, $cpf, $phone, $email, $address, $credit_limit, $notes, $active, $id]);
            $message = "Cliente atualizado com sucesso!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, cpf, phone, email, address, credit_limit, notes, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $cpf, $phone, $email, $address, $credit_limit, $notes, $active]);
            $message = "Cliente cadastrado com sucesso!";
        }
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Sucesso!', '$message', 'success')
                .then(() => { window.location.href='customers.php'; });
            });
        </script>";
    } catch (Exception $e) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Erro!', 'Erro ao salvar cliente: " . $e->getMessage() . "', 'error');
            });
        </script>";
    }
}

// Handle Delete Customer
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Sucesso!', 'Cliente excluído com sucesso!', 'success')
                .then(() => { window.location.href='customers.php'; });
            });
        </script>";
    } catch (Exception $e) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire('Erro!', 'Erro ao excluir cliente.', 'error');
            });
        </script>";
    }
}

// Get all customers
$stmt = $pdo->query("SELECT c.*, 
    (SELECT COUNT(*) FROM accounts_receivable WHERE customer_id = c.id AND status IN ('pending', 'partial', 'overdue')) as pending_accounts,
    (SELECT SUM(remaining_amount) FROM accounts_receivable WHERE customer_id = c.id AND status IN ('pending', 'partial', 'overdue')) as total_debt
    FROM customers c ORDER BY c.name");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="fw-bold text-white">Clientes (Crediário)</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="resetForm()">
                <i class="fas fa-plus me-2"></i>Novo Cliente
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-white" id="customersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Limite Crédito</th>
                            <th>Dívida Atual</th>
                            <th>Contas Pendentes</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>#<?php echo $customer['id']; ?></td>
                                <td><?php echo $customer['name']; ?></td>
                                <td><?php echo $customer['cpf'] ?: '-'; ?></td>
                                <td><?php echo $customer['phone'] ?: '-'; ?></td>
                                <td class="text-end">R$ <?php echo number_format($customer['credit_limit'], 2, ',', '.'); ?></td>
                                <td class="text-end <?php echo ($customer['total_debt'] > 0) ? 'text-danger fw-bold' : ''; ?>">
                                    R$ <?php echo number_format($customer['total_debt'] ?: 0, 2, ',', '.'); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($customer['pending_accounts'] > 0): ?>
                                        <span class="badge bg-warning text-dark"><?php echo $customer['pending_accounts']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($customer['active']): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editCustomer(<?php echo htmlspecialchars(json_encode($customer)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?php echo $customer['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Tem certeza que deseja excluir este cliente?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="accounts.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-file-invoice-dollar"></i> Contas
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <form method="POST">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="modalTitle">Novo Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="customer-id">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="name" id="customer-name" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CPF</label>
                            <input type="text" name="cpf" id="customer-cpf" class="form-control bg-dark text-white border-secondary" 
                                   placeholder="000.000.000-00" maxlength="14">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="phone" id="customer-phone" class="form-control bg-dark text-white border-secondary" 
                                   placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" id="customer-email" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Endereço</label>
                        <textarea name="address" id="customer-address" class="form-control bg-dark text-white border-secondary" rows="2"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Limite de Crédito</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-white">R$</span>
                                <input type="number" name="credit_limit" id="customer-credit-limit" 
                                       class="form-control bg-dark text-white border-secondary" 
                                       step="0.01" min="0" value="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="active" id="customer-active" checked>
                                <label class="form-check-label" for="customer-active">Cliente Ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" id="customer-notes" class="form-control bg-dark text-white border-secondary" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="save_customer" class="btn btn-success">Salvar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function resetForm() {
    $('#modalTitle').text('Novo Cliente');
    $('#customer-id').val('');
    $('#customer-name').val('');
    $('#customer-cpf').val('');
    $('#customer-phone').val('');
    $('#customer-email').val('');
    $('#customer-address').val('');
    $('#customer-credit-limit').val('0.00');
    $('#customer-notes').val('');
    $('#customer-active').prop('checked', true);
}

function editCustomer(customer) {
    $('#modalTitle').text('Editar Cliente');
    $('#customer-id').val(customer.id);
    $('#customer-name').val(customer.name);
    $('#customer-cpf').val(customer.cpf);
    $('#customer-phone').val(customer.phone);
    $('#customer-email').val(customer.email);
    $('#customer-address').val(customer.address);
    $('#customer-credit-limit').val(customer.credit_limit);
    $('#customer-notes').val(customer.notes);
    $('#customer-active').prop('checked', customer.active == 1);
    
    new bootstrap.Modal(document.getElementById('customerModal')).show();
}

// CPF Mask
$('#customer-cpf').on('input', function() {
    let value = $(this).val().replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        $(this).val(value);
    }
});

// Phone Mask
$('#customer-phone').on('input', function() {
    let value = $(this).val().replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/(\d{2})(\d)/, '($1) $2');
        value = value.replace(/(\d{5})(\d)/, '$1-$2');
        $(this).val(value);
    }
});

// DataTable
$(document).ready(function() {
    $('#customersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        order: [[1, 'asc']]
    });
});
</script>