/*
  Fundido de entrada al hacer scroll (ver .reveal en styles.css).
  Cada elemento se revela una sola vez, la primera vez que entra en
  el viewport — no se vuelve a animar si se sube y se baja otra vez.
*/
(function () {
  var items = Array.prototype.slice.call(document.querySelectorAll(".reveal"));
  if (!items.length) return;

  // Escalona los elementos que aparecen juntos (p.ej. las tarjetas de
  // un grid): el retraso depende de su posición entre los ".reveal"
  // que comparten el mismo padre, no de su posición global.
  var groups = [];
  items.forEach(function (el) {
    var group = null;
    for (var i = 0; i < groups.length; i++) {
      if (groups[i].parent === el.parentElement) {
        group = groups[i];
        break;
      }
    }
    if (!group) {
      group = { parent: el.parentElement, count: 0 };
      groups.push(group);
    }
    var index = Math.min(group.count, 4);
    group.count++;
    el.style.setProperty("--reveal-delay", index * 60 + "ms");
  });

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion || !("IntersectionObserver" in window)) {
    items.forEach(function (el) {
      el.classList.add("is-visible");
    });
    return;
  }

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: "0px 0px -80px 0px" }
  );

  items.forEach(function (el) {
    observer.observe(el);
  });
})();
