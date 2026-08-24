<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * create-order.php — Golden Stone Hotel
 * POST: Receives order from customer mobile and stores in MySQL.
 * ═══════════════════════════════════════════════════════════════
 *
 * Called by customer menu pages on "Confirm Order".
 *
 * Request body (JSON):
 * {
 *   "table": "5",
 *   "persons": 3,
 *   "customer_notes": "Less Spicy, No Onion",
 *   "items": [
 *     { "id": 3, "name": "Paneer Tikka", "category": "starter", "price": 220, "qty": 2 }
 *   ]
 * }
 *
 * Response:
 * { "success": true, "order_id": 101, "order_ref": "ORD-260603-T5-001", "message": "Order created successfully" }
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

// ─── Only POST allowed ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed. Use POST.'], 405);
}

// ─── Parse and validate request body ───
$body = getJsonBody();

// Validate required fields
if (!isset($body['table']) || trim((string)$body['table']) === '') {
    jsonResponse(['success' => false, 'error' => 'Table number is required.'], 400);
}

if (!isset($body['items']) || !is_array($body['items']) || count($body['items']) === 0) {
    jsonResponse(['success' => false, 'error' => 'At least one item is required.'], 400);
}

// ─── Sanitize inputs ───
$tableNo      = trim((string)$body['table']);
$persons      = max(1, intval($body['persons'] ?? 1));
$customerNotes = trim((string)($body['customer_notes'] ?? ''));
$items        = $body['items'];

// Valid category values (must match database ENUM)
$validCategories = [
    'starter', 'main_course', 'bread', 'rice_biryani',
    'beverage', 'dessert', 'salad', 'side_dish', 'water'
];

// ─── Validate each item ───
foreach ($items as $index => $item) {
    if (!isset($item['id'], $item['name'], $item['price'], $item['qty'])) {
        jsonResponse([
            'success' => false,
            'error'   => "Item at index $index is missing required fields (id, name, price, qty).",
        ], 400);
    }
    if (intval($item['qty']) < 1) {
        jsonResponse([
            'success' => false,
            'error'   => "Item '{$item['name']}' has invalid quantity.",
        ], 400);
    }
}

// ─── Distance Calculation Helper ───
function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371000;
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

$pdo = getDB();

// ─── Security Check: Geofencing & Anti-Leak Protection ───
try {
    $secStmt = $pdo->query("SELECT * FROM hotel_settings WHERE id = 1 LIMIT 1");
    $secConfig = $secStmt ? $secStmt->fetch() : null;

    if ($secConfig && (int)$secConfig['geofence_enabled'] === 1) {
        $isAllowed = false;
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

        // 1. Check Wi-Fi / Local Network Bypass
        if ((int)$secConfig['wifi_bypass_enabled'] === 1) {
            if (
                $clientIp === '127.0.0.1' || 
                $clientIp === '::1' || 
                str_starts_with($clientIp, '192.168.') || 
                str_starts_with($clientIp, '10.') || 
                preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $clientIp)
            ) {
                $isAllowed = true;
            }
        }

        // 2. Check Security Token or Passcode
        if (!$isAllowed) {
            $expectedToken = hash('sha256', (string)$secConfig['daily_passcode'] . date('Y-m-d') . 'hotel_tulsi_secret');
            $suppliedToken = trim((string)($body['security_token'] ?? ''));
            $suppliedPass  = trim((string)($body['passcode'] ?? ''));

            if (($suppliedToken !== '' && $suppliedToken === $expectedToken) || 
                ($suppliedPass !== '' && strcasecmp($suppliedPass, (string)$secConfig['daily_passcode']) === 0)) {
                $isAllowed = true;
            }
        }

        // 3. Check GPS Coordinates
        if (!$isAllowed && isset($body['client_lat'], $body['client_lng'])) {
            $cLat = (float)$body['client_lat'];
            $cLng = (float)$body['client_lng'];
            $hLat = (float)$secConfig['hotel_lat'];
            $hLng = (float)$secConfig['hotel_lng'];
            $maxDist = (float)$secConfig['radius_meters'] + 50.0; // 50m tolerance for GPS drift

            if ($cLat != 0.0 && $cLng != 0.0) {
                $dist = calculateDistanceMeters($cLat, $cLng, $hLat, $hLng);
                if ($dist <= $maxDist) {
                    $isAllowed = true;
                }
            }
        }

        if (!$isAllowed) {
            jsonResponse([
                'success' => false,
                'security_challenge' => true,
                'error'   => 'Security check failed: You must be present at Hotel Tulsi premises or enter the table code to place an order.'
            ], 403);
        }
    }
} catch (\Exception $e) {
    // If settings table missing, continue gracefully
}

try {
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    $now   = date('Y-m-d H:i:s');
    $today = date('Y-m-d');

    // ─── Generate unique batch ID for this submission ───
    $batchId = 'B' . time() . '_' . bin2hex(random_bytes(4));

    // ─── Check for existing active unbilled order on this table ───
    $checkStmt = $pdo->prepare("
        SELECT id, order_ref, subtotal, gst_amount, total_amount, customer_notes
        FROM orders
        WHERE table_no = :table_no
          AND payment_method IS NULL
          AND status != 'served'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $checkStmt->execute([
        'table_no' => $tableNo,
    ]);
    $existingOrder = $checkStmt->fetch();

    // Ensure occupied_tables entry exists
    $occStmt = $pdo->prepare("
        INSERT INTO occupied_tables (table_no, persons, occupied_at)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE persons = ?, occupied_at = COALESCE(occupied_at, ?)
    ");
    $occStmt->execute([(int)$tableNo, $persons, $now, $persons, $now]);

    // ─── Calculate batch totals ───
    $batchSubtotal = 0.00;
    foreach ($items as $item) {
        $batchSubtotal += floatval($item['price']) * intval($item['qty']);
    }
    $gstPercent = 5.00;
    $batchGst   = round($batchSubtotal * ($gstPercent / 100), 2);
    $batchTotal = round($batchSubtotal + $batchGst, 2);

    if ($existingOrder) {
        // ════════════════════════════════════════════
        // APPEND to existing active order (same table)
        // ════════════════════════════════════════════
        $orderId  = (int)$existingOrder['id'];
        $orderRef = $existingOrder['order_ref'];

        $newSubtotal = round(floatval($existingOrder['subtotal']) + $batchSubtotal, 2);
        $newGst      = round(floatval($existingOrder['gst_amount']) + $batchGst, 2);
        $newTotal    = round(floatval($existingOrder['total_amount']) + $batchTotal, 2);

        // Merge customer notes if new note provided
        $mergedNotes = $existingOrder['customer_notes'] ?? '';
        if ($customerNotes !== '' && $customerNotes !== $mergedNotes) {
            $mergedNotes = $mergedNotes !== '' && $mergedNotes !== null
                ? $mergedNotes . '; ' . $customerNotes
                : $customerNotes;
        }

        $updateStmt = $pdo->prepare("
            UPDATE orders
            SET subtotal       = :subtotal,
                gst_amount     = :gst_amount,
                total_amount   = :total_amount,
                customer_notes = :notes,
                updated_at     = :now
            WHERE id = :id
        ");
        $updateStmt->execute([
            'subtotal'   => $newSubtotal,
            'gst_amount' => $newGst,
            'total_amount' => $newTotal,
            'notes'      => $mergedNotes,
            'now'        => $now,
            'id'         => $orderId,
        ]);

        $isNewOrder = false;
        $message    = 'Items added to existing order';

    } else {
        // ════════════════════════════════════════════
        // CREATE new order
        // ════════════════════════════════════════════

        // Generate order reference: ORD-YYMMDD-T{table}-{seq}
        $dateCode = date('ymd');
        $seqStmt  = $pdo->prepare("
            SELECT COUNT(*) + 1 AS seq
            FROM orders
            WHERE DATE(created_at) = :today
        ");
        $seqStmt->execute(['today' => $today]);
        $seq      = (int)$seqStmt->fetchColumn();
        $orderRef = sprintf('ORD-%s-T%s-%03d', $dateCode, $tableNo, $seq);

        $insertStmt = $pdo->prepare("
            INSERT INTO orders (
                order_ref, table_no, persons, status,
                customer_notes, subtotal, gst_percent, gst_amount, total_amount,
                created_at, updated_at
            ) VALUES (
                :ref, :table_no, :persons, 'new',
                :notes, :subtotal, :gst_pct, :gst_amt, :total,
                :created_at, :updated_at
            )
        ");
        $insertStmt->execute([
            'ref'        => $orderRef,
            'table_no'   => $tableNo,
            'persons'    => $persons,
            'notes'      => $customerNotes !== '' ? $customerNotes : null,
            'subtotal'   => $batchSubtotal,
            'gst_pct'    => $gstPercent,
            'gst_amt'    => $batchGst,
            'total'      => $batchTotal,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = (int)$pdo->lastInsertId();

        // ─── Log initial status in history ───
        $logStmt = $pdo->prepare("
            INSERT INTO order_status_history (order_id, status, note, changed_at)
            VALUES (:order_id, 'new', 'Order received', :now)
        ");
        $logStmt->execute([
            'order_id' => $orderId,
            'now'      => $now,
        ]);

        $isNewOrder = true;
        $message    = 'Order created successfully';
    }

    // ─── Insert order items for this batch ───
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id, batch_id, menu_item_id, item_name, category,
            unit_price, qty, is_new, added_at
        ) VALUES (
            :order_id, :batch_id, :menu_id, :name, :category,
            :price, :qty, 1, :now
        )
    ");

    $itemsInserted = 0;
    foreach ($items as $item) {
        $category = $item['category'] ?? 'starter';

        // Map short category names from frontend to database ENUM values
        $categoryMap = [
            'main'  => 'main_course',
            'rice'  => 'rice_biryani',
            'side'  => 'side_dish',
        ];
        if (isset($categoryMap[$category])) {
            $category = $categoryMap[$category];
        }

        // Category mapping completed

        $itemStmt->execute([
            'order_id' => $orderId,
            'batch_id' => $batchId,
            'menu_id'  => intval($item['id']),
            'name'     => trim((string)$item['name']),
            'category' => $category,
            'price'    => floatval($item['price']),
            'qty'      => max(1, intval($item['qty'])),
            'now'      => $now,
        ]);
        $itemsInserted++;
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    $pdo->commit();

    // ─── Success response ───
    jsonResponse([
        'success'       => true,
        'order_id'      => $orderId,
        'order_ref'     => $orderRef,
        'batch_id'      => $batchId,
        'is_new_order'  => $isNewOrder,
        'items_added'   => $itemsInserted,
        'message'       => $message,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse([
        'success' => false,
        'error'   => 'Failed to create order.',
        'detail'  => $e->getMessage(),
    ], 500);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse([
        'success' => false,
        'error'   => 'Unexpected error.',
        'detail'  => $e->getMessage(),
    ], 500);
}
