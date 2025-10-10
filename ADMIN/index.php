
<?php 
$title = "Trang chủ";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/login.php");
    exit();
}

// // Kiểm tra quyền admin hoặc staff
// if ($_SESSION['role'] != 'staff' && $_SESSION['role'] != 'admin') {
//     die("Bạn không có quyền truy cập trang này!");
// }
// Kiểm tra quyền admin hoặc staff
if ($_SESSION['role'] != 'admin') {
    die("Bạn không có quyền truy cập trang này!");
}

// Lấy thông tin user từ session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$restaurant_id = $_SESSION['restaurant_id'];


include '../functions/database.php';
// include '../includes/header.php';

if (!isset($_SESSION['order'])) $_SESSION['order'] = [];
// Lấy danh sách món ăn
$dishes = [];
$result = $conn->query("SELECT * FROM dishes");
while($row = $result->fetch_assoc()) {
    $dishes[] = $row;
}

// Thêm món: Lưu theo key là id
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

// Xóa món: Xóa đúng id
// if (isset($_POST['remove_dish'])) {
//     $id = intval($_POST['remove_dish']);
//     unset($_SESSION['order'][$id]);
// }
if (isset($_POST['remove_dish'])) {
    $id = intval($_POST['remove_dish']);
    if (isset($_SESSION['order'][$id])) {
        // Giảm số lượng đi 1
        $_SESSION['order'][$id]['quantity'] -= 1;
        // Nếu số lượng <= 0 thì xóa khỏi đơn luôn
        if ($_SESSION['order'][$id]['quantity'] <= 0) {
            unset($_SESSION['order'][$id]);
        }
    }
}

// Các nút lưu/gửi bếp/thành tiền (demo)
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'save') {
        $msg = "Đã lưu đơn!";
    } elseif ($_POST['action'] == 'kitchen') {
        $msg = "Đã gửi bếp!";
    } elseif ($_POST['action'] == 'checkout') {
        $msg = "Đã thanh toán!";
        $_SESSION['order'] = [];
    }
}

// Tính tổng tiền
$total = 0;
foreach ($_SESSION['order'] as $item) {
    $qty = isset($item['quantity']) ? $item['quantity'] : 1;
    $total += $item['price'] * $qty;
}
ob_start();
?>
<!-- <!DOCTYPE html> -->
<html>
<!-- <head>
    <title>Order Món Ăn</title>
    <link rel="stylesheet" href="order.css">
</head> -->
<body>
    <!-- HEADER (giả lập) -->
    <!-- <header style="height:70px;background:#1976d2;color:#fff;display:flex;align-items:center;padding-left:20px;">
        <span style="font-weight:bold;font-size:22px;">Order Món Ăn</span>
    </header> -->
    <div class="order-container">
        <!-- Bên trái: Món đã chọn -->
        <div class="order-left">
            <h3>Đã chọn</h3>
            <div class="order-list-wrap">
                <ul class="order-list">
                    <?php foreach($_SESSION['order'] as $id => $item): ?>
                        <?php $qty = isset($item['quantity']) ? $item['quantity'] : 1; ?>
                        <li>
                            <img src="<?php echo $item['image']; ?>" class="dish-img">
                            <span><?php echo $item['name']; ?> (<?php echo $qty; ?>)</span>
                            <span><?php echo number_format($item['price'] * $qty); ?>đ</span>
                            <form method="post" style="display:inline;">
                                <button class="remove-btn" type="submit" name="remove_dish" value="<?php echo $id; ?>">Xóa</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    <?php if(count($_SESSION['order']) == 0): ?>
                        <li>Chưa chọn món nào</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="total-block">
                <span>Tổng tiền: </span>
                <span class="total-price"><?php echo number_format($total); ?> đ</span>
            </div>
            <form method="post" class="action-group">
                <button type="submit" name="action" value="save" class="action-btn save">Lưu</button>
                <button type="submit" name="action" value="kitchen" class="action-btn kitchen">Gửi bếp</button>
                <button type="submit" name="action" value="checkout" class="action-btn checkout">Tính tiền</button>
            </form>
            <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>
        </div>
        <!-- Bên phải: Món để chọn -->
        <div class="order-right">
            <h3>Chọn món</h3>
            <div class="dish-grid">
                <?php foreach($dishes as $dish): ?>
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
</body>
</html>





<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>