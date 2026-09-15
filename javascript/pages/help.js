/* Halaman Bantuan: validasi form komplain dan penyusunan tautan mailto.
   Halaman ini tidak mengirim data ke server. Semua proses berjalan di peramban. */
(function () {
  "use strict";

  var EMAIL = "sampai@kilat.co.id";
  var SUBJECT = "Komplain pengiriman";

  function value(id) {
    var el = document.getElementById(id);
    return el ? el.value.trim() : "";
  }

  function fields(form) {
    return Array.prototype.slice.call(
      form.querySelectorAll("input:not([type=hidden]), select, textarea")
    );
  }

  /* Menyusun tautan mailto berisi seluruh isian form. */
  function buildMailto(resi) {
    var body = [
      "Nomor resi: " + resi,
      "Nama pengirim atau penerima: " + value("komplain-nama"),
      "Nomor telepon: " + value("komplain-telepon"),
      "Kategori kendala: " + value("komplain-kategori"),
      "",
      "Kronologi:",
      value("komplain-kronologi"),
      "",
      "Hasil yang diharapkan:",
      value("komplain-harapan"),
      "",
      "Pesan ini disusun dari halaman Bantuan dan komplain SampaiKilat.",
    ];
    return (
      "mailto:" +
      EMAIL +
      "?subject=" +
      encodeURIComponent(SUBJECT + " " + resi) +
      "&body=" +
      encodeURIComponent(body.join("\n"))
    );
  }

  function init() {
    var form = document.getElementById("form-komplain");
    if (!form) return;

    var empty = document.getElementById("mailto-empty");
    var ready = document.getElementById("mailto-ready");
    var link = document.getElementById("mailto-link");
    var detail = document.getElementById("mailto-detail");
    var summary = form.querySelector("[data-form-alert]");

    function showSummary(message) {
      if (!summary) return;
      summary.textContent = message;
      summary.hidden = message === "";
    }

    form.addEventListener("submit", function (event) {
      /* Komplain tidak pernah dikirim otomatis dari halaman ini. */
      event.preventDefault();

      var list = fields(form);
      var invalid = [];
      list.forEach(function (input) {
        var valid = true;
        if (window.SK && typeof window.SK.validateField === "function") {
          valid = window.SK.validateField(input);
        }
        if (!valid) invalid.push(input);
      });

      if (invalid.length) {
        showSummary(
          invalid.length === 1
            ? "1 kolom perlu diperbaiki sebelum email disusun."
            : invalid.length + " kolom perlu diperbaiki sebelum email disusun."
        );
        if (ready) ready.hidden = true;
        if (empty) empty.hidden = false;
        invalid[0].focus();
        return;
      }

      showSummary("");
      var resi = value("komplain-resi");
      var url = buildMailto(resi);
      if (link) {
        link.setAttribute("href", url);
        link.setAttribute("data-mailto", url);
      }
      if (detail) {
        detail.textContent = "Subjek email: " + SUBJECT + " " + resi + ".";
      }
      if (empty) empty.hidden = true;
      if (ready) ready.hidden = false;
      if (window.SK && typeof window.SK.toast === "function") {
        window.SK.toast("Email komplain disusun. Buka aplikasi email untuk mengirim.", "success");
      }
      if (link) link.focus();
    });

    form.addEventListener("reset", function () {
      window.setTimeout(function () {
        showSummary("");
        if (ready) ready.hidden = true;
        if (empty) empty.hidden = false;
        if (link) {
          link.setAttribute("href", "mailto:" + EMAIL);
          link.removeAttribute("data-mailto");
        }
        if (detail) detail.textContent = "Subjek email: " + SUBJECT + ".";
      }, 0);
    });
  }

  document.addEventListener("DOMContentLoaded", init);
})();
