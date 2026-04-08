<?php
require_once 'config/db.php';

if (isset($_GET['term'])) {
    $term = "%" . $_GET['term'] . "%";
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR code LIKE ? LIMIT 10");
    $stmt->execute([$term, $term]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate max discount for each product
    foreach ($products as &$product) {
        $cost = floatval($product['cost_price']);
        $sell = floatval($product['sell_price']);

        // Safe margin of 10%
        // Min Price = Cost / (1 - 0.10)
        // If Cost is 0, we assume full price is profit, but let's cap discount at 50% for safety? 
        // Or just allow 100%? Let's say if cost is 0, max discount is 90% of sell price.

        if ($cost > 0) {
            $min_sell_price = $cost / 0.90;
            $max_discount = max(0, $sell - $min_sell_price);
        } else {
            // Fallback if no cost price: allow up to 50% discount
            $max_discount = $sell * 0.50;
        }

        $product['max_discount_value'] = $max_discount;
        $product['max_discount_percent'] = ($sell > 0) ? ($max_discount / $sell) * 100 : 0;
    }

    echo json_encode($products);
}
?>