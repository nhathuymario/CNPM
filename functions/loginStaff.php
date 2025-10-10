<?php
session_start();
require 'database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE (username = ? OR email = ? OR phone = ?) AND password = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $username, $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['restaurant_id'] = $user['restaurant_id'];
        $_SESSION['role'] = $user['role']; // <--- QUAN TRỌNG
        header('Location: ../staff/index.php'); // hoặc về trang phù hợp
        exit();
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}
// session_start();
// require 'database.php'; // Kết nối database

// $error = '';

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $username = trim($_POST['username']);
//     $password = $_POST['password'];

//     // Truy vấn tài khoản theo username/email/phone và password plain text
//     $sql = "SELECT id, username, password, restaurant_id, role 
//             FROM users 
//             WHERE (username = ? OR email = ? OR phone = ?) AND password = ? 
//             LIMIT 1";
    
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("ssss", $username, $username, $username, $password);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $user = $result->fetch_assoc();

//     if ($user) {
//         // ✅ Chỉ set session sau khi tìm thấy user
//         $_SESSION['user_id'] = $user['id'];
//         $_SESSION['username'] = $user['username'];
//         $_SESSION['restaurant_id'] = $user['restaurant_id'];
//         $_SESSION['role'] = $user['role'];

//         // ✅ Điều hướng theo vai trò
//         switch ($user['role']) {
//             case 'admin':
//                 header('Location: ../admin/index.php');
//                 break;
//             case 'staff':
//                 header('Location: ../staff/index.php');
//                 break;
//             case 'customer':
//                 header('Location: ../order/index.php');
//                 break;
//             default:
//                 header('Location: ../index.php');
//         }
//         exit();
//     } else {
//         $error = "Sai tài khoản hoặc mật khẩu!";
//     }
// }
// ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập nhà hàng</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-bg">
        <form class="login-box" method="POST">
            <h2 class="login-title">ĐĂNG NHẬP</h2>

            <div class="input-row">
                <span class="icon"><img src="../assets/images/icon-user.png" alt="User"></span>
                <input type="text" name="username" placeholder="Tên đăng nhập/Email/SĐT" required>
            </div>

            <div class="input-row">
                <span class="icon"><img src="../assets/images/icon-key.png" alt="Key"></span>
                <input type="password" name="password" placeholder="Mật khẩu" required>
            </div>

            <?php if ($error): ?>
                <div class="error"><?= $error ?></div>
            <?php endif; ?>

            <button type="submit" class="login-btn">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
