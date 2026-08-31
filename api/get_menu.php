<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$validCategories = ['starter','main_course','bread','rice_biryani','beverage','dessert','salad','side_dish','water','welcome_drink','breakfast'];
$category = trim($_GET['category'] ?? '');

if (!$category || !in_array($category, $validCategories, true)) {
    jsonResponse(['success' => false, 'error' => 'Invalid or missing category.'], 400);
}

$pdo  = getDB();
$stmt = $pdo->prepare("
    SELECT id, name_en, name_hi, name_mr, price, image_path, is_veg, prep_time_min, sort_order, section
    FROM menu_items
    WHERE category = ? AND is_available = 1
    ORDER BY sort_order ASC, id ASC
");
$stmt->execute([$category]);
$items = $stmt->fetchAll();

jsonResponse(['success' => true, 'category' => $category, 'items' => $items]);
