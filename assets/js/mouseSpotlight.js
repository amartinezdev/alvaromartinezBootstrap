/*
  EXP 2: mancha de luz que sigue al ratón por toda la página (ver
  body::before en styles.scss). Al ser position:fixed + z-index:-1,
  queda siempre detrás del contenido con fondo propio.
*/
(function () {
  // Mismo criterio que el CSS (ver body::before en styles.scss): ancho
  // mínimo de escritorio además de hover/pointer, porque algunos
  // portátiles con pantalla táctil informan "pointer: fine" igualmente.
  if (!window.matchMedia("(min-width: 768px) and (hover: hover) and (pointer: fine)").matches) return;
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

  var root = document.documentElement;
  var raf = null;

  window.addEventListener("mousemove", function (e) {
    if (raf) return;
    raf = requestAnimationFrame(function () {
      root.style.setProperty("--spot-x", e.clientX + "px");
      root.style.setProperty("--spot-y", e.clientY + "px");
      raf = null;
    });
  });
})();
