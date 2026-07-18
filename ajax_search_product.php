<?php
require_once 'config/db.php';

// Normaliza texto para busca: minusculas, sem acentos e sem espacos duplicados
function normalize_search($text)
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $map = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];
    $text = strtr($text, $map);
    return preg_replace('/\s+/', ' ', $text);
}

// Distancia de edicao tolerada conforme o tamanho da palavra digitada
function max_typos($word)
{
    $len = mb_strlen($word);
    if ($len <= 3) return 0;
    if ($len <= 5) return 1;
    return 2;
}

if (isset($_GET['term'])) {
    $term = normalize_search($_GET['term']);

    if ($term === '') {
        echo json_encode([]);
        exit;
    }

    $all_products = $pdo->query("SELECT * FROM products LIMIT 3000")->fetchAll(PDO::FETCH_ASSOC);

    $term_words = explode(' ', $term);
    $scored = [];

    foreach ($all_products as $product) {
        $haystack = normalize_search(
            ($product['name'] ?? '') . ' ' .
            ($product['code'] ?? '') . ' ' .
            ($product['size'] ?? '') . ' ' .
            ($product['color'] ?? '')
        );
        $hay_words = explode(' ', $haystack);

        // Match direto do termo inteiro (melhor pontuacao)
        if (strpos($haystack, $term) !== false) {
            $scored[] = ['score' => 1000 - strpos($haystack, $term), 'product' => $product];
            continue;
        }

        // Match palavra a palavra, com tolerancia a erros de digitacao
        $score = 0;
        $matched_words = 0;
        foreach ($term_words as $tw) {
            if ($tw === '') continue;
            $best = 0;
            foreach ($hay_words as $hw) {
                if ($hw === '') continue;
                if (strpos($hw, $tw) !== false) {
                    // Substring/prefixo da palavra do produto
                    $best = max($best, 90);
                    continue;
                }
                $tolerance = max_typos($tw);
                if ($tolerance > 0) {
                    // Compara com a palavra inteira e com o prefixo do mesmo tamanho
                    $dist_full = levenshtein($tw, $hw);
                    $dist_prefix = levenshtein($tw, substr($hw, 0, strlen($tw)));
                    $dist = min($dist_full, $dist_prefix);
                    if ($dist <= $tolerance) {
                        $best = max($best, 80 - ($dist * 15));
                    }
                }
            }
            if ($best > 0) {
                $matched_words++;
                $score += $best;
            }
        }

        // Exige que todas as palavras digitadas tenham algum match
        $total_words = count(array_filter($term_words, fn($w) => $w !== ''));
        if ($total_words > 0 && $matched_words == $total_words) {
            $scored[] = ['score' => $score, 'product' => $product];
        }
    }

    // Ordena por relevancia e limita
    usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);
    $scored = array_slice($scored, 0, 10);
    $products = array_map(fn($s) => $s['product'], $scored);

    // Calculate max discount for each product
    foreach ($products as &$product) {
        $cost = floatval($product['cost_price']);
        $sell = floatval($product['sell_price']);

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
