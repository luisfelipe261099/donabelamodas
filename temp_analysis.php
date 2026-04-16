<?php
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query('SELECT id, name, code, category_id FROM products ORDER BY id ASC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== TODOS OS PRODUTOS (" . count($rows) . ") ===\n\n";
foreach ($rows as $r) {
    echo "ID:{$r['id']} | Nome: [{$r['name']}] | Cod: [{$r['code']}] | Cat: {$r['category_id']}\n";
}
