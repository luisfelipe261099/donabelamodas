<?php
require_once 'config/db.php';

// Set Brazil timezone
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_GET['id'])) {
    die("ID da venda não fornecido.");
}

$sale_id = $_GET['id'];

// Fetch Sale Details
$stmt = $pdo->prepare("
    SELECT s.*, u.name as user_name 
    FROM sales s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sale) {
    die("Venda não encontrada.");
}

// Fetch Sale Items
$stmt_items = $pdo->prepare("
    SELECT si.*, p.name, p.code 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt_items->execute([$sale_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Recibo #<?php echo $sale_id; ?></title>
    <style>
        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }

            body {
                margin: 0;
                padding: 5px;
            }

            /* Impressao em negrito e preto forte para sair mais escuro */
            * {
                font-weight: bold !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            width: 72mm;
            margin: 0 auto;
            background: #fff;
            color: #000;
            padding: 2mm;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 10px;
        }

        .store-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
        }

        .item-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            margin-bottom: 2px;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 12px;
            margin-top: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }

        .no-print {
            text-align: center;
            margin-top: 20px;
            padding-bottom: 20px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="store-name">PDV DONA BELA</div>
        <div>R. Helena Carcereri Perkarski, 579 - Uberaba</div>
        <div>Tel: (11) 99999-9999</div>
        <div>Instagram: @donabelamoda_oficial</div>
    </div>

    <div class="info-row">
        <span>Venda: #<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></span>
        <span>Op: <?php echo explode(' ', $sale['user_name'])[0]; ?></span>
    </div>
    
    <div class="info-row">
        <span>Data:</span>
        <span><?php echo date('d/m/Y H:i', strtotime($sale['created_at'])); ?></span>
    </div>

    <div class="divider"></div>

    <div style="margin-bottom: 5px; font-weight: bold;">ITEM | QTD x UNIT | TOTAL</div>

    <?php foreach ($items as $item): ?>
        <div style="margin-bottom: 5px;">
            <div class="item-name"><?php echo $item['name']; ?></div>
            <div class="item-details">
                <span><?php echo $item['quantity']; ?> x R$ <?php echo number_format($item['unit_price'], 2, ',', '.'); ?></span>
                <span>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></span>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="divider"></div>

    <div class="total-row">
        <span>TOTAL</span>
        <span>R$ <?php echo number_format($sale['total_amount'], 2, ',', '.'); ?></span>
    </div>

    <div class="info-row" style="margin-top: 5px;">
        <span>Pagamento:</span>
        <span><?php
        $methods = [
            'Cash' => 'Dinheiro',
            'Credit Card' => 'Cartão Crédito',
            'Debit Card' => 'Cartão Débito',
            'Pix' => 'Pix'
        ];
        echo $methods[$sale['payment_method']] ?? $sale['payment_method'];
        ?></span>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <div>Obrigado pela preferência!</div>
        <div>Volte sempre.</div>
        <div style="font-size: 10px; margin-top: 5px;">Sistema PDV Fashion</div>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Fechar</button>
    </div>

    <script>
        window.onload = function () {
            window.print();
        }
    </script>
</body>

</html>