<?php 
$title = "Trang chủ";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../functions/login.php");
    exit();
}

$user_id       = $_SESSION['user_id'];
$username      = $_SESSION['username'];
$restaurant_id = $_SESSION['restaurant_id'];

require '../functions/checkloginAdmin.php';
checkRole(['admin']);
include '../functions/database.php';

// =============== CẤU HÌNH THƯ MỤC UPLOAD ===============
$uploadDir = '../assets/images/'; // thư mục chứa ảnh thật sự
$savePathPrefix = 'assets/images/'; // đường dẫn lưu vào database


// ================= XỬ LÝ THÊM MÓN =================
if (isset($_POST['add_dish'])) {
    $name     = $_POST['name'];
    $category = $_POST['category'];
    $price    = intval($_POST['price']);

    // --- Xử lý upload ảnh ---
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        die("Vui lòng chọn ảnh món ăn!");
    }

    // Tạo tên file duy nhất
    $fileName = time() . '_' . basename($_FILES['image']['name']);
    $targetFile = $uploadDir . $fileName;

    // Nếu thư mục chưa tồn tại → tạo
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Upload file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        die("Upload ảnh thất bại!");
    }

    $imagePathDB = $savePathPrefix . $fileName;

    // Escape chuỗi
    $nameEsc = $conn->real_escape_string($name);
    $categoryEsc = $conn->real_escape_string($category);
    $imageEsc = $conn->real_escape_string($imagePathDB);

    $conn->query("
        INSERT INTO dishes (name, price, image, category) 
        VALUES ('$nameEsc', $price, '$imageEsc', '$categoryEsc')
    ");

    header("Location: index.php");
    exit();
}


// ================= XỬ LÝ XÓA MÓN =================
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM dishes WHERE id=$id");
    header("Location: index.php");
    exit();
}


// ================= XỬ LÝ SỬA MÓN =================
if (isset($_POST['edit_dish'])) {
    $id       = intval($_POST['id']);
    $name     = $_POST['name'];
    $category = $_POST['category'];
    $price    = intval($_POST['price']);

    // Ảnh hiện tại
    $currentImage = $_POST['current_image'];

    // Nếu có upload ảnh mới → thay ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['name'] != '') {

        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            die("Upload ảnh mới thất bại!");
        }

        $imagePathDB = $savePathPrefix . $fileName; 

    } else {
        // Không thay ảnh → giữ ảnh cũ
        $imagePathDB = $currentImage;
    }

    $nameEsc = $conn->real_escape_string($name);
    $categoryEsc = $conn->real_escape_string($category);
    $imageEsc = $conn->real_escape_string($imagePathDB);

    $conn->query("
        UPDATE dishes 
        SET name='$nameEsc', price=$price, category='$categoryEsc', image='$imageEsc'
        WHERE id=$id
    ");

    header("Location: index.php");
    exit();
}


// ================= LẤY DANH SÁCH MÓN =================
$dishes = [];
$result = $conn->query("SELECT * FROM dishes");
while ($row = $result->fetch_assoc()) {
    $dishes[] = $row;
}


// ================= LẤY MÓN CẦN SỬA =================
$edit_dish = null;
if (isset($_GET['edit_id'])) {
    $id = intval($_GET['edit_id']);
    $r = $conn->query("SELECT * FROM dishes WHERE id=$id");
    $edit_dish = $r->fetch_assoc();
}

ob_start();
?>


<div class="menu-management-container">

  <!-- FORM BÊN TRÁI -->
  <div class="menu-form">
    <h2>Quản lý món ăn</h2>

    <form method="post" enctype="multipart/form-data">
      
      <div class="form-group">
        <label>Tên món:</label>
        <input type="text" name="name" required 
          value="<?php echo $edit_dish ? htmlspecialchars($edit_dish['name']) : ''; ?>">
      </div>

      <div class="form-group">
        <label>Phân loại:</label>
        <select name="category" required>
          <option value="food"  <?php if ($edit_dish && $edit_dish['category']=='food') echo 'selected'; ?>>Món ăn</option>
          <option value="drink" <?php if ($edit_dish && $edit_dish['category']=='drink') echo 'selected'; ?>>Đồ uống</option>
          <option value="other" <?php if ($edit_dish && $edit_dish['category']=='other') echo 'selected'; ?>>Khác</option>
        </select>
      </div>

      <div class="form-group">
        <label>Giá (vnđ):</label>
        <input type="number" name="price" required
          value="<?php echo $edit_dish ? intval($edit_dish['price']) : ''; ?>">
      </div>

      <div class="form-group">
        <label>Ảnh món:</label>
        <input type="file" name="image" accept="image/*">

        <?php if ($edit_dish): ?>
          <p>Ảnh hiện tại:</p>
          <img src="../<?php echo $edit_dish['image']; ?>" style="max-width:120px; border-radius:5px;">
          <input type="hidden" name="current_image" value="<?php echo $edit_dish['image']; ?>">
        <?php endif; ?>
      </div>

      <?php if ($edit_dish): ?>
        <input type="hidden" name="id" value="<?php echo $edit_dish['id']; ?>">
        <input type="submit" name="edit_dish" value="Lưu lại">
        <a href="index.php" class="btn-cancel">Hủy</a>

      <?php else: ?>
        <input type="submit" name="add_dish" value="Thêm món">
      <?php endif; ?>

    </form>
  </div>


  <!-- BẢNG BÊN PHẢI -->
  <div class="menu-list-section">
    <table class="menu-list">
      <tr>
        <th>ID</th>
        <th>Tên món</th>
        <th>Loại</th>
        <th>Giá</th>
        <th>Ảnh</th>
        <th>Thao tác</th>
      </tr>

      <?php foreach($dishes as $dish): ?>
      <tr>
        <td><?php echo $dish['id']; ?></td>
        <td><?php echo htmlspecialchars($dish['name']); ?></td>
        <td><?php echo htmlspecialchars($dish['category']); ?></td>
        <td><?php echo number_format($dish['price']); ?>đ</td>
        <td>
          <?php if (!empty($dish['image'])): ?>
            <img src="../<?php echo $dish['image']; ?>" style="max-width:80px; border-radius:5px;">
          <?php endif; ?>
        </td>
        <td>
          <a class="btn btn-edit" href="index.php?edit_id=<?php echo $dish['id']; ?>">Sửa</a>
          <a class="btn btn-delete" href="index.php?delete_id=<?php echo $dish['id']; ?>" onclick="return confirm('Xóa món này?')">Xóa</a>
        </td>
      </tr>
      <?php endforeach; ?>

    </table>
  </div>

</div>


<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>
