<?php
// GET ?action=detail&order_id=...
// GET ?action=latest
// POST ?action=mark_paid  body: { order_id }
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
foreach ($paths as $p) {
  if (file_exists($p)) { require_once $p; $dbIncluded = true; break; }
}
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
    foreach($params as $p){ $types.=is_int($p)?'i':(is_float($p)?'d':'s'); $bind[]=$p;
    }
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
function has_ordered_at($dbc,$dbType){
  if ($dbType==='pdo'){
    $st = $dbc->query("SHOW COLUMNS FROM `orders` LIKE 'ordered_at'");
    return $st && $st->fetch() ? true : false;
  } else {
    $res = $dbc->query("SHOW COLUMNS FROM `orders` LIKE 'ordered_at'");
    if ($res === false) throw new Exception($dbc->error ?: 'SHOW COLUMNS failed');
    $ok = $res->num_rows > 0; $res->free(); return $ok;
  }
}
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

if ($method==='GET' && $action==='detail'){
  $order_id = (int)($_GET['order_id'] ?? 0);
  if ($order_id<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'order_id is required']); exit; }

  $hasOrderedAt = has_ordered_at($dbc,$dbType);
  $orderCol = $hasOrderedAt ? 'ordered_at' : 'created_at';

  $select = "SELECT id, table_number, items, total, payment_method, payment_status, status, ref_code, created_at,
                    $orderCol AS ordered_at, TIMESTAMPDIFF(MINUTE, $orderCol, NOW()) AS wait_mins
             FROM orders WHERE id = ?";

  $order = db_query_one($dbc,$dbType,$select,[$order_id]);
  if (!$order){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'Order not found']); exit; }

  // Items từ order_items + dishes (fallback JSON)
  $lineItems = db_query_all(
    $dbc,$dbType,
    "SELECT oi.dish_id AS id, d.name, oi.price, oi.quantity, d.image
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
  $hasOrderedAt = has_ordered_at($dbc,$dbType);
  $orderCol = $hasOrderedAt ? 'ordered_at' : 'created_at';
  $select = "SELECT id, table_number, total, payment_method, payment_status, status, ref_code, created_at,
                    $orderCol AS ordered_at
             FROM orders
             ORDER BY $orderCol DESC
             LIMIT 1";
  $order = db_query_one($dbc,$dbType,$select,[]);
  if (!$order){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'No orders found']); exit; }
  echo json_encode(['success'=>true,'order'=>$order]); exit;
}

if ($method==='POST' && $action==='mark_paid'){
  $body=json_decode(file_get_contents('php://input'),true);
  $order_id=(int)($body['order_id'] ?? 0);
  if ($order_id<=0){ http_response_code(400); echo json_encode(['success'=>false,'message'=>'order_id is required']); exit; }

  $order = db_query_one($dbc,$dbType,"SELECT id, table_number, status FROM orders WHERE id = ?",[$order_id]);
  if (!$order){ http_response_code(404); echo json_encode(['success'=>false,'message'=>'Order not found']); exit; }
  if (in_array($order['status'], ['paid','cancelled'])){ echo json_encode(['success'=>true,'message'=>'Order already finalized']); exit; }

  try{
    if ($dbType==='pdo') $dbc->beginTransaction(); else $dbc->begin_transaction();

    db_exec($dbc,$dbType,"UPDATE orders SET status='paid', payment_status='paid' WHERE id = ?",[$order_id]);
    db_exec($dbc,$dbType,"UPDATE tables SET status='available' WHERE table_number = ?",[(int)$order['table_number']]);

    if ($dbType==='pdo') $dbc->commit(); else $dbc->commit();
    echo json_encode(['success'=>true,'message'=>'Đã xác nhận thanh toán và trả bàn về trống.']);
  }catch(Throwable $e){
    if ($dbType==='pdo'){ if ($dbc->inTransaction()) $dbc->rollBack(); } else { $dbc->rollback(); }
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
  }
  exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid action or method']);