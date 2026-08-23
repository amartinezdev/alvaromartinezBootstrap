/*
  Línea y puntos de la timeline de experiencia (ver #sobre_mi):

  - La línea (.timeline-line-fill) se rellena en proporción al scroll, sin
    transition, para que suba y baje exactamente con el gesto del usuario
    en vez de dispararse una sola vez (mismo patrón que scrollProgress.js).
    El progreso se calcula sobre una ventana de dos puntos del viewport
    (startRef -> endRef), no un único punto fijo: con un solo punto la
    línea se completaba en muy poco scroll y se sentía demasiado rápida.
  - Cada punto (.timeline-dot) empieza apagado (ver .timeline-dot en
    styles.scss) y se enciende (.is-lit) en el instante en que el relleno
    de la línea alcanza su posición — también reversible: si se sube y el
    relleno retrocede por debajo del punto, se apaga otra vez.

  El alto de la línea se mide entre el centro del primer y el último punto
  para no depender de cuánto ocupe el contenido de cada puesto; la
  posición de cada punto intermedio se guarda como fracción (0–1) de ese
  mismo tramo. Esa medición debe repetirse, no solo en resize: cada
  .timeline-item usa .reveal (fundido + translateY(16px) -> 0 la primera
  vez que entra en el viewport, ver scrollReveal.js), y esa animación
  desplaza el punto verticalmente DESPUÉS de la primera medición si esta
  se hizo antes de que el item se revelara. Por eso se vuelve a medir al
  terminar cada transición de "transform" dentro del track.
*/
(function () {
  var track = document.querySelector(".timeline-track");
  if (!track) return;

  var line = track.querySelector(".timeline-line");
  var fill = track.querySelector(".timeline-line-fill");
  var dots = Array.prototype.slice.call(track.querySelectorAll(".timeline-dot"));
  if (!line || !fill || dots.length < 2) return;

  var lineTopOffset = 0;
  var lineSpan = 0;
  var dotFractions = [];
  var ticking = false;

  function measure() {
    var trackRect = track.getBoundingClientRect();
    var firstRect = dots[0].getBoundingClientRect();
    var lastRect = dots[dots.length - 1].getBoundingClientRect();

    lineTopOffset = firstRect.top - trackRect.top + firstRect.height / 2;
    lineSpan = lastRect.top - trackRect.top + lastRect.height / 2 - lineTopOffset;

    line.style.top = lineTopOffset + "px";
    line.style.height = lineSpan + "px";
    fill.style.top = lineTopOffset + "px";
    fill.style.height = lineSpan + "px";

    dotFractions = dots.map(function (dot) {
      if (lineSpan <= 0) return 0;
      var rect = dot.getBoundingClientRect();
      var center = rect.top - trackRect.top + rect.height / 2;
      return (center - lineTopOffset) / lineSpan;
    });

    update();
  }

  function update() {
    if (lineSpan <= 0) return;

    var trackRect = track.getBoundingClientRect();
    var lineTop = trackRect.top + lineTopOffset;
    var lineBottom = lineTop + lineSpan;

    // Ventana en vez de un único punto: el inicio de la línea llega al
    // 0% de progreso al cruzar el 70% del viewport, el final llega al
    // 100% al cruzar el 50% — un margen pequeño, solo para suavizar el
    // punto de disparo, sin ralentizar tanto el relleno como para que
    // se sienta perezoso.
    var startRef = window.innerHeight * 0.7;
    var endRef = window.innerHeight * 0.5;
    var span = startRef - endRef + lineSpan;
    var rawProgress = span > 0 ? (startRef - lineTop) / span : 0;
    var progress = Math.min(1, Math.max(0, rawProgress));

    fill.style.transform = "scaleY(" + progress + ")";

    // Se compara con rawProgress (sin recortar a 0–1), no con progress:
    // el primer punto tiene fracción 0, y progress nunca baja de 0, así
    // que "progress >= 0" sería siempre cierto y quedaría encendido
    // desde la carga de la página.
    dots.forEach(function (dot, i) {
      dot.classList.toggle("is-lit", rawProgress >= dotFractions[i]);
    });

    ticking = false;
  }

  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(update);
      ticking = true;
    }
  }

  window.addEventListener("scroll", onScroll, { passive: true });
  window.addEventListener("resize", measure);

  track.addEventListener("transitionend", function (e) {
    if (e.propertyName === "transform" && e.target.classList.contains("timeline-item")) {
      measure();
    }
  });

  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(measure);
  }

  measure();
})();
