<?php
// Kết nối database (MySQLi, sửa lại thông tin kết nối cho đúng)
include '../functions/database.php';
session_start();

// Lấy danh sách ngân hàng từ bảng vietqr_banks
$banks = [];
$sql = "SELECT bank_code, bank_id, bank_name FROM vietqr_banks ORDER BY bank_name ASC";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $banks[] = $row;
    }
}

// Danh sách template cố định
$templates = [
    'compact' => 'compact',
    'compact2' => 'compact2',
    'qr_only' => 'qr_only',
    'print' => 'print'
];

// Xử lý khi submit
$success = false;
$errors = [];
$qr_url = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_code = $_POST['bank_code'] ?? '';
    $account_no = trim($_POST['account_no'] ?? '');
    $template = $_POST['template'] ?? '';

    // Validate
    if (!$bank_code) $errors[] = "Vui lòng chọn ngân hàng";
    if (!$account_no) $errors[] = "Vui lòng nhập số tài khoản";
    if (!$template || !isset($templates[$template])) $errors[] = "Vui lòng chọn template";

    if (empty($errors)) {
        // Lưu vào DB
        $stmt = $conn->prepare("INSERT INTO vietqr_links (bank_code, account_no, template) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $bank_code, $account_no, $template);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        // Lấy id vừa insert
        $insert_id = $conn->insert_id;
        $stmt->close();

        // Sinh link QR đúng chuẩn VietQR
        $qr_url = "https://img.vietqr.io/image/" . urlencode($bank_code) . "-" . urlencode($account_no) . "-" . urlencode($template) . ".png";

        // Cập nhật trường qr_image_url cho bản ghi vừa tạo
        if ($success && $insert_id) {
            $stmt = $conn->prepare("UPDATE vietqr_links SET qr_image_url=? WHERE id=?");
            $stmt->bind_param("si", $qr_url, $insert_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

ob_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo Quicklink</title>
    <link rel="stylesheet" href="../assets/css/bank_account.css">
</head>
<body>
<div class="ql-container">
    <form class="ql-form" method="POST" autocomplete="off">
        <div class="ql-field">
            <label for="bank_code">Ngân hàng</label>
            <select id="bank_code" name="bank_code" required>
                <option value="">-- Chọn ngân hàng --</option>
                <?php foreach ($banks as $b): ?>
                    <option value="<?= htmlspecialchars($b['bank_code']) ?>"
                        <?= (isset($bank_code) && $bank_code === $b['bank_code']) ? 'selected' : '' ?>>
                        (<?= htmlspecialchars($b['bank_code']) ?>) <?= htmlspecialchars($b['bank_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ql-field">
            <label for="account_no">Số tài khoản</label>
            <input type="text" id="account_no" name="account_no" value="<?= htmlspecialchars($account_no ?? '') ?>" required>
        </div>
        <div class="ql-field">
            <label for="template">Template</label>
            <select id="template" name="template" required>
                <?php foreach ($templates as $val => $label): ?>
                    <option value="<?= htmlspecialchars($val) ?>"
                        <?= (isset($template) && $template === $val) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ql-action">
            <button type="submit" class="ql-btn">Tạo Quicklink</button>
        </div>
    </form>
    <?php if (!empty($errors)): ?>
        <div class="ql-error">
            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="ql-success">
        ✅ Đã tạo Quicklink thành công!
    </div>
    <div style="margin-top:18px;">
        <strong>Mã QR:</strong>
        <!-- <div>
            <a href="<?= htmlspecialchars($qr_url) ?>" target="_blank"><?= htmlspecialchars($qr_url) ?></a>
        </div> -->
        <div style="margin-top:10px;">
            <img src="<?= htmlspecialchars($qr_url) ?>" alt="QR Code thực" style="max-width:180px;">
        </div>
    </div>
    <div style="margin-top: 24px; display: flex; gap: 12px;">
        <a href="bank_account_list.php" class="ql-btn">Xem danh sách Quicklink</a>
        <a href="bank_account.php" class="ql-btn ql-btn-light">Đã xong / Tạo QR khác</a>
    </div>
<?php endif; ?>

</div>
</body>
</html>




<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>