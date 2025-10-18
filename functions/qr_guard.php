<?php
require_once __DIR__ . '/database.php';
function require_valid_table_from_qr(): array {
    if (!isset($_GET['table'], $_GET['k'])) {
        http_response_code(400);
        die('Thiếu tham số bàn. Vui lòng quét đúng QR trên bàn.');
    }
    $table_number = intval($_GET['table']);
    $k = $_GET['k'];
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM `tables` WHERE `table_number` = ? AND `qr_secret` = ? AND `status` IN ('available','unavailable')");
    $stmt->bind_param("is", $table_number, $k);
    $stmt->execute();
    $res = $stmt->get_result();
    $table = $res->fetch_assoc();
    if (!$table) {
        http_response_code(403);
        die('QR không hợp lệ hoặc bàn không khả dụng.');
    }
    return $table;
}