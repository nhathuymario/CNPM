<?php
include '../functions/database.php';

// Xử lý thêm bàn
if (isset($_POST['add'])) {
    $number = intval($_POST['table_number']);
    $floor = intval($_POST['floor']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];

    // Chèn bàn mới, tạm thời để qr_secret là rỗng
    $stmt = $conn->prepare("INSERT INTO tables (table_number, floor, qr_secret, capacity, status) VALUES (?, ?, '', ?, ?)");
    $stmt->bind_param("iiis", $number, $floor, $capacity, $status);
    $stmt->execute();

    // Lấy id vừa thêm
    $last_id = $conn->insert_id;
    $qr_secret = 'TBL' . str_pad($last_id, 3, '0', STR_PAD_LEFT);

    // Cập nhật lại qr_secret
    $stmt2 = $conn->prepare("UPDATE tables SET qr_secret=? WHERE id=?");
    $stmt2->bind_param("si", $qr_secret, $last_id);
    $stmt2->execute();

    header("Location: table.php");
    exit();
}

// Xử lý cập nhật bàn
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $number = intval($_POST['table_number']);
    $floor = intval($_POST['floor']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];

    // KHÔNG cho sửa qr_secret
    $stmt = $conn->prepare("UPDATE tables SET table_number=?, floor=?, capacity=?, status=? WHERE id=?");
    $stmt->bind_param("iiisi", $number, $floor, $capacity, $status, $id);
    $stmt->execute();

    header("Location: table.php");
    exit();
}
?>