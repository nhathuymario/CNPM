<?php
session_start();
require_once '../functions/database.php';

// Lấy đơn
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) {
    http_response_code(400);
    die('Thiếu order_id');
}

$stmt = $conn->prepare("SELECT id, table_number, total, payment_method, payment_status, ref_code, created_at FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
if (!$order) {
    http_response_code(404);
    die('Không tìm thấy đơn hàng');
}
if ($order['payment_method'] !== 'bank_transfer') {
    die('Đơn hàng này không dùng chuyển khoản.');
}

// Thông tin tài khoản nhận (có thể đưa vào config)
$BANK_ACCOUNT_NAME = 'CA PHE QUIET';
$BANK_ACCOUNT_NUMBER = '0123456789';
$BANK_NAME = 'VPBank';

$ref_code = $order['ref_code'] ?: ('CF' . strtoupper(dechex($order['id'])) . '-' . date('d'));
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
    <h2>Thanh toán chuyển khoản cho đơn #<?php echo $order_id; ?></h2>
    <p>Bàn: <strong><?php echo htmlspecialchars($order['table_number']); ?></strong></p>
    <p>Số tiền: <strong><?php echo number_format($order['total']); ?> đ</strong></p>
    <hr>
    <h3>Thông tin chuyển khoản</h3>
    <p>Ngân hàng: <strong><?php echo htmlspecialchars($BANK_NAME); ?></strong></p>
    <p>Chủ TK: <strong><?php echo htmlspecialchars($BANK_ACCOUNT_NAME); ?></strong></p>
    <p>Số TK: <strong><?php echo htmlspecialchars($BANK_ACCOUNT_NUMBER); ?></strong></p>
    <p>Nội dung: <strong><?php echo htmlspecialchars($ref_code); ?></strong> (vui lòng ghi đúng để đối soát)</p>

    <div class="hint">
      Sau khi chuyển khoản thành công, đơn hàng sẽ được xác nhận. Nếu cần hỗ trợ, vui lòng bấm Gọi nhân viên.
    </div>

    <div class="status">
      Trạng thái: <strong><?php echo htmlspecialchars($order['payment_status']); ?></strong>
    </div>
  </div>
</body>
</html>