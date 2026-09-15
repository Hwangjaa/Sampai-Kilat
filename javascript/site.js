(function () {
  "use strict";

  var SK = {};
  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

  /* ---------- Mobile navigation drawer ---------- */
  SK.initNav = function () {
    var toggle = document.querySelector("[data-nav-toggle]");
    var nav = document.getElementById(toggle ? toggle.getAttribute("aria-controls") : "");
    if (!toggle || !nav) return;

    var scrim = document.createElement("div");
    scrim.className = "nav-scrim";
    scrim.hidden = true;
    document.body.appendChild(scrim);

    var supportsDialogTrap = typeof nav.querySelectorAll === "function";

    function focusable() {
      return Array.prototype.filter.call(
        nav.querySelectorAll("a[href], button:not([disabled])"),
        function (el) {
          return el.offsetParent !== null;
        }
      );
    }

    function open() {
      scrim.hidden = false;
      toggle.setAttribute("aria-expanded", "true");
      toggle.setAttribute("aria-label", "Tutup menu");
      document.body.classList.add("nav-open");
      var first = focusable()[0];
      if (first && supportsDialogTrap) first.focus();
    }

    function close(returnFocus) {
      toggle.setAttribute("aria-expanded", "false");
      toggle.setAttribute("aria-label", "Buka menu");
      document.body.classList.remove("nav-open");
      scrim.hidden = true;
      if (returnFocus) toggle.focus();
    }

    toggle.addEventListener("click", function () {
      if (toggle.getAttribute("aria-expanded") === "true") close(true);
      else open();
    });

    scrim.addEventListener("click", function () {
      close(true);
    });

    nav.addEventListener("click", function (event) {
      if (event.target.closest("a") && toggle.offsetParent !== null) close(false);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape" || toggle.getAttribute("aria-expanded") !== "true") return;
      close(true);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Tab" || toggle.getAttribute("aria-expanded") !== "true") return;
      var items = focusable();
      if (!items.length) return;
      var first = items[0];
      var last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 1080 && toggle.getAttribute("aria-expanded") === "true") close(false);
    });
  };

  /* ---------- Sticky header shadow ---------- */
  SK.initHeader = function () {
    var header = document.querySelector(".site-header");
    if (!header) return;
    var onScroll = function () {
      header.classList.toggle("is-stuck", window.scrollY > 4);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  };

  /* ---------- Toast feedback ---------- */
  SK.toast = function (message, variant) {
    var stack = document.querySelector(".toast-stack");
    if (!stack) {
      stack = document.createElement("div");
      stack.className = "toast-stack";
      stack.setAttribute("role", "status");
      stack.setAttribute("aria-live", "polite");
      document.body.appendChild(stack);
    }
    var toast = document.createElement("div");
    toast.className = "toast" + (variant ? " toast--" + variant : "");
    toast.textContent = message;
    stack.appendChild(toast);
    window.setTimeout(function () {
      toast.style.transition = "opacity 200ms ease, transform 200ms ease";
      toast.style.opacity = "0";
      toast.style.transform = "translateY(0.5rem)";
      window.setTimeout(function () {
        toast.remove();
      }, 220);
    }, variant === "danger" ? 6000 : 3600);
  };

  /* ---------- Copy to clipboard ---------- */
  SK.initCopy = function () {
    document.addEventListener("click", function (event) {
      var button = event.target.closest("[data-copy]");
      if (!button) return;
      var value = button.getAttribute("data-copy");
      var done = function (ok) {
        button.textContent = ok ? "Tersalin" : "Gagal menyalin";
        window.setTimeout(function () {
          button.textContent = button.getAttribute("data-copy-label") || "Salin";
        }, 1800);
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(function () {
          done(true);
        }, function () {
          done(false);
        });
      } else {
        done(false);
      }
    });
  };

  /* ---------- Native dialog helpers ---------- */
  SK.initDialogs = function () {
    document.addEventListener("click", function (event) {
      var opener = event.target.closest("[data-open-dialog]");
      if (opener) {
        var dialog = document.getElementById(opener.getAttribute("data-open-dialog"));
        if (dialog && typeof dialog.showModal === "function") {
          dialog.showModal();
          var target = dialog.querySelector("[data-autofocus]");
          if (target) target.focus();
        }
        return;
      }
      var closer = event.target.closest("[data-close-dialog]");
      if (closer) {
        var owner = closer.closest("dialog");
        if (owner) owner.close();
      }
    });
  };

  /* ---------- Form validation ---------- */
  var MESSAGES = {
    required: "Wajib diisi.",
    pattern: "Format belum sesuai.",
    email: "Alamat email belum valid.",
    tel: "Nomor telepon belum valid.",
    date: "Tanggal belum valid.",
    number: "Masukkan angka.",
    min: "Nilai terlalu kecil.",
    max: "Nilai terlalu besar.",
    minlength: "Teks terlalu pendek.",
    maxlength: "Teks terlalu panjang.",
    step: "Gunakan kelipatan yang diminta.",
  };

  function messageFor(input) {
    var value = input.value.trim();
    var override = function (key) {
      return input.getAttribute("data-msg-" + key) || "";
    };
    if (input.required && value === "") return override("required") || MESSAGES.required;
    if (value === "") return "";
    var type = input.getAttribute("type");
    var pattern = input.getAttribute("pattern");
    if (pattern && !new RegExp("^(?:" + pattern + ")$").test(value)) {
      return override("pattern") || MESSAGES.pattern;
    }
    if (type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
      return override("type") || MESSAGES.email;
    }
    if (type === "tel" && !/^[+0-9][0-9 +()-]{6,18}$/.test(value)) {
      return override("type") || MESSAGES.tel;
    }
    if (type === "number") {
      var num = Number(value);
      if (Number.isNaN(num)) return override("type") || MESSAGES.number;
      if (input.min !== "" && num < Number(input.min)) return override("min") || MESSAGES.min;
      if (input.max !== "" && num > Number(input.max)) return override("max") || MESSAGES.max;
      if (input.step && input.step !== "any") {
        var step = Number(input.step);
        var base = input.min === "" ? 0 : Number(input.min);
        if (step > 0 && Math.abs(Math.round((num - base) / step) - (num - base) / step) > 1e-9) {
          return override("step") || MESSAGES.step;
        }
      }
      return "";
    }
    if (input.minLength > 0 && value.length < input.minLength) {
      return override("minlength") || MESSAGES.minlength;
    }
    if (input.maxLength > 0 && value.length > input.maxLength) {
      return override("maxlength") || MESSAGES.maxlength;
    }
    if (type === "date" && Number.isNaN(new Date(value + "T00:00:00").getTime())) {
      return override("type") || MESSAGES.date;
    }
    return "";
  }

  function fieldOf(input) {
    return input.closest(".field") || input.parentElement;
  }

  function setError(input, message) {
    var field = fieldOf(input);
    var errorEl = field ? field.querySelector(".field__error") : null;
    input.setAttribute("aria-invalid", message ? "true" : "false");
    if (field) field.classList.toggle("field--invalid", Boolean(message));
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.toggle("is-visible", Boolean(message));
    }
  }

  function summaryFor(form) {
    var box = form.querySelector("[data-form-alert]");
    if (!box) {
      box = document.createElement("div");
      box.className = "alert alert--danger";
      box.setAttribute("data-form-alert", "");
      box.setAttribute("role", "alert");
      box.setAttribute("tabindex", "-1");
      form.insertBefore(box, form.firstElementChild);
    }
    return box;
  }

  SK.validateField = function (input) {
    var message = messageFor(input);
    setError(input, message);
    return message === "";
  };

  SK.initForms = function () {
    var forms = document.querySelectorAll("form[data-validate]");
    Array.prototype.forEach.call(forms, function (form) {
      var fields = Array.prototype.slice.call(
        form.querySelectorAll("input:not([type=hidden]):not([type=submit]), select, textarea")
      );
      var submitted = false;

      fields.forEach(function (input) {
        input.addEventListener("blur", function () {
          if (submitted) SK.validateField(input);
        });
        input.addEventListener("input", function () {
          if (input.getAttribute("aria-invalid") === "true") SK.validateField(input);
          form.dispatchEvent(new CustomEvent("sk:input", { detail: { input: input } }));
        });
      });

      form.addEventListener("submit", function (event) {
        submitted = true;
        var invalid = [];
        fields.forEach(function (input) {
          if (!SK.validateField(input)) invalid.push(input);
        });
        var box = summaryFor(form);
        if (invalid.length) {
          event.preventDefault();
          box.textContent =
            invalid.length === 1
              ? "1 kolom perlu diperbaiki sebelum dilanjutkan."
              : invalid.length + " kolom perlu diperbaiki sebelum dilanjutkan.";
          box.hidden = false;
          invalid[0].focus();
          return;
        }
        box.hidden = true;
        box.textContent = "";
        var submit = form.querySelector("[data-submit]") || form.querySelector("button[type=submit]");
        if (submit && form.getAttribute("data-busy") !== "off") {
          submit.setAttribute("aria-busy", "true");
          submit.disabled = true;
          window.setTimeout(function () {
            submit.disabled = false;
            submit.removeAttribute("aria-busy");
          }, 12000);
        }
      });

      form.addEventListener("reset", function () {
        submitted = false;
        var box = form.querySelector("[data-form-alert]");
        if (box) box.hidden = true;
        fields.forEach(function (input) {
          setError(input, "");
        });
      });
    });
  };

  /* ---------- Tracking number input ---------- */
  SK.initResiInput = function () {
    var inputs = document.querySelectorAll("[data-resi-input]");
    Array.prototype.forEach.call(inputs, function (input) {
      var sync = function () {
        var digits = input.value.toUpperCase().replace(/[^0-9]/g, "").slice(0, 7);
        var next = digits.length || /^rs/i.test(input.value) ? "RS-" + digits : "";
        if (input.value !== next) {
          var atEnd = input.selectionStart === input.value.length;
          input.value = next;
          if (atEnd) input.setSelectionRange(next.length, next.length);
        }
      };
      input.addEventListener("input", sync);
      sync();
    });
  };

  /* ---------- Generic client-side list filter ---------- */
  SK.initFilter = function (options) {
    var input = document.querySelector(options.input);
    var list = document.querySelector(options.list);
    var countEl = options.count ? document.querySelector(options.count) : null;
    var emptyEl = options.empty ? document.querySelector(options.empty) : null;
    if (!input || !list) return;

    var items = Array.prototype.slice.call(list.querySelectorAll(options.item));
    var total = items.length;
    var emptyText = emptyEl ? emptyEl.textContent : "";

    function apply() {
      var needle = input.value.trim().toLowerCase();
      var shown = 0;
      items.forEach(function (item) {
        var haystack = (item.getAttribute("data-search") || item.textContent).toLowerCase();
        var match = needle === "" || haystack.indexOf(needle) !== -1;
        item.hidden = !match;
        if (match) shown += 1;
      });
      if (countEl) {
        countEl.textContent =
          needle === ""
            ? countEl.getAttribute("data-total-label").replace("{n}", String(total))
            : countEl.getAttribute("data-match-label").replace("{n}", String(shown));
      }
      if (emptyEl) emptyEl.hidden = shown !== 0;
    }

    input.addEventListener("input", apply);
    var form = input.closest("form");
    if (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        apply();
      });
      var reset = form.querySelector("[type=reset]");
      if (reset) {
        reset.addEventListener("click", function () {
          window.setTimeout(apply, 0);
        });
      }
    }
    apply();
  };

  /* ---------- Reveal on scroll ---------- */
  SK.initReveal = function () {
    var items = document.querySelectorAll(".reveal");
    if (!items.length) return;
    if (reduceMotion.matches || !("IntersectionObserver" in window)) {
      Array.prototype.forEach.call(items, function (el) {
        el.classList.add("is-visible");
      });
      return;
    }
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.08 }
    );
    Array.prototype.forEach.call(items, function (el) {
      observer.observe(el);
    });
  };

  /* ---------- Recently viewed tracking numbers ---------- */
  var RESI_KEY = "sampaiKilat.recentResi";

  SK.recentResi = {
    all: function () {
      try {
        var raw = window.localStorage.getItem(RESI_KEY);
        var list = raw ? JSON.parse(raw) : [];
        return Array.isArray(list) ? list.slice(0, 5) : [];
      } catch (error) {
        return [];
      }
    },
    add: function (resi) {
      if (!resi) return;
      var list = SK.recentResi.all().filter(function (item) {
        return item !== resi;
      });
      list.unshift(resi);
      try {
        window.localStorage.setItem(RESI_KEY, JSON.stringify(list.slice(0, 5)));
      } catch (error) {
        /* private mode: recent list is a convenience, not a requirement */
      }
    },
    render: function (container, onPick) {
      if (!container) return;
      var list = SK.recentResi.all();
      container.innerHTML = "";
      if (!list.length) {
        container.hidden = true;
        return;
      }
      container.hidden = false;
      list.forEach(function (resi) {
        var button = document.createElement("button");
        button.type = "button";
        button.className = "chip";
        button.textContent = resi;
        button.addEventListener("click", function () {
          onPick(resi);
        });
        container.appendChild(button);
      });
    },
  };

  SK.formatRupiah = function (value) {
    return "Rp" + new Intl.NumberFormat("id-ID", { maximumFractionDigits: 0 }).format(Math.round(value));
  };

  SK.debounce = function (fn, wait) {
    var timer;
    return function () {
      var args = arguments;
      var self = this;
      window.clearTimeout(timer);
      timer = window.setTimeout(function () {
        fn.apply(self, args);
      }, wait);
    };
  };

  window.SK = SK;

  document.addEventListener("DOMContentLoaded", function () {
    SK.initNav();
    SK.initHeader();
    SK.initCopy();
    SK.initDialogs();
    SK.initForms();
    SK.initResiInput();
    SK.initReveal();
  });
})();
