<?php
include '../functions/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/login.php");
    exit();
}
// Kiểm tra quyền admin hoặc staff
// if ($_SESSION['role'] != 'admin') {
//     die("Bạn không có quyền truy cập trang này!");
// }

// Lấy thông tin user từ session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$restaurant_id = $_SESSION['restaurant_id'];

require '../functions/checkloginAdmin.php';
checkRole(['admin']);


// Lấy tất cả bản ghi đã xóa
$sql = "SELECT * FROM order_item_deletions ORDER BY deleted_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn đã xóa</title>
    <link rel="stylesheet" href="../assets/css/order_deletions.css">
</head>
<div class="main-content">
    <h2>Quản lý các đơn đã xóa</h2>
    <table class="deleted-orders-table">
        <tr>
            <th>ID</th>
            <th>Order ID</th>
            <th>Bàn</th>
            <th>Món</th>
            <th>Số lượng</th>
            <th>Giá</th>
            <th>Tổng tiền</th>
            <th>Lý do xóa</th>
            <th>Người xóa (DB User)</th>
            <th>Xóa lúc</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): 
            while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['order_id'] ?></td>
            <td><?= $row['table_number'] ?></td>
            <td>
                <?= htmlspecialchars($row['dish_name']) ?>
                <?php if($row['dish_id']): ?>
                <br><small>ID: <?= $row['dish_id'] ?></small>
                <?php endif; ?>
            </td>
            <td><?= $row['quantity'] ?></td>
            <td><?= number_format($row['price']) ?>₫</td>
            <td><?= number_format($row['line_total']) ?>₫</td>
            <td><?= htmlspecialchars($row['deleted_reason']) ?></td>
            <td><?= htmlspecialchars($row['deleted_by_db_user']) ?></td>
            <td><?= $row['deleted_at'] ?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="10">Không có đơn đã xóa nào!</td></tr>
        <?php endif; ?>
    </table>
</html>
<?php $conn->close(); ?>
<?php

$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>