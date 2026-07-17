<?php
/**
 * sync_webp_images.php — Golden Stone Hotel
 * Script to update all menu items in MySQL database to WebP images from 'ALL Images/'
 */
declare(strict_types=1);
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    $items = $pdo->query("SELECT id, item_code, name_en, category, image_path FROM menu_items")->fetchAll();

    $categoryDefaults = [
        'starter'     => 'ALL Images/Starter_Paneer_Tikka.webp',
        'main_course' => 'ALL Images/MainCourse_panner_butter_Masala.webp',
        'bread'       => 'ALL Images/Breads_Roti.webp',
        'rice_biryani'=> 'ALL Images/Rice_and_Biryani.webp',
        'beverage'    => 'ALL Images/Beverages_Mango_lassi.webp',
        'dessert'     => 'ALL Images/Dessert_Rasmalai.webp',
        'side_dish'   => 'ALL Images/Side_dishes_Raita.webp',
        'salad'       => 'ALL Images/Side_dishes_Raita.webp',
        'water'       => 'ALL Images/Water_bottle.webp',
    ];

    $updateStmt = $pdo->prepare("UPDATE menu_items SET image_path = ? WHERE id = ?");
    $updatedCount = 0;

    echo "Syncing menu items to 'ALL Images/*.webp'...\n";
    echo "================================================\n";

    foreach ($items as $row) {
        $id = $row['id'];
        $nameEn = trim($row['name_en']);
        $cat = $row['category'];
        $oldPath = $row['image_path'] ?? '';

        $exactPath = __DIR__ . '/../ALL Images/' . $nameEn . '.webp';
        
        if (file_exists($exactPath)) {
            $newPath = 'ALL Images/' . $nameEn . '.webp';
        } else {
            $newPath = $categoryDefaults[$cat] ?? 'ALL Images/Starter_Paneer_Tikka.webp';
        }

        $updateStmt->execute([$newPath, $id]);
        $updatedCount++;
        echo sprintf("[%s] %-30s -> %s\n", $row['item_code'], $nameEn, $newPath);
    }

    echo "================================================\n";
    echo "Successfully updated {$updatedCount} menu items to WebP image paths!\n";

} catch (Exception $e) {
    echo "Error syncing images: " . $e->getMessage() . "\n";
}
