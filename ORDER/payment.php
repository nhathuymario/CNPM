<?php
session_start();
require_once '../functions/database.php';

// Lấy order_id
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    http_response_code(400);
    die('Thiếu order_id');
}

// Kiểm tra schema linh hoạt
$hasPaymentStatus = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_status'")->num_rows > 0;
$hasRefCode       = $conn->query("SHOW COLUMNS FROM orders LIKE 'ref_code'")->num_rows > 0;
$hasStatus        = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'")->num_rows > 0;

// Build SELECT tùy cột
$sql = "SELECT id, table_number, total, payment_method";
if ($hasPaymentStatus) $sql .= ", payment_status";
if ($hasRefCode)       $sql .= ", ref_code";
if ($hasStatus && !$hasPaymentStatus) $sql .= ", status";
$sql .= ", created_at FROM orders WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();

if (!$order) {
    http_response_code(404);
    die('Không tìm thấy đơn hàng');
}
if (($order['payment_method'] ?? '') !== 'bank_transfer') {
    die('Đơn hàng này không dùng chuyển khoản.');
}

// Lấy floor của bàn từ bảng tables
$stmt2 = $conn->prepare("SELECT floor FROM tables WHERE table_number = ?");
$stmt2->bind_param("i", $order['table_number']);
$stmt2->execute();
$res2 = $stmt2->get_result();
$table_info = $res2->fetch_assoc();
$floor = $table_info ? intval($table_info['floor']) : 1;

// Chuẩn hóa giá trị hiển thị
$payment_status = 'pending';
if ($hasPaymentStatus) {
    $payment_status = $order['payment_status'] ?? 'pending';
} elseif ($hasStatus) {
    $payment_status = (isset($order['status']) && $order['status'] === 'paid') ? 'paid' : 'pending';
}

$ref_code = '';
if ($hasRefCode && !empty($order['ref_code'])) {
    $ref_code = $order['ref_code'];
} else {
    // Fallback sinh mã tham chiếu
    $ref_code = 'CF' . strtoupper(dechex($order['id'])) . '-' . date('d');
}

// Thông tin tài khoản nhận (có thể đưa vào config)
$BANK_ACCOUNT_NAME   = 'CALM SPACE';
$BANK_ACCOUNT_NUMBER = '0367903437';
$BANK_NAME           = 'VPBank';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Thanh toán chuyển khoản</title>
  <link rel="stylesheet" href="../assets/css/payment.css" />
</head>
<body>
  <div class="payment-container">
    <h2>Thanh toán chuyển khoản cho đơn #<?php echo htmlspecialchars($order_id); ?></h2>
    <p>Tầng: <strong><?php echo htmlspecialchars($floor); ?></strong></p>
    <p>Bàn: <strong><?php echo htmlspecialchars($order['table_number']); ?></strong></p>
    <p>Số tiền: <strong><?php echo number_format($order['total']); ?> đ</strong></p>
    <hr>
    <h3>Thông tin chuyển khoản</h3>
    <p>Ngân hàng: <strong><?php echo htmlspecialchars($BANK_NAME); ?></strong></p>
    <p>Chủ TK: <strong><?php echo htmlspecialchars($BANK_ACCOUNT_NAME); ?></strong></p>
    <p>Số TK: <strong><?php echo htmlspecialchars($BANK_ACCOUNT_NUMBER); ?></strong></p>
    <p>Nội dung: <strong><?php echo htmlspecialchars($ref_code); ?></strong> (vui lòng ghi đúng để đối soát)</p>

    <div class="hint">
      Sau khi chuyển khoản thành công, đơn hàng sẽ được xác nhận. Nếu cần hỗ trợ, vui lòng bấm nút Trợ giúp ở thanh trên cùng.
    </div>

    <div class="status">
      Trạng thái thanh toán: <strong><?php echo htmlspecialchars($payment_status); ?></strong>
    </div>
  </div>
</body>
</html>