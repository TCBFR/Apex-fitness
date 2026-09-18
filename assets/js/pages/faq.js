var details = document.querySelectorAll(".faq-item");
details.forEach(function (detail) {
  detail.addEventListener("toggle", function () {
    if (!detail.open) return;
    details.forEach(function (other) {
      if (other !== detail) other.open = false;
    });
  });
});
