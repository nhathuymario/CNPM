function toggleUserMenu() {
  var menu = document.getElementById("user-menu");
  menu.style.display = menu.style.display === "block" ? "none" : "block";
}

// Đóng menu nếu click ra ngoài
document.addEventListener("click", function (e) {
  var menu = document.getElementById("user-menu");
  var arrow = document.querySelector(".dropdown-arrow");
  if (menu && !menu.contains(e.target) && !arrow.contains(e.target)) {
    menu.style.display = "none";
  }
});
