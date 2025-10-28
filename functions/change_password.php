<?php
// Trang đổi mật khẩu cho admin
// Đặt file này trong thư mục ADMIN (ví dụ: /path/to/your/project/ADMIN/change_password.php)
// Nếu database.php của bạn không nằm ở ../database.php thì hãy chỉnh lại đường dẫn trong require bên dưới.
session_start();
require 'database.php'; // <- chỉnh đường dẫn nếu cần

// Kiểm tra đã đăng nhập và là admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Không phải admin -> đưa về trang login hoặc báo lỗi
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// CSRF token đơn giản
if (empty($_SESSION['csrf_change_pass'])) {
    $_SESSION['csrf_change_pass'] = bin2hex(random_bytes(24));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_change_pass'], $_POST['csrf'])) {
        $error = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    } else {
        $current = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Kiểm tra cơ bản
        if ($new !== $confirm) {
            $error = 'Mật khẩu mới và xác nhận mật khẩu không khớp.';
        } elseif (strlen($new) < 8) {
            $error = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
        } else {
            // Lấy mật khẩu hiện tại từ DB
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $dbPass = trim($row['password']);

                // Xác định xem DB đang lưu hash hay plaintext (tương tự logic trong login.php)
                $isHashed = (strpos($dbPass, '$2y$') === 0) ||
                            (strpos($dbPass, '$2a$') === 0) ||
                            (strpos($dbPass, '$argon2') === 0);

                $current_ok = false;
                if ($isHashed) {
                    if (password_verify($current, $dbPass)) {
                        $current_ok = true;
                    } else {
                        $current_ok = false;
                    }
                } else {
                    // DB lưu plaintext
                    if ($current === $dbPass) {
                        $current_ok = true;
                    } else {
                        $current_ok = false;
                    }
                }

                if (!$current_ok) {
                    $error = 'Mật khẩu hiện tại không chính xác.';
                } else {
                    // Hash mật khẩu mới và cập nhật
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param("si", $newHash, $_SESSION['user_id']);
                    if ($upd->execute()) {
                        $success = 'Đổi mật khẩu thành công.';
                        // Tùy chọn: hủy token CSRF để tránh re-submission
                        $_SESSION['csrf_change_pass'] = bin2hex(random_bytes(24));
                        // Optionally: yêu cầu đăng nhập lại để an toàn
                        // session_unset(); session_destroy(); header('Location: ../login.php'); exit();
                    } else {
                        $error = 'Có lỗi khi cập nhật mật khẩu. Vui lòng thử lại sau.';
                    }
                }
            } else {
                $error = 'Người dùng không tồn tại.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đổi mật khẩu - Admin</title>
    <style>
        /* Style đơn giản, bạn có thể dùng CSS riêng */
        body { font-family: Arial, sans-serif; background:#f4f6f8; padding:20px; }
        .box { max-width:420px; margin:40px auto; background:#fff; padding:20px; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
        .box h2 { margin-top:0; }
        .input { width:100%; padding:10px; margin:8px 0; box-sizing:border-box; }
        .btn { background:#0b79d0; color:#fff; padding:10px 14px; border:0; border-radius:4px; cursor:pointer; }
        .error { background:#ffe6e6; color:#900; padding:10px; margin-bottom:10px; border-radius:4px; }
        .success { background:#e6ffea; color:#065; padding:10px; margin-bottom:10px; border-radius:4px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Đổi mật khẩu</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input class="input" type="password" name="current_password" placeholder="Mật khẩu hiện tại" required>
            <input class="input" type="password" name="new_password" placeholder="Mật khẩu mới (ít nhất 8 ký tự)" required>
            <input class="input" type="password" name="confirm_password" placeholder="Xác nhận mật khẩu mới" required>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_change_pass'], ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn" type="submit">Cập nhật mật khẩu</button>
        </form>

        <p style="margin-top:12px"><a href="login.php">Đăng nhập</a></p>
        <p style="margin-top:12px"><a href="../ADMIN/index.php">Quay lại</a></p>
    </div>
</body>
</html>