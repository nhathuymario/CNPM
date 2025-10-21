<?php
// API gọi/nhận cuộc gọi bàn
// - POST ?action=call      body: { table_number, reason?, note? }  (public - không yêu cầu staff session)
// - POST ?action=ack       body: { id }                             (staff)
// - POST ?action=resolve   body: { id }                             (staff)
// - GET  ?action=list_open                                         (staff) [tùy chọn, hỗ trợ debug]

header('Content-Type: application/json; charset=utf-8');
session_start();

// Kết nối DB
$dbIncluded = false;
$paths = [
  __DIR__ . '/database.php',
  dirname(__DIR__) . '/functions/database.php',
  dirname(__DIR__) . '/includes/db.php',
];
foreach ($paths as $p) {
  if (file_exists($p)) { require_once $p; $dbIncluded = true; break; }
}
if (!$dbIncluded) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database bootstrap not found']); exit; }

$dbType = null; $dbc = null;
if (isset($pdo)) { $dbc = $pdo; $dbType='pdo'; }
elseif (isset($conn)) { $dbc = $conn; $dbType='mysqli'; }
else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database connection not found']); exit; }

// Helpers
function db_exec($dbc,$dbType,$sql,$params=[]){
  if ($dbType==='pdo'){ $st=$dbc->prepare($sql); return $st->execute($params); }
  $st=$dbc->prepare($sql);
  if ($params){
    $types=''; $bind=[];
    foreach($params as $p){ $types.=is_int($p)?'i':(is_float($p)?'d':'s'); $bind[]=$p; }
    $st->bind_param($types, ...$bind);
  }
  $ok=$st->execute(); $st->close(); return $ok;
}
function db_query_all($dbc,$dbType,$sql,$params=[]){
  if ($dbType==='pdo'){ $st=$dbc->prepare($sql); $st->execute($params); return $st->fetchAll(PDO::FETCH_ASSOC); }
  $st=$dbc->prepare($sql);
  if ($params){
    $types=''; $bind=[];
    foreach($params as $p){ $types.=is_int($p)?'i':(is_float($p)?'d':'s'); $bind[]=$p; }
    $st->bind_param($types, ...$bind);
  }
  $st->execute(); $res=$st->get_result();
  if ($res === false) { $err=$st->error; $st->close(); throw new Exception($err ?: 'Query failed'); }
  $rows=$res->fetch_all(MYSQLI_ASSOC); $st->close(); return $rows;
}
function db_query_one($dbc,$dbType,$sql,$params=[]){ $rows=db_query_all($dbc,$dbType,$sql,$params); return $rows[0]??null; }

function ensure_calls_table($dbc,$dbType){
  // Tạo table_calls nếu chưa có (không dùng placeholder trong SHOW)
  if ($dbType==='pdo'){
    $st = $dbc->query("SHOW TABLES LIKE 'table_calls'");
    $exists = $st && $st->fetch() ? true : false;
  } else {
    $res = $dbc->query("SHOW TABLES LIKE 'table_calls'");
    if ($res === false) throw new Exception($dbc->error ?: 'SHOW TABLES failed');
    $exists = $res->num_rows > 0; $res->free();
  }
  if ($exists) return;
  $sql = "CREATE TABLE IF NOT EXISTS table_calls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_number INT NOT NULL,
            reason VARCHAR(32) NOT NULL DEFAULT 'help',
            note VARCHAR(255) DEFAULT NULL,
            status ENUM('open','acknowledged','resolved') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            acknowledged_at TIMESTAMP NULL DEFAULT NULL,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_calls_table_status (table_number, status),
            INDEX idx_calls_created_at (created_at)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  if ($dbType==='pdo'){ $dbc->exec($sql); }
  else { if (!$dbc->query($sql)) throw new Exception($dbc->error ?: 'CREATE TABLE failed'); }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
  ensure_calls_table($dbc,$dbType);

  if ($method==='POST' && $action==='call') {
    $body = json_decode(file_get_contents('php://input'), true);
    $table_number = isset($body['table_number']) ? (int)$body['table_number'] : 0;
    $reason = isset($body['reason']) ? trim($body['reason']) : 'help';
    $note = isset($body['note']) ? trim($body['note']) : null;

    if ($table_number <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'table_number is required']); exit; }

    // Kiểm tra bàn tồn tại
    $exists = db_query_one($dbc,$dbType,"SELECT 1 FROM tables WHERE table_number = ? LIMIT 1",[$table_number]);
    if (!$exists) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Table not found']); exit; }

    // Nếu đã có call 'open' gần nhất, cập nhật mốc thời gian (chống spam)
    $open = db_query_one($dbc,$dbType,"SELECT id FROM table_calls WHERE table_number = ? AND status = 'open' ORDER BY created_at DESC LIMIT 1",[$table_number]);
    if ($open) {
      // Cập nhật created_at về NOW() để nổi lên danh sách
      if ($dbType==='pdo'){
        $dbc->prepare("UPDATE table_calls SET created_at = NOW(), note = COALESCE(?, note) WHERE id = ?")->execute([$note, (int)$open['id']]);
      } else {
        $st = $dbc->prepare("UPDATE table_calls SET created_at = NOW(), note = COALESCE(?, note) WHERE id = ?");
        $st->bind_param('si', $note, $open['id']); $st->execute(); $st->close();
      }
      echo json_encode(['success'=>true,'message'=>'Đã nhắc gọi nhân viên','call_id'=>(int)$open['id']]); exit;
    }

    // Tạo call mới
    if ($dbType==='pdo'){
      $st = $dbc->prepare("INSERT INTO table_calls (table_number, reason, note) VALUES (?, ?, ?)");
      $st->execute([$table_number, $reason, $note]);
      $id = (int)$dbc->lastInsertId();
    } else {
      $st = $dbc->prepare("INSERT INTO table_calls (table_number, reason, note) VALUES (?, ?, ?)");
      $st->bind_param('iss', $table_number, $reason, $note); $st->execute(); $id = $st->insert_id; $st->close();
    }
    echo json_encode(['success'=>true,'message'=>'Đã gửi yêu cầu trợ giúp','call_id'=>$id]); exit;
  }

  if ($method==='POST' && $action==='ack') {
    if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
    $body = json_decode(file_get_contents('php://input'), true);
    $id = isset($body['id']) ? (int)$body['id'] : 0;
    if ($id<=0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id is required']); exit; }

    db_exec($dbc,$dbType,"UPDATE table_calls SET status='acknowledged', acknowledged_at=NOW() WHERE id = ? AND status='open'",[$id]);
    echo json_encode(['success'=>true]); exit;
  }

  if ($method==='POST' && $action==='resolve') {
    if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
    $body = json_decode(file_get_contents('php://input'), true);
    $id = isset($body['id']) ? (int)$body['id'] : 0;
    if ($id<=0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id is required']); exit; }

    db_exec($dbc,$dbType,"UPDATE table_calls SET status='resolved', resolved_at=NOW() WHERE id = ? AND status IN ('open','acknowledged')",[$id]);
    echo json_encode(['success'=>true]); exit;
  }

  if ($method==='GET' && $action==='list_open') {
    if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }
    $rows = db_query_all($dbc,$dbType,"SELECT id, table_number, reason, note, status, created_at FROM table_calls WHERE status IN ('open','acknowledged') ORDER BY created_at DESC");
    echo json_encode(['success'=>true,'calls'=>$rows]); exit;
  }

  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Invalid action']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}