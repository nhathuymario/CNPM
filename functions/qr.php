<?php
// functions/qr.php
// Server-side QR endpoint for payment page.
// Usage: qr.php?order_id=123&bank_account_id=1
// This implementation builds an EMV-like (VietQR-style) payload and then
// redirects the browser to a QR image generator (api.qrserver.com) with the payload.
// Reason: avoids requiring composer / PHP QR libs. If you prefer server-side PNG generation
// locally, replace the redirect with a library (Endroid, chillerlan, etc).

session_start();
if (!isset($_SESSION['user_id'])) {
    // Allow anonymous too if needed — comment out the exit if public access required
    // For staff view, keep this check. If QR should be public, remove these lines.
    // http_response_code(403); echo "Forbidden"; exit;
}

// include your project's DB connection. This file should set $conn (mysqli).
require_once __DIR__ . '/database.php';

// Helpers
function crc16ccitt_str($str) {
    $data = unpack('C*', $str);
    $crc = 0xFFFF;
    foreach ($data as $b) {
        $crc ^= ($b << 8);
        for ($i=0;$i<8;$i++) {
            if ($crc & 0x8000) $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
            else $crc = ($crc << 1) & 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
}
function tlv($id, $value) {
    $len = strlen($value);
    $lenStr = str_pad($len, 2, '0', STR_PAD_LEFT);
    return $id . $lenStr . $value;
}
function build_emv_payload($params) {
    $gui = $params['gui'] ?? '';
    $accNo = $params['accountNumber'] ?? '';
    $accName = $params['accountName'] ?? '';
    $amount = $params['amount'] ?? '';
    $ref = $params['ref'] ?? '';
    $city = $params['city'] ?? 'HO CHI MINH';
    $payload = '';

    $payload .= tlv('00', '01'); // format indicator
    $payload .= tlv('01', '11'); // static QR (11) - use 12 for dynamic if you have PSP support

    // Merchant Account Information (26)
    $mai = '';
    $mai .= tlv('00', $gui);
    $mai .= tlv('01', $accNo);
    if ($accName !== '') $mai .= tlv('02', $accName);
    $payload .= tlv('26', $mai);

    $payload .= tlv('52', '0000'); // MCC
    $payload .= tlv('53', '704');  // VND
    if ($amount !== '') $payload .= tlv('54', (string)$amount);
    $payload .= tlv('58', 'VN');   // country
    $mname = $accName !== '' ? mb_substr($accName, 0, 25) : 'MERCHANT';
    $payload .= tlv('59', $mname);
    $payload .= tlv('60', $city);

    // Additional data field (62) - put bill/ref in subtag 01
    $add = '';
    if ($ref !== '') $add .= tlv('01', $ref);
    $payload .= tlv('62', $add);

    // CRC
    $payloadForCrc = $payload . '63' . '04';
    $crc = crc16ccitt_str($payloadForCrc);
    $payload .= '63' . '04' . $crc;
    return $payload;
}


// Read inputs
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$bank_account_id = isset($_GET['bank_account_id']) ? intval($_GET['bank_account_id']) : 0;
if ($order_id <= 0) {
    http_response_code(400); echo "Thiếu order_id"; exit;
}

// Get order
$stmt = $conn->prepare("SELECT id, total, ref_code FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
if (!$order) { http_response_code(404); echo "Order not found"; exit; }
$amount = (float)$order['total'];
$ref = $order['ref_code'] ?? ('CF' . strtoupper(dechex($order['id'])) . '-' . date('d'));

// Get bank account
if ($bank_account_id <= 0) {
    // choose first account by id asc
    $row = $conn->query("SELECT id FROM payment_accounts ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $bank_account_id = $row ? intval($row['id']) : 0;
}
$stmt = $conn->prepare("SELECT bank_name, account_number, account_name, emv_gui FROM payment_accounts WHERE id = ?");
$stmt->bind_param("i", $bank_account_id);
$stmt->execute();
$res = $stmt->get_result();
$acc = $res->fetch_assoc();
$stmt->close();
if (!$acc) { http_response_code(404); echo "Bank account not found"; exit; }

// GUI: prefer emv_gui from DB if available, otherwise construct from bank name (may not be recognized by banks)
$gui = (isset($acc['emv_gui']) && trim($acc['emv_gui']) !== '') ? $acc['emv_gui'] : preg_replace('/\s+/', '', strtolower($acc['bank_name']));

// Build payload
$payload = build_emv_payload([
    'gui' => $gui,
    'accountNumber' => $acc['account_number'],
    'accountName' => $acc['account_name'],
    'amount' => $amount,
    'ref' => $ref,
    'city' => 'HO CHI MINH'
]);

// Redirect to external QR generator to produce PNG
// NOTE: using an external service (api.qrserver.com) — if you prefer local generation, replace this logic.
$qrApi = 'https://api.qrserver.com/v1/create-qr-code/';
$query = http_build_query([
    'size' => '400x400',
    'data' => $payload,
    'ecc'  => 'M'
]);
header('Location: ' . $qrApi . '?' . $query);
exit;