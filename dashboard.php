<?php include 'includes/header.php'; ?>
<?php
require_once 'config/db.php';

// Fetch stats
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$sales_today = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = '$today'")->fetchColumn() ?: 0;
$sales_count_today = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) = '$today'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5")->fetchColumn();

// Resumo do mês
$sales_month = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) BETWEEN '$month_start' AND '$month_end'")->fetchColumn() ?: 0;
$sales_count_month = $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(created_at) BETWEEN '$month_start' AND '$month_end'")->fetchColumn();
$expenses_month = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE expense_date BETWEEN '$month_start' AND '$month_end'")->fetchColumn();
$lucro_mes = $sales_month - $expenses_month;

// Verificar se caixa está aberto
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE user_id = ? AND status = 'open' ORDER BY opened_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$caixa_aberto = $stmt->fetch(PDO::FETCH_ASSOC);

// Últimos fechamentos com furo
$furos = $pdo->query("SELECT cr.*, u.name as user_name FROM cash_register cr JOIN users u ON cr.user_id = u.id WHERE cr.status = 'closed' AND cr.difference != 0 ORDER BY cr.closed_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$mes_nome = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$mes_atual = $mes_nome[date('n') - 1];
?>

<div class="container-fluid">
    <!-- Saudação -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold" style="font-family:'Playfair Display',serif;color:#D4A34A;">
                Olá, <?php echo $_SESSION['user_name'] ?? 'Usuário'; ?>! 👋
            </h2>
            <p class="text-muted" style="font-size:1rem;">
                <?php echo date('d') . ' de ' . $mes_atual . ' de ' . date('Y'); ?> — Dona Bela
            </p>
        </div>
    </div>

    <!-- Status do Caixa -->
    <?php if ($caixa_aberto): ?>
        <div class="alerta-ok mb-4">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong style="color:#4CAF76;">Caixa aberto</strong> desde <?php echo date('H:i', strtotime($caixa_aberto['opened_at'])); ?>
                &nbsp;—&nbsp;
                <a href="caixa.php" style="color:var(--primary-color);text-decoration:none;font-weight:600;">
                    Ir para o Caixa <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="alerta-furo mb-4" style="border-color:var(--primary-color); background:rgba(212,163,74,0.06);">
            <i class="fas fa-exclamation-circle" style="color:var(--primary-color);"></i>
            <div>
                <strong style="color:var(--primary-color);">Caixa fechado</strong> — Abra o caixa para registrar vendas.
                &nbsp;
                <a href="caixa.php" style="color:var(--primary-color);text-decoration:none;font-weight:600;">
                    Abrir Caixa <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Resumo de Hoje -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="resumo-card">
                <div class="icon" style="color:var(--primary-color);"><i class="fas fa-shopping-bag"></i></div>
                <div class="label">Vendas Hoje</div>
                <div class="valor" style="color:var(--primary-color);">R$ <?php echo number_format($sales_today, 2, ',', '.'); ?></div>
                <small class="text-muted"><?php echo $sales_count_today; ?> venda(s)</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="resumo-card">
                <div class="icon" style="color:#4CAF76;"><i class="fas fa-chart-line"></i></div>
                <div class="label">Vendas em <?php echo $mes_atual; ?></div>
                <div class="valor" style="color:#4CAF76;">R$ <?php echo number_format($sales_month, 2, ',', '.'); ?></div>
                <small class="text-muted"><?php echo $sales_count_month; ?> venda(s)</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="resumo-card">
                <div class="icon" style="color:#D4574A;"><i class="fas fa-receipt"></i></div>
                <div class="label">Despesas do Mês</div>
                <div class="valor" style="color:#D4574A;">R$ <?php echo number_format($expenses_month, 2, ',', '.'); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="resumo-card" style="border-color:<?php echo $lucro_mes >= 0 ? 'rgba(76,175,118,0.3)' : 'rgba(212,87,74,0.3)'; ?>;">
                <div class="icon" style="color:<?php echo $lucro_mes >= 0 ? '#4CAF76' : '#D4574A'; ?>;"><i class="fas fa-coins"></i></div>
                <div class="label">Resultado do Mês</div>
                <div class="valor" style="color:<?php echo $lucro_mes >= 0 ? '#4CAF76' : '#D4574A'; ?>;">R$ <?php echo number_format($lucro_mes, 2, ',', '.'); ?></div>
                <small class="text-muted"><?php echo $lucro_mes >= 0 ? 'Lucro' : 'Prejuízo'; ?></small>
            </div>
        </div>
    </div>

    <!-- Alertas importantes -->
    <?php if ($low_stock > 0): ?>
        <div class="alerta-furo mb-3" style="border-color:#E8B84B; background:rgba(232,184,75,0.06);">
            <i class="fas fa-exclamation-triangle" style="color:#E8B84B;"></i>
            <div>
                <strong style="color:#E8B84B;"><?php echo $low_stock; ?> produto(s) com estoque baixo!</strong>
                &nbsp;
                <a href="stock.php" style="color:var(--primary-color);text-decoration:none;font-weight:600;">
                    Ver Estoque <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($furos) > 0): ?>
        <div class="alerta-furo mb-3">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong style="color:#D4574A;"><?php echo count($furos); ?> fechamento(s) recente(s) com diferença no caixa.</strong>
                &nbsp;
                <a href="caixa.php" style="color:var(--primary-color);text-decoration:none;font-weight:600;">
                    Ver Detalhes <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <hr class="section-divider">

    <!-- Ações Rápidas - Botões grandes e claros -->
    <h5 class="fw-bold text-white mb-3" style="font-size:1.1rem;">
        <i class="fas fa-bolt me-2" style="color:var(--primary-color);"></i>O que você quer fazer?
    </h5>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="pos.php" class="quick-action-btn w-100" style="background:linear-gradient(135deg,rgba(212,163,74,0.12),rgba(212,163,74,0.04)); color:var(--primary-color); border-color:rgba(212,163,74,0.2);">
                <i class="fas fa-cash-register"></i>
                Nova Venda
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="caixa.php" class="quick-action-btn w-100" style="background:linear-gradient(135deg,rgba(76,175,118,0.12),rgba(76,175,118,0.04)); color:#4CAF76; border-color:rgba(76,175,118,0.2);">
                <i class="fas fa-money-bill-wave"></i>
                Caixa
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="sales.php" class="quick-action-btn w-100" style="background:linear-gradient(135deg,rgba(91,168,181,0.12),rgba(91,168,181,0.04)); color:var(--info-color); border-color:rgba(91,168,181,0.2);">
                <i class="fas fa-shopping-bag"></i>
                Ver Vendas
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="finance.php" class="quick-action-btn w-100" style="background:linear-gradient(135deg,rgba(232,184,75,0.12),rgba(232,184,75,0.04)); color:#E8B84B; border-color:rgba(232,184,75,0.2);">
                <i class="fas fa-chart-line"></i>
                Financeiro
            </a>
        </div>
    </div>

    <!-- Segundo bloco de ações -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="products.php" class="quick-action-btn w-100" style="background:rgba(255,255,255,0.02); color:var(--text-muted); border-color:var(--border-color);">
                <i class="fas fa-box"></i>
                Produtos
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="despesas.php" class="quick-action-btn w-100" style="background:rgba(255,255,255,0.02); color:var(--text-muted); border-color:var(--border-color);">
                <i class="fas fa-receipt"></i>
                Despesas
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="stock.php" class="quick-action-btn w-100" style="background:rgba(255,255,255,0.02); color:var(--text-muted); border-color:var(--border-color);">
                <i class="fas fa-warehouse"></i>
                Estoque
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="customers.php" class="quick-action-btn w-100" style="background:rgba(255,255,255,0.02); color:var(--text-muted); border-color:var(--border-color);">
                <i class="fas fa-user-friends"></i>
                Clientes
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>