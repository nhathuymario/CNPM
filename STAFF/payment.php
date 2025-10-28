<?php
require_once '../functions/database.php';

// Lấy order_id từ GET
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) die('Thiếu order_id');

// 1. Lấy vietqr link mới nhất (theo created_at và id giảm dần)
$stmt = $conn->prepare("SELECT id, bank_code, account_no, template FROM vietqr_links ORDER BY created_at DESC, id DESC LIMIT 1");
$stmt->execute();
$vietqr = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$vietqr) die('Không tìm thấy vietqr_link');
$vietqr_id = $vietqr['id'];

// 2. Lấy số tiền từ đơn hàng
$stmt2 = $conn->prepare("SELECT total, table_number FROM orders WHERE id = ?");
$stmt2->bind_param("i", $order_id);
$stmt2->execute();
$order = $stmt2->get_result()->fetch_assoc();
$stmt2->close();
if (!$order) die('Không tìm thấy đơn hàng');
$amount = intval($order['total']);
$table_number = intval($order['table_number']);
if ($amount <= 0 || strlen("$amount") > 13) die('Số tiền không hợp lệ');

// 3. Sinh chuỗi đối soát (DESCRIPTION)
function randomReviewCode($length = 10) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; ++$i) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
$description = randomReviewCode(10);

// 4. Lưu vào bảng payment_review_codes
$stmt3 = $conn->prepare("INSERT INTO payment_review_codes (order_id, bank_account_id, review_code) VALUES (?, ?, ?)");
if (!$stmt3) die('Prepare failed (payment_review_codes): ' . $conn->error);
$stmt3->bind_param("iss", $order_id, $vietqr_id, $description);
$stmt3->execute();
$stmt3->close();

// 5. Ghép link QR
$qr_url = sprintf(
    'https://img.vietqr.io/image/%s-%s-%s.png?amount=%d&addInfo=%s',
    urlencode($vietqr['bank_code']),
    urlencode($vietqr['account_no']),
    urlencode($vietqr['template']),
    $amount,
    urlencode($description)
);

// 6. Xử lý nút "Thanh toán thành công"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paid'])) {
    // Update đơn hàng: mark là paid, set method là bank_transfer
    $stmt4 = $conn->prepare("UPDATE orders SET payment_status='paid', status='paid', payment_method='bank_transfer' WHERE id = ?");
    $stmt4->bind_param("i", $order_id);
    $stmt4->execute();
    $stmt4->close();

    // Update bàn về trống
    $stmt5 = $conn->prepare("UPDATE tables SET status='available' WHERE table_number = ?");
    $stmt5->bind_param("i", $table_number);
    $stmt5->execute();
    $stmt5->close();

    // Gửi signal về opener và đóng tab
    echo "<script>
    if (window.opener) {
        window.opener.postMessage({type:'staff-payment-success', order_id: $order_id}, '*');
        setTimeout(function(){ window.close(); }, 500);
    } else {
        window.location = 'index.php';
    }
    </script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>QR Thanh toán</title>
    <style>
  body { font-family: Arial, sans-serif; background: #f7f9fc; }
    .container { max-width: 420px; margin: 30px auto; background: #fff; border-radius: 10px; box-shadow: 0 0 6px #eee; padding: 24px; }
    img { max-width: 100%; }
    .qr-box { text-align: center; }
    .info { margin: 18px 0 8px 0; }
    .review-code { font-size: 18px; color: #0b72cf; font-weight: bold; }
    .amount { color: #e11d48; font-weight:bold; }
    .btn { display: inline-block; padding: 10px 20px; background: #0b72cf; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin: 12px 0; }
    .btn-back { background: #6c757d; margin-left: 12px; }
    .title-center { text-align: center; font-size: 2em; font-weight: bold; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="title-center">QR tự động</div>
    <div class="qr-box">
        <img src="<?php echo htmlspecialchars($qr_url); ?>" alt="QR chuyển khoản" width="300">
    </div>
    <div class="info">
        <p>Số tiền: <b class="amount"><?php echo number_format($amount); ?> đ</b></p>
        <p>Nội dung chuyển khoản: <b class="review-code"><?php echo htmlspecialchars($description); ?></b></p>
        <!-- <p>Link QR: <a href="<?php echo htmlspecialchars($qr_url); ?>" target="_blank">Mở ảnh QR</a></p> -->
    </div>
    <form method="post" style="display: flex; justify-content: center; gap: 12px; margin-top: 16px;">
    <button type="submit" name="paid" class="btn">Thanh toán thành công</button>
    <a href="javascript:window.close()" class="btn btn-back" style="text-decoration: none;">Quay lại</a>
</form>
</div>
</body>
</html>