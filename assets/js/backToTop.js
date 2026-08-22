const backToTop = document.getElementById("backToTop");

function toggleBackToTop() {
  backToTop.classList.toggle("is-visible", window.scrollY > 480);
}

window.addEventListener("scroll", toggleBackToTop, { passive: true });
toggleBackToTop();

backToTop.addEventListener("click", () => {
  window.scrollTo({ top: 0, behavior: "smooth" });
});
