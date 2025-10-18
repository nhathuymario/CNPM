<?php
// Endpoint: ghi nhận yêu cầu gọi nhân viên từ khách
// Trả JSON: { ok: true/false, message: "..." }

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$table_number = isset($_POST['table']) ? intval($_POST['table']) : 0;
$k           = isset($_POST['k']) ? trim($_POST['k']) : '';

if ($table_number <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Thiếu thông tin bàn']);
    exit;
}

// Xác thực QR nếu có mã k
if ($k !== '') {
    $stmt = $conn->prepare("SELECT qr_secret FROM `tables` WHERE table_number = ?");
    $stmt->bind_param("i", $table_number);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row || ($row['qr_secret'] !== '' && $row['qr_secret'] !== $k)) {
        echo json_encode(['ok' => false, 'message' => 'QR không hợp lệ']);
        exit;
    }
}

// Tạo bảng help_requests nếu chưa có
$conn->query("
CREATE TABLE IF NOT EXISTS `help_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `table_number` INT NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('new','ack','done') NOT NULL DEFAULT 'new',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_help_table_created` (`table_number`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Chống spam 60 giây/bàn
$chk = $conn->prepare("
  SELECT id FROM help_requests 
  WHERE table_number = ? AND status = 'new' AND created_at >= (NOW() - INTERVAL 60 SECOND)
  ORDER BY id DESC LIMIT 1
");
$chk->bind_param("i", $table_number);
$chk->execute();
$existed = $chk->get_result()->fetch_assoc();

if ($existed) {
    echo json_encode(['ok' => true, 'message' => 'Đã gửi yêu cầu trước đó. Vui lòng chờ nhân viên.']);
    exit;
}

// Ghi yêu cầu mới
$ins = $conn->prepare("INSERT INTO help_requests (table_number, note, status) VALUES (?, NULL, 'new')");
$ins->bind_param("i", $table_number);
$ins->execute();

echo json_encode(['ok' => true, 'message' => 'Đã gọi nhân viên. Vui lòng chờ trong giây lát.']);