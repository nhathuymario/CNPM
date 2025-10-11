<?php
/**
 * Hàm kiểm tra quyền truy cập trang.
 * @param array $allowed_roles Danh sách các role được phép vào (ví dụ: ['admin', 'staff'])
 */
function checkRole(array $allowed_roles = [])
{
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header("Location: ../functions/loginStaff.php");
        exit("Vui lòng đăng nhập để tiếp tục!");
    }

    // Kiểm tra quyền truy cập
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../functions/loginStaff.php");
        exit("Bạn không có quyền truy cập trang này!");
    }
}
?>
