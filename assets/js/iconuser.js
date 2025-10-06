document
  .getElementById("avatarUpload")
  .addEventListener("change", function (e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (ev) {
      document.getElementById("userAvatar").src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });
