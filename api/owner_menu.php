<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

$validCategories = ['starter','main_course','bread','rice_biryani','beverage','dessert','salad','side_dish','water'];

// GET — list items (all or by category)
if ($method === 'GET') {
    $cat = $_GET['category'] ?? null;
    if ($cat && in_array($cat, $validCategories, true)) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category = ? ORDER BY sort_order, name_en");
        $stmt->execute([$cat]);
    } else {
        $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY category, sort_order, name_en");
    }
    jsonResponse(['success' => true, 'items' => $stmt->fetchAll()]);
}

// POST — add new item
if ($method === 'POST') {
    $body = getJsonBody();
    requireFields($body, ['name_en', 'category', 'price'], 'Add item');
    if (!in_array($body['category'], $validCategories, true)) {
        jsonResponse(['success' => false, 'error' => 'Invalid category.'], 400);
    }
    $prefix = strtoupper(substr($body['category'], 0, 3));
    $max    = $pdo->query("SELECT MAX(CAST(SUBSTRING(item_code,5) AS UNSIGNED)) FROM menu_items WHERE item_code LIKE '{$prefix}-%'")->fetchColumn();
    $code   = $prefix . '-' . str_pad((string)((int)$max + 1), 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO menu_items (item_code,name_en,name_hi,name_mr,category,price,image_path,is_available,is_veg,prep_time_min,sort_order,section) VALUES (:code,:name_en,:name_hi,:name_mr,:category,:price,:image_path,:is_available,:is_veg,:prep_time,:sort_order,:section)");
    $stmt->execute([
        'code'         => $code,
        'name_en'      => trim($body['name_en']),
        'name_hi'      => trim($body['name_hi'] ?? ''),
        'name_mr'      => trim($body['name_mr'] ?? ''),
        'category'     => $body['category'],
        'price'        => round((float)$body['price'], 2),
        'image_path'   => trim($body['image_path'] ?? ''),
        'is_available' => isset($body['is_available']) ? (int)(bool)$body['is_available'] : 1,
        'is_veg'       => isset($body['is_veg']) ? (int)(bool)$body['is_veg'] : 1,
        'prep_time'    => max(1, (int)($body['prep_time_min'] ?? 10)),
        'sort_order'   => (int)($body['sort_order'] ?? 0),
        'section'      => trim($body['section'] ?? '') ?: null,
    ]);
    jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'item_code' => $code]);
}

// PUT — update item (including toggle availability)
if ($method === 'PUT') {
    $body = getJsonBody();
    requireFields($body, ['id'], 'Update item');
    $id = (int)$body['id'];
    $exists = $pdo->prepare("SELECT id FROM menu_items WHERE id = ?");
    $exists->execute([$id]);
    if (!$exists->fetch()) jsonResponse(['success' => false, 'error' => 'Item not found.'], 404);

    $allowed = ['name_en','name_hi','name_mr','category','price','image_path','is_available','is_veg','prep_time_min','sort_order','section'];
    $fields = []; $params = [];
    foreach ($allowed as $f) {
        if (!array_key_exists($f, $body)) continue;
        if ($f === 'category' && !in_array($body[$f], $validCategories, true)) continue;
        $fields[] = "`$f` = :$f";
        $params[$f] = ($f === 'price') ? round((float)$body[$f], 2) : $body[$f];
    }
    if (empty($fields)) jsonResponse(['success' => false, 'error' => 'No valid fields to update.'], 400);
    $params['id'] = $id;
    $pdo->prepare("UPDATE menu_items SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
    jsonResponse(['success' => true]);
}

// DELETE — remove item
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'error' => 'Missing id.'], 400);
    $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
