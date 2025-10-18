<?php 
$title = "Order";
session_start();

require_once '../functions/database.php';

// Đọc tham số QR (không ép khách đăng nhập)
$table_number = isset($_GET['table']) ? intval($_GET['table']) : 1;
$k = isset($_GET['k']) ? $_GET['k'] : '';

// Khởi tạo giỏ hàng
if (!isset($_SESSION['order'])) $_SESSION['order'] = [];

// Lấy danh sách món ăn
$dishes = [];
$result = $conn->query("SELECT id, name, price, image FROM dishes");
while ($row = $result->fetch_assoc()) {
    $dishes[] = $row;
}

// Thêm món (dùng GET để giữ ?table & ?k)
// YÊU CẦU: nếu món đã tồn tại trong giỏ thì KHÔNG tăng số lượng bằng nút "Chọn"
if (isset($_GET['add_dish'])) {
    $id = intval($_GET['add_dish']);
    foreach ($dishes as $dish) {
        if ($dish['id'] == $id) {
            if (!isset($_SESSION['order'][$id])) {
                $_SESSION['order'][$id] = [
                    'id' => intval($dish['id']),
                    'name' => $dish['name'],
                    'price' => floatval($dish['price']),
                    'image' => $dish['image'],
                    'quantity' => 1
                ];
                // Nội bộ
                $msg = "Đã thêm " . htmlspecialchars($dish['name']) . " vào giỏ.";
            } else {
                // Nội bộ
                $msg = htmlspecialchars($dish['name']) . " đã có trong giỏ. Dùng nút +/− để thay đổi số lượng.";
            }
            break;
        }
    }
}

// Giảm/xóa món bằng nút "−"
if (isset($_POST['remove_dish']) || isset($_POST['dec_dish'])) {
    $id = isset($_POST['remove_dish']) ? intval($_POST['remove_dish']) : intval($_POST['dec_dish']);
    if (isset($_SESSION['order'][$id])) {
        $_SESSION['order'][$id]['quantity']--;
        if ($_SESSION['order'][$id]['quantity'] <= 0) unset($_SESSION['order'][$id]);
    }
}

// Tăng số lượng món bằng nút "+"
if (isset($_POST['inc_dish'])) {
    $id = intval($_POST['inc_dish']);
    if (isset($_SESSION['order'][$id])) {
        $_SESSION['order'][$id]['quantity']++;
    }
}

// Tính tổng
$total = 0;
foreach ($_SESSION['order'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Đặt đơn (nút Tính tiền)
if (isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if ($total <= 0) {
        // Không hiển thị khi chưa có món
        $silentError = true;
    } else {
        $pm = $_POST['payment_method'] ?? 'cash';
        $payment_method = ($pm === 'bank_transfer') ? 'bank_transfer' : 'cash';

        $items_array = array_values($_SESSION['order']);
        $items_json = json_encode($items_array, JSON_UNESCAPED_UNICODE);

        // Kiểm tra cột payment_status tồn tại hay không
        $hasPaymentCols = $conn->query("SHOW COLUMNS FROM orders LIKE 'payment_status'")->num_rows > 0;

        if ($hasPaymentCols) {
            $user_id = $_SESSION['user_id'] ?? null;
            $payment_status = 'pending';
            $status = 'pending';

            $stmt = $conn->prepare("
                INSERT INTO orders (user_id, table_number, items, total, payment_method, payment_status, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "iisdsss",
                $user_id,
                $table_number,
                $items_json,
                $total,
                $payment_method,
                $payment_status,
                $status
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO orders (table_number, items, total, payment_method, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param(
                "isds",
                $table_number,
                $items_json,
                $total,
                $payment_method
            );
        }

        $stmt->execute();
        $order_id = $stmt->insert_id;

        // Khóa bàn
        $stmt2 = $conn->prepare("UPDATE tables SET status = 'unavailable' WHERE table_number = ?");
        $stmt2->bind_param("i", $table_number);
        $stmt2->execute();

        // Nếu chuyển khoản: tạo ref_code (nếu có cột) và chuyển sang trang thanh toán
        if ($payment_method === 'bank_transfer') {
            $hasRef = $conn->query("SHOW COLUMNS FROM orders LIKE 'ref_code'")->num_rows > 0;
            if ($hasRef) {
                $ref_code = 'CF' . strtoupper(dechex($order_id)) . '-' . date('d');
                $up = $conn->prepare("UPDATE orders SET ref_code = ? WHERE id = ?");
                $up->bind_param("si", $ref_code, $order_id);
                $up->execute();
            }
            $_SESSION['order'] = [];
            header("Location: payment.php?order_id=" . $order_id . "&table=" . urlencode($table_number) . "&k=" . urlencode($k));
            exit();
        }

        // Tiền mặt: giữ pending để nhân viên thu tại bàn
        $_SESSION['order'] = [];
        // HIỂN THỊ THÔNG BÁO CHO KHÁCH khi thanh toán tiền mặt
        $msg = "✅ Đã đặt đơn. Nhân viên sẽ phục vụ và thu tiền tại bàn.";
    }
}

// Gom nội dung trang (UI)
ob_start();
?>
<div class="order-page">
  <div class="left">
    <div class="category-tabs">
      <button class="tab active" data-filter="all">Hay dùng</button>
      <button class="tab" data-filter="food">Món ăn</button>
      <button class="tab" data-filter="drink">Đồ uống</button>
      <button class="tab" data-filter="other">Khác</button>
    </div>

    <div class="search-row">
      <input type="text" id="searchInput" placeholder="Nhập mã/Tên món cần tìm">
      <div class="sort">
        <span>Tên món</span>
        <span class="caret">▾</span>
      </div>
    </div>

    <div class="product-grid" id="productGrid">
      <?php foreach ($dishes as $dish): 
            $inCart = isset($_SESSION['order'][$dish['id']]); ?>
        <form method="get" class="product-card" data-name="<?php echo htmlspecialchars(mb_strtolower($dish['name'])); ?>">
          <input type="hidden" name="table" value="<?php echo $table_number; ?>">
          <input type="hidden" name="k" value="<?php echo htmlspecialchars($k); ?>">
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
    </div>
  </div>

  <div class="right">
    <div class="bill-header">
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

    <div class="add-more">
      <button type="button" class="add-more-btn" onclick="document.getElementById('searchInput').focus()">+ Thêm món khác</button>
    </div>

    <div class="total-line">
      <div class="label">Tổng tiền</div>
      <div class="value"><?php echo number_format($total); ?> đ</div>
    </div>

    <form method="post" class="checkout-form">
      <input type="hidden" name="table" value="<?php echo $table_number; ?>">
      <input type="hidden" name="k" value="<?php echo htmlspecialchars($k); ?>">

      <div class="payment-choices">
        <label><input type="radio" name="payment_method" value="cash" checked> Tiền mặt tại bàn</label>
        <label><input type="radio" name="payment_method" value="bank_transfer"> Chuyển khoản</label>
      </div>

      <button type="submit" name="action" value="place_order" class="primary-btn">Tính tiền</button>
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