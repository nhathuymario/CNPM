
<?php 
$title = "Trang chủ";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/login.php");
    exit();
}
// Kiểm tra quyền admin hoặc staff
// if ($_SESSION['role'] != 'admin') {
//     die("Bạn không có quyền truy cập trang này!");
// }

// Lấy thông tin user từ session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$restaurant_id = $_SESSION['restaurant_id'];

require '../functions/checkloginAdmin.php';
checkRole(['admin']);
include '../functions/database.php';
// include '../includes/header.php';

// Xử lý thêm món
if (isset($_POST['add_dish'])) {
    $name = $_POST['name'];
    $price = intval($_POST['price']);
    $image = $_POST['image'];
    $conn->query("INSERT INTO dishes (name, price, image) VALUES ('$name', $price, '$image')");
    header("Location: dishes.php");
    exit();
}

// Xử lý xóa món
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM dishes WHERE id=$id");
    header("Location: index.php");
    exit();
}

// Xử lý sửa món
if (isset($_POST['edit_dish'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $price = intval($_POST['price']);
    $image = $_POST['image'];
    $conn->query("UPDATE dishes SET name='$name', price=$price, image='$image' WHERE id=$id");
    header("Location: index.php");
    exit();
}

// Lấy danh sách món
$dishes = [];
$result = $conn->query("SELECT * FROM dishes");
while($row = $result->fetch_assoc()) {
    $dishes[] = $row;
}

// Nếu sửa, lấy thông tin món
$edit_dish = null;
if (isset($_GET['edit_id'])) {
    $id = intval($_GET['edit_id']);
    $r = $conn->query("SELECT * FROM dishes WHERE id=$id");
    $edit_dish = $r->fetch_assoc();
}
ob_start();
?>

<div class="container">
    <h2>Quản lý món ăn</h2>
    <!-- Form thêm/sửa món -->
    <form method="post">
        <div class="form-group">
            <label>Tên món:</label>
            <input type="text" name="name" required value="<?php echo $edit_dish ? $edit_dish['name'] : ''; ?>">
        </div>
        <div class="form-group">
            <label>Giá (vnđ):</label>
            <input type="number" name="price" required value="<?php echo $edit_dish ? $edit_dish['price'] : ''; ?>">
        </div>
        <div class="form-group">
            <label>Ảnh (đường dẫn):</label>
            <input type="text" name="image" required value="<?php echo $edit_dish ? $edit_dish['image'] : ''; ?>">
        </div>
        <?php if ($edit_dish): ?>
            <input type="hidden" name="id" value="<?php echo $edit_dish['id']; ?>">
            <input type="submit" name="edit_dish" value="Lưu sửa">
            <a href="dishes.php" style="margin-left:12px;">Hủy</a>
        <?php else: ?>
            <input type="submit" name="add_dish" value="Thêm món">
        <?php endif; ?>
    </form>
    <hr>
    <!-- Bảng món ăn -->
    <table>
        <tr>
            <th>ID</th>
            <th>Tên món</th>
            <th>Giá</th>
            <th>Ảnh</th>
            <th>Thao tác</th>
        </tr>
        <?php foreach($dishes as $dish): ?>
        <tr>
            <td><?php echo $dish['id']; ?></td>
            <td><?php echo $dish['name']; ?></td>
            <td><?php echo number_format($dish['price']); ?>đ</td>
            <td><img src="<?php echo $dish['image']; ?>" class="dish-img"></td>
            <td>
                <a class="btn btn-edit" href="index.php?edit_id=<?php echo $dish['id']; ?>">Sửa</a>
                <a class="btn btn-delete" href="index.php?delete_id=<?php echo $dish['id']; ?>" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>



<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>