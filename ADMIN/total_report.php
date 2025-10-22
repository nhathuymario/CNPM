<?php
include '../functions/database.php'; // Đổi path tùy cấu trúc project của bạn

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






// Xử lý lọc ngày/tháng/năm
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');

// Truy vấn lấy order theo ngày, gộp group by ngày
$sql = "
    SELECT 
        DATE(created_at) AS order_date,
        COUNT(*) AS num_orders,
        SUM(total) AS total_money
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY order_date
    ORDER BY order_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy chi tiết từng order trong khoảng để hiển thị (chi tiết theo từng món)
$sql2 = "
    SELECT 
        o.id, o.created_at, o.table_number, o.total, o.items
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at DESC
";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('ss', $from, $to);
$stmt2->execute();
$orderDetails = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

function parse_items($json) {
    $items = json_decode($json, true);
    if (!$items) return [];
    $result = [];
    foreach ($items as $item) {
        // mảng số lượng theo id món
        $iid = $item['id'];
        if (!isset($result[$iid])) {
            $result[$iid] = $item;
        } else {
            $result[$iid]['quantity'] += $item['quantity'];
        }
    }
    return $result;
}
ob_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo Tổng ca</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/total_report.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
</head>
<body>
<div class="report-container">
    <h2>Báo cáo tổng ca</h2>
    <form class="filter-form" method="get">
        <label>Từ ngày: <input type="date" name="from" value="<?=htmlspecialchars($from)?>"></label>
        <label>Đến ngày: <input type="date" name="to" value="<?=htmlspecialchars($to)?>"></label>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Lọc</button>
    </form>
    <h3>Tổng hợp theo ngày</h3>
    <table class="total-table">
        <tr>
            <th>Ngày</th>
            <th>Số đơn hàng</th>
            <th>Tổng tiền</th>
        </tr>
        <?php foreach($report as $row): ?>
        <tr>
            <td><?=htmlspecialchars($row['order_date'])?></td>
            <td><?=$row['num_orders']?></td>
            <td><?=number_format($row['total_money'])?> đ</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Chi tiết đơn hàng</h3>
    <table class="details-table">
        <tr>
            <th>Thời gian</th>
            <th>Bàn</th>
            <th>Chi tiết món</th>
            <th>Tổng tiền</th>
        </tr>
        <?php foreach($orderDetails as $order):
            $items = parse_items($order['items']);
        ?>
        <tr>
            <td><?=date('H:i d/m/Y', strtotime($order['created_at']))?></td>
            <td><?=$order['table_number']?></td>
            <td>
                <ul class="dish-list">
                <?php foreach($items as $item): ?>
                    <li>
                        <span class="dish-name"><?=$item['name']?></span>
                        <span class="dish-qty">x<?=$item['quantity']?></span>
                        <span class="dish-price"><?=number_format($item['price'])?>đ</span>
                    </li>
                <?php endforeach; ?>
                </ul>
            </td>
            <td><?=number_format($order['total'])?> đ</td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>

<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>