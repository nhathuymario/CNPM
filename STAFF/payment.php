<?php
session_start();
// Tuỳ bạn muốn cho ai mở trang này. Nếu chỉ Staff:
if (!isset($_SESSION['user_id'])) {
  header("Location: ../functions/loginStaff.php");
  exit();
}
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

// Build SELECT tuỳ cột
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

// Lấy floor của bàn từ bảng tables
$stmt2 = $conn->prepare("SELECT floor FROM tables WHERE table_number = ?");
$stmt2->bind_param("i", $order['table_number']);
$stmt2->execute();
$res2 = $stmt2->get_result();
$table_info = $res2->fetch_assoc();
$floor = $table_info ? intval($table_info['floor']) : 1;

// Chuẩn hoá hiển thị trạng thái thanh toán
$payment_status = 'pending';
if ($hasPaymentStatus) {
    $payment_status = $order['payment_status'] ?? 'pending';
} elseif ($hasStatus) {
    $payment_status = (isset($order['status']) && $order['status'] === 'paid') ? 'paid' : 'pending';
}

// Mã tham chiếu
$ref_code = '';
if ($hasRefCode && !empty($order['ref_code'])) {
    $ref_code = $order['ref_code'];
} else {
    $ref_code = 'CF' . strtoupper(dechex($order['id'])) . '-' . date('d');
}

// Thông tin tài khoản nhận (có thể đưa vào config)
$BANK_ACCOUNT_NAME   = 'CALM SPACE';
$BANK_ACCOUNT_NUMBER = '0367903437';
$BANK_NAME           = 'VPBank';

// BASE_URL (nếu có)
$baseUrl = '/';
if (file_exists(__DIR__ . '/../includes/config.php')) {
    include __DIR__ . '/../includes/config.php';
    if (defined('BASE_URL')) $baseUrl = BASE_URL;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Thanh toán chuyển khoản</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>assets/css/payment.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    .btn{padding:8px 12px;border-radius:8px;border:1px solid #d0d7de;background:#fff;cursor:pointer}
    .btn-primary{background:#0b72cf;color:#fff;border-color:#0a66c2}
    .status-pill{display:inline-block;padding:2px 8px;border:1px solid #d0d7de;border-radius:999px;margin-left:6px}
  </style>
</head>
<body>
  <div class="payment-container">
    <h2>Thanh toán chuyển khoản cho đơn #<?php echo htmlspecialchars($order_id); ?></h2>
    <p>Tầng: <strong><?php echo htmlspecialchars($floor); ?></strong></p>
    <p>Bàn: <strong><?php echo htmlspecialchars($order['table_number']); ?></strong></p>
    <p>Số tiền: <strong><?php echo number_format($order['total']); ?> đ</strong></p>
    <p>Phương thức hiện tại: <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong></p>
    <hr>
    <h3>Thông tin chuyển khoản</h3>
    <p>Ngân hàng: <strong><?php echo htmlspecialchars($BANK_NAME); ?></strong></p>
    <p>Chủ TK: <strong><?php echo htmlspecialchars($BANK_ACCOUNT_NAME); ?></strong></p>
    <p>Số TK: <strong><?php echo htmlspecialchars($BANK_ACCOUNT_NUMBER); ?></strong></p>
    <p>Nội dung: <strong><?php echo htmlspecialchars($ref_code); ?></strong> (vui lòng ghi đúng để đối soát)</p>

    <div class="hint">
      Sau khi khách hàng chuyển khoản xong, hãy bấm “Xác nhận đã nhận tiền” để hoàn tất đơn và trả bàn về trống.
    </div>

    <div class="status" style="margin-top:10px">
      Trạng thái thanh toán:
      <strong id="pay-status"><?php echo htmlspecialchars($payment_status); ?></strong>
      <span class="status-pill" id="status-pill"><?php echo htmlspecialchars($payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'); ?></span>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px">
      <button class="btn btn-primary" id="btn-confirm-received">Xác nhận đã nhận tiền</button>
      <a class="btn" href="<?php echo htmlspecialchars($baseUrl); ?>STAFF/floor.php">Quay lại Sơ đồ</a>
    </div>
  </div>

  <script>
    (function(){
      const btn = document.getElementById('btn-confirm-received');
      const statusEl = document.getElementById('pay-status');
      const pillEl = document.getElementById('status-pill');

      if (btn) {
        btn.addEventListener('click', async () => {
          btn.disabled = true;
          try {
            const resp = await fetch('../functions/staff_order_api.php?action=mark_paid', {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ order_id: <?php echo (int)$order_id; ?> })
            });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'Xác nhận thất bại');

            // Cập nhật UI
            statusEl.textContent = 'paid';
            pillEl.textContent = 'Đã thanh toán';
            alert('Đã xác nhận tiền chuyển khoản. Bàn đã được trả về trống.');
          } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
          } finally {
            btn.disabled = false;
          }
        });
      }
    })();
  </script>
</body>
</html>