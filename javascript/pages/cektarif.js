(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("tarif-form");
    var result = document.getElementById("tarif-result");
    var empty = document.getElementById("tarif-empty");
    var rows = document.getElementById("tarif-rows");
    var weightText = document.getElementById("chargeable-weight");
    var mailButton = document.getElementById("kirim-rincian");
    var repeat = document.getElementById("ulangi");
    if (!form || !result || !rows) return;

    var ids = ["weight", "length", "width", "height"];
    var inputs = ids.map(function (id) {
      return document.getElementById(id);
    });
    var number = function (value) {
      return Number(String(value).replace(",", "."));
    };
    var decimal = function (value) {
      return value.toLocaleString("id-ID", { maximumFractionDigits: 2 });
    };

    function estimate() {
      var values = {
        weight: number(inputs[0].value),
        length: number(inputs[1].value),
        width: number(inputs[2].value),
        height: number(inputs[3].value),
      };
      var volumeWeight = (values.length * values.width * values.height) / 5000;
      var billable = Math.max(values.weight, volumeWeight);
      var regular = Math.ceil(5000 + billable * 2000);
      return {
        values: values,
        volumeWeight: volumeWeight,
        billable: billable,
        services: [
          { name: "Same Day", eta: "Hari yang sama", price: regular * 2 },
          { name: "Overnight", eta: "1 hari", price: Math.ceil(regular * 1.5) },
          { name: "Regular", eta: "2 hari", price: regular },
        ],
      };
    }

    function render(data) {
      rows.innerHTML = "";
      data.services.forEach(function (service) {
        var row = document.createElement("tr");
        var head = document.createElement("th");
        head.setAttribute("scope", "row");
        head.setAttribute("data-label", "Layanan");
        head.textContent = service.name;
        var eta = document.createElement("td");
        eta.setAttribute("data-label", "Estimasi tiba");
        eta.textContent = service.eta;
        var price = document.createElement("td");
        price.setAttribute("data-label", "Tarif");
        price.textContent = window.SK ? SK.formatRupiah(service.price) : "Rp" + service.price;
        row.append(head, eta, price);
        rows.appendChild(row);
      });

      var comparison =
        data.volumeWeight > data.values.weight
          ? "Berat volume lebih besar, jadi berat volume yang dipakai."
          : "Berat timbangan lebih besar, jadi berat paket yang dipakai.";
      weightText.innerHTML =
        "Berat paket <strong>" + decimal(data.values.weight) + " kg</strong> · berat volume <strong>" +
        decimal(Math.round(data.volumeWeight * 100) / 100) + " kg</strong> · berat tagihan <strong>" +
        decimal(Math.round(data.billable * 100) / 100) + " kg</strong>. " + comparison;

      var status = document.getElementById("tarif-status");
      if (status) {
        var now = new Date();
        status.textContent =
          "Estimasi dihitung pukul " + now.getHours() + "." + String(now.getMinutes()).padStart(2, "0") +
          " dari data yang Anda isi. Ubah angkanya untuk menghitung ulang.";
      }

      var kota = document.getElementById("kota");
      var lines = [
        "Saya ingin konfirmasi tarif pengiriman SampaiKilat.",
        "",
        "Kota tujuan: " + (kota && kota.value ? kota.value : "belum dipilih"),
        "Berat paket: " + decimal(data.values.weight) + " kg",
        "Ukuran: " + decimal(data.values.length) + " x " + decimal(data.values.width) + " x " + decimal(data.values.height) + " cm",
        "Berat volume: " + decimal(Math.round(data.volumeWeight * 100) / 100) + " kg",
        "Berat tagihan: " + decimal(Math.round(data.billable * 100) / 100) + " kg",
        "",
        "Estimasi dari halaman Cek Tarif:",
      ];
      data.services.forEach(function (service) {
        lines.push("- " + service.name + " (" + service.eta + "): " + (window.SK ? SK.formatRupiah(service.price) : service.price));
      });
      lines.push("", "Mohon dibantu pengecekan tarif finalnya. Terima kasih.");
      if (mailButton) {
        mailButton.href =
          "mailto:sampai@kilat.co.id?subject=" +
          encodeURIComponent("Permintaan konfirmasi tarif pengiriman" + (kota && kota.value ? " - " + kota.value : "")) +
          "&body=" +
          encodeURIComponent(lines.join("\n"));
      }
    }

    function show(compute) {
      if (!compute) return;
      var data = estimate();
      render(data);
      if (empty) empty.hidden = true;
      var wasHidden = result.hidden;
      result.hidden = false;
      if (wasHidden) {
        result.setAttribute("tabindex", "-1");
        result.focus();
        result.scrollIntoView({
          block: "nearest",
          behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
        });
      }
    }

    form.addEventListener("submit", function (event) {
      var invalid = inputs.filter(function (input) {
        return window.SK ? !SK.validateField(input) : false;
      });
      if (invalid.length) {
        if (empty) empty.hidden = false;
        result.hidden = true;
        return;
      }
      event.preventDefault();
      show(true);
    });

    var live = function () {
      if (result.hidden) return;
      var invalid = inputs.some(function (input) {
        return window.SK ? !SK.validateField(input) : false;
      });
      if (invalid) return;
      show(true);
    };
    var debounced = window.SK ? SK.debounce(live, 250) : live;
    inputs.forEach(function (input) {
      input.addEventListener("input", debounced);
    });

    var kota = document.getElementById("kota");
    if (kota) kota.addEventListener("change", debounced);

    function reset() {
      window.setTimeout(function () {
        result.hidden = true;
        if (empty) empty.hidden = false;
        if (inputs[0]) inputs[0].focus();
      }, 0);
    }

    form.addEventListener("reset", reset);
    if (repeat) {
      repeat.addEventListener("click", function () {
        result.hidden = true;
        if (empty) empty.hidden = false;
        if (inputs[0]) inputs[0].focus();
      });
    }
  });
})();
