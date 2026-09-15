(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var params = new URLSearchParams(window.location.search);
    var box = document.getElementById("login-error");

    if (box && params.get("error") === "1") {
      box.innerHTML = "";
      var alert = document.createElement("p");
      alert.className = "alert alert--danger";
      alert.setAttribute("role", "alert");
      alert.setAttribute("tabindex", "-1");
      var title = document.createElement("strong");
      title.className = "alert__title";
      title.textContent = "Login gagal";
      var message = document.createElement("span");
      message.textContent = "Username atau password tidak cocok. Periksa kembali lalu coba lagi.";
      alert.append(title, message);
      box.appendChild(alert);
      box.hidden = false;
      alert.focus();
    }

    var toggle = document.getElementById("toggle-password");
    var password = document.getElementById("password");
    if (toggle && password) {
      toggle.addEventListener("click", function () {
        var show = password.type === "password";
        password.type = show ? "text" : "password";
        toggle.textContent = show ? "Sembunyikan" : "Tampilkan";
        toggle.setAttribute("aria-pressed", show ? "true" : "false");
        password.focus();
      });
    }
  });
})();
