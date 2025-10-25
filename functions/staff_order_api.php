<?php
// GET ?action=detail&order_id=...
// GET ?action=latest
// POST ?action=mark_paid  body/query: { order_id, method? ('cash'|'bank') }
// POST ?action=delete_item body: { order_id, order_item_id?, admin_username, admin_password, reason?, dish_id?, quantity?, price?, name? }
// Luôn trả JSON.
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Kết nối DB
$dbIncluded = false;
$paths = [
  __DIR__ . '/database.php',
  dirname(__DIR__) . '/functions/database.php',
  dirname(__DIR__) . '/includes/db.php',
];
foreach ($paths as $p) { if (file_exists($p)) { require_once $p; $dbIncluded = true; break; } }
if (!$dbIncluded) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database bootstrap not found']); exit; }

$dbType = null; $dbc = null;
if (isset($pdo)) { $dbc = $pdo; $dbType='pdo'; }
elseif (isset($conn)) { $dbc = $conn; $dbType='mysqli'; }
else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database connection not found']); exit; }

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
function db_has_column($dbc,$dbType,$table,$column){
  if ($dbType==='pdo'){ $q = $dbc->quote($column); $st = $dbc->query("SHOW COLUMNS FROM `$table` LIKE $q"); return $st && $st->fetch() ? true : false; }
  $col = $dbc->real_escape_string($column);
  $res = $dbc->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
  if ($res === false) throw new Exception($dbc->error ?: 'SHOW COLUMNS failed');
  $ok = $res->num_rows > 0; $res->free(); return $ok;
}
function has_ordered_at($dbc,$dbType){ return db_has_column($dbc,$dbType,'orders','ordered_at'); }
function normalize_items_json($itemsJson){
  $decoded=json_decode($itemsJson,true);
  if (!is_array($decoded) || empty($decoded)) return [];
  $arr = array_values($decoded);
  return array_map(function($i){
    return [
      'id' => isset($i['id']) ? (int)$i['id'] : null,
      'name' => $i['name'] ?? '',
      'price' => isset($i['price']) ? (float)$i['price'] : 0,
      'quantity' => isset($i['quantity']) ? (int)$i['quantity'] : 0,
      'image' => $i['image'] ?? null
    ];
  }, $arr);
}

// Xác thực Admin
function verify_admin($dbc,$dbType,$username,$password){
  if (!$username || $password==='') return [false,'Thiếu tài khoản/mật khẩu'];
  $u = db_query_one($dbc,$dbType,
    "SELECT id, username, email, phone, role, password FROM users
     WHERE (username=? OR email=? OR phone=?) LIMIT 1", [$username,$username,$username]);
  if (!$u) return [false,'Sai tài khoản hoặc mật khẩu'];
  $dbPass = trim($u['password'] ?? '');
  $isHashed = (strpos($dbPass,'$2y$')===0) || (strpos($dbPass,'$2a$')===0) || (strpos($dbPass,'$argon2')===0);
  $ok = $isHashed ? password_verify($password,$dbPass) : ($password === $dbPass);
  if (!$ok) return [false,'Sai tài khoản hoặc mật khẩu'];
  if (strtolower($u['role'])!=='admin') return [false,'Tài khoản không có quyền admin'];
  if (!$isHashed){
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    if ($dbType==='pdo'){ $st=$dbc->prepare("UPDATE users SET password=? WHERE id=?"); $st->execute([$newHash,(int)$u['id']]); }
    else { $st=$dbc->prepare("UPDATE users SET password=? WHERE id=?"); $id=(int)$u['id']; $st->bind_param('si',$newHash,$id); $st->execute(); $st->close(); }
  }
  return [true,$u];
}

if ($method==='GET' && $action==='detail'){
  $order_id = (int)($_GET['order_id'] ?? 0);
  if ($order_id<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'order_id is required']); exit; }

  $orderCol = has_ordered_at($dbc,$dbType) ? 'ordered_at' : 'created_at';
  $select = "SELECT id, table_number, items, total, payment_method, payment_status, status, ref_code, created_at,
                    $orderCol AS ordered_at, TIMESTAMPDIFF(MINUTE, $orderCol, NOW()) AS wait_mins
             FROM orders WHERE id = ?";
  $order = db_query_one($dbc,$dbType,$select,[$order_id]);
  if (!$order){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'Order not found']); exit; }

  $lineItems = db_query_all(
    $dbc,$dbType,
    "SELECT oi.id AS order_item_id, oi.dish_id AS id, d.name, oi.price, oi.quantity, d.image
     FROM order_items oi
     LEFT JOIN dishes d ON d.id = oi.dish_id
     WHERE oi.order_id = ?",
    [$order_id]
  );
  if (!$lineItems || count($lineItems)===0) {
    $lineItems = normalize_items_json($order['items'] ?? '[]');
  } else {
    $lineItems = array_map(function($r){
      return [
        'order_item_id' => (int)$r['order_item_id'],
        'id' => (int)$r['id'],
        'name' => $r['name'] ?? '',
        'price' => isset($r['price']) ? (float)$r['price'] : 0,
        'quantity' => isset($r['quantity']) ? (int)$r['quantity'] : 0,
        'image' => $r['image'] ?? null
      ];
    }, $lineItems);
  }

  echo json_encode([
    'success'=>true,
    'order'=>[
      'id'=>(int)$order['id'],
      'table_number'=>(int)$order['table_number'],
      'items'=>$lineItems,
      'total'=>(float)$order['total'],
      'payment_method'=>$order['payment_method'],
      'payment_status'=>$order['payment_status'],
      'status'=>$order['status'],
      'ref_code'=>$order['ref_code'],
      'created_at'=>$order['created_at'],
      'ordered_at'=>$order['ordered_at'],
      'wait_mins'=>(int)($order['wait_mins'] ?? 0)
    ]
  ]);
  exit;
}

if ($method==='GET' && $action==='latest'){
  $orderCol = has_ordered_at($dbc,$dbType) ? 'ordered_at' : 'created_at';
  $select = "SELECT id, table_number, total, payment_method, payment_status, status, ref_code, created_at, $orderCol AS ordered_at
             FROM orders ORDER BY $orderCol DESC LIMIT 1";
  $order = db_query_one($dbc,$dbType,$select,[]);
  if (!$order){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'No orders found']); exit; }
  echo json_encode(['success'=>true,'order'=>$order]); exit;
}

if ($method==='POST' && $action==='mark_paid'){
  $raw = file_get_contents('php://input');
  $body = $raw ? json_decode($raw,true) : null;

  $order_id = 0;
  $paidMethod = null;
  if (is_array($body)) { $order_id = (int)($body['order_id'] ?? 0); $paidMethod = isset($body['method']) ? strtolower(trim($body['method'])) : null; }
  if (!$order_id && isset($_POST['order_id'])) $order_id = (int)$_POST['order_id'];
  if (!$order_id && isset($_GET['order_id']))  $order_id = (int)$_GET['order_id'];
  if (!$paidMethod && isset($_POST['method'])) $paidMethod = strtolower(trim($_POST['method']));
  if (!$paidMethod && isset($_GET['method']))  $paidMethod = strtolower(trim($_GET['method']));
  if ($paidMethod && !in_array($paidMethod, ['cash','bank'], true)) $paidMethod = null;

  if ($order_id<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'order_id is required']); exit; }

  $order = db_query_one($dbc,$dbType,"SELECT id, table_number, status FROM orders WHERE id = ?",[$order_id]);
  if (!$order){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'Order not found']); exit; }
  if (in_array($order['status'], ['paid','cancelled'])){ echo json_encode(['success'=>true,'message'=>'Order already finalized']); exit; }

  $hasPaymentStatus = db_has_column($dbc,$dbType,'orders','payment_status');
  $hasPaymentMethod = db_has_column($dbc,$dbType,'orders','payment_method');

  try{
    if ($dbType==='pdo') $dbc->beginTransaction(); else $dbc->begin_transaction();

    $sql = "UPDATE orders SET status='paid'";
    $params = [];
    if ($hasPaymentStatus) { $sql .= ", payment_status='paid'"; }
    if ($paidMethod && $hasPaymentMethod) { $sql .= ", payment_method=?"; $params[] = $paidMethod; }
    $sql .= " WHERE id = ?";
    $params[] = $order_id;

    db_exec($dbc,$dbType,$sql,$params);
    db_exec($dbc,$dbType,"UPDATE tables SET status='available' WHERE table_number = ?",[(int)$order['table_number']]);

    if ($dbType==='pdo') $dbc->commit(); else $dbc->commit();
    echo json_encode(['success'=>true,'message'=>'Đã xác nhận thanh toán và trả bàn về trống.','applied_payment_method'=>$paidMethod ?? null]);
  }catch(Throwable $e){
    if ($dbType==='pdo'){ if ($dbc->inTransaction()) $dbc->rollBack(); } else { $dbc->rollback(); }
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage() ?: 'Internal error']);
  }
  exit;
}

if ($method==='POST' && $action==='delete_item'){
  $body = json_decode(file_get_contents('php://input'), true);
  $order_id      = (int)($body['order_id'] ?? 0);
  $order_item_id = (int)($body['order_item_id'] ?? 0);
  $admin_user    = trim($body['admin_username'] ?? '');
  $admin_pass    = (string)($body['admin_password'] ?? '');
  $reason        = isset($body['reason']) ? trim($body['reason']) : null;

  // Fallback params (đơn cũ)
  $dish_id  = isset($body['dish_id']) ? (int)$body['dish_id'] : null;
  $qty_in   = isset($body['quantity']) ? (int)$body['quantity'] : null;
  $price_in = isset($body['price']) ? (float)$body['price'] : null;
  $name_in  = isset($body['name']) ? (string)$body['name'] : null;

  if ($order_id<=0){
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'order_id is required']);
    exit;
  }

  // Verify admin
  list($ok,$u) = verify_admin($dbc,$dbType,$admin_user,$admin_pass);
  if (!$ok){
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>$u ?: 'Không hợp lệ']);
    exit;
  }
  $admin_id = (int)$u['id'];

  try{
    if ($dbType==='pdo') $dbc->beginTransaction(); else $dbc->begin_transaction();

    if ($order_item_id > 0) {
      // Xóa theo dòng order_items (đơn mới)
      $row = db_query_one(
        $dbc,$dbType,
        "SELECT oi.id AS order_item_id, oi.order_id, o.table_number, oi.dish_id, d.name AS dish_name, oi.price, oi.quantity
         FROM order_items oi
         LEFT JOIN orders o ON o.id = oi.order_id
         LEFT JOIN dishes d ON d.id = oi.dish_id
         WHERE oi.id = ? AND oi.order_id = ?",
        [$order_item_id, $order_id]
      );
      if (!$row){ throw new Exception('Không tìm thấy món cần xóa'); }

      $line_total = (float)$row['price'] * (int)$row['quantity'];

      db_exec(
        $dbc,$dbType,
        "INSERT INTO order_item_deletions
         (order_id, table_number, dish_id, dish_name, quantity, price, line_total, deleted_reason, deleted_by_user_id, deleted_by_db_user)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_USER())",
        [
          (int)$row['order_id'],
          (int)$row['table_number'],
          (int)$row['dish_id'],
          $row['dish_name'],
          (int)$row['quantity'],
          (float)$row['price'],
          $line_total,
          $reason,
          $admin_id
        ]
      );

      db_exec($dbc,$dbType, "DELETE FROM order_items WHERE id = ?", [ $order_item_id ]);
      db_exec($dbc,$dbType, "UPDATE orders SET total = GREATEST(0, total - ?) WHERE id = ?", [ $line_total, (int)$row['order_id'] ]);
    } else {
      // Fallback: xóa theo JSON orders.items (đơn cũ)
      $ord = db_query_one($dbc,$dbType,"SELECT id, table_number, items, total FROM orders WHERE id = ? FOR UPDATE", [$order_id]);
      if (!$ord) throw new Exception('Order not found');

      $items = normalize_items_json($ord['items'] ?? '[]');
      if (!$items || count($items)===0) throw new Exception('Đơn không có món (JSON trống)');

      // Tìm item khớp: ưu tiên dish_id, kế đến name+price(+qty)
      $idx = null;
      foreach ($items as $k=>$it) {
        $match = false;
        if ($dish_id && isset($it['id']) && (int)$it['id'] === (int)$dish_id) $match = true;
        else if ($name_in && strcasecmp($name_in, (string)$it['name'])===0) {
          if ($price_in !== null && isset($it['price']) && (float)$it['price'] == (float)$price_in) $match = true;
          if ($qty_in !== null && isset($it['quantity']) && (int)$it['quantity'] == (int)$qty_in) $match = true;
        }
        if ($match) { $idx = $k; break; }
      }
      if ($idx === null) throw new Exception('Không tìm thấy món phù hợp để xóa (JSON)');

      $it = $items[$idx];
      $q  = isset($it['quantity']) ? (int)$it['quantity'] : (int)($qty_in ?? 1);
      $p  = isset($it['price']) ? (float)$it['price'] : (float)($price_in ?? 0);
      $n  = $it['name'] ?? $name_in ?? ('Dish#' . ($it['id'] ?? ''));
      $d  = isset($it['id']) ? (int)$it['id'] : (int)($dish_id ?? 0);
      $line_total = $q * $p;

      // Ghi log
      db_exec(
        $dbc,$dbType,
        "INSERT INTO order_item_deletions
         (order_id, table_number, dish_id, dish_name, quantity, price, line_total, deleted_reason, deleted_by_user_id, deleted_by_db_user)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_USER())",
        [
          (int)$ord['id'],
          (int)$ord['table_number'],
          $d ?: null,
          $n,
          $q,
          $p,
          $line_total,
          $reason,
          $admin_id
        ]
      );

      // Xóa item khỏi JSON
      array_splice($items, $idx, 1);
      $newJson = json_encode($items, JSON_UNESCAPED_UNICODE);
      if ($newJson === false) throw new Exception('Không thể ghi lại JSON items');

      db_exec($dbc,$dbType, "UPDATE orders SET items=?, total=GREATEST(0,total-?) WHERE id = ?", [ $newJson, $line_total, (int)$ord['id'] ]);
    }

    if ($dbType==='pdo') $dbc->commit(); else $dbc->commit();
    echo json_encode(['success'=>true,'message'=>'Đã xóa món và ghi log.']);
  } catch (Throwable $e) {
    if ($dbType==='pdo'){ if ($dbc->inTransaction()) $dbc->rollBack(); } else { $dbc->rollback(); }
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage() ?: 'Không thể xóa']);
  }
  exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid action or method']);