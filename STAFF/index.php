<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/loginStaff.php");
    exit();
}

require '../functions/checkloginStaff.php';
checkRole(['admin', 'staff']);
require '../functions/database.php';
ob_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="../assets/css/staff.css">
    <meta http-equiv="refresh" content="10"> <!-- Tự refresh 10s -->
</head>
<body>
    <h2>👨‍🍳 Danh sách đơn hàng</h2>
    <table border="1" cellpadding="10" cellspacing="0">
        <tr style="background:#1976d2;color:white;">
            <th>ID</th>
            <th>Bàn</th>
            <th>Món</th>
            <th>Tổng</th>
            <th>Thanh toán</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
        <?php
        $orders = $conn->query("SELECT id, table_number, items, total, payment_method, payment_status, status FROM orders ORDER BY id DESC");
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
                <?php if ($order['status'] == 'pending'): ?>
                    <form method="post" action="update_status.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
                        <button type="submit" name="done">✅ Hoàn tất</button>
                    </form>
                <?php else: ?>
                    ✅ Đã xong
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
$content = ob_get_clean();
include '../includes/masterStaff.php';