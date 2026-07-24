<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) {
    echo json_encode(['items' => []]);
    exit;
}

$showOutOfStock = setting('show_out_of_stock', '1') === '1';

$where = ['p.status = 1', 'c.status = 1', '(p.name LIKE :q_name OR p.short_description LIKE :q_short)'];
if (!$showOutOfStock) {
    $where[] = '(p.stock_qty IS NULL OR p.stock_qty > 0)';
}

$sql = 'SELECT p.name, p.slug, p.price, p.discount_price, c.name AS category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY p.featured DESC, p.created_at DESC
        LIMIT 8';

$stmt = db()->prepare($sql);
$like = '%' . $q . '%';
$stmt->bindValue(':q_name', $like);
$stmt->bindValue(':q_short', $like);
$stmt->execute();

$items = [];
$base = rtrim((string)BASE_URL, '/');
while ($row = $stmt->fetch()) {
    $price = isset($row['discount_price']) && $row['discount_price'] !== null
        ? (float)$row['discount_price']
        : (float)$row['price'];

    $items[] = [
        'name' => (string)$row['name'],
        'slug' => (string)$row['slug'],
        'category_name' => (string)$row['category_name'],
        'price_label' => format_currency($price),
        'url' => $base . '/shop/product/' . rawurlencode((string)$row['slug']),
    ];
}

echo json_encode(['items' => $items], JSON_UNESCAPED_SLASHES);
