
<header>
    <script></script>
<div class="header-bar">
    <div class="header-left">
    <span class="menu-icon" id="sidebarToggle">
    <i class="fa-solid fa-bars"></i>
</span>

<div class="sidebar-menu" id="sidebarMenu">
    <div class="sidebar-top">Tổng quát</div>
    <a class="sidebar-item" href="index.php"><i class="fa-solid fa-clipboard"></i>Chỉnh menu</a>
    <a class="sidebar-item" href="table.php"><i class="fa-solid fa-map"></i>Số bàn</a>
    <a class="sidebar-item" href="bank_account.php"><i class="fa-solid fa-qrcode"></i>QR Pay</a>
    <a class="sidebar-item" href="total_report.php"><i class="fa-solid fa-table-list"></i>Tổng ca</a>
    <a class="sidebar-item" href="#"><i class="fa-solid fa-comments"></i></i>Trợ giúp</a>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
            
        </span>
        <a class="header-btn order-btn active" href="index.php">
        <i class="fa-solid fa-clipboard"></i>
            <span>Chỉnh menu</span>
        </a>

        <a class="header-btn" href="table.php">
        <i class="fa-solid fa-map"></i> 
            <span>Số bàn</span>
        </a>

        <a class="header-btn" href="bank_account.php">
        <i class="fa-solid fa-qrcode"></i>
            <span>QR Pay</span>
        </a>

        </a>
        <a class="header-btn" href="total_report.php">
        <i class="fa-solid fa-table-list"></i> 
            <span>Tổng ca</span>
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
        <div class="menu-item logout" onclick="window.location.href='<?php echo BASE_URL; ?>functions/change_password.php'">
          Đổi mật khẩu
        </div>
        <div class="menu-item logout" onclick="window.location.href='<?php echo BASE_URL; ?>functions/logoutAdmin.php'">
          Đăng xuất
        </div>
    </div>
</div>
</div>
</header>