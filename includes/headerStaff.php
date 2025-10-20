<?php
// Xác định trang hiện tại để set class "active"
$current = basename($_SERVER['SCRIPT_NAME']);
$isOrder = ($current === 'index.php');
$isFloor = ($current === 'floor.php');
?>
<header>
  <div class="header-bar">
    <div class="header-left">
      <span class="menu-icon" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
      </span>

      <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-top">Tổng quát</div>
        <a class="sidebar-item <?php echo $isOrder ? 'active' : ''; ?>" href="../STAFF/index.php">
          <i class="fa-solid fa-clipboard"></i> Order
        </a>
        <a class="sidebar-item <?php echo $isFloor ? 'active' : ''; ?>" href="../STAFF/floor.php">
          <i class="fa-solid fa-map"></i> Sơ đồ
        </a>
        <a class="sidebar-item" href="#">
          <i class="fa-solid fa-kitchen-set"></i> Trả món
        </a>
        <a class="sidebar-item" href="#">
          <i class="fa-solid fa-warehouse"></i> Kho
        </a>
        <a class="sidebar-item" href="#">
          <i class="fa-solid fa-comments"></i> Trợ giúp
        </a>
      </div>
      <div class="sidebar-overlay" id="sidebarOverlay"></div>

      <a class="header-btn order-btn <?php echo $isOrder ? 'active' : ''; ?>" href="../STAFF/index.php">
        <i class="fa-solid fa-clipboard"></i>
        <span>Order</span>
      </a>
      <a class="header-btn <?php echo $isFloor ? 'active' : ''; ?>" href="../STAFF/floor.php">
        <i class="fa-solid fa-map"></i>
        <span>Sơ đồ</span>
      </a>
    </div>

    <div class="user-info">
      <span class="username">
        <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
      </span>
      <span class="dropdown-arrow" onclick="toggleUserMenu()">&#9660;</span>
      <div id="user-menu" class="user-menu">
        <div class="menu-item user">
          <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
        </div>
        <div class="menu-item logout" onclick="window.location.href='/functions/logoutStaff.php'">
          Đăng xuất
        </div>
      </div>
    </div>
  </div>
</header>