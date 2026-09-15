/* Halaman Cakupan Layanan: penyaring daftar daerah dan penyembunyian grup kosong. */
(function () {
  "use strict";

  function init() {
    var input = document.getElementById("cari-daerah");
    var list = document.getElementById("daftar-cakupan");
    if (!input || !list) return;

    var groups = list.querySelectorAll(".coverage-group");

    /* Grup yang seluruh barisnya tersaring disembunyikan, termasuk judul kotanya. */
    function syncGroups() {
      var visibleRows = 0;
      Array.prototype.forEach.call(groups, function (group) {
        var visible = 0;
        Array.prototype.forEach.call(group.querySelectorAll(".coverage-row"), function (row) {
          if (!row.hidden) visible += 1;
        });
        group.hidden = visible === 0;
        visibleRows += visible;
      });
      list.hidden = visibleRows === 0;
    }

    if (window.SK && typeof window.SK.initFilter === "function") {
      window.SK.initFilter({
        input: "#cari-daerah",
        list: "#daftar-cakupan",
        item: ".coverage-row",
        count: "#hitung-cakupan",
        empty: "#cakupan-kosong",
      });
    }

    input.addEventListener("input", syncGroups);

    var form = input.closest("form");
    if (form) {
      form.addEventListener("reset", function () {
        window.setTimeout(syncGroups, 0);
      });
    }

    var reset = document.getElementById("reset-cari-cakupan");
    if (reset) {
      reset.addEventListener("click", function () {
        input.value = "";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.focus();
      });
    }

    syncGroups();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
