<?php
/**
 * ============================================================================
 * E&N SCHOOL SUPPLIES — INTERACTIVE DATABASE TEST SETUP SCRIPT
 * ============================================================================
 *
 * ⚠️  FOR TESTING PURPOSES ONLY
 *
 * This script provides a fully editable interface to configure:
 *   • User accounts per role (with custom credentials)
 *   • Item categories
 *   • Inventory items (name, category, stock, price, max order qty)
 *   • Order generation toggles per status
 *   • System settings
 *
 * Then DROP + recreate the database with your configured data.
 * ============================================================================
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/aes.php';

// ── Feedback collector ───────────────────────────────────────────────────────
$messages = [];
$hasError = false;
$executed = false;

if (!empty($_SESSION['setup_flash']) && is_array($_SESSION['setup_flash'])) {
    $flash = $_SESSION['setup_flash'];
    $messages = $flash['messages'] ?? [];
    $hasError = !empty($flash['hasError']);
    $executed = !empty($flash['executed']);
    unset($_SESSION['setup_flash']);
}

function msg($text, $type = 'info')
{
    global $messages;
    $messages[] = ['text' => $text, 'type' => $type];
}

// ── Default Configuration (editable via form) ───────────────────────────────

$defaultUsers = [
    'admin' => [
        ['username' => 'admin', 'email' => 'admin@en.com', 'password' => 'admin', 'full_name' => 'System Administrator', 'phone' => '09170000001', 'status' => 'active'],
    ],
    'staff' => [],
    'customer' => [],
];

$defaultCategories = ['Notebooks', 'Pens & Pencils'];

$defaultInventory = [
    ['item_name' => 'Spiral Notebook',       'category' => 'Notebooks',      'stock_count' => 50,  'price' => 45.00, 'max_order_qty' => 10],
    ['item_name' => 'Ballpen (Blue)',         'category' => 'Pens & Pencils', 'stock_count' => 200, 'price' => 12.00, 'max_order_qty' => 20],
];

$defaultItemNames = ['Spiral Notebook', 'Composition Notebook', 'Ballpen', 'Pencil', 'Eraser', 'Ruler',
                     'Backpack', 'Crayons', 'Watercolor Set', 'Bond Paper', 'Folder', 'Glue Stick'];

$defaultOrderStatuses = [
    'pending'   => ['enabled' => true,  'label' => 'Pending'],
    'ready'     => ['enabled' => true,  'label' => 'Ready'],
    'claimed'   => ['enabled' => true,  'label' => 'Claimed'],
    'cancelled' => ['enabled' => true,  'label' => 'Cancelled'],
];

$defaultSettings = [
    'store_name'              => config('system.store_name') ?: 'E&N School Supplies',
    'store_phone'             => config('system.store_phone') ?: '',
    'store_email'             => config('system.store_email') ?: '',
    'logo_path'               => config('system.logo_path') ?: 'assets/images/logo.png',
    'timezone'                => config('system.timezone') ?: 'Asia/Manila',
    'navbar_country_flag'     => 'PH',
    'auto_logout_hours'       => (string)(config('system.auto_logout_hours') ?: 8),
    'low_stock_percent'       => (string)(config('system.low_stock_percent') ?: 10),
    'kiosk_idle_seconds'      => (string)(config('system.kiosk_idle_seconds') ?: 90),
    'force_dark'              => '0',
    'system_status'           => 'online',
    'disable_no_login_orders' => '0',
    'online_payment'          => '0',
];

// ── Process POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_db'])) {
    $executed = true;

    try {
        // ── Parse submitted users ────────────────────────────────────────────
        $submittedUsers = [];
        if (isset($_POST['users']) && is_array($_POST['users'])) {
            foreach ($_POST['users'] as $role => $accounts) {
                if (!is_array($accounts)) continue;
                foreach ($accounts as $acc) {
                    if (empty($acc['email']) || empty($acc['full_name'])) continue;
                    $username = trim($acc['username'] ?? '');
                    if ($username === '') {
                        $local = strstr(trim($acc['email']), '@', true);
                        $username = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $local !== false ? $local : trim($acc['email'])));
                    }
                    $submittedUsers[] = [
                        'username'  => $username,
                        'email'     => trim($acc['email']),
                        'password'  => trim($acc['password'] ?: 'password123'),
                        'full_name' => trim($acc['full_name']),
                        'phone'     => trim($acc['phone'] ?: ''),
                        'role'      => $role,
                        'status'    => $acc['status'] ?? 'active',
                    ];
                }
            }
        }

        // ── Parse submitted categories ───────────────────────────────────────
        $submittedCategories = [];
        if (isset($_POST['categories']) && is_array($_POST['categories'])) {
            foreach ($_POST['categories'] as $cat) {
                $name = trim($cat['name'] ?? '');
                if ($name !== '') $submittedCategories[] = $name;
            }
        }

        // ── Parse submitted inventory ────────────────────────────────────────
        $submittedInventory = [];
        if (isset($_POST['inventory']) && is_array($_POST['inventory'])) {
            foreach ($_POST['inventory'] as $item) {
                if (empty($item['item_name'])) continue;
                $submittedInventory[] = [
                    'item_name'     => trim($item['item_name']),
                    'category'      => trim($item['category'] ?? ''),
                    'stock_count'   => max(0, (int)($item['stock_count'] ?? 0)),
                    'price'         => max(0, (float)($item['price'] ?? 0)),
                    'max_order_qty' => max(1, (int)($item['max_order_qty'] ?? 10)),
                ];
            }
        }

        // ── Parse order status toggles ───────────────────────────────────────
        $enabledStatuses = [];
        if (isset($_POST['order_status']) && is_array($_POST['order_status'])) {
            $enabledStatuses = array_keys($_POST['order_status']);
        }

        // ── Parse settings ───────────────────────────────────────────────────
        $submittedSettings = [];
        if (isset($_POST['settings']) && is_array($_POST['settings'])) {
            foreach ($_POST['settings'] as $key => $val) {
                $submittedSettings[$key] = trim($val);
            }
        }

        $db_name = config('database.name');

        // ──────────────────────────────────────────────────────────────────────
        // 1. DROP & CREATE DATABASE
        // ──────────────────────────────────────────────────────────────────────
        $pdo->exec("DROP DATABASE IF EXISTS `{$db_name}`");
        msg("Dropped existing database '{$db_name}'.", 'warning');

        $pdo->exec("CREATE DATABASE `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db_name}`");
        msg("Created fresh database '{$db_name}'.", 'success');

        // ──────────────────────────────────────────────────────────────────────
        // 2. CREATE ALL TABLES (from database.sql, stripping CREATE DB / USE)
        // ──────────────────────────────────────────────────────────────────────
        $sql = file_get_contents(APP_ROOT . '/database.sql');
        if ($sql === false) {
            throw new RuntimeException('Unable to read database.sql.');
        }
        $sql = preg_replace('/^\s*CREATE\s+DATABASE.*?;\s*/ims', '', $sql);
        $sql = preg_replace('/^\s*USE\s+`[^`]+`\s*;\s*/ims', '', $sql);

        preg_match_all(
            '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`[^`]+`\s*\(.*?\)\s*ENGINE=InnoDB\s+DEFAULT\s+CHARSET=utf8mb4(?:\s+COLLATE\s+[^;]+)?\s*;/is',
            $sql,
            $matches
        );

        if (empty($matches[0])) {
            throw new RuntimeException('No table definitions were found in database.sql.');
        }

        $tableCount = 0;
        foreach ($matches[0] as $statement) {
            $pdo->exec($statement);
            $tableCount++;
        }
        msg("All {$tableCount} tables created successfully.", 'success');

        // ──────────────────────────────────────────────────────────────────────
        // 3. SEED SYSTEM SETTINGS
        // ──────────────────────────────────────────────────────────────────────
        $settingsToUse = !empty($submittedSettings) ? $submittedSettings : $defaultSettings;
        $settingsStmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settingsToUse as $k => $v) {
            $settingsStmt->execute([$k, $v]);
        }
        msg("Seeded " . count($settingsToUse) . " system settings.", 'success');

        // ──────────────────────────────────────────────────────────────────────
        // 4. SEED CATEGORIES
        // ──────────────────────────────────────────────────────────────────────
        $categoriesToUse = !empty($submittedCategories) ? $submittedCategories : $defaultCategories;
        $catStmt = $pdo->prepare("INSERT INTO item_categories (category_name) VALUES (?)");
        $categoryMap = []; // name => id
        foreach ($categoriesToUse as $cname) {
            $catStmt->execute([$cname]);
            $categoryMap[$cname] = (int)$pdo->lastInsertId();
        }
        msg("Seeded " . count($categoriesToUse) . " categories.", 'success');

        // ──────────────────────────────────────────────────────────────────────
        // 5. SEED DEFAULT ITEM NAMES
        // ──────────────────────────────────────────────────────────────────────
        $diStmt = $pdo->prepare("INSERT INTO default_item_names (item_name) VALUES (?)");
        foreach ($defaultItemNames as $n) {
            $diStmt->execute([$n]);
        }
        msg("Seeded " . count($defaultItemNames) . " default item names.", 'success');

        // ──────────────────────────────────────────────────────────────────────
        // 6. SEED USER ACCOUNTS
        // ──────────────────────────────────────────────────────────────────────
        $userStmt = $pdo->prepare(
            "INSERT INTO users (full_name, username, email, phone, password, role, status, theme_preference) VALUES (?, ?, ?, ?, ?, ?, ?, 'auto')"
        );

        $usersToSeed = !empty($submittedUsers) ? $submittedUsers : [];
        if (empty($usersToSeed)) {
            foreach ($defaultUsers as $role => $accounts) {
                foreach ($accounts as $acc) {
                    $usersToSeed[] = array_merge($acc, ['role' => $role]);
                }
            }
        }

        $usersByRole = ['admin' => [], 'staff' => [], 'customer' => []];
        $userCount = 0;

        foreach ($usersToSeed as $u) {
            $pwd = aes_encrypt($u['password']);
            $userStmt->execute([
                $u['full_name'],
                $u['username'],
                $u['email'],
                $u['phone'],
                $pwd,
                $u['role'],
                $u['status'],
            ]);
            $userId = (int)$pdo->lastInsertId();
            $usersByRole[$u['role']][] = [
                'id'       => $userId,
                'username' => $u['username'],
                'email'    => $u['email'],
                'status'   => $u['status'],
            ];
            $userCount++;
        }
        msg("Created {$userCount} user accounts.", 'success');

        // ──────────────────────────────────────────────────────────────────────
        // 7. SEED INVENTORY
        // ──────────────────────────────────────────────────────────────────────
        $inventoryToSeed = !empty($submittedInventory) ? $submittedInventory : $defaultInventory;

        $invStmt = $pdo->prepare(
            "INSERT INTO inventory (item_name, category_id, stock_count, price, max_order_qty) VALUES (?, ?, ?, ?, ?)"
        );
        $inventoryIds = [];
        foreach ($inventoryToSeed as $item) {
            $catId = $categoryMap[$item['category']] ?? null;
            $invStmt->execute([
                $item['item_name'],
                $catId,
                $item['stock_count'],
                $item['price'],
                $item['max_order_qty'],
            ]);
            $inventoryIds[] = [
                'id'    => (int)$pdo->lastInsertId(),
                'name'  => $item['item_name'],
                'price' => (float)$item['price'],
                'stock' => (int)$item['stock_count'],
            ];
        }
        msg("Created " . count($inventoryIds) . " inventory items.", 'success');

        // ──────────────────────────────────────────────────────────────────────
        // 8. SEED ORDERS (based on toggles)
        // ──────────────────────────────────────────────────────────────────────
        $activeCustomers = array_values(array_filter($usersByRole['customer'], fn($c) => $c['status'] === 'active'));
        $activeStaff     = array_values(array_filter($usersByRole['staff'], fn($s) => $s['status'] === 'active'));
        $activeInv       = array_values(array_filter($inventoryIds, fn($i) => $i['stock'] > 0));

        if (!empty($activeCustomers) && !empty($activeInv) && !empty($enabledStatuses)) {

            $orderStmt = $pdo->prepare(
                "INSERT INTO orders (order_code, user_id, guest_name, guest_phone, guest_note, status, total_price, claim_pin, processed_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $oiStmt = $pdo->prepare(
                "INSERT INTO order_items (order_id, item_id, item_name_snapshot, quantity, unit_price) VALUES (?, ?, ?, ?, ?)"
            );

            $orderCount = 0;
            $custIdx = 0;
            $invIdx = 0;
            $codeNum = 1;

            $nextCust = function () use (&$activeCustomers, &$custIdx) {
                $c = $activeCustomers[$custIdx % count($activeCustomers)];
                $custIdx++;
                return $c;
            };
            $nextInv = function () use (&$activeInv, &$invIdx) {
                $i = $activeInv[$invIdx % count($activeInv)];
                $invIdx++;
                return $i;
            };
            $makeCode = function () use (&$codeNum) {
                return 'ORD-' . str_pad((string)$codeNum++, 5, '0', STR_PAD_LEFT);
            };
            $makePin = function () {
                return str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            };

            // ── PENDING ──────────────────────────────────────────────────────
            if (in_array('pending', $enabledStatuses)) {
                $cust = $nextCust(); $inv = $nextInv();
                $qty = 2; $total = $inv['price'] * $qty;
                $code = $makeCode(); $pin = $makePin();
                $orderStmt->execute([$code, $cust['id'], null, null, null, 'pending', $total, $pin, null, date('Y-m-d H:i:s', strtotime('-1 hour'))]);
                $oid = (int)$pdo->lastInsertId();
                $oiStmt->execute([$oid, $inv['id'], $inv['name'], $qty, $inv['price']]);
                $orderCount++;
                msg("Order #{$oid} ({$code}) — PENDING", 'info');
            }

            // ── READY ────────────────────────────────────────────────────────
            if (in_array('ready', $enabledStatuses)) {
                $cust = $nextCust(); $inv = $nextInv();
                $qty = 3; $total = $inv['price'] * $qty;
                $code = $makeCode(); $pin = $makePin();
                $staffId = !empty($activeStaff) ? $activeStaff[0]['id'] : null;
                $orderStmt->execute([$code, $cust['id'], null, null, null, 'ready', $total, $pin, $staffId, date('Y-m-d H:i:s', strtotime('-5 hours'))]);
                $oid = (int)$pdo->lastInsertId();
                $oiStmt->execute([$oid, $inv['id'], $inv['name'], $qty, $inv['price']]);
                $orderCount++;
                msg("Order #{$oid} ({$code}) — READY", 'info');
            }

            // ── CLAIMED ──────────────────────────────────────────────────────
            if (in_array('claimed', $enabledStatuses)) {
                $cust = $nextCust(); $inv = $nextInv();
                $qty = 1; $total = $inv['price'] * $qty;
                $code = $makeCode(); $pin = $makePin();
                $staffId = !empty($activeStaff) ? $activeStaff[0]['id'] : null;
                $orderStmt->execute([$code, $cust['id'], null, null, null, 'claimed', $total, $pin, $staffId, date('Y-m-d H:i:s', strtotime('-2 days'))]);
                $oid = (int)$pdo->lastInsertId();
                $oiStmt->execute([$oid, $inv['id'], $inv['name'], $qty, $inv['price']]);
                $orderCount++;
                msg("Order #{$oid} ({$code}) — CLAIMED", 'info');
            }

            // ── CANCELLED ────────────────────────────────────────────────────
            if (in_array('cancelled', $enabledStatuses)) {
                $cust = $nextCust(); $inv = $nextInv();
                $qty = 2; $total = $inv['price'] * $qty;
                $code = $makeCode(); $pin = $makePin();
                $orderStmt->execute([$code, $cust['id'], null, null, null, 'cancelled', $total, $pin, null, date('Y-m-d H:i:s', strtotime('-7 days'))]);
                $oid = (int)$pdo->lastInsertId();
                $oiStmt->execute([$oid, $inv['id'], $inv['name'], $qty, $inv['price']]);
                $orderCount++;
                msg("Order #{$oid} ({$code}) — CANCELLED", 'info');
            }

            // ── KIOSK (guest) ORDER ──────────────────────────────────────────
            if (in_array('pending', $enabledStatuses)) {
                $inv = $nextInv();
                $qty = 1; $total = $inv['price'] * $qty;
                $code = $makeCode(); $pin = $makePin();
                $orderStmt->execute([$code, null, 'Walk-in Guest', '09190000000', 'Sample kiosk order', 'pending', $total, $pin, null, date('Y-m-d H:i:s', strtotime('-30 minutes'))]);
                $oid = (int)$pdo->lastInsertId();
                $oiStmt->execute([$oid, $inv['id'], $inv['name'], $qty, $inv['price']]);
                $orderCount++;
                msg("Order #{$oid} ({$code}) — PENDING (kiosk guest)", 'info');
            }

            msg("Created {$orderCount} sample orders.", 'success');
        } else {
            if (empty($enabledStatuses)) {
                msg("No order statuses enabled — skipped order seeding.", 'warning');
            } elseif (empty($activeCustomers)) {
                msg("No active customers — skipped order seeding.", 'warning');
            } elseif (empty($activeInv)) {
                msg("No in-stock inventory — skipped order seeding.", 'warning');
            }
        }

        // ──────────────────────────────────────────────────────────────────────
        // 9. ENSURE FOLDERS EXIST
        // ──────────────────────────────────────────────────────────────────────
        $dirs = ['logs', 'uploads/admin/profiles', 'uploads/staff/profiles', 'uploads/customer/profiles', 'uploads/inventory'];
        foreach ($dirs as $d) {
            $full = APP_ROOT . '/' . $d;
            if (!is_dir($full)) @mkdir($full, 0775, true);
        }
        msg("Created uploads/ and logs/ folders.", 'success');

    } catch (PDOException $e) {
        $hasError = true;
        msg("DATABASE ERROR: " . $e->getMessage(), 'error');
    } catch (Throwable $e) {
        $hasError = true;
        msg("ERROR: " . $e->getMessage(), 'error');
    }

    $_SESSION['setup_flash'] = [
        'messages' => $messages,
        'hasError' => $hasError,
        'executed' => $executed,
    ];
    redirect(url('/setup.php'));
}

// ── Role display config ──────────────────────────────────────────────────────
$roleConfig = [
    'admin'    => ['icon' => '🛡️', 'color' => '#EF5350', 'label' => 'Admin'],
    'staff'    => ['icon' => '🏷️', 'color' => '#AB47BC', 'label' => 'Staff'],
    'customer' => ['icon' => '👤', 'color' => '#66BB6A', 'label' => 'Customer'],
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Setup — E&amp;N School Supplies</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(url('/uploads/system/logo.png')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/css/global.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/css/components.css')) ?>">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(129, 199, 132, 0.22), transparent 28%),
                radial-gradient(circle at top right, rgba(76, 175, 80, 0.14), transparent 24%),
                var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px 16px 48px;
        }

        .container { max-width: 1480px; margin: 0 auto; }

        /* ── Header ── */
        .header { text-align: center; margin-bottom: 18px; }
        .header .logo { font-size: 42px; }
        .header h1 { font-size: 1.45em; margin: 6px 0 4px; }
        .header .sub { color: var(--text-muted); font-size: 0.88em; }

        /* ── Warning ── */
        .warning-box {
            background: rgba(46, 125, 50, 0.08);
            border: 1px solid rgba(46, 125, 50, 0.18);
            border-left: 4px solid var(--primary);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 18px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            box-shadow: var(--shadow-sm);
        }
        .warning-box .wi { font-size: 24px; flex-shrink: 0; }
        .warning-box p { color: var(--text-muted); line-height: 1.45; font-size: 0.88em; margin: 0; }
        .warning-box strong { color: var(--primary); }

        .setup-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            align-items: start;
        }
        .setup-col {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }
        .setup-col-main, .setup-col-side { align-self: start; }

        /* ── Sections ── */
        .section {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .section-header {
            background: rgba(46, 125, 50, 0.04);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }
        .section-header:hover { background: rgba(46, 125, 50, 0.08); }
        .section-header .si { font-size: 20px; }
        .section-header h2 { font-size: 0.96em; font-weight: 700; flex: 1; }
        .section-header .badge {
            background: rgba(46,125,50,0.12);
            color: var(--primary);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72em;
            font-weight: 600;
        }
        .section-header .chevron { color: var(--text-muted); transition: transform 0.3s; font-size: 18px; }
        .section-header.collapsed .chevron { transform: rotate(-90deg); }
        .section-body { padding: 14px 16px 16px; }
        .section-body.collapsed { display: none; }

        /* ── Role Groups ── */
        .role-group {
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .role-group:last-child { margin-bottom: 0; }
        .role-header {
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }
        .role-header:hover { background: rgba(46,125,50,0.05); }
        .role-header .ri { font-size: 20px; }
        .role-header .role-label { font-weight: 700; font-size: 0.88em; flex: 1; }
        .role-header .count-badge {
            background: rgba(46,125,50,0.12);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            color: var(--primary);
        }
        .role-body { padding: 0 14px 14px; }

        /* ── User Card ── */
        .user-card {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 12px 10px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }
        .user-card:last-child { margin-bottom: 0; }
        .user-card .full-width { grid-column: 1 / -1; }

        .user-card-header {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .user-card-header span { font-size: 0.78em; color: var(--text-muted); }
        .btn-remove {
            background: rgba(211,47,47,0.08);
            border: 1px solid rgba(211,47,47,0.24);
            color: var(--danger);
            border-radius: 6px;
            padding: 3px 8px;
            font-size: 0.75em;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }
        .btn-remove:hover { background: rgba(211,47,47,0.14); }

        /* ── Inputs ── */
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field label {
            font-size: 0.68em;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .field input, .field select {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 0.84em;
            font-family: inherit;
            transition: border-color 0.2s;
            width: 100%;
        }
        .field input:focus, .field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(46,125,50,0.12);
        }
        .field input::placeholder { color: var(--text-muted); }

        /* ── Inventory / Category Table ── */
        .inv-table {
            width: 100%;
            border-collapse: collapse;
        }
        .inv-table th {
            text-align: left;
            font-size: 0.68em;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border-bottom: 1px solid var(--border);
        }
        .inv-table td {
            padding: 5px 8px;
            border-bottom: 1px solid rgba(200,230,201,0.7);
            vertical-align: middle;
        }
        .inv-table input, .inv-table select {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 7px 9px;
            border-radius: 7px;
            font-size: 0.8em;
            font-family: inherit;
            width: 100%;
            transition: border-color 0.2s;
        }
        .inv-table input:focus, .inv-table select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .inv-table .btn-remove { padding: 5px 10px; }
        .inv-table tr:last-child td { border-bottom: none; }

        /* ── Toggle Switches ── */
        .toggle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 10px;
        }
        .toggle-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .toggle-item:hover { border-color: var(--primary); background: rgba(46,125,50,0.05); }
        .toggle-item.active { border-color: var(--primary); background: rgba(46,125,50,0.08); }

        .switch {
            position: relative;
            width: 42px;
            height: 24px;
            flex-shrink: 0;
        }
        .switch input { opacity: 0; width: 0; height: 0; }
        .switch .slider {
            position: absolute;
            inset: 0;
            background: var(--border);
            border-radius: 24px;
            transition: 0.3s;
            cursor: pointer;
        }
        .switch .slider::before {
            content: '';
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: var(--text-muted);
            border-radius: 50%;
            transition: 0.3s;
        }
        .switch input:checked + .slider { background: #2e7d32; }
        .switch input:checked + .slider::before {
            transform: translateX(18px);
            background: #fff;
        }
        .toggle-label { font-size: 0.84em; font-weight: 600; }

        /* ── Settings Grid ── */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
        }

        /* ── Add Buttons ── */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(46,125,50,0.08);
            border: 1px dashed rgba(46,125,50,0.25);
            color: var(--primary);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.8em;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            margin-top: 8px;
        }
        .btn-add:hover { background: rgba(46,125,50,0.14); border-color: var(--primary); }

        /* ── Submit Button ── */
        .btn-submit-wrap { margin: 32px 0 20px; }
        .btn-submit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 16px;
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            color: #fff;
            font-size: 0.95em;
            font-weight: 700;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            letter-spacing: 0.3px;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #0D3B0F, #1B5E20);
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(46,125,50,0.45);
        }
        .btn-submit:active { transform: translateY(0); }
        .btn-submit .si { font-size: 26px; }
        .btn-submit.reset {
            background: linear-gradient(135deg, #E65100, #FB8C00);
            margin-top: 16px;
        }
        .btn-submit.reset:hover {
            background: linear-gradient(135deg, #BF360C, #E65100);
            box-shadow: 0 8px 28px rgba(230,81,0,0.4);
        }

        /* ── Feedback ── */
        .feedback { margin-top: 0; }
        .msg {
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
            font-size: 0.8em;
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .msg .mi { font-size: 18px; flex-shrink: 0; }
        .msg-success { background: rgba(102,187,106,0.1); border: 1px solid rgba(102,187,106,0.2); color: #A5D6A7; }
        .msg-success .mi { color: #66BB6A; }
        .msg-info    { background: rgba(41,182,246,0.08); border: 1px solid rgba(41,182,246,0.18); color: #90CAF9; }
        .msg-info .mi { color: #29B6F6; }
        .msg-warning { background: rgba(255,167,38,0.1); border: 1px solid rgba(255,167,38,0.2); color: #FFE0B2; }
        .msg-warning .mi { color: #FFA726; }
        .msg-error   { background: rgba(239,83,80,0.12); border: 1px solid rgba(239,83,80,0.25); color: #EF9A9A; }
        .msg-error .mi { color: #EF5350; }

        .bottom-link { text-align: center; margin-top: 18px; }
        .bottom-link a { color: var(--primary); text-decoration: none; font-size: 0.88em; }
        .bottom-link a:hover { text-decoration: underline; }

        /* ── Responsive ── */
        @media (max-width: 1100px) {
            .setup-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .user-card { grid-template-columns: 1fr; }
            .toggle-grid { grid-template-columns: 1fr; }
            .settings-grid { grid-template-columns: 1fr; }
            .inv-table-wrap { overflow-x: auto; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <div class="header">
        <div class="logo">📚</div>
        <h1>Interactive Database Setup</h1>
        <p class="sub">E&amp;N School Supplies — Development Tool</p>
    </div>

    <!-- Warning -->
    <div class="warning-box">
        <span class="wi">⚠️</span>
        <p><strong>Warning:</strong> This will <strong>completely delete</strong> the existing database and recreate it with the configuration below. <strong>All current data will be permanently lost.</strong> Use only for testing.</p>
    </div>

    <?php if (!empty($messages)): ?>
    <div class="section" style="margin-top:0;">
        <div class="section-header" style="cursor:default;">
            <span class="si"><?= $hasError ? '❌' : '💻' ?></span>
            <h2><?= $hasError ? 'Completed with Errors' : 'Execution Log' ?></h2>
            <span class="badge"><?= count($messages) ?> entries</span>
        </div>
        <div class="section-body feedback">
            <?php foreach ($messages as $m): ?>
                <?php
                    $iconMap = ['success' => '✅', 'info' => 'ℹ️', 'warning' => '⚠️', 'error' => '❌'];
                    $icon = $iconMap[$m['type']] ?? 'ℹ️';
                ?>
                <div class="msg msg-<?= $m['type'] ?>">
                    <span class="mi"><?= $icon ?></span>
                    <?= htmlspecialchars($m['text']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" id="setupForm">
    <div class="setup-grid">
        <div class="setup-col setup-col-main">

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1: USER ACCOUNTS                                          -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header" onclick="toggleSection(this)">
            <span class="si">👥</span>
            <h2>User Accounts</h2>
            <span class="badge" id="userCountBadge"><?= array_sum(array_map('count', $defaultUsers)) ?> users</span>
            <span class="chevron">▼</span>
        </div>
        <div class="section-body" id="usersSection">
            <?php foreach ($roleConfig as $role => $cfg): ?>
            <div class="role-group" id="role-group-<?= $role ?>">
                <div class="role-header" onclick="toggleRole(this)" style="background: <?= $cfg['color'] ?>15;">
                    <span class="ri"><?= $cfg['icon'] ?></span>
                    <span class="role-label" style="color: <?= $cfg['color'] ?>"><?= $cfg['label'] ?></span>
                    <span class="count-badge role-count" data-role="<?= $role ?>"><?= count($defaultUsers[$role] ?? []) ?></span>
                    <span class="chevron" style="color: #666; font-size: 16px;">▼</span>
                </div>
                <div class="role-body" id="role-body-<?= $role ?>">
                    <div class="user-cards-container" id="users-<?= $role ?>">
                        <?php if (isset($defaultUsers[$role])): ?>
                        <?php foreach ($defaultUsers[$role] as $idx => $u): ?>
                        <div class="user-card" data-role="<?= $role ?>">
                            <div class="user-card-header">
                                <span><?= $cfg['label'] ?> #<?= $idx + 1 ?></span>
                                <button type="button" class="btn-remove" onclick="removeUserCard(this)">✕ Remove</button>
                            </div>
                            <div class="field">
                                <label>Username</label>
                                <input type="text" name="users[<?= $role ?>][<?= $idx ?>][username]" value="<?= htmlspecialchars($u['username']) ?>" placeholder="username" required>
                            </div>
                            <div class="field">
                                <label>Email</label>
                                <input type="email" name="users[<?= $role ?>][<?= $idx ?>][email]" value="<?= htmlspecialchars($u['email']) ?>" placeholder="user@example.com" required>
                            </div>
                            <div class="field">
                                <label>Password</label>
                                <input type="text" name="users[<?= $role ?>][<?= $idx ?>][password]" value="<?= htmlspecialchars($u['password']) ?>" placeholder="password">
                            </div>
                            <div class="field">
                                <label>Full Name</label>
                                <input type="text" name="users[<?= $role ?>][<?= $idx ?>][full_name]" value="<?= htmlspecialchars($u['full_name']) ?>" placeholder="Full Name" required>
                            </div>
                            <div class="field">
                                <label>Phone</label>
                                <input type="text" name="users[<?= $role ?>][<?= $idx ?>][phone]" value="<?= htmlspecialchars($u['phone']) ?>" placeholder="09XX-XXX-XXXX">
                            </div>
                            <div class="field full-width">
                                <label>Status</label>
                                <select name="users[<?= $role ?>][<?= $idx ?>][status]">
                                    <option value="active" <?= ($u['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="pending" <?= ($u['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="flagged" <?= ($u['status'] ?? '') === 'flagged' ? 'selected' : '' ?>>Flagged</option>
                                </select>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn-add" onclick="addUserCard('<?= $role ?>', '<?= $cfg['label'] ?>')">
                        + Add <?= $cfg['label'] ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 2: CATEGORIES                                             -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header" onclick="toggleSection(this)">
            <span class="si">🏷️</span>
            <h2>Item Categories</h2>
            <span class="badge" id="catCountBadge"><?= count($defaultCategories) ?> categories</span>
            <span class="chevron">▼</span>
        </div>
        <div class="section-body">
            <div class="inv-table-wrap">
                <table class="inv-table" id="catTable">
                    <thead><tr><th style="width:5%">#</th><th>Category Name</th><th style="width:10%"></th></tr></thead>
                    <tbody id="catBody">
                        <?php foreach ($defaultCategories as $idx => $cat): ?>
                        <tr>
                            <td style="color:#666; font-size:0.85em;"><?= $idx + 1 ?></td>
                            <td><input type="text" name="categories[<?= $idx ?>][name]" value="<?= htmlspecialchars($cat) ?>" required></td>
                            <td><button type="button" class="btn-remove" onclick="removeRow(this, 'catBody', 'catCountBadge', 'categories')">✕</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn-add" onclick="addCatRow()">+ Add Category</button>
        </div>
    </div>

        </div>
        <div class="setup-col setup-col-side">

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 3: INVENTORY                                              -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header" onclick="toggleSection(this)">
            <span class="si">📦</span>
            <h2>Inventory Items</h2>
            <span class="badge" id="invCountBadge"><?= count($defaultInventory) ?> items</span>
            <span class="chevron">▼</span>
        </div>
        <div class="section-body">
            <div class="inv-table-wrap">
                <table class="inv-table" id="inventoryTable">
                    <thead>
                        <tr>
                            <th style="width:4%">#</th>
                            <th style="width:28%">Item Name</th>
                            <th style="width:22%">Category</th>
                            <th style="width:10%">Stock</th>
                            <th style="width:12%">Price (₱)</th>
                            <th style="width:10%">Max Qty</th>
                            <th style="width:6%"></th>
                        </tr>
                    </thead>
                    <tbody id="inventoryBody">
                        <?php foreach ($defaultInventory as $idx => $item): ?>
                        <tr>
                            <td style="color:#666; font-size:0.85em;"><?= $idx + 1 ?></td>
                            <td><input type="text" name="inventory[<?= $idx ?>][item_name]" value="<?= htmlspecialchars($item['item_name']) ?>" required></td>
                            <td><input type="text" name="inventory[<?= $idx ?>][category]" value="<?= htmlspecialchars($item['category']) ?>" placeholder="Category"></td>
                            <td><input type="number" name="inventory[<?= $idx ?>][stock_count]" value="<?= $item['stock_count'] ?>" min="0"></td>
                            <td><input type="number" step="0.01" name="inventory[<?= $idx ?>][price]" value="<?= $item['price'] ?>" min="0"></td>
                            <td><input type="number" name="inventory[<?= $idx ?>][max_order_qty]" value="<?= $item['max_order_qty'] ?>" min="1"></td>
                            <td><button type="button" class="btn-remove" onclick="removeRow(this, 'inventoryBody', 'invCountBadge', 'items')">✕</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn-add" onclick="addInvRow()">+ Add Item</button>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 4: ORDER STATUS TOGGLES                                   -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header" onclick="toggleSection(this)">
            <span class="si">🧾</span>
            <h2>Order Statuses to Seed</h2>
            <span class="badge" id="orderCountBadge"><?= count(array_filter($defaultOrderStatuses, fn($s) => $s['enabled'])) ?> enabled</span>
            <span class="chevron">▼</span>
        </div>
        <div class="section-body">
            <p style="font-size:0.85em; color:#8888AA; margin-bottom:16px;">Toggle which order statuses should have sample orders created. Each enabled status generates 1 sample order. "Pending" also adds a kiosk guest order.</p>
            <div class="toggle-grid" id="orderToggles">
                <?php foreach ($defaultOrderStatuses as $status => $cfg): ?>
                <label class="toggle-item <?= $cfg['enabled'] ? 'active' : '' ?>" id="toggle-<?= $status ?>">
                    <div class="switch">
                        <input type="checkbox" name="order_status[<?= $status ?>]" value="1" <?= $cfg['enabled'] ? 'checked' : '' ?>
                               onchange="this.closest('.toggle-item').classList.toggle('active', this.checked); updateOrderCount();">
                        <span class="slider"></span>
                    </div>
                    <span class="toggle-label"><?= $cfg['label'] ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <div style="margin-top:14px; display:flex; gap:10px;">
                <button type="button" class="btn-add" style="margin:0;" onclick="toggleAllOrders(true)">☑ Enable All</button>
                <button type="button" class="btn-add" style="margin:0; color:#EF5350; border-color:rgba(239,83,80,0.3); background:rgba(239,83,80,0.05);" onclick="toggleAllOrders(false)">☐ Disable All</button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 5: SYSTEM SETTINGS                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="section">
        <div class="section-header collapsed" onclick="toggleSection(this)">
            <span class="si">⚙️</span>
            <h2>System Settings</h2>
            <span class="badge"><?= count($defaultSettings) ?> settings</span>
            <span class="chevron" style="transform:rotate(-90deg);">▼</span>
        </div>
        <div class="section-body collapsed">
            <div class="settings-grid">
                <?php foreach ($defaultSettings as $key => $val): ?>
                <div class="field">
                    <label><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></label>
                    <?php if (in_array($key, ['force_dark', 'disable_no_login_orders', 'online_payment'])): ?>
                    <select name="settings[<?= htmlspecialchars($key) ?>]">
                        <option value="1" <?= $val === '1' ? 'selected' : '' ?>>Enabled</option>
                        <option value="0" <?= $val === '0' ? 'selected' : '' ?>>Disabled</option>
                    </select>
                    <?php elseif ($key === 'system_status'): ?>
                    <select name="settings[<?= htmlspecialchars($key) ?>]">
                        <option value="online" <?= $val === 'online' ? 'selected' : '' ?>>Online</option>
                        <option value="maintenance" <?= $val === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        <option value="offline" <?= $val === 'offline' ? 'selected' : '' ?>>Offline</option>
                    </select>
                    <?php else: ?>
                    <input type="text" name="settings[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($val) ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- SUBMIT                                                            -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div class="btn-submit-wrap">
        <button type="submit" name="create_db" class="btn-submit"
                onclick="return confirm('⚠️ This will DELETE the entire database and recreate it with your configuration.\n\nAre you absolutely sure?');">
            <span class="si">🚀</span>
            CREATE DATABASE &amp; SEED DATA
        </button>
    </div>

    </form>

    <?php if (!$hasError): ?>
    <form method="POST">
        <button type="submit" name="create_db" class="btn-submit reset"
                onclick="return confirm('Run again? This will DELETE everything and recreate with DEFAULT values.');">
            <span class="si">🔄</span>
            QUICK RESET (Default Values)
        </button>
    </form>
    <?php endif; ?>

    <div class="bottom-link">
        <a href="<?= htmlspecialchars(url('/login.php')) ?>">← Go to Login</a>
        &nbsp;&nbsp;·&nbsp;&nbsp;
        <a href="<?= htmlspecialchars(url('/index.php')) ?>">Landing Page →</a>
    </div>
</div>

<script>
// ── Section collapse/expand ──────────────────────────────────────────────────
function toggleSection(header) {
    const body = header.nextElementSibling;
    const chevron = header.querySelector('.chevron');
    header.classList.toggle('collapsed');
    body.classList.toggle('collapsed');
    chevron.style.transform = header.classList.contains('collapsed') ? 'rotate(-90deg)' : '';
}

// ── Role group collapse/expand ───────────────────────────────────────────────
function toggleRole(header) {
    const body = header.nextElementSibling;
    const chevron = header.querySelector('.chevron');
    const isHidden = body.style.display === 'none';
    body.style.display = isHidden ? '' : 'none';
    chevron.style.transform = isHidden ? '' : 'rotate(-90deg)';
}

// ── Add user card ────────────────────────────────────────────────────────────
function addUserCard(role, roleLabel) {
    const container = document.getElementById('users-' + role);
    const idx = Date.now();

    const card = document.createElement('div');
    card.className = 'user-card';
    card.setAttribute('data-role', role);
    card.innerHTML = `
        <div class="user-card-header">
            <span>${roleLabel} (new)</span>
            <button type="button" class="btn-remove" onclick="removeUserCard(this)">✕ Remove</button>
        </div>
        <div class="field">
            <label>Username</label>
            <input type="text" name="users[${role}][${idx}][username]" placeholder="username" required>
        </div>
        <div class="field">
            <label>Email</label>
            <input type="email" name="users[${role}][${idx}][email]" placeholder="user@example.com" required>
        </div>
        <div class="field">
            <label>Password</label>
            <input type="text" name="users[${role}][${idx}][password]" value="password123" placeholder="password">
        </div>
        <div class="field">
            <label>Full Name</label>
            <input type="text" name="users[${role}][${idx}][full_name]" placeholder="Full Name" required>
        </div>
        <div class="field">
            <label>Phone</label>
            <input type="text" name="users[${role}][${idx}][phone]" placeholder="09XX-XXX-XXXX">
        </div>
        <div class="field full-width">
            <label>Status</label>
            <select name="users[${role}][${idx}][status]">
                <option value="active" selected>Active</option>
                <option value="pending">Pending</option>
                <option value="flagged">Flagged</option>
            </select>
        </div>
    `;
    container.appendChild(card);
    updateUserCount();
    card.querySelector('input').focus();
}

function removeUserCard(btn) {
    const card = btn.closest('.user-card');
    card.style.opacity = '0';
    card.style.transform = 'scale(0.95)';
    card.style.transition = 'all 0.2s';
    setTimeout(() => { card.remove(); updateUserCount(); }, 200);
}

function updateUserCount() {
    let total = document.querySelectorAll('.user-card').length;
    document.getElementById('userCountBadge').textContent = total + ' users';
    document.querySelectorAll('.role-count').forEach(badge => {
        const role = badge.dataset.role;
        badge.textContent = document.querySelectorAll('#users-' + role + ' .user-card').length;
    });
}

// ── Generic row remove + renumber ────────────────────────────────────────────
function removeRow(btn, tbodyId, badgeId, unit) {
    const row = btn.closest('tr');
    row.style.opacity = '0';
    row.style.transition = 'opacity 0.2s';
    setTimeout(() => {
        row.remove();
        renumber(tbodyId);
        const count = document.getElementById(tbodyId).rows.length;
        document.getElementById(badgeId).textContent = count + ' ' + unit;
    }, 200);
}

function renumber(tbodyId) {
    document.querySelectorAll('#' + tbodyId + ' tr').forEach((row, i) => {
        row.cells[0].textContent = i + 1;
    });
}

// ── Add category row ─────────────────────────────────────────────────────────
function addCatRow() {
    const tbody = document.getElementById('catBody');
    const idx = Date.now();
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="color:#666; font-size:0.85em;">${tbody.rows.length + 1}</td>
        <td><input type="text" name="categories[${idx}][name]" placeholder="Category name" required></td>
        <td><button type="button" class="btn-remove" onclick="removeRow(this, 'catBody', 'catCountBadge', 'categories')">✕</button></td>
    `;
    tbody.appendChild(tr);
    document.getElementById('catCountBadge').textContent = tbody.rows.length + ' categories';
    tr.querySelector('input').focus();
}

// ── Add inventory row ────────────────────────────────────────────────────────
function addInvRow() {
    const tbody = document.getElementById('inventoryBody');
    const idx = Date.now();
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="color:#666; font-size:0.85em;">${tbody.rows.length + 1}</td>
        <td><input type="text" name="inventory[${idx}][item_name]" placeholder="Item name" required></td>
        <td><input type="text" name="inventory[${idx}][category]" placeholder="Category"></td>
        <td><input type="number" name="inventory[${idx}][stock_count]" value="0" min="0"></td>
        <td><input type="number" step="0.01" name="inventory[${idx}][price]" value="0" min="0"></td>
        <td><input type="number" name="inventory[${idx}][max_order_qty]" value="10" min="1"></td>
        <td><button type="button" class="btn-remove" onclick="removeRow(this, 'inventoryBody', 'invCountBadge', 'items')">✕</button></td>
    `;
    tbody.appendChild(tr);
    document.getElementById('invCountBadge').textContent = tbody.rows.length + ' items';
    tr.querySelector('input').focus();
}

// ── Order toggles ────────────────────────────────────────────────────────────
function toggleAllOrders(state) {
    document.querySelectorAll('#orderToggles input[type="checkbox"]').forEach(cb => {
        cb.checked = state;
        cb.closest('.toggle-item').classList.toggle('active', state);
    });
    updateOrderCount();
}

function updateOrderCount() {
    const checked = document.querySelectorAll('#orderToggles input[type="checkbox"]:checked').length;
    document.getElementById('orderCountBadge').textContent = checked + ' enabled';
}
</script>
</body>
</html>
