<?php
include '../functions/database.php';

// Xử lý thêm bàn
if (isset($_POST['add'])) {
    $number = intval($_POST['table_number']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("INSERT INTO tables (table_number, capacity, status) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $number, $capacity, $status);
    $stmt->execute();
    header("Location: table.php");
    exit();
}

// Xử lý cập nhật bàn
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $number = intval($_POST['table_number']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE tables SET table_number=?, capacity=?, status=? WHERE id=?");
    $stmt->bind_param("iisi", $number, $capacity, $status, $id);
    $stmt->execute();
    header("Location: table.php");
    exit();
}
?>