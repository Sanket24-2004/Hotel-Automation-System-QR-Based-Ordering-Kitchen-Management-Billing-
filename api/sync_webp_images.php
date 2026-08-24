<?php
/**
 * sync_webp_images.php — Hotel Tulsi
 * Accurately maps all menu items in MySQL database to their respective WebP images in 'ALL Images/'
 */
declare(strict_types=1);
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    $items = $pdo->query("SELECT id, item_code, name_en, category, image_path FROM menu_items ORDER BY id ASC")->fetchAll();

    // Scan all image files in ALL Images
    $imgDir = __DIR__ . '/../ALL Images';
    $files = scandir($imgDir);
    $imgMap = []; // lowercase stripped key => actual relative path

    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || is_dir($imgDir . '/' . $f)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['webp', 'png', 'jpg', 'jpeg'])) continue;

        $base = pathinfo($f, PATHINFO_FILENAME);
        $normKey = strtolower(preg_replace('/[^a-z0-9]/i', '', $base));
        $imgMap[$normKey] = 'ALL Images/' . $f;
    }

    // Specific manual overrides for fuzzy names
    $customOverrides = [
        'water bottle (500 ml - cold)'   => 'ALL Images/Water Bottle (500 ml - Cold) new.webp',
        'water bottle (500 ml - normal)' => 'ALL Images/Water Bottle (500 ml - Normal) new.webp',
        'water bottle (1 litre - cold)'  => 'ALL Images/Water Bottle (1 Litre - Cold) new.webp',
        'water bottle (1 litre - normal)'=> 'ALL Images/Water Bottle (1 Litre - Normal) new.webp',
        'water bottle (500ml cold)'      => 'ALL Images/Water Bottle (500 ml - Cold) new.webp',
        'water bottle (500ml normal)'    => 'ALL Images/Water Bottle (500 ml - Normal) new.webp',
        'water bottle (1l cold)'         => 'ALL Images/Water Bottle (1 Litre - Cold) new.webp',
        'water bottle (1l normal)'       => 'ALL Images/Water Bottle (1 Litre - Normal) new.webp',
        'mango shrikhand'                => 'ALL Images/Mango Shrikhand (Seasonal).webp',
        'mango shrikhand (seasonal)'     => 'ALL Images/Mango Shrikhand (Seasonal).webp',
        'plain roti'                     => 'ALL Images/Breads_Roti.webp',
        'butter roti'                    => 'ALL Images/Butter roti.webp',
        'spring roll'                    => 'ALL Images/Spring Rolls.webp',
        'spring rolls'                   => 'ALL Images/Spring Rolls.webp',
        'gulab jamun'                    => 'ALL Images/Gulab Jamun (2 pcs).webp',
        'gulab jamun (2 pcs)'            => 'ALL Images/Gulab Jamun (2 pcs).webp',
        'kala jamun'                     => 'ALL Images/Kala Jamun (2 pcs).webp',
        'kala jamun (2 pcs)'             => 'ALL Images/Kala Jamun (2 pcs).webp',
        'rasmalai'                       => 'ALL Images/Rasmalai (2 pcs).webp',
        'rasmalai (2 pcs)'               => 'ALL Images/Rasmalai (2 pcs).webp',
        'masala chaas'                   => 'ALL Images/Masala Chaas (Buttermilk).webp',
        'masala chaas (buttermilk)'      => 'ALL Images/Masala Chaas (Buttermilk).webp',
        'lassi'                          => 'ALL Images/Sweet Lassi.webp',
        'roasted papad'                  => 'ALL Images/Roasted Papad.webp',
        'fried papad'                    => 'ALL Images/Fried Papad.webp',
        'masala papad'                   => 'ALL Images/Masala Papad.webp',
        'cheese masala papad'            => 'ALL Images/Cheese Masala Papad.webp',
        'paneer tikka'                   => 'ALL Images/Paneer Tikka.webp',
        'paneer malai tikka'             => 'ALL Images/Paneer Malai Tikka.webp',
        'hariyali paneer tikka'          => 'ALL Images/Hariyali Paneer Tikka.webp',
        'hara bhara kebab'               => 'ALL Images/Hara Bhara Kebab.webp',
        'veg seekh kebab'                => 'ALL Images/Veg Seekh Kebab.webp',
        'dahi ke kebab'                  => 'ALL Images/Dahi Ke Kebab.webp',
        'tandoori mushroom'              => 'ALL Images/Tandoori Mushroom.webp',
        'stuffed mushroom'               => 'ALL Images/Stuffed Mushroom.webp',
        'crispy corn'                    => 'ALL Images/Crispy Corn.webp',
        'honey chilli potato'            => 'ALL Images/Honey Chilli Potato.webp',
        'chilli paneer dry'              => 'ALL Images/Chilli Paneer Dry.webp',
        'veg manchurian dry'             => 'ALL Images/Veg Manchurian Dry.webp',
        'gobi 65'                        => 'ALL Images/Gobi 65.webp',
        'cheese corn balls'              => 'ALL Images/Cheese Corn Balls.webp',
        'veg crispy'                     => 'ALL Images/Veg Crispy.webp',
        'soya chaap tikka'               => 'ALL Images/Soya Chaap Tikka.webp',
        'malai chaap'                    => 'ALL Images/Malai Chaap.webp',
        'tandoori broccoli'              => 'ALL Images/Tandoori Broccoli.webp',
        'veg tandoori platter'           => 'ALL Images/Veg Tandoori Platter.webp',
    ];

    $categoryDefaults = [
        'starter'     => 'ALL Images/Starter_Paneer_Tikka.webp',
        'main_course' => 'ALL Images/MainCourse_panner_butter_Masala.webp',
        'bread'       => 'ALL Images/Breads_Roti.webp',
        'rice_biryani'=> 'ALL Images/Rice_and_Biryani.webp',
        'beverage'    => 'ALL Images/Beverages_Mango_lassi.webp',
        'dessert'     => 'ALL Images/Dessert_Rasmalai.webp',
        'side_dish'   => 'ALL Images/Side_dishes_Raita.webp',
        'salad'       => 'ALL Images/Side_dishes_Raita.webp',
        'water'       => 'ALL Images/Water Bottle (500 ml - Normal) new.webp',
    ];

    $updateStmt = $pdo->prepare("UPDATE menu_items SET image_path = ? WHERE id = ?");
    $updatedCount = 0;

    echo "Mapping all menu items to their respective images in 'ALL Images/'...\n";
    echo "===================================================================\n";

    foreach ($items as $row) {
        $id = $row['id'];
        $nameEn = trim($row['name_en']);
        $cat = $row['category'];
        $nameLower = strtolower($nameEn);
        $normKey = strtolower(preg_replace('/[^a-z0-9]/i', '', $nameEn));

        $newPath = null;

        // 1. Direct file check
        $exactPath = $imgDir . '/' . $nameEn . '.webp';
        if (file_exists($exactPath)) {
            $newPath = 'ALL Images/' . $nameEn . '.webp';
        }

        // 2. Custom override check
        if (!$newPath && isset($customOverrides[$nameLower])) {
            $targetOverride = __DIR__ . '/../' . $customOverrides[$nameLower];
            if (file_exists($targetOverride)) {
                $newPath = $customOverrides[$nameLower];
            }
        }

        // 3. Normalized key check
        if (!$newPath && isset($imgMap[$normKey])) {
            $newPath = $imgMap[$normKey];
        }

        // 4. Fallback if still not found
        if (!$newPath) {
            $newPath = $categoryDefaults[$cat] ?? 'ALL Images/Starter_Paneer_Tikka.webp';
        }

        $updateStmt->execute([$newPath, $id]);
        $updatedCount++;
        echo sprintf("[%s] %-35s -> %s\n", $row['item_code'], $nameEn, $newPath);
    }

    echo "===================================================================\n";
    echo "Successfully mapped {$updatedCount} menu items to their respective images!\n";

} catch (Exception $e) {
    echo "Error syncing images: " . $e->getMessage() . "\n";
}
