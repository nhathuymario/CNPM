<?php
  // Giữ tham số bàn khi điều hướng giữa các trang
  $orderQuery = [];
  if (!empty($_GET['table'])) $orderQuery['table'] = $_GET['table'];
  if (!empty($_GET['k']))     $orderQuery['k'] = $_GET['k'];

  $orderHref = 'index.php' . (!empty($orderQuery) ? ('?' . http_build_query($orderQuery)) : '');

  // Dữ liệu cho nút Trợ giúp
  $dataTable = isset($_GET['table']) ? $_GET['table'] : '';
  $dataK     = isset($_GET['k']) ? $_GET['k'] : '';
?>
<header>
  <div class="header-bar">
    <div class="header-left">
      <span class="menu-icon" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
      </span>

      <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-top">Tổng quát</div>
        <a class="sidebar-item" href="<?php echo htmlspecialchars($orderHref); ?>">
          <i class="fa-solid fa-clipboard"></i>Order
        </a>
        <!-- Đã bỏ các mục khác theo yêu cầu -->
        <a class="sidebar-item" href="#"><i class="fa-solid fa-comments"></i>Trợ giúp</a>
      </div>

      <div class="sidebar-overlay" id="sidebarOverlay" style="display:none;"></div>

      <a class="header-btn order-btn" href="<?php echo htmlspecialchars($orderHref); ?>">
        <i class="fa-solid fa-clipboard"></i>
<<<<<<< HEAD
        <span>Order</span>
      </a>
=======
            <span>Order</span>
        </a>
        <a class="header-btn" href="#">
        <i class="fa-solid fa-map"></i> 
            <span>Sơ đồ</span>
        </a>
>>>>>>> 1ce4d838420fd2a429d0c41a03c085ae7ad349c3
    </div>

    <div class="header-right">
      <button
        id="callStaffBtn"
        class="header-btn help-btn"
        type="button"
        data-table="<?php echo htmlspecialchars($dataTable); ?>"
        data-k="<?php echo htmlspecialchars($dataK); ?>"
        title="Gọi nhân viên hỗ trợ"
      >
        <i class="fa-solid fa-bell"></i>
        <span>Trợ giúp</span>
      </button>
    </div>
  </div>
</header>