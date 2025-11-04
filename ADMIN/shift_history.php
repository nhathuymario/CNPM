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

// Lấy danh sách ca đã kết thúc (có end_time)
$sql = "SELECT shifts.*, users.username 
        FROM shifts INNER JOIN users ON shifts.user_id = users.id
        WHERE end_time IS NOT NULL
        ORDER BY shifts.end_time DESC";
$result = $conn->query($sql);
if (!$result) {
    echo "Query failed: " . $conn->error;
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Lịch sử kết ca</title>
    <style>
        table { border-collapse: collapse; margin: 20px auto; width: 90%; }
        th, td { border: 1px solid #334; padding: 8px 12px; text-align: center; }
        th { background: #aab; }
        tr:nth-child(even) { background: #eef2f8; }
    </style>
</head>
<body>
    <h2 style="text-align: center;">Lịch sử kết ca nhân viên</h2>
    <table>
        <thead>
            <tr>
                <th>ID Ca</th>
                <th>Thời gian bắt đầu</th>
                <th>Thời gian kết thúc</th>
                <th>Số đơn hoàn thành</th>
                <th>Ngày tạo bản ghi</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['start_time']; ?></td>
                <td><?php echo $row['end_time']; ?></td>
                <td><?php echo $row['orders_count']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>