<?php
    // Cấu hình kết nối database
    $host = "localhost";
    $username = "root";
    $password = "123456"; 
    // xóa pass là sql củ
    $database = "webnhahang";
    $port = 3307;

    // Kết nối MySQL với port chỉ định củ
    // $conn = new mysqli($host, $username, $password, $database, $port);
// Dòng 10
$conn = new mysqli('localhost', 'root', '123456', 'webnhahang'); 
                                                              // ^ Đảm bảo tên này khớp
    // Kiểm tra kết nối
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }
?>
