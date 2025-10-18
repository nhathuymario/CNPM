<?php
    // Cấu hình kết nối database
    $host = "localhost";
    $username = "root";
    $password = "";
    $database = "webnhahang";
    $port = 3306;

    // Kết nối MySQL với port chỉ định
    $conn = new mysqli($host, $username, $password, $database, $port);

    // Kiểm tra kết nối
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }
?>
