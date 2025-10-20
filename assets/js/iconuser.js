(() => {
  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  ready(() => {
    // Các phần tử trong headerStaff.php
    const dropdownArrow = document.querySelector('.dropdown-arrow');
    const username = document.querySelector('.user-info .username');
    const userMenu = document.getElementById('user-menu');

    if (!userMenu) {
      // Trang không có menu người dùng -> không khởi tạo để tránh lỗi
      return;
    }

    function toggle() {
      userMenu.classList.toggle('open');
    }

    function closeOnOutsideClick(e) {
      const clickedInsideMenu = userMenu.contains(e.target);
      const clickedToggle = dropdownArrow?.contains(e.target) || username?.contains(e.target);
      if (!clickedInsideMenu && !clickedToggle) {
        userMenu.classList.remove('open');
      }
    }

    // Gắn sự kiện nếu có phần tử
    if (dropdownArrow) dropdownArrow.addEventListener('click', toggle);
    if (username) username.addEventListener('click', toggle);
    document.addEventListener('click', closeOnOutsideClick);

    // Hỗ trợ inline onclick="toggleUserMenu()" trong headerStaff.php
    window.toggleUserMenu = toggle;
  });
})();