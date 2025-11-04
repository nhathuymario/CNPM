<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/loginStaff.php");
    exit();
}

require '../functions/checkloginStaff.php';
checkRole(['admin', 'staff']);
require '../functions/database.php';
$user_id = $_SESSION['user_id'];
ob_start();

// 1. Xác định ca hiện tại (chưa kết thúc) hoặc tạo mới nếu chưa có
$ca = $conn->query("SELECT * FROM shifts WHERE user_id = $user_id AND end_time IS NULL ORDER BY start_time DESC LIMIT 1")->fetch_assoc();
if (!$ca) {
    // Tạo mới ca khi vào trang lần đầu
    $conn->query("INSERT INTO shifts (user_id, start_time) VALUES ($user_id, NOW())");
    $ca = $conn->query("SELECT * FROM shifts WHERE user_id = $user_id AND end_time IS NULL ORDER BY start_time DESC LIMIT 1")->fetch_assoc();
}

// 2. Xử lý khi kết ca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_shift'])) {
    $shift_id = intval($ca['id']);
    $start_time = $ca['start_time'];
    // Đếm số đơn hàng trong ca này
    $orders_count = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE ordered_at >= '$start_time' AND user_id = $user_id")->fetch_assoc()['c'];
    // Kết thúc ca
    $conn->query("UPDATE shifts SET end_time=NOW(), orders_count=$orders_count WHERE id=$shift_id");
    // Có thể thông báo hoặc redirect, ở đây reload lại trang
    header("Location: ".$_SERVER['PHP_SELF']);
    // Đăng xuất tài khoản bằng cách hủy session
    session_unset();
    session_destroy();

    // Chuyển hướng về trang đăng nhập (sửa login.php thành đúng file của bạn)
    header("Location: ../functions/loginStaff.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="../assets/css/staff.css">
    <meta http-equiv="refresh" content="10">
    <style>
        .shift-actions { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .btn-shift { background: #e11d48; color: #fff; border: none; border-radius: 8px; padding: 10px 22px; font-size: 1.08em; font-weight: bold; cursor: pointer; }
        .shift-info { float: left; color: #1976d2; font-size: 1.07em; margin-bottom: 14px; }
        @media (max-width:650px) {
            .shift-actions { flex-direction: column; align-items: stretch; }
            .btn-shift { width: 100%; margin-top: 10px; }
        }
    </style>
</head>
<body>
    <h2 style="display:flex;justify-content:space-between;align-items:center;">
        <span>👨‍🍳 Danh sách đơn hàng</span>
    </h2>
    <div class="shift-actions">
        <div class="shift-info">
            Ca bắt đầu: <b><?=date('d/m/Y H:i:s', strtotime($ca['start_time']))?></b>
        </div>
        <form method="post" style="margin-left: auto;">
            <button type="submit" name="end_shift" class="btn-shift" onclick="return confirm('Kết thúc ca? Sau khi kết ca sẽ không thể thêm đơn vào ca này!')" >Kết ca</button>
        </form>
    </div>
    <div class="order-table-scroll">
    <table border="1" cellpadding="10" cellspacing="0">
        <tr style="background:#1976d2;color:white;">
            <th>ID</th>
            <th>Bàn</th>
            <th>Món</th>
            <th>Tổng</th>
            <th>Thanh toán</th>
            <th>Trạng thái</th>
            <th>Thời gian</th>
            <th>Hành động</th>
        </tr>
        <?php
        // Hiện 20 đơn mới nhất trong ca này
        $orders = $conn->query("SELECT id, table_number, items, total, payment_method, payment_status, status, ordered_at FROM orders WHERE ordered_at >= '{$ca['start_time']}' AND user_id = $user_id ORDER BY id DESC LIMIT 20");
        while ($order = $orders->fetch_assoc()):
            $items = json_decode($order['items'], true) ?: [];
        ?>
        <tr>
            <td><?php echo $order['id']; ?></td>
            <td><?php echo $order['table_number']; ?></td>
            <td>
                <ul style="margin:0;padding-left:16px;">
                    <?php foreach ($items as $it): ?>
                        <li><?php echo htmlspecialchars($it['name']); ?> x <?php echo intval($it['quantity']); ?> — <?php echo number_format($it['price'] * $it['quantity']); ?>đ</li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td><?php echo number_format($order['total']); ?>đ</td>
            <td><?php echo htmlspecialchars($order['payment_method']); ?> (<?php echo htmlspecialchars($order['payment_status']); ?>)</td>
            <td><?php echo htmlspecialchars($order['status']); ?></td>
            <td>
                <?php
                $time = strtotime($order['ordered_at']);
                echo date('d/m/Y H:i:s', $time);
                ?>
            </td>
            <td>
                <?php if ($order['status'] == 'pending'): ?>
                    <form method="post" action="update_status.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                        <span style="color: #45a049; font-weight: bold;">Đang</span>
                    </form>
                <?php else: ?>
                    <span style="color: #45a049; font-weight: bold;">Đã xong</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>
</body>
</html>

<?php
$content = ob_get_clean();
include '../includes/masterStaff.php';