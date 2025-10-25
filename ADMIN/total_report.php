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

// Xử lý lọc ngày/tháng/năm và phương thức thanh toán
$from = $_GET['from'] ?? date('Y-m-d');
$to   = $_GET['to']   ?? date('Y-m-d');
$payment = $_GET['payment'] ?? 'all'; // 'all' | 'cash' | 'bank_transfer'
$payment = in_array($payment, ['all','cash','bank_transfer'], true) ? $payment : 'all';

// Build WHERE fragment for payment filter when needed
$paymentSql = '';
if ($payment !== 'all') {
    $paymentSql = " AND payment_method = ?";
}

// --- Tổng hợp theo ngày (áp dụng filter payment nếu có) ---
$sql = "
    SELECT 
        DATE(created_at) AS order_date,
        COUNT(*) AS num_orders,
        SUM(total) AS total_money
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ? " . $paymentSql . "
    GROUP BY order_date
    ORDER BY order_date DESC
";

if ($payment !== 'all') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $from, $to, $payment);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $from, $to);
}
$stmt->execute();
$report = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Chi tiết từng order trong khoảng để hiển thị (áp dụng filter payment nếu có) ---
$sql2 = "
    SELECT 
        o.id, o.created_at, o.table_number, o.total, o.items, o.payment_method
    FROM orders o
    WHERE DATE(o.created_at) BETWEEN ? AND ? " . $paymentSql . "
    ORDER BY o.created_at DESC
";

if ($payment !== 'all') {
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param('sss', $from, $to, $payment);
} else {
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param('ss', $from, $to);
}
$stmt2->execute();
$orderDetails = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// --- Thống kê theo phương thức thanh toán trong khoảng (để hiển thị hai ô Tiền mặt / Chuyển khoản) ---
$sql3 = "
    SELECT payment_method, COUNT(*) AS cnt, IFNULL(SUM(total),0) AS sum_total
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method
";
$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param('ss', $from, $to);
$stmt3->execute();
$payStatsRaw = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt3->close();

// normalize stats
$payStats = [
    'cash' => ['cnt'=>0,'sum_total'=>0],
    'bank_transfer' => ['cnt'=>0,'sum_total'=>0],
];
foreach ($payStatsRaw as $r) {
    $pm = $r['payment_method'];
    if (!isset($payStats[$pm])) continue;
    $payStats[$pm]['cnt'] = (int)$r['cnt'];
    $payStats[$pm]['sum_total'] = (float)$r['sum_total'];
}

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
    <form class="filter-form" method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <label>Từ ngày: <input type="date" name="from" value="<?=htmlspecialchars($from)?>"></label>
        <label>Đến ngày: <input type="date" name="to" value="<?=htmlspecialchars($to)?>"></label>
        <input type="hidden" name="payment" id="hidden-payment" value="<?=htmlspecialchars($payment)?>" />
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Lọc</button>
        <a href="?from=<?=htmlspecialchars($from)?>&to=<?=htmlspecialchars($to)?>&payment=all" class="btn" style="margin-left:8px">Bỏ chọn PTTT</a>
    </form>

    <!-- Payment filter boxes -->
    <div class="payment-filters" role="tablist" aria-label="Bộ lọc phương thức thanh toán">
      <a href="?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&payment=cash" class="pay-box <?= $payment==='cash' ? 'active' : '' ?>" title="Lọc tiền mặt">
        <span class="pay-icon">💵</span>
        <span class="label">Tiền mặt</span>
        <span class="count"><?= (int)$payStats['cash']['cnt'] ?></span>
        <span class="amount"><?= number_format($payStats['cash']['sum_total']) ?> đ</span>
      </a>

      <a href="?from=<?=urlencode($from)?>&to=<?=urlencode($to)?>&payment=bank_transfer" class="pay-box <?= $payment==='bank_transfer' ? 'active' : '' ?>" title="Lọc chuyển khoản">
        <span class="pay-icon">💳</span>
        <span class="label">Chuyển khoản</span>
        <span class="count"><?= (int)$payStats['bank_transfer']['cnt'] ?></span>
        <span class="amount"><?= number_format($payStats['bank_transfer']['sum_total']) ?> đ</span>
      </a>
    </div>

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
            <th>PTTT</th>
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
            <td><?= htmlspecialchars( $order['payment_method'] === 'cash' ? 'Tiền mặt' : ($order['payment_method']==='bank_transfer' ? 'Chuyển khoản' : $order['payment_method']) ) ?></td>
            <td><?=number_format($order['total'])?> đ</td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
  // đồng bộ hidden input payment khi click vào ô (nếu cần client side)
  document.querySelectorAll('.pay-box').forEach(function(el){
    el.addEventListener('click', function(e){
      // anchor sẽ điều hướng - giữ hành vi mặc định
    });
  });
</script>
</body>
</html>

<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>