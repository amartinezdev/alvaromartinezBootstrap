/*
  Cinta infinita de certificados ("como si fueran marcas", ver la skill
  animation-vocabulary: esto es un "Marquee" — y ahí mismo se confirma
  que velocidad lineal, sin easing, es lo correcto para uno). Avanza
  sola muy despacio hacia la izquierda y se puede arrastrar con el ratón
  (con inercia al soltar, ver "Momentum"/"Drag" en esa misma guía),
  pausándose mientras hay cualquier interacción (arrastre, touch,
  trackpad, rueda) para no pelearse con el gesto del usuario.

  El bucle infinito usa el patrón de triple copia: [clon][original][clon].
  Añadir un certificado más en el HTML no requiere tocar este archivo:
  se clona lo que haya.

  Importante: la posición se lleva en la variable "pos" (no se lee
  strip.scrollLeft de vuelta para decidir el siguiente valor). Si se
  intenta escribir un scrollLeft fuera de [0, scrollWidth-clientWidth],
  el navegador lo recorta él solo — y leerlo después ya no dice qué
  valor "de verdad" queríamos, así que un arrastre fuerte y rápido
  podía salirse de la banda segura y quedarse clavado en el límite
  real. Por eso aquí el envolvimiento (wrapValue) se calcula siempre en
  el número lógico ANTES de escribirlo en scrollLeft — nunca se le pide
  al navegador un valor fuera de rango.
*/
(function () {
  var strip = document.querySelector(".certs-grid");
  if (!strip) return;

  var originals = Array.prototype.slice.call(strip.children);
  if (!originals.length) return;

  // Se etiqueta siempre, aunque solo haya un certificado (certModal.js
  // depende de este atributo para saber cuál abrir).
  originals.forEach(function (tile, i) {
    tile.setAttribute("data-cert-index", i);
  });

  // moved / suppressClick: comunes a ratón y a touch. En touch NO se
  // reimplementa el arrastre (el scroll nativo ya es correcto), pero sí
  // hay que saber si hubo un desplazamiento real para no abrir el modal
  // al soltar el dedo después de deslizar la cinta.
  var moved = false;

  strip.addEventListener(
    "click",
    function (e) {
      if (moved) {
        e.preventDefault();
        e.stopPropagation();
      }
    },
    true
  );

  var touchStartX = null;
  strip.addEventListener(
    "touchstart",
    function (e) {
      moved = false;
      touchStartX = e.touches && e.touches[0] ? e.touches[0].clientX : null;
      markInteraction();
    },
    { passive: true }
  );
  strip.addEventListener(
    "touchmove",
    function (e) {
      if (touchStartX !== null && e.touches && e.touches[0]) {
        if (Math.abs(e.touches[0].clientX - touchStartX) > 5) moved = true;
      }
      markInteraction();
    },
    { passive: true }
  );

  if (originals.length < 2) return; // no tiene sentido clonar/animar un único certificado

  // Los clones son decorativos (repiten certificados ya presentes):
  // fuera del árbol de accesibilidad y del orden de tabulación, pero
  // siguen siendo clicables con ratón/touch para abrir el modal.
  function cloneSet() {
    return originals.map(function (tile) {
      var clone = tile.cloneNode(true);
      clone.setAttribute("aria-hidden", "true");
      clone.setAttribute("tabindex", "-1");
      return clone;
    });
  }

  var before = cloneSet();
  var after = cloneSet();
  var firstOriginal = originals[0];
  before.forEach(function (clone) {
    strip.insertBefore(clone, firstOriginal);
  });
  after.forEach(function (clone) {
    strip.appendChild(clone);
  });

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var isFineMouse = window.matchMedia("(pointer: fine)").matches;

  var setWidth = 0;
  var pos = 0;

  // Envuelve cualquier valor (por grande o pequeño que sea el salto) a
  // su equivalente dentro de la banda [setWidth, setWidth*2) usando
  // módulo — no bucles ni lectura del DOM — así funciona igual de bien
  // para un frame de auto-scroll que para un arrastre muy rápido.
  function wrapValue(v) {
    if (setWidth <= 0) return v;
    var rel = (v - setWidth) % setWidth;
    if (rel < 0) rel += setWidth;
    return setWidth + rel;
  }

  function setPos(v) {
    pos = wrapValue(v);
    strip.scrollLeft = pos;
    // Autocorrección: si el navegador recorta el valor que le hemos
    // pedido (puede pasar si clientWidth se acerca al ancho de un set,
    // p.ej. con la ventana muy ancha), nos realineamos al valor real en
    // vez de arrastrar esa diferencia para siempre — el próximo
    // wrapValue() lo vuelve a traer a la banda segura de todos modos.
    var actual = strip.scrollLeft;
    if (Math.abs(actual - pos) > 0.5) {
      pos = actual;
    }
  }

  function measure() {
    var oldSetWidth = setWidth;
    setWidth = strip.scrollWidth / 3;
    if (!oldSetWidth) {
      setPos(setWidth); // primera medición: arranca al inicio del set "original"
    } else {
      // Reescala la posición en proporción al cambio de ancho (p.ej. al
      // cruzar el breakpoint de 768px, donde .cert-tile pasa de % a un
      // ancho fijo) en vez de solo reenvolver el valor antiguo, que
      // dejaría la cinta en un punto visualmente ajeno al que se veía.
      setPos((pos / oldSetWidth) * setWidth);
    }
  }
  measure();
  window.addEventListener("resize", measure);

  var AUTO_SPEED = 0.04; // px/ms (~40px/s) — deliberadamente lento, "poco a poco"
  var RESUME_DELAY = 700; // ms de calma tras cualquier interacción antes de retomar
  var lastInteraction = -Infinity;
  var isDown = false;
  var momentumId = null;
  var lastTick = null;

  function markInteraction() {
    lastInteraction = performance.now();
  }

  function autoScrollTick(now) {
    if (lastTick === null) lastTick = now;
    var dt = now - lastTick;
    lastTick = now;

    if (!reduceMotion && !isDown && !momentumId && now - lastInteraction > RESUME_DELAY) {
      setPos(pos + AUTO_SPEED * dt);
    }
    requestAnimationFrame(autoScrollTick);
  }
  requestAnimationFrame(autoScrollTick);

  // OJO: nada de escuchar "scroll" aquí. El propio auto-scroll (y el
  // arrastre) cambian scrollLeft, y eso dispara un evento "scroll"
  // nativo indistinguible del de un gesto real del usuario — si
  // "scroll" marcara interacción, el auto-scroll se marcaría a sí
  // mismo como "interacción reciente" en cada frame y se quedaría
  // parado para siempre tras un primer microscopic paso. Con
  // wheel/touchstart/touchmove basta para detectar gestos reales.
  strip.addEventListener("wheel", markInteraction, { passive: true });

  // El arrastre a medida (con inercia) es solo para ratón: en
  // touch/trackpad el scroll nativo ya es correcto y tocarlo aquí solo
  // lo empeoraría. La detección de "hubo movimiento" para no abrir el
  // modal (touchstart/touchmove de arriba) sigue activa igual.
  if (!isFineMouse) return;

  var startX = 0;
  var startPos = 0;
  var lastX = 0;
  var lastMoveTime = 0;
  var velocity = 0;

  function cancelMomentum() {
    if (momentumId) {
      cancelAnimationFrame(momentumId);
      momentumId = null;
    }
  }

  function startMomentum() {
    if (reduceMotion || Math.abs(velocity) < 0.05) {
      strip.classList.remove("is-dragging");
      return;
    }

    var lastStepTime = performance.now();

    function step(now) {
      var dt = now - lastStepTime;
      lastStepTime = now;
      setPos(pos - velocity * dt);
      velocity *= 0.95;
      markInteraction();
      if (Math.abs(velocity) > 0.05) {
        momentumId = requestAnimationFrame(step);
      } else {
        momentumId = null;
        strip.classList.remove("is-dragging");
      }
    }
    momentumId = requestAnimationFrame(step);
  }

  // Nota: a propósito NO se usa strip.setPointerCapture aquí. Capturar
  // el puntero en el contenedor hace que, en varios navegadores, el
  // "click" posterior se retargete al contenedor en vez de al <button>
  // pulsado — Bootstrap nunca encuentra su data-bs-toggle="modal" y el
  // clic deja de abrir nada. Escuchar el move/up en window mientras se
  // arrastra evita ese problema y sigue funcionando aunque el puntero
  // salga de la tira durante el gesto.
  function onPointerMove(e) {
    var dx = e.clientX - startX;
    if (Math.abs(dx) > 5) moved = true;
    setPos(startPos - dx);
    markInteraction();

    var now = performance.now();
    var dt = now - lastMoveTime;
    if (dt > 0) velocity = (e.clientX - lastX) / dt;
    lastX = e.clientX;
    lastMoveTime = now;
  }

  function onPointerUp() {
    window.removeEventListener("pointermove", onPointerMove);
    window.removeEventListener("pointerup", onPointerUp);
    window.removeEventListener("pointercancel", onPointerUp);
    if (!isDown) return;
    isDown = false;
    startMomentum();
  }

  strip.addEventListener("pointerdown", function (e) {
    if (e.pointerType !== "mouse") return;
    isDown = true;
    moved = false;
    velocity = 0;
    cancelMomentum();
    markInteraction();
    strip.classList.add("is-dragging");
    startX = e.clientX;
    startPos = pos;
    lastX = e.clientX;
    lastMoveTime = performance.now();
    window.addEventListener("pointermove", onPointerMove);
    window.addEventListener("pointerup", onPointerUp);
    window.addEventListener("pointercancel", onPointerUp);
  });
})();
