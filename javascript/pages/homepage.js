(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var list = document.getElementById("recent-resi-list");
    var wrapper = document.getElementById("recent-resi");
    var input = document.getElementById("lacak");
    if (!list || !wrapper || !input || !window.SK) return;

    SK.recentResi.render(list, function (resi) {
      input.value = resi;
      input.focus();
    });
  });
})();
