<?php
require_once 'config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle Open Cash Register
if (isset($_POST['open_cash'])) {
    $opening_amount = $_POST['opening_amount'];
    $stmt = $pdo->prepare("INSERT INTO cash_register (user_id, opening_amount, status) VALUES (?, ?, 'open')");
    $stmt->execute([$_SESSION['user_id'], $opening_amount]);
    header('Location: caixa.php');
    exit;
}

// Handle Close Cash Register
if (isset($_POST['close_cash'])) {
    $cash_id = $_POST['cash_id'];
    $closing_amount_cash = $_POST['closing_amount_cash'];
    $closing_amount_other = $_POST['closing_amount_other'];
    $notes = $_POST['notes'];

    // Get expected amount from sales - DINHEIRO
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE cash_register_id = ? AND payment_method = 'Cash'");
    $stmt->execute([$cash_id]);
    $sales_cash = $stmt->fetchColumn() ?: 0;

    // Get expected amount from sales - OUTROS (PIX, Débito, Crédito)
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE cash_register_id = ? AND payment_method IN ('Pix', 'Debit Card', 'Credit Card')");
    $stmt->execute([$cash_id]);
    $sales_other = $stmt->fetchColumn() ?: 0;

    // Get opening amount
    $stmt = $pdo->prepare("SELECT opening_amount FROM cash_register WHERE id = ?");
    $stmt->execute([$cash_id]);
    $opening_amount = $stmt->fetchColumn();

    // Get expenses for today
    $expenses_total = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = CURDATE()")->fetchColumn();

    // Calculate expected amounts
    $expected_cash = $opening_amount + $sales_cash - $expenses_total;
    $expected_other = $sales_other;
    $expected_total = $expected_cash + $expected_other;

    // Calculate differences
    $difference_cash = $closing_amount_cash - $expected_cash;
    $difference_other = $closing_amount_other - $expected_other;
    $difference_total = $difference_cash + $difference_other;

    // Total closing amount
    $closing_amount_total = $closing_amount_cash + $closing_amount_other;

    // Update cash register
    $stmt = $pdo->prepare("UPDATE cash_register SET closing_amount = ?, expected_amount = ?, difference = ?, status = 'closed', closed_at = NOW(), notes = ? WHERE id = ?");
    $stmt->execute([$closing_amount_total, $expected_total, $difference_total, $notes, $cash_id]);

    header('Location: caixa.php');
    exit;
}

// Get current open cash register
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE user_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$current_cash = $stmt->fetch(PDO::FETCH_ASSOC);

// Get sales for current cash register
$sales_today = [];
$sales_cash = 0;
$sales_other = 0;
$sales_total = 0;
if ($current_cash) {
    // Get all sales for display
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE cash_register_id = ? ORDER BY created_at DESC");
    $stmt->execute([$current_cash['id']]);
    $sales_today = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get cash sales total
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE cash_register_id = ? AND payment_method = 'Cash'");
    $stmt->execute([$current_cash['id']]);
    $sales_cash = $stmt->fetchColumn() ?: 0;

    // Get other payment methods total (PIX, Debit, Credit)
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE cash_register_id = ? AND payment_method IN ('Pix', 'Debit Card', 'Credit Card')");
    $stmt->execute([$current_cash['id']]);
    $sales_other = $stmt->fetchColumn() ?: 0;

    $sales_total = $sales_cash + $sales_other;
}

// Get expenses for today
$expenses_today = $pdo->query("SELECT * FROM expenses WHERE expense_date = CURDATE() ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$expenses_total = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date = CURDATE()")->fetchColumn();

// Get cash register history
$stmt = $pdo->prepare("SELECT cr.*, u.name as user_name FROM cash_register cr JOIN users u ON cr.user_id = u.id WHERE cr.status = 'closed' ORDER BY cr.closed_at DESC LIMIT 10");
$stmt->execute();
$cash_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold" style="font-family:'Playfair Display',serif;color:#D4A34A;">
                <i class="fas fa-cash-register me-2"></i>Controle de Caixa
            </h2>
            <p class="text-muted" style="font-size:1rem;">Abra o caixa no início do dia e feche no final para conferir os valores.</p>
        </div>
    </div>

    <?php if (!$current_cash): ?>
        <!-- ============ ABRIR CAIXA ============ -->
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card" style="border: 2px solid rgba(76,175,118,0.3);">
                    <div class="card-body p-4 p-md-5 text-center">
                        <div style="font-size:3rem; color:#4CAF76; margin-bottom:16px;">
                            <i class="fas fa-unlock"></i>
                        </div>
                        <h3 class="fw-bold text-white mb-2">Abrir Caixa</h3>
                        <p class="text-muted mb-4" style="font-size:1rem;">
                            Digite quanto dinheiro você tem no caixa agora para começar o dia.
                        </p>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label" style="font-size:1.05rem;">Quanto tem no caixa agora? (R$)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text" style="background:rgba(76,175,118,0.1);border-color:rgba(76,175,118,0.3);color:#4CAF76;font-size:1.2rem;font-weight:700;">R$</span>
                                    <input type="number" name="opening_amount"
                                        class="form-control" step="0.01" min="0"
                                        value="0.00" required
                                        style="font-size:1.5rem; font-weight:700; text-align:center;">
                                </div>
                            </div>
                            <button type="submit" name="open_cash" class="btn btn-success btn-lg w-100" style="font-size:1.15rem; padding:16px;">
                                <i class="fas fa-play-circle me-2"></i>Abrir Caixa e Começar o Dia
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- ============ CAIXA ABERTO - RESUMO DO DIA ============ -->

        <!-- Alerta informativo -->
        <div class="alerta-ok">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong style="color:#4CAF76;">Caixa aberto</strong> desde <?php echo date('d/m/Y \à\s H:i', strtotime($current_cash['opened_at'])); ?>
            </div>
        </div>

        <!-- Resumo visual grande e claro -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="resumo-card">
                    <div class="icon" style="color:var(--info-color);"><i class="fas fa-piggy-bank"></i></div>
                    <div class="label">Valor Inicial</div>
                    <div class="valor" style="color:var(--info-color);">R$ <?php echo number_format($current_cash['opening_amount'], 2, ',', '.'); ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="resumo-card">
                    <div class="icon" style="color:#4CAF76;"><i class="fas fa-arrow-up"></i></div>
                    <div class="label">Total de Vendas</div>
                    <div class="valor" style="color:#4CAF76;">R$ <?php echo number_format($sales_total, 2, ',', '.'); ?></div>
                    <small class="text-muted">Dinheiro: R$ <?php echo number_format($sales_cash, 2, ',', '.'); ?> | Outros: R$ <?php echo number_format($sales_other, 2, ',', '.'); ?></small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="resumo-card">
                    <div class="icon" style="color:#D4574A;"><i class="fas fa-arrow-down"></i></div>
                    <div class="label">Despesas do Dia</div>
                    <div class="valor" style="color:#D4574A;">R$ <?php echo number_format($expenses_total, 2, ',', '.'); ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="resumo-card" style="border-color:var(--primary-color);">
                    <div class="icon" style="color:var(--primary-color);"><i class="fas fa-coins"></i></div>
                    <div class="label">Deveria Ter no Caixa</div>
                    <div class="valor" style="color:var(--primary-color);">R$ <?php echo number_format($current_cash['opening_amount'] + $sales_total - $expenses_total, 2, ',', '.'); ?></div>
                </div>
            </div>
        </div>

        <hr class="section-divider">

        <!-- ============ FECHAR CAIXA - Formulário simplificado ============ -->
        <div class="row mb-4">
            <div class="col-lg-8 mx-auto">
                <div class="card" style="border: 2px solid rgba(212,87,74,0.3);">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div style="font-size:2.5rem; color:#D4574A; margin-bottom:10px;">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h3 class="fw-bold text-white">Fechar o Caixa</h3>
                            <p class="text-muted" style="font-size:1rem;">
                                Conte o dinheiro e preencha os valores abaixo. O sistema vai calcular se está tudo certo.
                            </p>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="cash_id" value="<?php echo $current_cash['id']; ?>">

                            <div class="row g-4 mb-4">
                                <!-- DINHEIRO -->
                                <div class="col-md-6">
                                    <div class="card h-100" style="border-color:rgba(76,175,118,0.3);">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="fas fa-money-bill-wave" style="font-size:1.3rem; color:#4CAF76;"></i>
                                                <h5 class="mb-0 text-white fw-bold">Dinheiro em Espécie</h5>
                                            </div>

                                            <div class="mb-3 p-3" style="background:rgba(76,175,118,0.06); border-radius:10px;">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Valor inicial:</span>
                                                    <span class="text-white">R$ <?php echo number_format($current_cash['opening_amount'], 2, ',', '.'); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">+ Vendas em dinheiro:</span>
                                                    <span style="color:#4CAF76;">R$ <?php echo number_format($sales_cash, 2, ',', '.'); ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">- Despesas do dia:</span>
                                                    <span style="color:#D4574A;">R$ <?php echo number_format($expenses_total, 2, ',', '.'); ?></span>
                                                </div>
                                                <hr style="border-color:rgba(255,255,255,0.1); margin:8px 0;">
                                                <div class="d-flex justify-content-between">
                                                    <strong style="color:var(--primary-color);">Deveria ter:</strong>
                                                    <strong style="color:var(--primary-color); font-size:1.1rem;">R$ <?php echo number_format($current_cash['opening_amount'] + $sales_cash - $expenses_total, 2, ',', '.'); ?></strong>
                                                </div>
                                            </div>

                                            <label class="form-label" style="font-size:1rem;"><strong>Quanto dinheiro tem no caixa?</strong></label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text" style="background:rgba(76,175,118,0.1);border-color:rgba(76,175,118,0.3);color:#4CAF76;font-weight:700;">R$</span>
                                                <input type="number" name="closing_amount_cash"
                                                    class="form-control" step="0.01" min="0" required
                                                    placeholder="0,00"
                                                    style="font-size:1.3rem; font-weight:700; text-align:center;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PIX / CARTÃO -->
                                <div class="col-md-6">
                                    <div class="card h-100" style="border-color:rgba(91,168,181,0.3);">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center gap-2 mb-3">
                                                <i class="fas fa-credit-card" style="font-size:1.3rem; color:var(--info-color);"></i>
                                                <h5 class="mb-0 text-white fw-bold">PIX / Cartão</h5>
                                            </div>

                                            <div class="mb-3 p-3" style="background:rgba(91,168,181,0.06); border-radius:10px;">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Total PIX + Débito + Crédito:</span>
                                                    <span style="color:var(--info-color);">R$ <?php echo number_format($sales_other, 2, ',', '.'); ?></span>
                                                </div>
                                                <hr style="border-color:rgba(255,255,255,0.1); margin:8px 0;">
                                                <div class="d-flex justify-content-between">
                                                    <strong style="color:var(--primary-color);">Deveria ter:</strong>
                                                    <strong style="color:var(--primary-color); font-size:1.1rem;">R$ <?php echo number_format($sales_other, 2, ',', '.'); ?></strong>
                                                </div>
                                            </div>

                                            <label class="form-label" style="font-size:1rem;"><strong>Quanto recebeu em PIX/Cartão?</strong></label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text" style="background:rgba(91,168,181,0.1);border-color:rgba(91,168,181,0.3);color:var(--info-color);font-weight:700;">R$</span>
                                                <input type="number" name="closing_amount_other"
                                                    class="form-control" step="0.01" min="0" required
                                                    placeholder="0,00"
                                                    style="font-size:1.3rem; font-weight:700; text-align:center;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" style="font-size:1rem;">
                                    <i class="fas fa-sticky-note me-1 text-muted"></i> Observações (opcional)
                                </label>
                                <textarea name="notes" class="form-control" rows="2"
                                    placeholder="Ex: Troco de R$50 dado ao cliente, faltou moeda, etc."></textarea>
                            </div>

                            <button type="submit" name="close_cash" class="btn btn-danger btn-lg w-100" style="font-size:1.15rem; padding:16px;">
                                <i class="fas fa-lock me-2"></i>Fechar Caixa e Ver Resultado
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <hr class="section-divider">

        <!-- Vendas e Despesas do dia -->
        <div class="row g-4">
            <!-- Sales List -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag me-2" style="color:#4CAF76;"></i>Vendas de Hoje (<?php echo count($sales_today); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-white">
                                <thead>
                                    <tr>
                                        <th>Hora</th>
                                        <th>Pagamento</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($sales_today) > 0): ?>
                                        <?php foreach ($sales_today as $sale): ?>
                                            <tr>
                                                <td><?php echo date('H:i', strtotime($sale['created_at'])); ?></td>
                                                <td><span
                                                        class="badge bg-info text-dark"><?php echo $sale['payment_method']; ?></span>
                                                </td>
                                                <td class="text-end fw-bold text-success">R$
                                                    <?php echo number_format($sale['total_amount'], 2, ',', '.'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Nenhuma venda registrada</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenses List -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2" style="color:#D4574A;"></i>Despesas de Hoje (<?php echo count($expenses_today); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover text-white">
                                <thead>
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Categoria</th>
                                        <th class="text-end">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($expenses_today) > 0): ?>
                                        <?php foreach ($expenses_today as $expense): ?>
                                            <tr>
                                                <td><?php echo $expense['description']; ?></td>
                                                <td><span class="badge bg-secondary"><?php echo $expense['category']; ?></span></td>
                                                <td class="text-end fw-bold text-danger">R$
                                                    <?php echo number_format($expense['amount'], 2, ',', '.'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">Nenhuma despesa registrada hoje</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============ HISTÓRICO DE FECHAMENTOS ============ -->
    <?php if (count($cash_history) > 0): ?>
        <hr class="section-divider">
        <div class="row mt-2">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2" style="color:var(--primary-color);"></i>Últimos Fechamentos</h5>
                    </div>
                    <div class="card-body p-0 p-md-3">
                        <!-- Versão cards para mobile e mais legível -->
                        <?php foreach ($cash_history as $cash):
                            $diff = $cash['difference'];
                            $diff_class = $diff == 0 ? 'diferenca-zero' : ($diff > 0 ? 'diferenca-positiva' : 'diferenca-negativa');
                            $diff_icon = $diff == 0 ? 'fa-check-circle' : ($diff > 0 ? 'fa-arrow-up' : 'fa-arrow-down');
                            $diff_label = $diff == 0 ? 'Caixa correto' : ($diff > 0 ? 'Sobrou' : 'Faltou');
                        ?>
                        <div class="p-3 mb-3" style="background:rgba(255,255,255,0.02); border-radius:12px; border:1px solid var(--border-color);">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="text-muted" style="font-size:0.8rem;">FECHAMENTO</div>
                                    <div class="text-white fw-bold" style="font-size:1.05rem;">
                                        <?php echo date('d/m/Y', strtotime($cash['closed_at'])); ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.85rem;">
                                        <?php echo date('H:i', strtotime($cash['opened_at'])); ?> → <?php echo date('H:i', strtotime($cash['closed_at'])); ?>
                                        &nbsp;•&nbsp; <?php echo $cash['user_name']; ?>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center mt-2 mt-md-0">
                                    <div class="text-muted" style="font-size:0.75rem;">ESPERADO</div>
                                    <div class="text-white fw-bold" style="font-size:1.1rem;">
                                        R$ <?php echo number_format($cash['expected_amount'], 2, ',', '.'); ?>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center mt-2 mt-md-0">
                                    <div class="text-muted" style="font-size:0.75rem;">REAL</div>
                                    <div class="text-white fw-bold" style="font-size:1.1rem;">
                                        R$ <?php echo number_format($cash['closing_amount'], 2, ',', '.'); ?>
                                    </div>
                                </div>
                                <div class="col-md-3 mt-2 mt-md-0">
                                    <div class="<?php echo $diff_class; ?> text-center">
                                        <i class="fas <?php echo $diff_icon; ?> me-1"></i>
                                        <strong><?php echo $diff_label; ?>: R$ <?php echo number_format(abs($diff), 2, ',', '.'); ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center mt-2 mt-md-0">
                                    <?php if ($cash['notes']): ?>
                                        <span class="text-muted" title="<?php echo htmlspecialchars($cash['notes']); ?>" style="cursor:help;">
                                            <i class="fas fa-sticky-note me-1"></i><?php echo mb_substr($cash['notes'], 0, 20) . (mb_strlen($cash['notes']) > 20 ? '...' : ''); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>