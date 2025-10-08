<?php
session_start();
require '../functions/database.php'; // Đường dẫn database

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Truy vấn tài khoản theo username/email/phone và password plain text
    $sql = "SELECT id, username, password, restaurant_id FROM users
            WHERE (username = ? OR email = ? OR phone = ?) AND password = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $username, $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['restaurant_id'] = $user['restaurant_id'];
        header('Location: ../index.php'); // Chuyển về index ở gốc
        exit();
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Đăng nhập nhà hàng</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-bg">
        <form class="login-box" method="POST">
            <h2 class="login-title">ĐĂNG NHẬP</h2>
            <div class="input-row">
                <span class="icon"><img src="../assets/images/icon-user.png"></span>
                <input type="text" name="username" placeholder="Tên đăng nhập/Email/SĐT" required>
            </div>
            <div class="input-row">
                <span class="icon"><img src="../assets/images/icon-key.png"></span>
                <input type="password" name="password" placeholder="Mật khẩu" required>
            </div>
            <?php if($error) echo '<div class="error">'.$error.'</div>'; ?>
            <button type="submit" class="login-btn">Đăng nhập</button>
        </form>
    </div>
</body>
</html>