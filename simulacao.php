<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-white"><i class="fas fa-calculator me-2"></i>Simulação de Vendas no Crédito</h2>
            <p class="text-muted">Simule as parcelas e taxas para vendas no cartão de crédito</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Digite o Valor da Venda</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label for="valorVenda" class="form-label text-muted">Valor Total da Venda</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-primary text-white">R$</span>
                            <input type="number" class="form-control form-control-lg" id="valorVenda"
                                placeholder="Digite o valor (ex: 5000)" min="0" step="0.01"
                                oninput="calcularParcelas()">
                        </div>
                        <small class="text-muted">Digite o valor em reais (ex: 5000 para R$ 5.000,00)</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted">Quem paga a taxa?</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipoTaxa" id="taxaLojista" value="lojista"
                                checked onchange="calcularParcelas()">
                            <label class="btn btn-outline-warning" for="taxaLojista">
                                <i class="fas fa-store me-2"></i>Lojista Absorve
                            </label>

                            <input type="radio" class="btn-check" name="tipoTaxa" id="taxaComprador" value="comprador"
                                onchange="calcularParcelas()">
                            <label class="btn btn-outline-info" for="taxaComprador">
                                <i class="fas fa-user me-2"></i>Comprador Paga
                            </label>
                        </div>
                        <small class="text-muted mt-2 d-block" id="explicacaoTaxa">
                            <i class="fas fa-info-circle me-1"></i>O lojista recebe menos, o cliente paga o valor
                            original.
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Taxas aplicadas:</strong> As taxas variam de 3,79% (1x) até 12,88% (12x)
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Simulação das Parcelas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center">Parcelas</th>
                                    <th class="text-center">Taxa (%)</th>
                                    <th class="text-center">Valor Taxa</th>
                                    <th class="text-center">Valor Parcela</th>
                                    <th class="text-center" id="colTotalCliente" style="display: none;">Total Cliente
                                        Paga</th>
                                    <th class="text-center">Você Recebe</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaParcelas">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-arrow-left fa-2x mb-2 d-block"></i>
                                        Digite um valor para simular
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Taxas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-percentage me-2"></i>Tabela de Taxas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>1x</strong></td>
                                        <td class="text-end text-warning">3,79%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>2x</strong></td>
                                        <td class="text-end text-warning">7,98%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>3x</strong></td>
                                        <td class="text-end text-warning">8,47%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>4x</strong></td>
                                        <td class="text-end text-warning">8,96%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>5x</strong></td>
                                        <td class="text-end text-warning">9,45%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>6x</strong></td>
                                        <td class="text-end text-warning">9,94%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>7x</strong></td>
                                        <td class="text-end text-warning">10,43%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>8x</strong></td>
                                        <td class="text-end text-warning">10,92%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>9x</strong></td>
                                        <td class="text-end text-warning">11,41%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>10x</strong></td>
                                        <td class="text-end text-warning">11,90%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>11x</strong></td>
                                        <td class="text-end text-warning">12,39%</td>
                                    </tr>
                                    <tr>
                                        <td><strong>12x</strong></td>
                                        <td class="text-end text-warning">12,88%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Taxas por parcela
    const taxas = {
        1: 3.79,
        2: 7.98,
        3: 8.47,
        4: 8.96,
        5: 9.45,
        6: 9.94,
        7: 10.43,
        8: 10.92,
        9: 11.41,
        10: 11.90,
        11: 12.39,
        12: 12.88
    };

    function formatMoney(value) {
        return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calcularParcelas() {
        const input = document.getElementById('valorVenda');
        const valor = parseFloat(input.value) || 0;
        const tbody = document.getElementById('tabelaParcelas');
        const tipoTaxa = document.querySelector('input[name="tipoTaxa"]:checked').value;
        const explicacao = document.getElementById('explicacaoTaxa');
        const colTotalCliente = document.getElementById('colTotalCliente');

        // Mostra/oculta coluna de total do cliente
        if (tipoTaxa === 'comprador') {
            colTotalCliente.style.display = '';
        } else {
            colTotalCliente.style.display = 'none';
        }

        // Atualiza explicação
        if (tipoTaxa === 'lojista') {
            explicacao.innerHTML = '<i class="fas fa-info-circle me-1"></i>O lojista recebe menos, o cliente paga o valor original.';
        } else {
            explicacao.innerHTML = '<i class="fas fa-info-circle me-1"></i>O cliente paga mais (valor + taxa), o lojista recebe o valor original.';
        }

        if (valor <= 0) {
            const colspan = tipoTaxa === 'comprador' ? 6 : 5;
            tbody.innerHTML = `
            <tr>
                <td colspan="${colspan}" class="text-center text-muted py-4">
                    <i class="fas fa-arrow-left fa-2x mb-2 d-block"></i>
                    Digite um valor para simular
                </td>
            </tr>
        `;
            return;
        }

        let html = '';

        for (let parcelas = 1; parcelas <= 12; parcelas++) {
            const taxa = taxas[parcelas];

            let valorTaxa, valorRecebido, valorTotal, valorParcela;

            if (tipoTaxa === 'lojista') {
                // Lojista absorve a taxa - cliente paga o valor original
                valorTotal = valor;
                valorTaxa = valor * (taxa / 100);
                valorRecebido = valor - valorTaxa;
                valorParcela = valor / parcelas;
            } else {
                // Comprador paga a taxa - lojista recebe o valor integral
                // Calcula o valor que o cliente deve pagar para o lojista receber o valor original
                valorRecebido = valor;
                valorTotal = valor / (1 - (taxa / 100));
                valorTaxa = valorTotal - valor;
                valorParcela = valorTotal / parcelas;
            }

            // Cores para a coluna de parcelas (indica nível da taxa)
            let parcelaColor = '';
            if (parcelas <= 3) {
                parcelaColor = '#28a745'; // Verde
            } else if (parcelas <= 6) {
                parcelaColor = '#17a2b8'; // Azul
            } else if (parcelas <= 9) {
                parcelaColor = '#ffc107'; // Amarelo
            } else {
                parcelaColor = '#dc3545'; // Vermelho
            }

            if (tipoTaxa === 'lojista') {
                html += `
                <tr>
                    <td class="text-center fw-bold" style="color: ${parcelaColor};">${parcelas}x</td>
                    <td class="text-center text-white">${taxa.toFixed(2).replace('.', ',')}%</td>
                    <td class="text-center" style="color: #ff6b6b;">- R$ ${formatMoney(valorTaxa)}</td>
                    <td class="text-center text-white">R$ ${formatMoney(valorParcela)}</td>
                    <td class="text-center fw-bold" style="color: #51cf66;">R$ ${formatMoney(valorRecebido)}</td>
                </tr>
            `;
            } else {
                html += `
                <tr>
                    <td class="text-center fw-bold" style="color: ${parcelaColor};">${parcelas}x</td>
                    <td class="text-center text-white">${taxa.toFixed(2).replace('.', ',')}%</td>
                    <td class="text-center" style="color: #ff6b6b;">+ R$ ${formatMoney(valorTaxa)}</td>
                    <td class="text-center" style="color: #ffc107;">R$ ${formatMoney(valorParcela)}</td>
                    <td class="text-center fw-bold" style="color: #17a2b8; background: rgba(23, 162, 184, 0.2);">R$ ${formatMoney(valorTotal)}</td>
                    <td class="text-center fw-bold" style="color: #51cf66;">R$ ${formatMoney(valorRecebido)}</td>
                </tr>
            `;
            }
        }

        tbody.innerHTML = html;

        // Atualiza cabeçalhos da tabela
        const headerCells = document.querySelectorAll('#tabelaParcelas').closest('table').querySelectorAll('thead th');
        if (tipoTaxa === 'lojista') {
            headerCells[2].textContent = 'Desconto Taxa';
            headerCells[3].textContent = 'Valor Parcela';
        } else {
            headerCells[2].textContent = 'Acréscimo Taxa';
            headerCells[3].textContent = 'Cliente Paga/Parcela';
        }
    }

    // Calcular ao carregar
    document.addEventListener('DOMContentLoaded', function () {
        calcularParcelas();
    });
</script>

<?php include 'includes/footer.php'; ?>