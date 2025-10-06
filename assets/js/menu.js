document.getElementById("sidebarToggle").onclick = function () {
  document.getElementById("sidebarMenu").classList.add("open");
  document.getElementById("sidebarOverlay").style.display = "block";
};
document.getElementById("sidebarOverlay").onclick = function () {
  document.getElementById("sidebarMenu").classList.remove("open");
  document.getElementById("sidebarOverlay").style.display = "none";
};
