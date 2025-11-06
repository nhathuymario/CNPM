<?php
// API: Chuyển bàn (table transfer) với xác thực admin
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Kết nối DB
$dbIncluded = false;
$paths = [ __DIR__ . '/database.php', dirname(__DIR__) . '/functions/database.php', dirname(__DIR__) . '/includes/db.php' ];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; $dbIncluded = true; break; } }
if (!$dbIncluded) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database bootstrap not found']); exit; }

$dbType = null; $dbc = null;
if (isset($pdo)) { $dbc = $pdo; $dbType = 'pdo'; }
elseif (isset($conn)) { $dbc = $conn; $dbType = 'mysqli'; }
else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database connection not found']); exit; }

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$from_table = isset($input['from_table']) ? (int)$input['from_table'] : 0;
$to_table = isset($input['to_table']) ? (int)$input['to_table'] : 0;
$admin_username = $input['admin_username'] ?? '';
$admin_password = $input['admin_password'] ?? '';

if (!$from_table || !$to_table) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bàn']);
    exit;
}

if ($from_table === $to_table) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Không thể chuyển sang cùng bàn']);
    exit;
}

// Xác thực admin
try {
    if ($dbType === 'pdo') {
        $st = $dbc->prepare("SELECT id, role FROM users WHERE username = ? LIMIT 1");
        $st->execute([$admin_username]);
        $user = $st->fetch(PDO::FETCH_ASSOC);
    } else {
        $st = $dbc->prepare("SELECT id, role FROM users WHERE username = ? LIMIT 1");
        $st->bind_param('s', $admin_username);
        $st->execute();
        $res = $st->get_result();
        $user = $res->fetch_assoc();
        $st->close();
    }

    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Chỉ Admin mới có quyền chuyển bàn']);
        exit;
    }

    // Verify password
    if ($dbType === 'pdo') {
        $st = $dbc->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
        $st->execute([$admin_username]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
    } else {
        $st = $dbc->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
        $st->bind_param('s', $admin_username);
        $st->execute();
        $res = $st->get_result();
        $row = $res->fetch_assoc();
        $st->close();
    }

    if (!$row || !password_verify($admin_password, $row['password'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sai mật khẩu Admin']);
        exit;
    }

    // Check if target table is free
    if ($dbType === 'pdo') {
        $st = $dbc->prepare("SELECT COUNT(*) as cnt FROM orders WHERE table_number = ? AND status NOT IN ('paid','cancelled')");
        $st->execute([$to_table]);
        $check = $st->fetch(PDO::FETCH_ASSOC);
    } else {
        $st = $dbc->prepare("SELECT COUNT(*) as cnt FROM orders WHERE table_number = ? AND status NOT IN ('paid','cancelled')");
        $st->bind_param('i', $to_table);
        $st->execute();
        $res = $st->get_result();
        $check = $res->fetch_assoc();
        $st->close();
    }

    if ((int)$check['cnt'] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bàn đích đang có đơn, không thể chuyển']);
        exit;
    }

    // Transfer: update orders table_number
    if ($dbType === 'pdo') {
        $st = $dbc->prepare("UPDATE orders SET table_number = ? WHERE table_number = ? AND status NOT IN ('paid','cancelled')");
        $st->execute([$to_table, $from_table]);
    } else {
        $st = $dbc->prepare("UPDATE orders SET table_number = ? WHERE table_number = ? AND status NOT IN ('paid','cancelled')");
        $st->bind_param('ii', $to_table, $from_table);
        $st->execute();
        $st->close();
    }

    echo json_encode(['success' => true, 'message' => "Đã chuyển bàn $from_table sang bàn $to_table"]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
