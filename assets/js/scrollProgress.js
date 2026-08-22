/*
  EXP 4: barra fina y fija arriba de la página que muestra cuánto se
  ha hecho scroll. El valor se lee directamente del scroll (sin
  transition) para que no vaya a la zaga del gesto del usuario.
*/
(function () {
  var bar = document.getElementById("scrollProgress");
  if (!bar) return;

  var ticking = false;

  function update() {
    var scrollTop = window.scrollY;
    var docHeight = document.documentElement.scrollHeight - window.innerHeight;
    var progress = docHeight > 0 ? Math.min(1, Math.max(0, scrollTop / docHeight)) : 0;
    bar.style.transform = "scaleX(" + progress + ")";
    ticking = false;
  }

  window.addEventListener(
    "scroll",
    function () {
      if (!ticking) {
        requestAnimationFrame(update);
        ticking = true;
      }
    },
    { passive: true }
  );
  window.addEventListener("resize", update);

  update();
})();
