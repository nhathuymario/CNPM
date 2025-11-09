<?php
  // Tham số định tuyến Order
  $orderQuery = [];
  if (!empty($_GET['table'])) $orderQuery['table'] = $_GET['table'];
  if (!empty($_GET['k']))     $orderQuery['k'] = $_GET['k'];
  $orderHref = 'index.php' . (!empty($orderQuery) ? ('?' . http_build_query($orderQuery)) : '');

  $dataTable = isset($table_number) ? (int)$table_number : (isset($_GET['table']) ? (int)$_GET['table'] : '');
  $dataK     = isset($k) ? $k : (isset($_GET['k']) ? $_GET['k'] : '');
?>
<header>
  <div class="header-bar">
    <div class="header-left">
      <span class="menu-icon" id="sidebarToggle" title="Mở menu">
        <i class="fa-solid fa-bars"></i>
      </span>

      <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-top">Tổng quát</div>
        <a class="sidebar-item" href="<?php echo htmlspecialchars($orderHref); ?>">
          <i class="fa-solid fa-clipboard"></i>Order
        </a>
        <a class="sidebar-item"
           href="#"
           data-action="help"
           <?php if ($dataTable !== ''): ?>
             data-table="<?php echo htmlspecialchars($dataTable); ?>"
           <?php endif; ?>
           title="Gọi nhân viên hỗ trợ">
          <i class="fa-solid fa-bell"></i>Trợ giúp
        </a>
      </div>

      <div class="sidebar-overlay" id="sidebarOverlay" style="display:none;"></div>

      <!-- Đồng bộ kiểu nút với Staff/Admin: dùng header-btn -->
      <a class="header-btn order-btn" href="<?php echo htmlspecialchars($orderHref); ?>">
        <i class="fa-solid fa-clipboard"></i>
        <span>Order</span>
      </a>
      <a
        id="callStaffBtn"
        class="header-btn"
        href="#"
        data-action="help"
        <?php if ($dataTable !== ''): ?>
          data-table="<?php echo htmlspecialchars($dataTable); ?>"
        <?php endif; ?>
        data-k="<?php echo htmlspecialchars($dataK); ?>"
        title="Gọi nhân viên hỗ trợ"
      >
        <i class="fa-solid fa-bell"></i>
        <span>Trợ giúp</span>
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
        <a class="menu-item" href="../functions/logout.php">
          <i class="fa-solid fa-right-from-bracket"></i>Đăng xuất
        </a>
      </div>
    </div>
  </div>
</header>