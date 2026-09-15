(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var input = document.getElementById("resi-number");
    var result = document.querySelector(".result-head__resi");
    var list = document.getElementById("recent-resi-list");
    var wrapper = document.getElementById("recent-resi");

    var focusTarget = document.querySelector("[data-focus-target]");
    if (focusTarget) {
      focusTarget.focus();
    }

    if (result && window.SK) {
      SK.recentResi.add(result.textContent.trim());
    }

    if (list && wrapper && input && window.SK) {
      SK.recentResi.render(list, function (resi) {
        input.value = resi;
        input.focus();
      });
    }

    document.addEventListener("click", function (event) {
      if (event.target.closest("[data-print]")) {
        window.print();
        return;
      }

      var clear = event.target.closest("[data-clear-resi]");
      if (!clear) return;
      if (input) {
        input.value = "";
        input.focus();
      }
      var form = document.querySelector(".track-form-card");
      if (form) {
        form.scrollIntoView({
          block: "start",
          behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
        });
      }
      if (window.SK) SK.toast("Masukkan nomor resi yang ingin dilacak.");
    });
  });
})();
