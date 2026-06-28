<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Product Search & List -->
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-dark border-secondary text-white"><i
                                class="fas fa-search"></i></span>
                        <input type="text" id="product-search" class="form-control bg-dark text-white border-secondary"
                            placeholder="Buscar produto por nome ou código..." autofocus>
                    </div>
                </div>
                <div class="card-body overflow-auto pos-products-container">
                    <div id="search-results" class="row g-3">
                        <!-- Results will appear here -->
                        <div class="col-12 text-center text-muted mt-5">
                            <i class="fas fa-barcode fa-3x mb-3"></i>
                            <h4>Digite para buscar produtos</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart & Checkout -->
        <div class="col-md-5">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Carrinho</h5>
                    <span class="badge bg-white text-primary" id="cart-count">0 itens</span>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <div class="table-responsive flex-grow-1 pos-cart-container">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-end">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                                <!-- Cart items will appear here -->
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-dark border-top border-secondary">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="text-muted mb-0">Subtotal</h5>
                            <h5 class="fw-bold text-white mb-0" id="cart-subtotal">R$ 0,00</h5>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="text-white mb-0">Total</h4>
                            <h2 class="fw-bold text-success mb-0" id="cart-total">R$ 0,00</h2>
                        </div>
                        <button class="btn btn-success w-100 py-3 fw-bold fs-5" onclick="openCheckout()">
                            <i class="fas fa-check-circle me-2"></i> Finalizar Venda
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Finalizar Venda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4 text-center">
                    <h1 class="text-success fw-bold" id="modal-total">R$ 0,00</h1>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Desconto</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-dark border-secondary text-white">R$</span>
                        <input type="number" id="sale-discount" class="form-control bg-dark text-white border-secondary" value="0" min="0" step="0.01" onchange="updateModalTotal()">
                    </div>
                    <button class="btn btn-outline-info w-100" type="button" onclick="promptDiscountOptions()">
                        <i class="fas fa-tags me-2"></i> Escolher Desconto
                    </button>
                    <small class="text-muted d-block mt-2" id="discount-feedback"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Forma de Pagamento</label>
                    <select id="payment-method" class="form-select bg-dark text-white border-secondary" onchange="handlePaymentChange()">
                        <option value="Cash">💵 Dinheiro</option>
                        <option value="Credit Card">💳 Cartão de Crédito</option>
                        <option value="Debit Card">💳 Cartão de Débito</option>
                        <option value="Pix">📱 Pix</option>
                        <option value="Account">📋 Crediário/Conta</option>
                    </select>
                </div>

                <!-- Campo de Valor Pago (só aparece para Dinheiro) -->
                <div class="mb-3" id="cash-payment-section" style="display: none;">
                    <label class="form-label">Valor Pago pelo Cliente</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-dark border-secondary text-white">R$</span>
                        <input type="number" id="cash-paid" class="form-control bg-dark text-white border-secondary"
                               placeholder="0.00" min="0" step="0.01" onkeyup="calculateChange()">
                    </div>
                    <div id="change-display" class="alert alert-info" style="display: none;">
                        <strong>TROCO: R$ <span id="change-amount">0,00</span></strong>
                    </div>
                </div>

                <!-- Campo de Cliente (só aparece para Crediário) -->
                <div class="mb-3" id="account-section" style="display: none;">
                    <label class="form-label">Cliente</label>
                    <select id="customer-select" class="form-select bg-dark text-white border-secondary">
                        <option value="">Selecione o cliente...</option>
                        <?php
                        require_once 'config/db.php';
                        $customers = $pdo->query("SELECT id, name, phone FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($customers as $customer) {
                            echo "<option value='{$customer['id']}'>{$customer['name']} - {$customer['phone']}</option>";
                        }
                        ?>
                    </select>
                    <small class="text-muted">Selecione o cliente para lançar no crediário</small>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="processSale()">
                    <i class="fas fa-check-circle me-2"></i>Finalizar Venda
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    let cart = [];

    $('#product-search').on('keyup', function () {
        let term = $(this).val();
        if (term.length > 1) {
            $.get('ajax_search_product.php', { term: term }, function (data) {
                let products = JSON.parse(data);
                let html = '';
                products.forEach(p => {
                    html += `
                    <div class="col-md-4">
                        <div class="card h-100 product-card"
                            onclick='addToCart(${JSON.stringify(p)})' style="cursor: pointer;">
                            <div class="card-body text-center">
                                <h6 class="fw-bold text-truncate">${p.name}</h6>
                                <p class="text-muted small">${p.size} | ${p.color}</p>
                                <h5 class="text-success">R$ ${parseFloat(p.sell_price).toFixed(2)}</h5>
                            </div>
                        </div>
                    </div>
                    `;
                });
                $('#search-results').html(html);
            });
        } else {
            $('#search-results').html(`
                <div class="col-12 text-center text-muted mt-5">
                    <i class="fas fa-barcode fa-3x mb-3"></i>
                    <h4>Digite para buscar produtos</h4>
                </div>
            `);
        }
    });

    function addToCart(product) {
        let existing = cart.find(i => i.id === product.id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.sell_price),
                quantity: 1
            });
        }
        updateCart();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCart();
    }

    function updateCart() {
        let html = '';
        let total = 0;
        let count = 0;

        cart.forEach((item, index) => {
            let subtotal = item.price * item.quantity;
            total += subtotal;
            count += item.quantity;

            html += `
            <tr>
                <td>${item.name}</td>
                <td class="text-center">
                    <input type="number"
                        class="form-control form-control-sm bg-dark text-white text-center"
                        value="${item.quantity}" min="1"
                        style="width: 60px; margin: 0 auto;"
                        onchange="updateQuantity(${index}, this.value)">
                </td>
                <td class="text-end">R$ ${subtotal.toFixed(2)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger"
                        onclick="removeFromCart(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
            `;
        });

        $('#cart-items').html(html);
        $('#cart-subtotal').text('R$ ' + total.toFixed(2));
        $('#cart-total').text('R$ ' + total.toFixed(2));
        $('#cart-count').text(count + ' itens');
    }

    function updateQuantity(index, qty) {
        if (qty < 1) qty = 1;
        cart[index].quantity = parseInt(qty);
        updateCart();
    }

    function openCheckout() {
        if (cart.length === 0) {
            Swal.fire('Carrinho vazio', 'Adicione produtos antes de finalizar.', 'warning');
            return;
        }

        // Reset discount
        $('#sale-discount').val(0);
        $('#discount-feedback').text('');

        let total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
        $('#modal-total').text('R$ ' + total.toFixed(2));

        // Reset payment fields
        $('#cash-paid').val('');
        $('#change-display').hide();
        $('#payment-method').val('Cash');
        handlePaymentChange();

        new bootstrap.Modal(document.getElementById('checkoutModal')).show();
    }

    function handlePaymentChange() {
        const method = $('#payment-method').val();

        // Esconder todos os campos extras
        $('#cash-payment-section').hide();
        $('#account-section').hide();

        // Mostrar campo específico
        if (method === 'Cash') {
            $('#cash-payment-section').show();
            $('#cash-paid').focus();
        } else if (method === 'Account') {
            $('#account-section').show();
        }
    }

    function calculateChange() {
        const total = parseFloat($('#modal-total').text().replace('R$ ', '').replace(',', '.'));
        const discount = parseFloat($('#sale-discount').val()) || 0;
        const finalTotal = total - discount;
        const paid = parseFloat($('#cash-paid').val()) || 0;

        if (paid > 0) {
            const change = paid - finalTotal;
            if (change >= 0) {
                $('#change-amount').text(change.toFixed(2).replace('.', ','));
                $('#change-display').removeClass('alert-danger').addClass('alert-success').show();
            } else {
                $('#change-amount').text('VALOR INSUFICIENTE!');
                $('#change-display').removeClass('alert-success').addClass('alert-danger').show();
            }
        } else {
            $('#change-display').hide();
        }
    }
    
    // Descontos por forma de pagamento
    const DISCOUNT_OPTIONS = {
        'Cash':       { label: 'Dinheiro', percent: 10 },
        'Pix':        { label: 'Pix',      percent: 10 },
        'Debit Card': { label: 'Débito',   percent: 5 }
    };

    function promptDiscountOptions() {
        const inputOptions = {};
        Object.keys(DISCOUNT_OPTIONS).forEach(key => {
            const opt = DISCOUNT_OPTIONS[key];
            inputOptions[key] = `${opt.label} - ${opt.percent}%`;
        });

        Swal.fire({
            title: 'Escolher Desconto',
            input: 'select',
            inputOptions: inputOptions,
            inputPlaceholder: 'Selecione a forma de pagamento',
            showCancelButton: true,
            confirmButtonText: 'Aplicar Desconto',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#1cc88a',
            cancelButtonColor: '#858796',
            inputValidator: (value) => {
                if (!value) {
                    return 'Selecione uma forma de pagamento';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                applyPaymentDiscount(result.value);
            }
        });
    }

    function applyPaymentDiscount(methodKey) {
        const opt = DISCOUNT_OPTIONS[methodKey];
        if (!opt) return;

        const total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
        const discount = total * (opt.percent / 100);

        $('#sale-discount').val(discount.toFixed(2));

        // Sincroniza a forma de pagamento com o desconto escolhido
        $('#payment-method').val(methodKey);
        handlePaymentChange();

        updateModalTotal();

        Swal.fire({
            icon: 'success',
            title: 'Desconto aplicado!',
            html: `${opt.label}: <strong>${opt.percent}%</strong> (R$ ${discount.toFixed(2)})`,
            timer: 1800,
            showConfirmButton: false
        });
    }
    
    function updateModalTotal() {
        let total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
        let discount = parseFloat($('#sale-discount').val()) || 0;

        if (discount > 0) {
            const pct = total > 0 ? (discount / total) * 100 : 0;
            $('#discount-feedback').removeClass('text-danger').text(`Desconto aplicado: R$ ${discount.toFixed(2)} (${pct.toFixed(1)}%)`);
        } else {
            $('#discount-feedback').text('');
        }

        let finalTotal = Math.max(0, total - discount);
        $('#modal-total').text('R$ ' + finalTotal.toFixed(2));

        // Recalcular troco se estiver em modo dinheiro
        calculateChange();
    }

    function processSale() {
        let paymentMethod = $('#payment-method').val();
        let discount = parseFloat($('#sale-discount').val()) || 0;
        let total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
        let finalTotal = total - discount;

        // Validações
        if (paymentMethod === 'Cash') {
            let paid = parseFloat($('#cash-paid').val()) || 0;
            if (paid < finalTotal) {
                Swal.fire('Valor Insuficiente', 'O valor pago é menor que o total da venda!', 'error');
                return;
            }
        }

        if (paymentMethod === 'Account') {
            let customerId = $('#customer-select').val();
            if (!customerId) {
                Swal.fire('Cliente não selecionado', 'Selecione um cliente para venda no crediário!', 'error');
                return;
            }
        }

        let saleData = {
            items: cart,
            total: finalTotal,
            discount: discount,
            payment_method: paymentMethod
        };

        // Adicionar customer_id se for crediário
        if (paymentMethod === 'Account') {
            saleData.customer_id = $('#customer-select').val();
        }

        $.ajax({
            url: 'process_sale.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(saleData),
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Venda Realizada!',
                        text: 'ID: ' + response.sale_id,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#1cc88a',
                        cancelButtonColor: '#858796',
                        confirmButtonText: '<i class="fas fa-print"></i> Imprimir Comprovante',
                        cancelButtonText: 'Fechar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('print_receipt.php?id=' + response.sale_id, '_blank', 'width=400,height=600');
                        }
                        // Reset Cart
                        cart = [];
                        updateCart();
                        $('#product-search').val('').focus();
                        $('#search-results').html('');
                        bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                    });
                } else {
                    Swal.fire('Erro', response.message, 'error');
                }
            }
        });
    }
</script>