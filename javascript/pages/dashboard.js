/* Dashboard staf: pencarian langsung, filter status, buka riwayat, dan dialog hapus transit.
   Semua penanganan memakai event delegation di level document karena baris tabel
   bisa dirender ulang oleh server. */
(function () {
  "use strict";

  var TOTAL_LABEL = "Menampilkan {x} dari {y} pengiriman";

  function init() {
    var search = document.querySelector("[data-filter-search]");
    var rows = Array.prototype.slice.call(document.querySelectorAll("[data-dash-row]"));
    var total = rows.length;
    var buttons = Array.prototype.slice.call(document.querySelectorAll("[data-status-filter]"));
    var countEl = document.querySelector("[data-filter-count]");
    var tableWrap = document.querySelector("[data-dash-table]");
    var emptyState = document.getElementById("dash-empty");
    var noMatchState = document.getElementById("dash-nomatch");
    var activeStatus = "all";

    function detailRowOf(row) {
      var toggle = row.querySelector("[data-row-toggle]");
      var id = toggle ? toggle.getAttribute("aria-controls") : "";
      return id ? document.getElementById(id) : null;
    }

    function collapse(row) {
      var toggle = row.querySelector("[data-row-toggle]");
      var detail = detailRowOf(row);
      if (toggle) toggle.setAttribute("aria-expanded", "false");
      if (detail) detail.hidden = true;
    }

    function statusOf(button) {
      return button ? button.getAttribute("data-status-filter") || "all" : "all";
    }

    function setPressed(value) {
      buttons.forEach(function (button) {
        button.setAttribute("aria-pressed", statusOf(button) === value ? "true" : "false");
      });
    }

    function matches(row) {
      var status = row.getAttribute("data-status") || "";
      if (activeStatus !== "all" && status !== activeStatus) return false;
      var needle = search ? search.value.trim().toLowerCase() : "";
      if (needle === "") return true;
      var haystack = (row.getAttribute("data-search") || row.textContent || "").toLowerCase();
      return haystack.indexOf(needle) !== -1;
    }

    function apply() {
      var shown = 0;
      rows.forEach(function (row) {
        var ok = matches(row);
        row.hidden = !ok;
        if (!ok) collapse(row);
        if (ok) shown += 1;
      });

      if (countEl) {
        countEl.textContent = TOTAL_LABEL.replace("{x}", String(shown)).replace("{y}", String(total));
      }

      var noMatch = total > 0 && shown === 0;
      if (tableWrap) tableWrap.hidden = total === 0 || noMatch;
      if (noMatchState) noMatchState.hidden = !noMatch;
      if (emptyState) emptyState.hidden = total > 0;
    }

    var refresh = window.SK && typeof window.SK.debounce === "function"
      ? window.SK.debounce(apply, 120)
      : apply;

    document.addEventListener("input", function (event) {
      if (event.target === search) refresh();
    });

    document.addEventListener("click", function (event) {
      var filter = event.target.closest("[data-status-filter]");
      if (filter) {
        activeStatus = statusOf(filter);
        setPressed(activeStatus);
        apply();
        return;
      }

      var reset = event.target.closest("[data-reset-filter]");
      if (reset) {
        if (search) search.value = "";
        activeStatus = "all";
        setPressed("all");
        apply();
        if (search) search.focus();
        return;
      }

      var toggle = event.target.closest("[data-row-toggle]");
      if (toggle) {
        var row = toggle.closest("[data-dash-row]");
        var detail = row ? detailRowOf(row) : null;
        if (!detail) return;
        var open = toggle.getAttribute("aria-expanded") === "true";
        toggle.setAttribute("aria-expanded", open ? "false" : "true");
        detail.hidden = open;
      }
    });

    /* Jika javascript/site.js tidak tersedia, sediakan pengait dialog sendiri.
       Bila SK.initDialogs ada, pengait di site.js yang dipakai agar showModal tidak dobel. */
    if (!window.SK || typeof window.SK.initDialogs !== "function") {
      document.addEventListener("click", function (event) {
        var opener = event.target.closest("[data-open-dialog]");
        if (opener) {
          var dialog = document.getElementById(opener.getAttribute("data-open-dialog") || "");
          if (dialog && typeof dialog.showModal === "function" && !dialog.open) {
            dialog.showModal();
            var autofocus = dialog.querySelector("[data-autofocus]");
            if (autofocus) autofocus.focus();
          }
          return;
        }
        var closer = event.target.closest("[data-close-dialog]");
        if (closer) {
          var owner = closer.closest("dialog");
          if (owner) owner.close();
        }
      });
    }

    apply();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
