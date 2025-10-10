<header>
<div class="header-bar">
    <div class="header-left">
    <span class="menu-icon" id="sidebarToggle">
    <i class="fa-solid fa-bars"></i>
</span>

<div class="sidebar-menu" id="sidebarMenu">
    <div class="sidebar-top">Tổng quát</div>
    <a class="sidebar-item" href="#"><i class="fa-solid fa-clipboard"></i>Order</a>
    <a class="sidebar-item" href="#"><i class="fa-solid fa-map"></i> Sơ đồ</a>
    <a class="sidebar-item" href="#"><i class="fa-solid fa-kitchen-set"></i>Trả món</a>
    <a class="sidebar-item" href="#"><i class="fa-solid fa-warehouse"></i>Kho</a>
    <a class="sidebar-item" href="#"><i class="fa-solid fa-comments"></i></i>Trợ giúp</a>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
            
        </span>
        <a class="header-btn order-btn active" href="index.php">
        <i class="fa-solid fa-clipboard"></i>
            <span>Order</span>
        </a>
        <a class="header-btn" href="#">
        <i class="fa-solid fa-map"></i> 
            <span>Sơ đồ</span>
        </a>
        <a class="header-btn" href="#">
        <i class="fa-solid fa-kitchen-set"></i>
            <span>Trả món</span>
        </a> 
    </div>
    <!-- <div class="header-right">
        <span class="power-icon">
            <img src="icons/power.png" alt="Power" />
    </span> -->
        <!-- <span class="user-info">
    <label for="avatarUpload" style="cursor:pointer;">
        <img id="userAvatar" src="icons/user.png" alt="User" />
    </label>
    <input type="file" id="avatarUpload" style="display:none;" accept="image/*" />
    <span class="username">Nguyễn Văn Tèo</span>
    <span class="dropdown-arrow">&#9660;</span>
    </span> -->
    <!-- </div> -->
    <div class="user-info">
    <label for="avatarUpload" style="cursor:pointer;">
        <img id="userAvatar" src="icons/user.png" alt="User" />
    </label>
    <input type="file" id="avatarUpload" style="display:none;" accept="image/*" />
    <span class="username">
        <?php echo htmlspecialchars($_SESSION['username']); ?>
    </span>
    <span class="dropdown-arrow" onclick="toggleUserMenu()">&#9660;</span>
    <div id="user-menu" class="user-menu">
        <div class="menu-item user">
            <?php echo htmlspecialchars($_SESSION['username']); ?>
        </div>
        <div class="menu-item logout" onclick="window.location.href='../functions/logoutAdmin.php'">
            Đăng xuất
        </div>
    </div>
</div>
</div>
</header>