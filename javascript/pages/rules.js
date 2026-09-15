/* Halaman Larangan Pengiriman: pencarian kategori barang sisi klien. */
(function () {
  "use strict";

  function init() {
    var input = document.getElementById("cari-barang");
    var list = document.getElementById("daftar-larangan");
    if (!input || !list) return;

    if (window.SK && typeof window.SK.initFilter === "function") {
      window.SK.initFilter({
        input: "#cari-barang",
        list: "#daftar-larangan",
        item: ".rules-card",
        count: "#hitung-larangan",
        empty: "#larangan-kosong",
      });
    }

    var reset = document.getElementById("reset-cari-larangan");
    if (reset) {
      reset.addEventListener("click", function () {
        input.value = "";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.focus();
      });
    }
  }

  document.addEventListener("DOMContentLoaded", init);
})();
