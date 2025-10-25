<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../functions/loginStaff.php");
  exit();
}
require_once '../functions/database.php';

// Lấy order_id
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) { http_response_code(400); die('Thiếu order_id'); }

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
if (!$order) { http_response_code(404); die('Không tìm thấy đơn hàng'); }

// Lấy floor của bàn
$stmt2 = $conn->prepare("SELECT floor FROM tables WHERE table_number = ?");
$stmt2->bind_param("i", $order['table_number']);
$stmt2->execute();
$res2 = $stmt2->get_result();
$table_info = $res2->fetch_assoc();
$floor = $table_info ? intval($table_info['floor']) : 1;

// Chuẩn hoá trạng thái thanh toán
$payment_status = 'pending';
if ($hasPaymentStatus) $payment_status = $order['payment_status'] ?? 'pending';
elseif ($hasStatus)     $payment_status = (isset($order['status']) && $order['status'] === 'paid') ? 'paid' : 'pending';
$isPaid = ($payment_status === 'paid');

// Mã tham chiếu
$ref_code = ($hasRefCode && !empty($order['ref_code'])) ? $order['ref_code'] : ('CF' . strtoupper(dechex($order['id'])) . '-' . date('d'));

// Thông tin tài khoản nhận
$BANK_ACCOUNT_NAME   = 'CALM SPACE';
$BANK_ACCOUNT_NUMBER = '0367903437';
$BANK_NAME           = 'VPBank';

// BASE_URL
$baseUrl = '/';
if (file_exists(__DIR__ . '/../includes/config.php')) {
  include __DIR__ . '/../includes/config.php';
  if (defined('BASE_URL')) $baseUrl = BASE_URL;
}
$baseUrl = rtrim($baseUrl, '/') . '/';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <title>Thanh toán chuyển khoản</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($baseUrl); ?>assets/css/payment.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { background:#f7f9fc; }
    .payment-container{ max-width:680px; margin:20px auto; background:#fff; border:1px solid #e6e8ee; border-radius:12px; padding:18px 20px; }
    .btn{padding:8px 12px;border-radius:8px;border:1px solid #d0d7de;background:#fff;cursor:pointer}
    .btn-primary{background:#0b72cf;color:#fff;border-color:#0a66c2}
    .status-pill{display:inline-block;padding:2px 8px;border:1px solid #d0d7de;border-radius:999px;margin-left:6px}
    .hint{margin:10px 0; color:#5b6574; background:#f9fbff; border:1px dashed #cfe0ff; padding:8px 10px; border-radius:8px;}
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

    <div class="hint">Sau khi khách chuyển khoản xong, bấm “Xác nhận đã nhận tiền” để hoàn tất (lưu payment_method=bank).</div>

    <div class="status" style="margin-top:10px">
      Trạng thái thanh toán:
      <strong id="pay-status"><?php echo htmlspecialchars($payment_status); ?></strong>
      <span class="status-pill" id="status-pill"><?php echo htmlspecialchars($isPaid ? 'Đã thanh toán' : 'Chưa thanh toán'); ?></span>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px">
      <button class="btn btn-primary" id="btn-confirm-received" <?php echo $isPaid ? 'disabled' : ''; ?>>
        <?php echo $isPaid ? 'Đã thanh toán' : 'Xác nhận đã nhận tiền'; ?>
      </button>
      <a class="btn" id="btn-back" href="<?php echo htmlspecialchars($baseUrl); ?>STAFF/floor.php">Quay lại Sơ đồ</a>
    </div>
  </div>

  <script>
    (function(){
      const ORDER_ID = <?php echo (int)$order_id; ?>;
      const BASE_URL = <?php echo json_encode($baseUrl); ?>;

      const btnConfirm = document.getElementById('btn-confirm-received');
      const btnBack    = document.getElementById('btn-back');
      const statusEl   = document.getElementById('pay-status');
      const pillEl     = document.getElementById('status-pill');

      if (btnBack) {
        btnBack.addEventListener('click', function(e){
          try {
            if (window.opener && !window.opener.closed) {
              e.preventDefault(); window.close();
              setTimeout(()=>{ location.href = BASE_URL + 'STAFF/floor.php'; }, 400);
            }
          } catch (err) {
            e.preventDefault(); location.href = BASE_URL + 'STAFF/floor.php';
          }
        });
      }

      if (btnConfirm) {
        btnConfirm.addEventListener('click', async () => {
          btnConfirm.disabled = true;
          try {
            // Gửi cả body và query để fallback chắc chắn
            const url = `../functions/staff_order_api.php?action=mark_paid&order_id=${ORDER_ID}&method=bank`;
            const resp = await fetch(url, {
              method: 'POST',
              headers: {'Content-Type': 'application/json'},
              body: JSON.stringify({ order_id: ORDER_ID, method: 'bank' })
            });
            const ct = (resp.headers.get('content-type') || '').toLowerCase();
            const data = ct.includes('application/json') ? await resp.json() : { success:false, message: await resp.text() };
            if (!data.success) throw new Error(data.message || 'Xác nhận thất bại');

            statusEl.textContent = 'paid';
            pillEl.textContent   = 'Đã thanh toán';
            btnConfirm.textContent = 'Đã thanh toán';
            console.log('applied_payment_method:', data.applied_payment_method);

            let notified = false;
            try { if (window.opener && !window.opener.closed) { window.opener.postMessage({ type:'staff-payment-success', order_id: ORDER_ID }, '*'); notified = true; } } catch (e) {}
            if (notified) { window.close(); setTimeout(()=>{ location.href = BASE_URL + 'STAFF/floor.php'; }, 500); }
            else { location.href = BASE_URL + 'STAFF/floor.php'; }
          } catch (e) {
            alert(e.message || 'Có lỗi xảy ra.');
            btnConfirm.disabled = false;
          }
        });
      }
    })();
  </script>
</body>
</html>