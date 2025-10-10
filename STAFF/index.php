<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/loginStaff.php");
    exit();
}

// // Kiểm tra quyền admin hoặc staff
// if ($_SESSION['role'] != 'staff' && $_SESSION['role'] != 'admin') {
//     die("Bạn không có quyền truy cập trang này!");
// }
// Kiểm tra quyền admin hoặc staff
if ($_SESSION['role'] != 'staff') {
    header("Location: ../functions/loginStaff.php");
    die("Bạn không có quyền truy cập trang này!");
}

// Lấy thông tin user từ session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$restaurant_id = $_SESSION['restaurant_id'];



require '../functions/database.php';
ob_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="../assets/css/staff.css">
</head>
<body>
    <h2>👨‍🍳 Danh sách đơn hàng đang chờ xử lý</h2>
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
        $orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
        while ($order = $orders->fetch_assoc()):
        ?>
        <tr>
            <td><?php echo $order['id']; ?></td>
            <td><?php echo $order['table_number']; ?></td>
            <td><?php echo htmlspecialchars($order['items']); ?></td>
            <td><?php echo number_format($order['total']); ?>đ</td>
            <td><?php echo $order['payment_method']; ?></td>
            <td><?php echo $order['status']; ?></td>
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
?>