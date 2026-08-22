/*
  EXP 10: efecto "decodificando" en los títulos de sección
  (sobre_mi/, proyectos/...) la primera vez que entran en el
  viewport. Encaja con la estética de terminal; se desactiva del
  todo con prefers-reduced-motion.
*/
(function () {
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  if (!("IntersectionObserver" in window)) return;

  var chars = "!<>-_\\/[]{}—=+*^?#";
  var titles = Array.prototype.slice.call(document.querySelectorAll(".section-eyebrow .section-title"));
  if (!titles.length) return;

  function scramble(el) {
    var original = el.textContent;
    var length = original.length;
    var revealed = 0;
    var frame = 0;
    var revealEvery = 2;

    var timer = setInterval(function () {
      frame++;
      if (frame % revealEvery === 0 && revealed < length) revealed++;

      var out = "";
      for (var i = 0; i < length; i++) {
        if (i < revealed || original[i] === " " || original[i] === "/") {
          out += original[i];
        } else {
          out += chars[Math.floor(Math.random() * chars.length)];
        }
      }
      el.textContent = out;

      if (revealed >= length) {
        clearInterval(timer);
        el.textContent = original;
      }
    }, 35);
  }

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          scramble(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.4 }
  );

  titles.forEach(function (el) {
    observer.observe(el);
  });
})();
