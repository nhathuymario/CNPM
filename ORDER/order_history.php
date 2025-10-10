<?php
$title = "Lịch sử đơn hàng";
session_start();
require '../functions/database.php';

// ✅ Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/login.php");
    exit();
}

// ✅ Lấy ID user hiện tại
$user_id = $_SESSION['user_id'];

// ✅ Truy vấn danh sách đơn hàng của user
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $row['items'] = json_decode($row['items'], true);
    $orders[] = $row;
}

ob_start();
?>
<div class="history-container">
    <h2>Lịch sử đơn hàng</h2>

    <?php if (count($orders) === 0): ?>
        <p>Bạn chưa có đơn hàng nào.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-id">Đơn #<?php echo $order['id']; ?></span>
                    <span class="order-date"><?php echo date("d/m/Y H:i", strtotime($order['created_at'])); ?></span>
                </div>
                <div class="order-body">
                    <ul>
                        <?php foreach ($order['items'] as $item): ?>
                            <li>
                                <?php echo $item['name']; ?> x <?php echo $item['quantity']; ?> — 
                                <?php echo number_format($item['price'] * $item['quantity']); ?>đ
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="order-total">Tổng: <strong><?php echo number_format($order['total']); ?>đ</strong></p>
                </div>
                <div class="order-footer">
                    <span class="status <?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                    <span class="method"><?php echo ucfirst($order['payment_method']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include '../includes/master.php';
?>
