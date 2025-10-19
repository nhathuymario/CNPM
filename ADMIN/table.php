<?php
// Kết nối database
include '../functions/database.php';
session_start();

// Xử lý xóa bàn nếu có yêu cầu
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM tables WHERE id = $id");
    header("Location: table.php");
    exit();
}
// Lấy dữ liệu bàn
$result = $conn->query("SELECT * FROM tables ORDER BY floor, table_number");
ob_start();
?>
<head>
<!-- <link rel="stylesheet" href="table.css"> -->
</head>
<div class="table-management-container">
    <!-- Bên trái: Form thêm/sửa bàn -->
    <div class="table-add-form">
        <?php
        // Hiển thị form sửa nếu chọn Sửa
        if (isset($_GET['edit'])):
            $id = intval($_GET['edit']);
            $edit = $conn->query("SELECT * FROM tables WHERE id = $id")->fetch_assoc();
        ?>
        <h3>Sửa bàn</h3>
        <form action="process.php" method="post">
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <label for="table_number">Số bàn:</label>
            <input type="number" id="table_number" name="table_number" value="<?= $edit['table_number'] ?>" required>
            
            <label for="floor">Tầng:</label>
            <input type="number" id="floor" name="floor" value="<?= $edit['floor'] ?>" min="1" required>
            
            <label for="capacity">Sức chứa:</label>
            <input type="number" id="capacity" name="capacity" value="<?= $edit['capacity'] ?>" required>
            <label for="status">Trạng thái:</label>
            <select id="status" name="status">
                <option value="available" <?= $edit['status']=='available'?'selected':'' ?>>Trống</option>
                <option value="unavailable" <?= $edit['status']=='unavailable'?'selected':'' ?>>Đã đặt</option>
            </select>
            <button type="submit" name="update">Cập nhật</button>
            <a href="table.php">Hủy</a>
        </form>
        <?php else: ?>
        <h3>Thêm bàn mới</h3>
        <form action="process.php" method="post">
            <label for="table_number">Số bàn:</label>
            <input type="number" id="table_number" name="table_number" required>
            
            <label for="floor">Tầng:</label>
            <input type="number" id="floor" name="floor" value="1" min="1" required>
            
            <label for="capacity">Sức chứa:</label>
            <input type="number" id="capacity" name="capacity" value="4" required>
            <label for="status">Trạng thái:</label>
            <select id="status" name="status">
                <option value="available">Trống</option>
                <option value="unavailable">Đã đặt</option>
            </select>
            <button type="submit" name="add">Thêm bàn</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Bên phải: Danh sách bàn ăn -->
    <div class="table-list-section">
        <h2>Danh sách bàn ăn</h2>
        <table class="table-list">
            <tr>
                <th>Số bàn</th>
                <th>Tầng</th>
                <th>Sức chứa</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th>Thao tác</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['table_number'] ?></td>
                <td><?= $row['floor'] ?></td>
                <td><?= $row['capacity'] ?></td>
                <td><?= $row['status'] ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    <a href="table.php?edit=<?= $row['id'] ?>">Sửa</a> |
                    <a href="table.php?delete=<?= $row['id'] ?>" onclick="return confirm('Xác nhận xóa bàn này?');">Xóa</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../includes/masterAdmin.php';
?>