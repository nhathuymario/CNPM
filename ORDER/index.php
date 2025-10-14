<?php 
$title = "Trang chủ";
session_start();

// // ✅ Kiểm tra đăng nhập
// if (!isset($_SESSION['user_id'])) {
//     header("Location: ../functions/login.php");
//     exit();
// }

// // ✅ Kiểm tra quyền truy cập
// if (!isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
//     header('Location: ../staff/index.php');
//     exit();
// }

// // ✅ Thông tin user
// $user_id = $_SESSION['user_id'];
// $username = $_SESSION['username'];
// $restaurant_id = $_SESSION['restaurant_id'];

include '../functions/database.php';

// ✅ Khởi tạo giỏ hàng
if (!isset($_SESSION['order'])) $_SESSION['order'] = [];

// ✅ Lấy danh sách món ăn
$dishes = [];
$result = $conn->query("SELECT * FROM dishes");
while ($row = $result->fetch_assoc()) {
    $dishes[] = $row;
}

// ✅ Xử lý thêm món
if (isset($_POST['add_dish'])) {
    $id = intval($_POST['add_dish']);
    if (isset($_SESSION['order'][$id])) {
        $_SESSION['order'][$id]['quantity']++;
    } else {
        foreach ($dishes as $dish) {
            if ($dish['id'] == $id) {
                $_SESSION['order'][$id] = [
                    'id' => $dish['id'],
                    'name' => $dish['name'],
                    'price' => $dish['price'],
                    'image' => $dish['image'],
                    'quantity' => 1
                ];
                break;
            }
        }
    }
}

// ✅ Xử lý xóa món
if (isset($_POST['remove_dish'])) {
    $id = intval($_POST['remove_dish']);
    if (isset($_SESSION['order'][$id])) {
        $_SESSION['order'][$id]['quantity']--;
        if ($_SESSION['order'][$id]['quantity'] <= 0) {
            unset($_SESSION['order'][$id]);
        }
    }
}

// ✅ Tính tổng
$total = 0;
foreach ($_SESSION['order'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

// ✅ Hành động đơn hàng
if (isset($_POST['action'])) {
    $table_number = 1; // sau này sẽ lấy từ QR
    if ($_POST['action'] == 'save') {
        $items_json = json_encode($_SESSION['order'], JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO orders (table_number, items, total, payment_method, status)
                                VALUES (?, ?, ?, 'cash', 'pending')");
        $stmt->bind_param("isi", $table_number, $items_json, $total);
        $stmt->execute();

        $stmt2 = $conn->prepare("UPDATE tables SET status = 'unavailable' WHERE table_number = ?");
        $stmt2->bind_param("i", $table_number);
        $stmt2->execute();

        $msg = "✅ Đã lưu đơn!";
    } elseif ($_POST['action'] == 'checkout') {
        $_SESSION['order'] = [];
        $stmt2 = $conn->prepare("UPDATE tables SET status = 'available' WHERE table_number = ?");
        $stmt2->bind_param("i", $table_number);
        $stmt2->execute();

        $msg = "💰 Đã thanh toán!";
    }
}

// ✅ Gom nội dung trang
ob_start();
?>
<div class="order-container">
    <!-- Bên trái -->
    <div class="order-left">
        <h3>Đã chọn</h3>
        <ul class="order-list">
            <?php foreach ($_SESSION['order'] as $id => $item): ?>
                <li>
                    <img src="<?php echo $item['image']; ?>" class="dish-img">
                    <span><?php echo $item['name']; ?> (<?php echo $item['quantity']; ?>)</span>
                    <span><?php echo number_format($item['price'] * $item['quantity']); ?>đ</span>
                    <form method="post" style="display:inline;">
                        <button type="submit" name="remove_dish" value="<?php echo $id; ?>">Xóa</button>
                    </form>
                </li>
            <?php endforeach; ?>
            <?php if (count($_SESSION['order']) == 0): ?>
                <li>Chưa chọn món nào</li>
            <?php endif; ?>
        </ul>

        <div class="total-block">
            <strong>Tổng tiền: </strong>
            <span><?php echo number_format($total); ?> đ</span>
        </div>

        <form method="post" class="action-group">
            <button type="submit" name="action" value="save">Lưu</button>
            <button type="submit" name="action" value="checkout"><a href="../functions/payment.php" class="action-btn checkout">Tính tiền</a></button>
        </form>

        <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>
    </div>

    <!-- Bên phải -->
    <div class="order-right">
        <h3>Chọn món</h3>
        <div class="dish-grid">
            <?php foreach ($dishes as $dish): ?>
                <form method="post" class="dish-card">
                    <img src="<?php echo $dish['image']; ?>" class="dish-img">
                    <div class="dish-name"><?php echo $dish['name']; ?></div>
                    <div class="dish-price"><?php echo number_format($dish['price']); ?>đ</div>
                    <button type="submit" name="add_dish" value="<?php echo $dish['id']; ?>">Chọn</button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

include '../includes/masterOrder.php';
?> 
