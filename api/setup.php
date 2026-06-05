<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * setup.php — Golden Stone Hotel
 * One-Time Database Initialization (PHP 8.0+)
 * ═══════════════════════════════════════════════════════════════
 *
 * Visit: http://localhost/hotel/api/setup.php
 *
 * This script will:
 *   1. Create the golden_stone_hotel database
 *   2. Create all 4 required tables (if not already present)
 *   3. Create all indexes and foreign keys
 *   4. Insert sample menu items for testing
 *
 * ⚠ DELETE OR RENAME THIS FILE AFTER SETUP IS COMPLETE.
 */

declare(strict_types=1);

// ─── Database Credentials (must match db.php) ───
define('SETUP_DB_HOST',    'localhost');
define('SETUP_DB_PORT',    3306);
define('SETUP_DB_USER',    'root');
define('SETUP_DB_PASS',    '');
define('SETUP_DB_CHARSET', 'utf8mb4');
define('SETUP_DB_NAME',    'golden_stone_hotel');

header('Content-Type: text/html; charset=utf-8');

// ─── HTML Output ───
echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Golden Stone Hotel — Database Setup</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:"Segoe UI",Inter,sans-serif;background:#0a0c10;color:#e8e8e8;padding:2rem;line-height:1.6}
  h1{color:#d4a832;font-size:1.6rem;margin-bottom:.5rem}
  h2{color:#9aa0b0;font-size:.9rem;font-weight:400;margin-bottom:2rem;letter-spacing:.04em}
  .step{margin:.4rem 0;padding:.5rem .8rem;border-radius:8px;font-size:.9rem}
  .ok{background:rgba(34,197,94,.1);border-left:3px solid #22c55e;color:#86efac}
  .err{background:rgba(239,68,68,.1);border-left:3px solid #ef4444;color:#fca5a5}
  .skip{background:rgba(234,179,8,.08);border-left:3px solid #eab308;color:#fde047}
  .info{background:rgba(59,130,246,.08);border-left:3px solid #3b82f6;color:#93c5fd}
  hr{border:none;border-top:1px solid #2a2f3a;margin:1.5rem 0}
  .footer{margin-top:2rem;padding:1rem;background:#141820;border-radius:12px;border:1px solid #2a2f3a}
  .footer p{font-size:.85rem;color:#9aa0b0;margin:.3rem 0}
  code{background:#1a1e28;padding:.15rem .5rem;border-radius:4px;font-size:.85rem;color:#d4a832}
  .warn{color:#f97316;font-weight:700}
</style></head><body>';

echo '<h1>🍽 Golden Stone Hotel — Database Setup</h1>';
echo '<h2>One-time initialization script</h2>';

$steps   = 0;
$success = 0;
$errors  = 0;

function logOk(string $msg): void {
    global $steps, $success;
    $steps++; $success++;
    echo "<div class='step ok'>✅ $msg</div>";
}

function logErr(string $msg): void {
    global $steps, $errors;
    $steps++; $errors++;
    echo "<div class='step err'>❌ $msg</div>";
}

function logSkip(string $msg): void {
    global $steps;
    $steps++;
    echo "<div class='step skip'>⏩ $msg</div>";
}

function logInfo(string $msg): void {
    echo "<div class='step info'>ℹ️ $msg</div>";
}

// ═══════════════════════════════════════════════════════
// STEP 1: Connect to MySQL (without database selected)
// ═══════════════════════════════════════════════════════
try {
    $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', SETUP_DB_HOST, SETUP_DB_PORT, SETUP_DB_CHARSET);
    $pdo = new PDO($dsn, SETUP_DB_USER, SETUP_DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    logOk('Connected to MySQL server');
} catch (PDOException $e) {
    logErr('Failed to connect to MySQL: ' . htmlspecialchars($e->getMessage()));
    echo '<hr><p>Make sure XAMPP is running (Apache + MySQL). Check credentials at the top of this file.</p>';
    echo '</body></html>';
    exit;
}

// ═══════════════════════════════════════════════════════
// STEP 2: Create Database
// ═══════════════════════════════════════════════════════
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . SETUP_DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . SETUP_DB_NAME . "`");
    logOk('Database <code>' . SETUP_DB_NAME . '</code> ready');
} catch (PDOException $e) {
    logErr('Database creation failed: ' . htmlspecialchars($e->getMessage()));
    echo '</body></html>';
    exit;
}

// ═══════════════════════════════════════════════════════
// STEP 3: Create Tables
// ═══════════════════════════════════════════════════════
$tables = [

    // ── menu_items ──
    'menu_items' => "CREATE TABLE IF NOT EXISTS `menu_items` (
        `id`            INT UNSIGNED        PRIMARY KEY AUTO_INCREMENT,
        `item_code`     VARCHAR(20)         NOT NULL UNIQUE,
        `name_en`       VARCHAR(150)        NOT NULL,
        `name_hi`       VARCHAR(150)        DEFAULT NULL,
        `name_mr`       VARCHAR(150)        DEFAULT NULL,
        `category`      ENUM('starter','main_course','bread','rice_biryani','beverage','dessert','salad','side_dish','water') NOT NULL,
        `price`         DECIMAL(8,2)        NOT NULL DEFAULT 0.00,
        `image_path`    VARCHAR(255)        DEFAULT NULL,
        `is_available`  TINYINT(1)          NOT NULL DEFAULT 1,
        `is_veg`        TINYINT(1)          NOT NULL DEFAULT 1,
        `prep_time_min` SMALLINT UNSIGNED   NOT NULL DEFAULT 5,
        `sort_order`    SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
        `created_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_category`  (`category`),
        INDEX `idx_available` (`is_available`),
        INDEX `idx_sort`      (`category`, `sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ── orders ──
    'orders' => "CREATE TABLE IF NOT EXISTS `orders` (
        `id`              INT UNSIGNED      PRIMARY KEY AUTO_INCREMENT,
        `order_ref`       VARCHAR(30)       NOT NULL UNIQUE,
        `table_no`        VARCHAR(10)       NOT NULL,
        `persons`         TINYINT UNSIGNED  NOT NULL DEFAULT 1,
        `status`          ENUM('new','preparing','ready','served') NOT NULL DEFAULT 'new',
        `customer_notes`  TEXT              DEFAULT NULL,
        `subtotal`        DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
        `gst_percent`     DECIMAL(4,2)      NOT NULL DEFAULT 5.00,
        `gst_amount`      DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
        `total_amount`    DECIMAL(10,2)     NOT NULL DEFAULT 0.00,
        `prep_started_at` DATETIME          DEFAULT NULL,
        `ready_at`        DATETIME          DEFAULT NULL,
        `served_at`       DATETIME          DEFAULT NULL,
        `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status`     (`status`),
        INDEX `idx_table_no`   (`table_no`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ── order_items ──
    'order_items' => "CREATE TABLE IF NOT EXISTS `order_items` (
        `id`            INT UNSIGNED        PRIMARY KEY AUTO_INCREMENT,
        `order_id`      INT UNSIGNED        NOT NULL,
        `batch_id`      VARCHAR(50)         NOT NULL,
        `menu_item_id`  INT UNSIGNED        NOT NULL,
        `item_name`     VARCHAR(150)        NOT NULL,
        `category`      ENUM('starter','main_course','bread','rice_biryani','beverage','dessert','salad','side_dish','water') NOT NULL,
        `unit_price`    DECIMAL(8,2)        NOT NULL,
        `qty`           SMALLINT UNSIGNED   NOT NULL DEFAULT 1,
        `line_total`    DECIMAL(10,2)       GENERATED ALWAYS AS (`unit_price` * `qty`) STORED,
        `is_new`        TINYINT(1)          NOT NULL DEFAULT 1,
        `added_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`)     REFERENCES `orders`(`id`)     ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `fk_oi_menu`  FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
        INDEX `idx_order_id`  (`order_id`),
        INDEX `idx_batch_id`  (`batch_id`),
        INDEX `idx_menu_item` (`menu_item_id`),
        INDEX `idx_is_new`    (`is_new`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // ── order_status_history ──
    'order_status_history' => "CREATE TABLE IF NOT EXISTS `order_status_history` (
        `id`          INT UNSIGNED      PRIMARY KEY AUTO_INCREMENT,
        `order_id`    INT UNSIGNED      NOT NULL,
        `status`      ENUM('new','preparing','ready','served') NOT NULL,
        `note`        VARCHAR(255)      DEFAULT NULL,
        `changed_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX `idx_order_id`   (`order_id`),
        INDEX `idx_changed_at` (`changed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($tables as $name => $sql) {
    try {
        // Check if table exists
        $exists = $pdo->query("SHOW TABLES LIKE '$name'")->rowCount() > 0;
        if ($exists) {
            logSkip("Table <code>$name</code> already exists — skipped");
        } else {
            $pdo->exec($sql);
            logOk("Created table <code>$name</code>");
        }
    } catch (PDOException $e) {
        logErr("Table <code>$name</code> failed: " . htmlspecialchars($e->getMessage()));
    }
}

// ═══════════════════════════════════════════════════════
// STEP 4: Insert Sample Menu Items (only if table is empty)
// ═══════════════════════════════════════════════════════
echo '<hr>';
echo '<div class="step info">📋 Checking sample menu data...</div>';

$menuCount = (int) $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();

if ($menuCount > 0) {
    logSkip("Menu already has $menuCount items — sample data not inserted");
} else {
    $sampleItems = [
        // ── Starters ──
        ['STR-001', 'Roasted Papad',       'भुना पापड़',           'भाजलेला पापड',          'starter',      30,  'Starters/Roasted Papad.png',     5, 1],
        ['STR-002', 'Masala Papad',         'मसाला पापड़',          'मसाला पापड',           'starter',      50,  'Starters/Masala Papad.png',      5, 2],
        ['STR-003', 'Paneer Tikka',         'पनीर टिक्का',          'पनीर टिक्का',          'starter',      220, 'Starters/Paneer Tikka.png',      8, 3],
        ['STR-004', 'Veg Manchurian Dry',   'वेज मंचूरियन ड्राई',     'व्हेज मंचुरियन ड्राय',   'starter',      180, 'Starters/Veg Manchurian Dry.png',7, 4],
        ['STR-005', 'Spring Roll',          'स्प्रिंग रोल',          'स्प्रिंग रोल',          'starter',      160, 'Starters/Spring Roll.png',       6, 5],

        // ── Main Course ──
        ['MNC-001', 'Paneer Butter Masala', 'पनीर बटर मसाला',       'पनीर बटर मसाला',       'main_course',  260, 'Main Cource/Paneer Butter Masala.png', 12, 1],
        ['MNC-002', 'Dal Tadka',            'दाल तड़का',            'डाळ तडका',            'main_course',  180, 'Main Cource/Dal Tadka.png',            10, 2],
        ['MNC-003', 'Malai Kofta',          'मलाई कोफ़्ता',          'मलाई कोफ्ता',          'main_course',  250, 'Main Cource/Malai Kofta.png',          12, 3],
        ['MNC-004', 'Shahi Paneer',         'शाही पनीर',            'शाही पनीर',            'main_course',  260, 'Main Cource/Shahi Paneer.png',         12, 4],
        ['MNC-005', 'Veg Kolhapuri',        'वेज कोल्हापुरी',        'व्हेज कोल्हापुरी',       'main_course',  220, 'Main Cource/Veg Kolhapuri.png',        10, 5],

        // ── Breads ──
        ['BRD-001', 'Plain Roti',           'प्लेन रोटी',            'प्लेन रोटी',           'bread',        30,  'Breads/Plain Roti.png',          3, 1],
        ['BRD-002', 'Butter Roti',          'बटर रोटी',            'बटर रोटी',            'bread',        40,  'Breads/Butter Roti.png',         3, 2],
        ['BRD-003', 'Butter Naan',          'बटर नान',             'बटर नान',             'bread',        60,  'Breads/Butter Naan.png',         5, 3],
        ['BRD-004', 'Garlic Naan',          'गार्लिक नान',           'गार्लिक नान',           'bread',        70,  'Breads/Garlic Naan.png',         5, 4],
        ['BRD-005', 'Laccha Paratha',       'लच्छा पराठा',          'लच्छा पराठा',          'bread',        60,  'Breads/Laccha Paratha.png',      5, 5],

        // ── Rice & Biryani ──
        ['RCE-001', 'Steamed Rice',         'स्टीम्ड राइस',          'वाफवलेला भात',          'rice_biryani', 100, 'Rice & Biryanies/Steamed Rice.png',  6, 1],
        ['RCE-002', 'Jeera Rice',           'जीरा राइस',            'जिरा राइस',            'rice_biryani', 130, 'Rice & Biryanies/Jeera Rice.png',    6, 2],
        ['RCE-003', 'Veg Biryani',          'वेज बिरयानी',           'व्हेज बिर्याणी',         'rice_biryani', 200, 'Rice & Biryanies/Veg Biryani.png',   10, 3],
        ['RCE-004', 'Veg Pulao',            'वेज पुलाव',            'व्हेज पुलाव',           'rice_biryani', 180, 'Rice & Biryanies/Veg Pulao.png',     8, 4],

        // ── Beverages ──
        ['BEV-001', 'Fresh Lime Soda',      'फ्रेश लाइम सोडा',       'फ्रेश लाईम सोडा',       'beverage',     70,  'Beverages/Fresh Lime Soda.png',  2, 1],
        ['BEV-002', 'Mango Lassi',          'मैंगो लस्सी',           'मँगो लस्सी',           'beverage',     99,  'Beverages/Mango Lassi.png',      3, 2],
        ['BEV-003', 'Sweet Lassi',          'मीठी लस्सी',           'गोड लस्सी',            'beverage',     79,  'Beverages/Sweet Lassi.png',      2, 3],
        ['BEV-004', 'Masala Chaas',         'मसाला छाछ',            'मसाला ताक',            'beverage',     59,  'Beverages/Masala Chaas.png',     2, 4],

        // ── Desserts ──
        ['DST-001', 'Gulab Jamun (2 pcs)',  'गुलाब जामुन (2 नग)',    'गुलाब जामुन (२ नग)',    'dessert',      79,  'Desserts/Gulab Jamun (2 pcs).png', 3, 1],
        ['DST-002', 'Rasmalai (2 pcs)',     'रसमलाई (2 नग)',        'रसमलाई (२ नग)',         'dessert',      99,  'Desserts/Rasmalai (2 pcs).png',    3, 2],
        ['DST-003', 'Gajar Halwa',          'गाजर हलवा',            'गाजर हलवा',            'dessert',      129, 'Desserts/Gajar Halwa.png',         4, 3],
        ['DST-004', 'Vanilla Ice Cream',    'वैनिला आइसक्रीम',       'व्हॅनिला आईस्क्रीम',      'dessert',      79,  'Desserts/Vanilla Ice Cream.png',   2, 4],

        // ── Side Dishes (includes Salad items) ──
        ['SID-001', 'Green Salad',          'ग्रीन सलाद',            'ग्रीन सॅलड',            'side_dish',    39,  'Side Dishes/Green Salad.png',    2, 1],
        ['SID-002', 'Onion Salad',          'प्याज सलाद',            'कांदा कोशिंबीर',         'side_dish',    45,  'Side Dishes/Onion Salad.png',    2, 2],
        ['SID-003', 'Veg Raita',            'वेज रायता',             'व्हेज रायता',           'side_dish',    59,  'Side Dishes/Veg Raita.png',      2, 3],
        ['SID-004', 'Boondi Raita',         'बूंदी रायता',            'बुंदी रायता',            'side_dish',    65,  'Side Dishes/Boondi Raita.png',   2, 4],

        // ── Water ──
        ['WTR-001', 'Water Bottle (500ml Cold)',   'पानी की बोतल (500 मिली ठंडी)',   'पाण्याची बाटली (500 मिली थंड)',   'water', 10, 'Water Bottle/Water Bottle (500 ml - Cold).png',   1, 1],
        ['WTR-002', 'Water Bottle (500ml Normal)', 'पानी की बोतल (500 मिली नॉर्मल)', 'पाण्याची बाटली (500 मिली नॉर्मल)', 'water', 10, 'Water Bottle/Water Bottle (500 ml - Normal).png', 1, 2],
        ['WTR-003', 'Water Bottle (1L Cold)',      'पानी की बोतल (1 लीटर ठंडी)',    'पाण्याची बाटली (1 लीटर थंड)',    'water', 20, 'Water Bottle/Water Bottle (1 Litre - Cold).png',  1, 3],
        ['WTR-004', 'Water Bottle (1L Normal)',    'पानी की बोतल (1 लीटर नॉर्मल)',   'पाण्याची बाटली (1 लीटर नॉर्मल)',  'water', 20, 'Water Bottle/Water Bottle (1 Litre - Normal).png',1, 4],
    ];

    $insertSQL = "INSERT INTO menu_items (item_code, name_en, name_hi, name_mr, category, price, image_path, prep_time_min, sort_order)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertSQL);

    $inserted = 0;
    foreach ($sampleItems as $item) {
        try {
            $stmt->execute($item);
            $inserted++;
        } catch (PDOException $e) {
            logErr("Failed to insert <code>{$item[1]}</code>: " . htmlspecialchars($e->getMessage()));
        }
    }

    logOk("Inserted <strong>$inserted</strong> sample menu items across all categories");
}

// ═══════════════════════════════════════════════════════
// STEP 5: Verify Tables
// ═══════════════════════════════════════════════════════
echo '<hr>';
logInfo('Verifying database structure...');

$expectedTables = ['menu_items', 'orders', 'order_items', 'order_status_history'];
foreach ($expectedTables as $tbl) {
    $count = $pdo->query("SHOW TABLES LIKE '$tbl'")->rowCount();
    if ($count > 0) {
        $rows = (int) $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
        logOk("Table <code>$tbl</code> verified — $rows rows");
    } else {
        logErr("Table <code>$tbl</code> NOT FOUND");
    }
}

// ═══════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════
echo '<hr>';
echo '<div class="footer">';

if ($errors === 0) {
    echo '<p style="color:#22c55e;font-size:1.2rem;font-weight:700">✅ Setup Complete — All systems ready!</p>';
    echo '<p><strong>Next steps:</strong></p>';
    echo '<p>1. Open <code>http://localhost/hotel/kitchen.html</code> for the Kitchen Dashboard</p>';
    echo '<p>2. Open <code>http://localhost/hotel/index.html</code> on a customer phone</p>';
    echo '<p>3. Place a test order and verify it appears on the kitchen dashboard</p>';
    echo '<p class="warn">⚠ Security: Delete or rename this file after setup is complete.</p>';
} else {
    echo "<p style='color:#ef4444;font-size:1.1rem;font-weight:700'>⚠ Setup completed with $errors error(s). Review the messages above.</p>";
}

echo '<hr style="margin:.8rem 0">';
echo '<p style="font-size:.75rem;color:#5a6070">Setup ran ' . $steps . ' steps • ' . $success . ' OK • ' . $errors . ' errors • PHP ' . PHP_VERSION . ' • MySQL ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . '</p>';
echo '</div>';
echo '</body></html>';
