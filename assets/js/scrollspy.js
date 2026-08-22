/*
  Scrollspy propio para el navbar.

  Se sustituye al Scrollspy de Bootstrap porque su heurística interna
  (basada en detectar la dirección del scroll) activaba a veces la
  sección anterior justo al llegar tras un scroll suave — pasaba con
  todas las secciones menos "contacto", que Bootstrap fuerza como
  activa al llegar al final de la página. Esta versión es más simple
  y determinista: en cada scroll, activa la última sección cuyo borde
  superior ya ha cruzado la línea de la navbar.
*/
(function () {
  // Mismo valor que "scroll-padding-top" en styles.scss: al pulsar un
  // link, el navegador alinea la sección justo a esa distancia del
  // borde superior, así que el umbral de activación tiene que ser
  // igual (con un pequeño margen) para no quedarse corto y detectar
  // aún la sección anterior justo al llegar.
  var scrollPaddingTop = parseInt(getComputedStyle(document.documentElement).scrollPaddingTop, 10) || 84;
  var offset = scrollPaddingTop + 8;
  var sections = Array.prototype.slice
    .call(document.querySelectorAll("#collapse .nav-link"))
    .map(function (link) {
      var id = link.getAttribute("href").slice(1);
      return { link: link, el: document.getElementById(id) };
    })
    .filter(function (item) {
      return item.el;
    });

  if (!sections.length) return;

  function setActive() {
    var atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
    var current = sections[0];

    if (atBottom) {
      current = sections[sections.length - 1];
    } else {
      sections.forEach(function (item) {
        if (item.el.getBoundingClientRect().top - offset <= 0) {
          current = item;
        }
      });
    }

    sections.forEach(function (item) {
      item.link.classList.toggle("active", item === current);
    });
  }

  window.addEventListener("scroll", setActive, { passive: true });
  window.addEventListener("resize", setActive);
  setActive();
})();
