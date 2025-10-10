<?php
session_start();
require '../functions/database.php';

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ✅ Lấy thông tin đơn hàng hiện tại (từ session)
$order_items = isset($_SESSION['order']) ? $_SESSION['order'] : [];
if (empty($order_items)) {
    header("Location: ../order/index.php");
    exit();
}

// ✅ Tính tổng tiền
$total = 0;
foreach ($order_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// ✅ Khi người dùng chọn phương thức thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'];
    $table_number = 1; // sau này sẽ lấy từ QR hoặc session
    $items_json = json_encode($order_items, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare("INSERT INTO orders (table_number, items, total, payment_method, status)
                            VALUES (?, ?, ?, ?, 'paid')");
    $stmt->bind_param("isis", $table_number, $items_json, $total, $method);
    $stmt->execute();

    // ✅ Cập nhật bàn sang unavailable
    $conn->query("UPDATE tables SET status = 'unavailable' WHERE table_number = $table_number");

    // ✅ Xóa giỏ hàng session
    $_SESSION['order'] = [];

    $msg = "Thanh toán thành công bằng " . ($method == 'cash' ? "tiền mặt" : "chuyển khoản") . "!";
}

ob_start();
?>

<div class="payment-container">
    <h2>Thanh Toán Đơn Hàng</h2>

    <div class="payment-summary">
        <h3>Chi tiết đơn hàng</h3>
        <ul>
            <?php foreach ($order_items as $item): ?>
                <li>
                    <?php echo $item['name']; ?> x <?php echo $item['quantity']; ?> 
                    <span><?php echo number_format($item['price'] * $item['quantity']); ?> đ</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="total">
            <strong>Tổng cộng:</strong> <?php echo number_format($total); ?> đ
        </div>
    </div>

    <form class="payment-form" method="POST" onsubmit="return handlePayment(event)">
    <input type="hidden" name="table_id" value="<?php echo isset($_POST['table_id']) ? $_POST['table_id'] : 1; ?>">
    <input type="hidden" name="user_id" value="1">

    <label>Chọn phương thức thanh toán:</label>
    <div class="method-options">
        <label><input type="radio" name="payment_method" value="cash" required onclick="hideQR()"> Tiền mặt</label>
        <label><input type="radio" name="payment_method" value="bank_transfer" onclick="showQR()"> Chuyển khoản</label>
    </div>

    <div id="qr-popup" class="qr-popup">
        <div class="qr-content">
            <h3>Quét mã để thanh toán</h3>
            <img src="../assets/qr-demo.png" alt="QR Code" class="qr-image">
            <p>Ngân hàng: <strong>Vietcombank</strong></p>
            <p>Chủ TK: <strong>NHÀ HÀNG ABC</strong></p>
            <p>Số TK: <strong>0123456789</strong></p>
            <button type="button" class="close-btn" onclick="hideQR()">Đóng</button>
        </div>
    </div>

    <button type="submit" class="pay-btn">Xác nhận thanh toán</button>
</form>


    <?php if (isset($msg)): ?>
        <div class="msg success"><?php echo $msg; ?></div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include '../includes/master.php';
?>
