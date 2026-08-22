/*
  EXP 3: la ventana del hero está siempre en el viewport al cargar, así
  que no hay scroll que dispare su ".reveal" (ver scrollReveal.js).
  En su lugar, se revela en 5 pasos escalonados (data-reveal-step),
  cada 500ms: la ventana, el saludo, el nombre, la profesión y el botón.
*/
(function () {
  var steps = [
    { selector: '[data-reveal-step="1"]', delay: 0 },
    { selector: '[data-reveal-step="2"]', delay: 500 },
    { selector: '[data-reveal-step="3"]', delay: 1000 },
    { selector: '[data-reveal-step="4"]', delay: 1500 },
    { selector: '[data-reveal-step="5"]', delay: 2000 },
  ];

  var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  steps.forEach(function (step) {
    var els = document.querySelectorAll(step.selector);
    if (!els.length) return;

    if (reduceMotion) {
      els.forEach(function (el) {
        el.classList.add("is-visible");
      });
      return;
    }

    setTimeout(function () {
      els.forEach(function (el) {
        el.classList.add("is-visible");
      });
    }, step.delay);
  });
})();
