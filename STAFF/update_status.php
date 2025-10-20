<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}
require '../functions/checkloginStaff.php';
checkRole(['admin','staff']);
require '../functions/database.php';

if (isset($_POST['done'])) {
    $order_id = intval($_POST['id']);

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

    if ($order['payment_method'] === 'cash') {
        $stmt1 = $conn->prepare("UPDATE orders SET status = 'paid', payment_status = 'paid' WHERE id = ?");
        $stmt1->bind_param("i", $order_id);
        $stmt1->execute();
    } else {
        $stmt1 = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $stmt1->bind_param("i", $order_id);
        $stmt1->execute();
    }

    $stmt2 = $conn->prepare("UPDATE tables SET status = 'available' WHERE table_number = ?");
    $stmt2->bind_param("i", $table_number);
    $stmt2->execute();

    header("Location: index.php");
    exit();
}