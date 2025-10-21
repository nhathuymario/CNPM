<?php
  // Tham số định tuyến Order
  $orderQuery = [];
  if (!empty($_GET['table'])) $orderQuery['table'] = $_GET['table'];
  if (!empty($_GET['k']))     $orderQuery['k'] = $_GET['k'];
  $orderHref = 'index.php' . (!empty($orderQuery) ? ('?' . http_build_query($orderQuery)) : '');

  // Ưu tiên dùng biến đã có từ ORDER/index.php; fallback dùng $_GET
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
        <!-- Trợ giúp trong sidebar: gọi API qua order-help.js -->
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

      <!-- Nút Order: thêm class help-btn để giống nút Trợ giúp -->
      <a class="header-btn order-btn help-btn"
         href="<?php echo htmlspecialchars($orderHref); ?>">
        <i class="fa-solid fa-clipboard"></i>
        <span>Order</span>
      </a>

      <!-- Nút Trợ giúp ngay cạnh Order (giữ nguyên) -->
      <button
        id="callStaffBtn"
        class="header-btn help-btn"
        type="button"
        data-action="help"
        <?php if ($dataTable !== ''): ?>
          data-table="<?php echo htmlspecialchars($dataTable); ?>"
        <?php endif; ?>
        data-k="<?php echo htmlspecialchars($dataK); ?>"
        title="Gọi nhân viên hỗ trợ"
      >
        <i class="fa-solid fa-bell"></i>
        <span>Trợ giúp</span>
      </button>
    </div>
  </div>
</header>