<?php
// Endpoint: chuyển order từ bàn A sang bàn B (bàn B phải đang trống).
// Input: JSON POST { from_table, to_table, admin_user, admin_pass }
// Output: { ok: true, message, order_id?, from_table, to_table }

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database.php';

function jexit($ok, $msg, $extra = []) {
  http_response_code($ok ? 200 : 400);
  echo json_encode(array_merge(['ok'=>$ok,'message'=>$msg], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

function has_column(mysqli $conn, $table, $col) {
  $t = $conn->real_escape_string($table);
  $c = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
  return $res && $res->num_rows > 0;
}

function verify_admin(mysqli $conn, $user, $pass) {
  // Tùy DB: dùng users.username hoặc email; role in ('admin','manager')
  $sql = "SELECT id, username, password, role FROM users WHERE (username = ? OR email = ?) LIMIT 1";
  $st = $conn->prepare($sql);
  $st->bind_param("ss", $user, $user);
  $st->execute();
  $u = $st->get_result()->fetch_assoc();
  if (!$u) return false;

  // Thử password_verify, rồi đến md5, cuối cùng là plain (fallback)
  $hash = $u['password'] ?? '';
  $ok = false;
  if (is_string($hash) && strlen($hash) >= 20) {
    if (password_verify($pass, $hash)) $ok = true;
  }
  if (!$ok && strlen($hash) === 32 && ctype_xdigit($hash)) {
    if (md5($pass) === strtolower($hash)) $ok = true;
  }
  if (!$ok && $hash === $pass) $ok = true;

  // Kiểm tra role
  $role = strtolower($u['role'] ?? '');
  $isAdmin = in_array($role, ['admin','manager','owner','superadmin'], true);

  return $ok && $isAdmin;
}

$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) $in = $_POST;

$from = isset($in['from_table']) ? intval($in['from_table']) : 0;
$to   = isset($in['to_table'])   ? intval($in['to_table'])   : 0;
$au   = trim($in['admin_user'] ?? '');
$ap   = (string)($in['admin_pass'] ?? '');

if (!$from || !$to) jexit(false, "Thiếu thông tin bàn nguồn/đích.");
if ($from === $to) jexit(false, "Bàn đích phải khác bàn nguồn.");
if ($au === '' || $ap === '') jexit(false, "Vui lòng nhập tài khoản và mật khẩu Admin.");

if (!verify_admin($conn, $au, $ap)) jexit(false, "Tài khoản Admin không hợp lệ.");

$hasStatusCol        = has_column($conn, 'orders', 'status');
$hasPaymentStatusCol = has_column($conn, 'orders', 'payment_status');

try {
  $conn->begin_transaction();

  // 1) Kiểm tra bàn đích đang trống
  $st = $conn->prepare("SELECT status FROM tables WHERE table_number = ? LIMIT 1");
  $st->bind_param("i", $to); $st->execute();
  $tinfo = $st->get_result()->fetch_assoc();
  if (!$tinfo) throw new Exception("Không tìm thấy bàn đích.");
  if (strtolower($tinfo['status'] ?? '') !== 'available') {
    throw new Exception("Bàn đích chưa trống.");
  }

  // 2) Tìm đơn mở ở bàn nguồn
  $cond = "1=1";
  if ($hasStatusCol) {
    $cond = "status NOT IN ('paid','cancelled')";
  } elseif ($hasPaymentStatusCol) {
    $cond = "payment_status <> 'paid'";
  }
  $sqlFind = "SELECT id FROM orders WHERE table_number = ? AND $cond ORDER BY id DESC LIMIT 1";
  $st2 = $conn->prepare($sqlFind);
  $st2->bind_param("i", $from);
  $st2->execute();
  $ord = $st2->get_result()->fetch_assoc();
  if (!$ord) throw new Exception("Bàn nguồn không có đơn đang mở.");
  $order_id = intval($ord['id']);

  // 3) Cập nhật đơn sang bàn đích
  $st3 = $conn->prepare("UPDATE orders SET table_number = ? WHERE id = ?");
  $st3->bind_param("ii", $to, $order_id);
  $st3->execute();

  // 4) Cập nhật trạng thái bàn
  // - Bàn đích: unavailable
  $st4 = $conn->prepare("UPDATE tables SET status = 'unavailable' WHERE table_number = ?");
  $st4->bind_param("i", $to);
  $st4->execute();

  // - Bàn nguồn: nếu không còn đơn mở nào -> available; ngược lại giữ nguyên
  $st5 = $conn->prepare("SELECT COUNT(*) AS c FROM orders WHERE table_number = ? AND $cond");
  $st5->bind_param("i", $from);
  $st5->execute();
  $c = $st5->get_result()->fetch_assoc();
  if (intval($c['c'] ?? 0) === 0) {
    $st6 = $conn->prepare("UPDATE tables SET status = 'available' WHERE table_number = ?");
    $st6->bind_param("i", $from);
    $st6->execute();
  }

  $conn->commit();
  jexit(true, "Đã chuyển đơn #$order_id từ bàn $from sang bàn $to.", [
    'order_id' => $order_id,
    'from_table' => $from,
    'to_table' => $to,
  ]);
} catch (Throwable $e) {
  $conn->rollback();
  jexit(false, "Không thể chuyển bàn: " . $e->getMessage());
}