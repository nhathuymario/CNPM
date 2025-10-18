<?php
// Trang tạo link QR cho từng bàn
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../functions/database.php';

// BASE_URL đã có "/" cuối (ví dụ http://localhost/CNPM/)
$base = defined('BASE_URL') ? BASE_URL : null;

// Fallback nếu BASE_URL không tồn tại vì config lỗi
if (!$base) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // scripts/qr_links.php -> về gốc dự án bằng cách bỏ "/scripts"
    $prefix = rtrim(str_replace('/scripts', '', dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    if ($prefix === '' || $prefix === '/') {
        $base = $scheme . '://' . $host . '/';
    } else {
        $base = $scheme . '://' . $host . $prefix . '/';
    }
}

// Truy vấn bàn
$res = $conn->query("SELECT table_number, qr_secret FROM `tables` ORDER BY table_number ASC");
if (!$res) {
    http_response_code(500);
    echo "Không truy vấn được danh sách bàn: " . htmlspecialchars($conn->error);
    exit;
}

// HTML đơn giản + debug BASE_URL
echo "<!DOCTYPE html><html lang='vi'><head><meta charset='utf-8'><title>QR Links</title>
<style>
body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;padding:24px}
.debug{background:#f6f8fa;border:1px solid #d0d7de;border-radius:6px;padding:12px;margin-bottom:16px;color:#24292f}
ul{line-height:1.9}
kbd{font-family:monospace;background:#eee;padding:0 4px;border-radius:3px;border:1px solid #ccc}
</style>
</head><body>";

echo "<div class='debug'><strong>BASE_URL:</strong> " . htmlspecialchars($base) . "<br>
<small>Nếu BASE_URL sai (thiếu /CNPM/ hoặc sai host), sửa ở <kbd>includes/config.php</kbd>.</small>
</div>";

echo "<h2>QR Links</h2><ul>";
while ($row = $res->fetch_assoc()) {
    $t = (int) $row['table_number'];
    $k = (string) $row['qr_secret'];

    if ($k === '' || $k === null) {
        echo "<li>Bàn " . htmlspecialchars($t) . ": <em>Chưa có qr_secret</em> — hãy thiết lập trong DB (ví dụ TBL" . str_pad($t, 3, '0', STR_PAD_LEFT) . ")</li>";
        continue;
    }

    // CHÚ Ý: “ORDER” phải đúng chữ HOA theo thư mục dự án
    $url = $base . "ORDER/index.php?table=" . urlencode($t) . "&k=" . urlencode($k);
    echo "<li>Bàn " . htmlspecialchars($t) . ": <a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($url) . "</a></li>";
}
echo "</ul></body></html>";