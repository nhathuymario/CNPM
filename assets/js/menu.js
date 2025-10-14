document.addEventListener("DOMContentLoaded", function () {
  var sidebarToggle = document.getElementById("sidebarToggle");
  var sidebarMenu = document.getElementById("sidebarMenu");
  var sidebarOverlay = document.getElementById("sidebarOverlay");

  if (sidebarToggle && sidebarMenu && sidebarOverlay) {
    sidebarToggle.onclick = function () {
      sidebarMenu.classList.add("open");
      sidebarOverlay.style.display = "block";
    };
    sidebarOverlay.onclick = function () {
      sidebarMenu.classList.remove("open");
      sidebarOverlay.style.display = "none";
    };
  }
});
