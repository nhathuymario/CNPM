<?php
require '../functions/database.php';

if (isset($_POST['done'])) {
    $order_id = intval($_POST['id']);

    // Lấy số bàn của đơn hàng
    $res = $conn->query("SELECT table_number FROM orders WHERE id = $order_id");
    $order = $res->fetch_assoc();
    $table_number = $order['table_number'];

    // Cập nhật trạng thái đơn
    $conn->query("UPDATE orders SET status = 'done' WHERE id = $order_id");

    // Cho phép bàn đặt tiếp
    $conn->query("UPDATE tables SET status = 'available' WHERE table_number = $table_number");

    header("Location: index.php");
    exit();
}
?>
