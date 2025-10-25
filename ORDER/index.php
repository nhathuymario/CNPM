<?php 
$title = "Order";
session_start();

require_once '../functions/database.php';

/* ===================== Input & helpers ===================== */

// Đọc tham số QR
$table_number = isset($_GET['table']) ? intval($_GET['table']) : 1;
$k            = isset($_GET['k']) ? $_GET['k'] : '';

// Tham số lọc
$category = isset($_GET['category']) ? trim(strtolower($_GET['category'])) : 'all';
$q        = isset($_GET['q']) ? trim($_GET['q']) : '';

// Khởi tạo giỏ hàng
if (!isset($_SESSION['order'])) $_SESSION['order'] = [];

// Helper: kiểm tra schema/bảng
function has_column(mysqli $conn, string $table, string $col): bool {
  $table = $conn->real_escape_string($table);
  $col   = $conn->real_escape_string($col);
  $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
  return $res && $res->num_rows > 0;
}
function has_table(mysqli $conn, string $table): bool {
  $table = $conn->real_escape_string($table);
  $res = $conn->query("SHOW TABLES LIKE '$table'");
  return $res && $res->num_rows > 0;
}

// Helper: nhãn hiển thị cho category
function cat_label(string $c): string {
  $c = strtolower($c);
  if ($c === 'food')  return 'Món ăn';
  if ($c === 'drink') return 'Đồ uống';
  if ($c === 'other') return 'Khác';
  return mb_convert_case($c, MB_CASE_TITLE, "UTF-8");
}

/* ===================== Tầng của bàn ===================== */

$stmt = $conn->prepare("SELECT floor FROM tables WHERE table_number = ? AND qr_secret = ?");
$stmt->bind_param("is", $table_number, $k);
$stmt->execute();
$res         = $stmt->get_result();
$table_info  = $res->fetch_assoc();
$floor       = $table_info ? intval($table_info['floor']) : 1;

/* ===================== Categories (tabs) ===================== */

$categories_db = [];
$catStmt = $conn->query("SELECT DISTINCT category FROM dishes ORDER BY category ASC");
while ($row = $catStmt->fetch_assoc()) {
  if (!empty($row['category'])) $categories_db[] = strtolower($row['category']);
}
$baseCats   = ['food','drink','other'];                 // luôn hiện 3 nhóm chuẩn
$categories = array_values(array_unique(array_merge($baseCats, $categories_db))); // nếu DB có thêm nhóm, tab sẽ xuất hiện

/* ===================== Dishes list (server-side filter) ===================== */

$dishes = [];
$where  = "1=1";
$params = [];
$types  = "";

// Lọc theo category (trừ khi 'all')
if ($category !== 'all') {
  $where    .= " AND category = ?";
  $params[]  = $category;
  $types    .= "s";
}

// Tìm kiếm theo q (server-side bổ trợ)
if ($q !== '') {
  $where    .= " AND (LOWER(name) LIKE ? OR CAST(id AS CHAR) LIKE ?)";
  $like      = '%' . mb_strtolower($q, 'UTF-8') . '%';
  $params[]  = $like; $types .= "s";
  $params[]  = $like; $types .= "s";
}

$sql = "SELECT id, name, price, image, category
        FROM dishes
        WHERE $where
        ORDER BY name ASC";
$stmtList = $conn->prepare($sql);
if (!empty($params)) {
  $stmtList->bind_param($types, ...$params);
}
$stmtList->execute();
$rList = $stmtList->get_result();
while ($row = $rList->fetch_assoc()) {
  $dishes[] = $row;
}

/* ===================== Cart operations ===================== */

// Thêm món (GET để giữ tham số)
if (isset($_GET['add_dish'])) {
  $id = intval($_GET['add_dish']);
  foreach ($dishes as $dish) {
    if ((int)$dish['id'] === $id) {
      if (!isset($_SESSION['order'][$id])) {
        $_SESSION['order'][$id] = [
          'id'       => intval($dish['id']),
          'name'     => $dish['name'],
          'price'    => floatval($dish['price']),
          'image'    => $dish['image'],
          'quantity' => 1
        ];
        $msg = "Đã thêm " . htmlspecialchars($dish['name']) . " vào giỏ.";
      } else {
        $msg = htmlspecialchars($dish['name']) . " đã có trong giỏ. Dùng nút +/− để thay đổi số lượng.";
      }
      break;
    }
  }
}

// Giảm/xóa món
if (isset($_POST['remove_dish']) || isset($_POST['dec_dish'])) {
  $id = isset($_POST['remove_dish']) ? intval($_POST['remove_dish']) : intval($_POST['dec_dish']);
  if (isset($_SESSION['order'][$id])) {
    $_SESSION['order'][$id]['quantity']--;
    if ($_SESSION['order'][$id]['quantity'] <= 0) unset($_SESSION['order'][$id]);
  }
}

// Tăng số lượng
if (isset($_POST['inc_dish'])) {
  $id = intval($_POST['inc_dish']);
  if (isset($_SESSION['order'][$id])) $_SESSION['order'][$id]['quantity']++;
}

// Tính tổng giỏ
$total = 0;
foreach ($_SESSION['order'] as $item) {
  $total += $item['price'] * $item['quantity'];
}

/* ===================== Place order (merge or create) ===================== */

if (isset($_POST['action']) && $_POST['action'] === 'place_order') {
  if ($total > 0) {
    $items_array         = array_values($_SESSION['order']);
    $hasPaymentStatus    = has_column($conn, 'orders', 'payment_status');
    $hasStatus           = has_column($conn, 'orders', 'status');
    $hasOrderItemsTbl    = has_table($conn, 'order_items');

    // Tìm đơn đang mở của bàn
    $cond = "1=1";
    if ($hasStatus) {
      $cond = "status NOT IN ('paid','cancelled')";
    } elseif ($hasPaymentStatus) {
      $cond = "payment_status <> 'paid'";
    }

    $sqlFind = "SELECT id, items, total, payment_method FROM orders WHERE table_number = ? AND $cond ORDER BY id DESC LIMIT 1";
    $stmtF   = $conn->prepare($sqlFind);
    $stmtF->bind_param("i", $table_number);
    $stmtF->execute();
    $openRes   = $stmtF->get_result();
    $openOrder = $openRes->fetch_assoc();

    $conn->begin_transaction();
    try {
      if ($openOrder) {
        // Gộp vào đơn hiện tại
        $order_id = intval($openOrder['id']);
        $additional_total = 0.0;

        // 1) Gộp vào order_items (nếu có)
        if ($hasOrderItemsTbl) {
          foreach ($items_array as $it) {
            $dish_id = intval($it['id']);
            $qty     = intval($it['quantity']);
            $price   = floatval($it['price']);

            $stmtChk = $conn->prepare("SELECT id, quantity FROM order_items WHERE order_id = ? AND dish_id = ? LIMIT 1");
            $stmtChk->bind_param("ii", $order_id, $dish_id);
            $stmtChk->execute();
            $r = $stmtChk->get_result()->fetch_assoc();

            if ($r) {
              $oi_id  = intval($r['id']);
              $stmtUp = $conn->prepare("UPDATE order_items SET quantity = quantity + ? WHERE id = ?");
              $stmtUp->bind_param("ii", $qty, $oi_id);
              $stmtUp->execute();
            } else {
              $stmtIns = $conn->prepare("INSERT INTO order_items (order_id, dish_id, price, quantity) VALUES (?, ?, ?, ?)");
              $stmtIns->bind_param("iidi", $order_id, $dish_id, $price, $qty);
              $stmtIns->execute();
            }
            $additional_total += $price * $qty;
          }
        }

        // 2) Gộp vào JSON items
        $current_json  = $openOrder['items'] ?? '[]';
        $current_items = json_decode($current_json, true);
        if (!is_array($current_items)) $current_items = [];
        $idxById = [];
        foreach ($current_items as $idx => $ci) {
          if (isset($ci['id'])) $idxById[intval($ci['id'])] = $idx;
        }
        foreach ($items_array as $it) {
          $did = intval($it['id']);
          if (isset($idxById[$did])) {
            $i = $idxById[$did];
            $curQ = isset($current_items[$i]['quantity']) ? intval($current_items[$i]['quantity']) : 0;
            $current_items[$i]['quantity'] = $curQ + intval($it['quantity']);
            $current_items[$i]['name']  = $it['name'];
            $current_items[$i]['price'] = $it['price'];
            if (!isset($current_items[$i]['image']) && isset($it['image'])) {
              $current_items[$i]['image'] = $it['image'];
            }
          } else {
            $current_items[] = [
              'id'       => $did,
              'name'     => $it['name'],
              'price'    => floatval($it['price']),
              'quantity' => intval($it['quantity']),
              'image'    => $it['image'] ?? null
            ];
          }
          if (!$hasOrderItemsTbl) {
            $additional_total += floatval($it['price']) * intval($it['quantity']);
          }
        }
        $new_items_json = json_encode(array_values($current_items), JSON_UNESCAPED_UNICODE);

        $stmtUpOrd = $conn->prepare("UPDATE orders SET items = ?, total = total + ? WHERE id = ?");
        $stmtUpOrd->bind_param("sdi", $new_items_json, $additional_total, $order_id);
        $stmtUpOrd->execute();

        // Khóa bàn
        $stmt2 = $conn->prepare("UPDATE tables SET status = 'unavailable' WHERE table_number = ?");
        $stmt2->bind_param("i", $table_number);
        $stmt2->execute();

        $conn->commit();
        $_SESSION['order'] = [];
        $msg = "✅ Đã thêm món vào đơn hiện tại (#" . $order_id . ").";
      } else {
        // Tạo đơn mới
        $items_json          = json_encode($items_array, JSON_UNESCAPED_UNICODE);
        $payment_method_new  = 'cash';     // dùng biến, KHÔNG bind hằng
        $payment_status      = 'pending';  // dùng biến, KHÔNG bind hằng
        $status              = 'pending';  // dùng biến, KHÔNG bind hằng

        if ($hasPaymentStatus) {
          $user_id = $_SESSION['user_id'] ?? null;

          if ($hasStatus) {
            $stmt = $conn->prepare("
              INSERT INTO orders (user_id, table_number, items, total, payment_method, payment_status, status)
              VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            //       i      i      s      d     s     s     s
            $stmt->bind_param(
              "iisdsss",
              $user_id,
              $table_number,
              $items_json,
              $total,
              $payment_method_new,
              $payment_status,
              $status
            );
          } else {
            $stmt = $conn->prepare("
              INSERT INTO orders (user_id, table_number, items, total, payment_method, payment_status)
              VALUES (?, ?, ?, ?, ?, ?)
            ");
            //       i      i      s      d     s     s
            $stmt->bind_param(
              "iisdss",
              $user_id,
              $table_number,
              $items_json,
              $total,
              $payment_method_new,
              $payment_status
            );
          }
        } else {
          if ($hasStatus) {
            // 'pending' là literal trong SQL -> không nằm trong bind_param
            $stmt = $conn->prepare("
              INSERT INTO orders (table_number, items, total, payment_method, status)
              VALUES (?, ?, ?, ?, 'pending')
            ");
            //       i      s      d     s
            $stmt->bind_param(
              "isds",
              $table_number,
              $items_json,
              $total,
              $payment_method_new
            );
          } else {
            $stmt = $conn->prepare("
              INSERT INTO orders (table_number, items, total, payment_method)
              VALUES (?, ?, ?, ?)
            ");
            //       i      s      d     s
            $stmt->bind_param(
              "isds",
              $table_number,
              $items_json,
              $total,
              $payment_method_new
            );
          }
        }

        $stmt->execute();
        $order_id = $stmt->insert_id;

        // Đổ xuống order_items nếu có
        if ($hasOrderItemsTbl) {
          foreach ($items_array as $it) {
            $dish_id = intval($it['id']);
            $qty     = intval($it['quantity']);
            $price   = floatval($it['price']);
            $stmtIns = $conn->prepare("INSERT INTO order_items (order_id, dish_id, price, quantity) VALUES (?, ?, ?, ?)");
            $stmtIns->bind_param("iidi", $order_id, $dish_id, $price, $qty);
            $stmtIns->execute();
          }
        }

        // Khóa bàn
        $stmt2 = $conn->prepare("UPDATE tables SET status = 'unavailable' WHERE table_number = ?");
        $stmt2->bind_param("i", $table_number);
        $stmt2->execute();

        $conn->commit();
        $_SESSION['order'] = [];
        $msg = "✅ Đã gọi món (Mã đơn: " . intval($order_id) . ").";
      }
    } catch (Throwable $e) {
      $conn->rollback();
      $msg = "❌ Không thể gọi món: " . htmlspecialchars($e->getMessage());
    }
  }
}

/* ===================== UI ===================== */

// Gom nội dung trang (UI)
ob_start();
?>
<div class="order-page">
  <div class="left">
    <div class="category-tabs">
      <!-- Tab Tất cả -->
      <a class="tab <?php echo $category==='all'?'active':''; ?>"
         href="?table=<?php echo $table_number; ?>&k=<?php echo htmlspecialchars($k); ?>&category=all">Tất cả</a>

      <!-- Tabs động theo category trong DB (luôn có food/drink/other và các category khác nếu có) -->
      <?php foreach ($categories as $cat): ?>
        <a class="tab <?php echo (strtolower($cat)===$category)?'active':''; ?>"
           href="?table=<?php echo $table_number; ?>&k=<?php echo htmlspecialchars($k); ?>&category=<?php echo urlencode(strtolower($cat)); ?>">
          <?php echo htmlspecialchars(cat_label($cat)); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="search-row">
      <form method="get" id="searchForm" style="display:flex;gap:8px;flex:1">
        <input type="hidden" name="table" value="<?php echo $table_number; ?>">
        <input type="hidden" name="k" value="<?php echo htmlspecialchars($k); ?>">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
        <input type="text" id="searchInput" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Nhập mã/Tên món cần tìm" style="flex:1">
        <button type="submit" class="add-btn" style="white-space:nowrap">Tìm</button>
      </form>
    </div>

    <div class="product-grid" id="productGrid">
      <?php foreach ($dishes as $dish): 
            $inCart = isset($_SESSION['order'][$dish['id']]); 
            $nameLc = mb_strtolower($dish['name'], 'UTF-8');
            $catVal = strtolower($dish['category']);
      ?>
        <form method="get" class="product-card" 
              data-name="<?php echo htmlspecialchars($nameLc); ?>"
              data-cat="<?php echo htmlspecialchars($catVal); ?>">
          <input type="hidden" name="floor" value="<?php echo $floor; ?>">
          <input type="hidden" name="table" value="<?php echo $table_number; ?>">
          <input type="hidden" name="k" value="<?php echo htmlspecialchars($k); ?>">
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
          <img src="<?php echo htmlspecialchars($dish['image']); ?>" alt="<?php echo htmlspecialchars($dish['name']); ?>">
          <div class="price-tag"><?php echo number_format($dish['price']/1000, 0); ?>K</div>
          <div class="title"><?php echo htmlspecialchars($dish['name']); ?></div>
          <?php if ($inCart): ?>
            <button type="button" class="add-btn disabled" disabled>Đã chọn</button>
          <?php else: ?>
            <button type="submit" name="add_dish" value="<?php echo intval($dish['id']); ?>" class="add-btn">Chọn</button>
          <?php endif; ?>
        </form>
      <?php endforeach; ?>

      <?php if (empty($dishes)): ?>
        <div class="empty" style="margin:12px 0">Không có món nào trong mục này.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="right">
    <div class="bill-header">
      <div class="table-code">Tầng: <?php echo htmlspecialchars($floor); ?></div>
      <div class="table-code">Bàn: <?php echo htmlspecialchars($table_number); ?></div>
      <div class="order-code">Đơn tạm</div>
    </div>

    <div class="bill-table">
      <div class="bill-row bill-head">
        <div class="col name">Tên món</div>
        <div class="col qty">SL</div>
        <div class="col amount">Thành tiền</div>
      </div>

      <?php if (count($_SESSION['order']) > 0): ?>
        <?php foreach ($_SESSION['order'] as $id => $item): ?>
          <div class="bill-row">
            <div class="col name"><?php echo htmlspecialchars($item['name']); ?></div>
            <div class="col qty">
              <form method="post" class="qty-form">
                <input type="hidden" name="floor" value="<?php echo $floor; ?>">
                <input type="hidden" name="table" value="<?php echo $table_number; ?>">
                <input type="hidden" name="k" value="<?php echo htmlspecialchars($k); ?>">
                <button type="submit" name="dec_dish" value="<?php echo intval($id); ?>" class="qty-btn minus" aria-label="Giảm">−</button>
                <span class="qty-num"><?php echo intval($item['quantity']); ?></span>
                <button type="submit" name="inc_dish" value="<?php echo intval($id); ?>" class="qty-btn plus" aria-label="Tăng">+</button>
              </form>
            </div>
            <div class="col amount"><?php echo number_format($item['price'] * $item['quantity']); ?> đ</div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty">Chưa chọn món nào.</div>
      <?php endif; ?>
    </div>

    <div class="total-line">
      <div class="label">Tổng tiền</div>
      <div class="value"><?php echo number_format($total); ?> đ</div>
    </div>

    <form method="post" class="checkout-form" id="checkoutForm">
      <input type="hidden" name="table" value="<?php echo $table_number; ?>">
      <input type="hidden" name="k" value="<?php echo htmlspecialchars($k); ?>">
      <button type="submit" name="action" value="place_order" class="primary-btn" id="btnPlaceOrder">Gọi món</button>
    </form>

    <?php if (!empty($msg)): ?>
      <div class="msg"><?php echo $msg; ?></div>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/masterOrder.php';
?>