/* Formulir pendaftaran pelanggan: penghitung karakter yang menunjukkan batas kolom
   sebelum pengguna melewatinya, plus fokus ke kolom yang ditolak server. */
(function () {
  "use strict";

  function limitOf(counter, input) {
    var limit = Number(counter.getAttribute("data-limit"));
    if (!limit) limit = Number(input.getAttribute("maxlength"));
    return limit > 0 ? limit : 0;
  }

  function sync(counter) {
    var input = document.getElementById(counter.getAttribute("data-count-for") || "");
    if (!input) return;
    var limit = limitOf(counter, input);
    var length = input.value.length;
    var teks = length + " / " + limit;
    if (limit > 0 && length >= limit) {
      teks += " (batas kolom)";
    }
    counter.textContent = teks;
    counter.setAttribute("data-at-limit", limit > 0 && length >= limit ? "true" : "false");
  }

  function init() {
    var counters = Array.prototype.slice.call(document.querySelectorAll("[data-count-for]"));
    var form = document.querySelector("form[data-validate]");

    counters.forEach(sync);

    document.addEventListener("input", function (event) {
      var input = event.target;
      if (!input || !input.id) return;
      counters.forEach(function (counter) {
        if (counter.getAttribute("data-count-for") === input.id) sync(counter);
      });
    });

    document.addEventListener("reset", function () {
      window.setTimeout(function () {
        counters.forEach(sync);
      }, 0);
    });

    if (!form) return;

    /* Formulir bisa dibuka kembali setelah server menolak input: arahkan fokus ke
       kolom pertama yang bermasalah supaya pesan kesalahan langsung terbaca. */
    var invalid = form.querySelector(".field__error.is-visible");
    if (!invalid) return;
    var input = document.getElementById(invalid.getAttribute("data-error-for") || "");
    if (!input) return;
    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)");
    input.focus({ preventScroll: true });
    if (!reduce.matches && typeof input.scrollIntoView === "function") {
      input.scrollIntoView({ block: "center", behavior: "smooth" });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
