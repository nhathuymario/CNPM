<?php
// API: GET action=list
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? 'list';

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
if (isset($pdo)) { $dbc = $pdo; $dbType = 'pdo'; }
elseif (isset($conn)) { $dbc = $conn; $dbType = 'mysqli'; }
else { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Database connection not found']); exit; }

// Helpers
function db_query_all($dbc,$dbType,$sql,$params=[]){
  if ($dbType==='pdo'){
    $st=$dbc->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
  $st=$dbc->prepare($sql);
  if ($params){
    $types=''; $bind=[];
    foreach($params as $p){ $types.=is_int($p)?'i':(is_float($p)?'d':'s'); $bind[]=$p; }
    $st->bind_param($types, ...$bind);
  }
  $st->execute();
  $res=$st->get_result();
  if ($res === false) { $err=$st->error; $st->close(); throw new Exception($err ?: 'Query failed'); }
  $rows=$res->fetch_all(MYSQLI_ASSOC);
  $st->close();
  return $rows;
}
function db_query_one($dbc,$dbType,$sql,$params=[]){
  $rows=db_query_all($dbc,$dbType,$sql,$params);
  return $rows[0]??null;
}
function db_has_column($dbc,$dbType,$table,$column){
  if ($dbType==='pdo'){
    // Không dùng placeholder với SHOW; dùng quote an toàn
    $q = $dbc->quote($column);
    $sql = "SHOW COLUMNS FROM `$table` LIKE $q";
    $st = $dbc->query($sql);
    return $st && $st->fetch() ? true : false;
  } else {
    $col = $dbc->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `$table` LIKE '$col'";
    $res = $dbc->query($sql);
    if ($res === false) throw new Exception($dbc->error ?: 'SHOW COLUMNS failed');
    $ok = $res->num_rows > 0;
    $res->free();
    return $ok;
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

if ($action === 'list') {
  try {
    $hasOrderedAt = db_has_column($dbc,$dbType,'orders','ordered_at');
    $orderCol = $hasOrderedAt ? 'ordered_at' : 'created_at';

    $tables = db_query_all(
      $dbc, $dbType,
      "SELECT id, table_number, floor, capacity, status
       FROM tables
       ORDER BY floor ASC, table_number ASC"
    );

    $result = [];
    $floorsAgg = [];
    $summaryAgg = ['total_tables'=>0,'total_seats'=>0,'free_tables'=>0,'busy_tables'=>0];

    foreach ($tables as $t) {
      $tableNumber = (int)$t['table_number'];

      // Đơn chưa finalized gần nhất theo thời gian order (ordered_at nếu có, fallback created_at)
      $order = db_query_one(
        $dbc, $dbType,
        "SELECT id, table_number, items, total, payment_method, payment_status, status, created_at, ref_code,
                $orderCol AS ordered_at, TIMESTAMPDIFF(MINUTE, $orderCol, NOW()) AS wait_mins
         FROM orders
         WHERE table_number = ?
           AND status NOT IN ('paid','cancelled')
         ORDER BY $orderCol DESC
         LIMIT 1",
        [$tableNumber]
      );

      $current_order = null;
      if ($order) {
        // Lấy items từ order_items + dishes (fallback JSON)
        $lineItems = db_query_all(
          $dbc,$dbType,
          "SELECT oi.dish_id AS id, d.name, oi.price, oi.quantity, d.image
           FROM order_items oi
           LEFT JOIN dishes d ON d.id = oi.dish_id
           WHERE oi.order_id = ?",
          [(int)$order['id']]
        );
        if (!$lineItems || count($lineItems) === 0) {
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

        $current_order = [
          'id' => (int)$order['id'],
          'table_number' => (int)$order['table_number'],
          'items' => $lineItems,
          'total' => (float)$order['total'],
          'payment_method' => $order['payment_method'],
          'payment_status' => $order['payment_status'],
          'status' => $order['status'],
          'ref_code' => $order['ref_code'],
          'created_at' => $order['created_at'],
          'ordered_at' => $order['ordered_at'],
          'wait_mins' => (int)($order['wait_mins'] ?? 0)
        ];
      }

      $is_busy = ($t['status'] === 'unavailable') || ($current_order !== null);

      $row = [
        'id' => (int)$t['id'],
        'table_number' => $tableNumber,
        'floor' => (int)$t['floor'],
        'capacity' => isset($t['capacity']) ? (int)$t['capacity'] : 0,
        'status' => $t['status'],
        'is_busy' => $is_busy,
        'current_order' => $current_order
      ];
      $result[] = $row;

      $f = (int)$t['floor'];
      if (!isset($floorsAgg[$f])) {
        $floorsAgg[$f] = ['floor'=>$f,'total_tables'=>0,'total_seats'=>0,'free_tables'=>0,'busy_tables'=>0];
      }
      $floorsAgg[$f]['total_tables'] += 1;
      $floorsAgg[$f]['total_seats'] += $row['capacity'];
      if ($is_busy) $floorsAgg[$f]['busy_tables'] += 1; else $floorsAgg[$f]['free_tables'] += 1;

      $summaryAgg['total_tables'] += 1;
      $summaryAgg['total_seats'] += $row['capacity'];
      if ($is_busy) $summaryAgg['busy_tables'] += 1; else $summaryAgg['free_tables'] += 1;
    }

    ksort($floorsAgg);
    echo json_encode([
      'success' => true,
      'summary' => $summaryAgg,
      'floors' => array_values($floorsAgg),
      'tables' => $result,
      'refreshed_at' => gmdate('c')
    ]);
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);