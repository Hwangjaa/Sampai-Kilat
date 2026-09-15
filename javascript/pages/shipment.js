(function () {
  "use strict";

  var RESI_PATTERN = /^RS-[0-9]{7}$/;

  /* Server-rendered errors are announced: move focus so screen readers read them. */
  function focusServerAlert() {
    var target = document.querySelector("[data-focus-target]");
    if (target) target.focus();
  }

  /* Character counters for the tightest database limits (writes into the help line). */
  function initCounters() {
    var inputs = document.querySelectorAll("[data-count]");
    Array.prototype.forEach.call(inputs, function (input) {
      var target = document.getElementById(input.getAttribute("data-count"));
      if (!target) return;
      var limit = Number(input.getAttribute("maxlength")) || 0;
      if (limit <= 0) return;
      var update = function () {
        var used = input.value.length;
        if (used >= limit) {
          target.textContent = "Batas " + limit + " karakter tercapai.";
          return;
        }
        target.textContent = "Batas " + limit + " karakter. Sisa " + (limit - used) + ".";
      };
      input.addEventListener("input", update);
      update();
    });
  }

  /* Show the real weight and price of the chosen service before it is submitted. */
  function initServiceSummary() {
    var select = document.querySelector("[data-servis-select]");
    var summary = document.querySelector("[data-servis-summary]");
    if (!select || !summary) return;

    var render = function () {
      var option = select.options[select.selectedIndex];
      if (!option || !option.value) {
        summary.textContent = "Belum ada layanan yang dipilih.";
        return;
      }
      summary.textContent =
        option.value + ": " +
        option.getAttribute("data-tipe") + ", paket " +
        option.getAttribute("data-paket") + ", berat " +
        option.getAttribute("data-berat") + ", biaya " +
        option.getAttribute("data-biaya") + ", pembayaran " +
        option.getAttribute("data-bayar") + ".";
    };

    select.addEventListener("change", render);
    render();
  }

  /* State clearly which position change will be saved. */
  function initPositionSummary() {
    var select = document.querySelector("[data-position-select]");
    var summary = document.querySelector("[data-position-summary]");
    if (!select || !summary) return;

    var currentId = select.getAttribute("data-current-id") || "";
    var labelFor = function (id) {
      for (var index = 0; index < select.options.length; index += 1) {
        if (select.options[index].value === id) return select.options[index].textContent.trim();
      }
      return id;
    };

    var render = function () {
      var currentLabel = currentId ? labelFor(currentId) : "";
      var chosen = select.value;
      if (!chosen) {
        summary.textContent = currentLabel
          ? "Lokasi saat ini " + currentLabel + ". Pilih lokasi baru pada daftar di atas."
          : "Pilih lokasi paket sekarang.";
        return;
      }
      if (chosen === currentId) {
        summary.textContent = "Lokasi tidak berubah (" + currentLabel + "). Menyimpan tetap memperbarui waktu pembaruan paket.";
        return;
      }
      var chosenLabel = labelFor(chosen);
      summary.textContent = currentLabel
        ? "Lokasi akan diubah dari " + currentLabel + " menjadi " + chosenLabel + "."
        : "Lokasi akan dicatat sebagai " + chosenLabel + ".";
    };

    select.addEventListener("change", render);
    render();
  }

  /* Registered customer name for the typed sender id, taken from the real datalist. */
  function initCustomerMatch() {
    var input = document.querySelector("[data-pelanggan-input]");
    var output = document.querySelector("[data-pelanggan-match]");
    if (!input || !output) return;
    var list = document.getElementById(input.getAttribute("list") || "");
    if (!list) return;

    var render = function () {
      var typed = input.value.trim().toUpperCase();
      output.hidden = true;
      output.textContent = "";
      if (typed === "") return;
      for (var index = 0; index < list.options.length; index += 1) {
        if (list.options[index].value.toUpperCase() === typed) {
          var name = (list.options[index].textContent || "").trim();
          output.textContent = name !== ""
            ? "Pelanggan terdaftar: " + name + "."
            : "ID pengirim terdaftar.";
          output.hidden = false;
          return;
        }
      }
    };

    input.addEventListener("input", render);
    input.addEventListener("change", render);
    render();
  }

  /* Recent tracking numbers for the lookup form, powered by SK.recentResi. */
  function initRecentResi() {
    var list = document.querySelector("[data-recent-resi]");
    if (!list || !window.SK || !window.SK.recentResi) return;
    var wrap = document.getElementById("recent-resi-wrap");
    var form = document.getElementById(list.getAttribute("data-recent-form") || "");
    var lookedUp = list.getAttribute("data-looked-up-resi") || "";

    if (RESI_PATTERN.test(lookedUp)) window.SK.recentResi.add(lookedUp);

    if (SK.recentResi.all().length === 0) {
      if (wrap) wrap.hidden = true;
      return;
    }
    if (wrap) wrap.hidden = false;

    SK.recentResi.render(list, function (resi) {
      if (!form) return;
      var input = form.querySelector("input[name=resi]");
      if (!input) return;
      input.value = resi;
      if (typeof form.requestSubmit === "function") form.requestSubmit();
      else form.submit();
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    focusServerAlert();
    initCounters();
    initServiceSummary();
    initPositionSummary();
    initCustomerMatch();
    initRecentResi();
  });
})();
