<?php
/**
 * Migração única: adiciona a coluna cost_price em sale_items para
 * registrar o CUSTO HISTÓRICO (custo do produto no momento da venda).
 *
 * Acesse esta página UMA vez no navegador (precisa estar logado).
 * É idempotente: pode rodar de novo sem causar problema.
 */
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Você precisa estar logado para executar a migração. <a href="login.php">Entrar</a>');
}

header('Content-Type: text/html; charset=utf-8');

$messages = [];

try {
    // 1) Adiciona a coluna se ainda não existir
    $exists = $pdo->query("SHOW COLUMNS FROM sale_items LIKE 'cost_price'")->fetch();
    if ($exists) {
        $messages[] = '✔ A coluna sale_items.cost_price já existe.';
    } else {
        $pdo->exec("ALTER TABLE sale_items ADD COLUMN cost_price DECIMAL(10,2) NOT NULL DEFAULT 0");
        $messages[] = '✔ Coluna sale_items.cost_price criada com sucesso.';
    }

    // 2) Backfill: preenche vendas antigas com o custo ATUAL do produto
    //    (apenas onde ainda está zerado), para o histórico ter um valor.
    $affected = $pdo->exec("
        UPDATE sale_items si
        JOIN products p ON si.product_id = p.id
        SET si.cost_price = p.cost_price
        WHERE (si.cost_price = 0 OR si.cost_price IS NULL)
    ");
    $messages[] = "✔ Backfill concluído: {$affected} item(ns) atualizado(s) com o custo atual do produto.";

    $messages[] = '<strong>Migração finalizada!</strong> A partir de agora cada venda grava o custo do momento.';
} catch (Exception $e) {
    $messages[] = '✖ Erro: ' . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Migração - Custo Histórico</title>
    <style>
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #eee; padding: 40px; }
        .box { max-width: 640px; margin: 0 auto; background: #2a2a2a; border: 1px solid #444; border-radius: 8px; padding: 24px; }
        h1 { font-size: 20px; }
        ul { line-height: 1.8; }
        a { color: #4CAF50; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Migração: Custo Histórico nas Vendas</h1>
        <ul>
            <?php foreach ($messages as $m): ?>
                <li><?php echo $m; ?></li>
            <?php endforeach; ?>
        </ul>
        <p><a href="finance.php">← Voltar ao Financeiro</a></p>
    </div>
</body>
</html>
