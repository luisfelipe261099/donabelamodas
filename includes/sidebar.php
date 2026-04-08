<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-3">
            <img src="website/img/logo.png" alt="Dona Bela" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid rgba(212,163,74,0.4);">
            <div>
                <div style="font-family:'Playfair Display',serif;font-weight:800;font-size:1.1rem;color:#D4A34A;letter-spacing:2px;line-height:1.1;">DONA BELA</div>
                <div style="font-size:0.6rem;color:rgba(255,255,255,0.4);letter-spacing:1.5px;text-transform:uppercase;">Sistema PDV</div>
            </div>
        </div>
    </div>
    <nav class="nav flex-column">
        <!-- PRINCIPAL -->
        <div class="sidebar-section-title">INÍCIO</div>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
            href="dashboard.php">
            <i class="fas fa-home"></i> Página Inicial
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>" href="pos.php">
            <i class="fas fa-cash-register"></i> Nova Venda
        </a>

        <!-- CAIXA E DINHEIRO -->
        <div class="sidebar-section-title">CAIXA E DINHEIRO</div>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'caixa.php' ? 'active' : ''; ?>"
            href="caixa.php">
            <i class="fas fa-money-bill-wave"></i> Abrir / Fechar Caixa
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>"
            href="sales.php">
            <i class="fas fa-shopping-bag"></i> Vendas Realizadas
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'despesas.php' ? 'active' : ''; ?>"
            href="despesas.php">
            <i class="fas fa-receipt"></i> Despesas
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'finance.php' ? 'active' : ''; ?>"
            href="finance.php">
            <i class="fas fa-chart-line"></i> Resumo Financeiro
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'accounts.php' ? 'active' : ''; ?>"
            href="accounts.php">
            <i class="fas fa-file-invoice-dollar"></i> Contas a Receber
        </a>

        <!-- PRODUTOS E ESTOQUE -->
        <div class="sidebar-section-title">PRODUTOS</div>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>"
            href="products.php">
            <i class="fas fa-box"></i> Produtos
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>"
            href="stock.php">
            <i class="fas fa-warehouse"></i> Estoque
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>"
            href="categories.php">
            <i class="fas fa-tags"></i> Categorias
        </a>

        <!-- CLIENTES -->
        <div class="sidebar-section-title">CLIENTES</div>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>"
            href="customers.php">
            <i class="fas fa-user-friends"></i> Clientes
        </a>

        <!-- RELATÓRIOS -->
        <div class="sidebar-section-title">RELATÓRIOS</div>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"
            href="reports.php">
            <i class="fas fa-file-alt"></i> Relatórios
        </a>

        <!-- FERRAMENTAS (recolhível) -->
        <div class="sidebar-section-title sidebar-toggle-title" onclick="document.getElementById('ferramentas-section').classList.toggle('d-none')">
            FERRAMENTAS <i class="fas fa-chevron-down ms-1" style="font-size:0.55rem;"></i>
        </div>
        <div id="ferramentas-section" class="d-none">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pricing.php' ? 'active' : ''; ?>"
                href="pricing.php">
                <i class="fas fa-calculator"></i> Precificação
            </a>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'simulacao.php' ? 'active' : ''; ?>"
                href="simulacao.php">
                <i class="fas fa-sliders-h"></i> Simulação
            </a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>"
                    href="users.php">
                    <i class="fas fa-users-cog"></i> Usuários
                </a>
            <?php endif; ?>
        </div>
    </nav>
</div>