<?php
// session_start();
// require 'database.php';

// $error = '';
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $username = trim($_POST['username']);
//     $password = $_POST['password'];

//     $sql = "SELECT * FROM users WHERE (username = ? OR email = ? OR phone = ?) AND password = ? LIMIT 1";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("ssss", $username, $username, $username, $password);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $user = $result->fetch_assoc();

//     if ($user) {
//         $_SESSION['user_id'] = $user['id'];
//         $_SESSION['username'] = $user['username'];
//         $_SESSION['restaurant_id'] = $user['restaurant_id'];
//         $_SESSION['role'] = $user['role']; // <--- QUAN TRỌNG
//         header('Location: ../staff/index.php'); // hoặc về trang phù hợp
//         exit();
//     } else {
//         $error = "Sai tài khoản hoặc mật khẩu!";
//     }
// }



session_start();
require 'database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    // KHÔNG trim password nếu bạn muốn cho phép khoảng trắng đầu/cuối là hợp lệ
    $password = $_POST['password'];

    // Lấy user theo username/email/phone (KHÔNG so sánh password ở đây)
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ? OR phone = ?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Lấy giá trị password từ DB (trim để loại bỏ whitespace vô ý)
        $dbPass = $user['password'];
        $dbPassTrim = trim($dbPass);

        // Kiểm tra xem password trong DB đã là hash hay chưa
        $isHashed = (strpos($dbPassTrim, '$2y$') === 0) ||
                    (strpos($dbPassTrim, '$2a$') === 0) ||
                    (strpos($dbPassTrim, '$argon2') === 0);

        if (!$isHashed) {
            // Nếu DB chứa plaintext (ví dụ "1-6"), kiểm tra trực tiếp
            if ($password === $dbPassTrim) {
                // Hash lại mật khẩu và cập nhật DB
                $newHash = password_hash($password, PASSWORD_DEFAULT);

                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $newHash, $user['id']);
                $upd->execute();
                // Cập nhật biến user để password_verify có thể dùng tiếp
                $user['password'] = $newHash;
            } else {
                // plaintext không khớp -> lỗi đăng nhập
                $error = "Sai tài khoản hoặc mật khẩu!";
            }
        }

        // Nếu đã là hash (hoặc vừa update thành hash), kiểm tra bằng password_verify
        if (empty($error) && password_verify($password, $user['password'])) {
            // Đăng nhập thành công
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['restaurant_id'] = $user['restaurant_id'];
            $_SESSION['role'] = $user['role'];
        
            // ✅ PHÂN QUYỀN: kiểm tra role và điều hướng tương ứng
            if (in_array($user['role'], ['admin', 'staff'])) {
                header('Location: ../staff/index.php');
                exit();
            } else {
                session_destroy();
                exit("Bạn không có quyền truy cập vào khu vực này!");
            }
        }
        
        elseif (empty($error)) {
            // Nếu chưa set $error nhưng password_verify fail
            $error = "Sai tài khoản hoặc mật khẩu!";
        }
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}

?>
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
