<?php
require '../functions/database.php';

if (isset($_POST['done'])) {
    $order_id = intval($_POST['id']);

    // Lấy thông tin đơn
    $stmt = $conn->prepare("SELECT table_number, payment_method FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    if (!$order) {
        header("Location: index.php");
        exit();
    }

    $table_number = intval($order['table_number']);

    // Cập nhật trạng thái đơn: 'paid' (phù hợp enum hiện có)
    // Nếu tiền mặt, đánh dấu đã thanh toán
    if ($order['payment_method'] === 'cash') {
        $stmt1 = $conn->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid' WHERE id = ?");
        $stmt1->bind_param("i", $order_id);
        $stmt1->execute();
    } else {
        // Chuyển khoản: chỉ chuyển trạng thái order nếu bạn muốn xác nhận thủ công
        $stmt1 = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $stmt1->bind_param("i", $order_id);
        $stmt1->execute();
    }

    // Cho phép bàn đặt tiếp
    $stmt2 = $conn->prepare("UPDATE tables SET status = 'available' WHERE table_number = ?");
    $stmt2->bind_param("i", $table_number);
    $stmt2->execute();

    header("Location: index.php");
    exit();
}