/*
  Modal de certificados: uno solo compartido en vez de uno por
  certificado (ver el comentario junto a #certModal en index.php).
  Al abrirse, Bootstrap pasa en event.relatedTarget el botón .cert-tile
  que lo disparó (puede ser un original o uno de los clones que añade
  certsMarquee.js para el bucle infinito) — de ahí se lee
  data-cert-index para saber cuál es, y data-cert-full/-alt para la
  imagen. Las flechas prev/next (y las del teclado) recorren solo los
  certificados "reales" (sin clones), así el recorrido no se repite.

  Requiere que certsMarquee.js se ejecute antes (ver el orden de los
  <script> en index.php): es quien pone data-cert-index en el HTML.
*/
(function () {
  var modalEl = document.getElementById("certModal");
  if (!modalEl) return;

  var img = document.getElementById("certModalImg");
  var realTiles = Array.prototype.slice.call(document.querySelectorAll(".cert-tile[data-cert-index]:not([aria-hidden])"));
  if (!realTiles.length) return;

  var currentIndex = 0;

  function showAt(index) {
    currentIndex = (index + realTiles.length) % realTiles.length;
    var tile = realTiles[currentIndex];
    var alt = tile.getAttribute("data-cert-full-alt") || "";
    img.src = tile.getAttribute("data-cert-full");
    img.alt = alt;
    // El nombre accesible del modal cambia con cada certificado (antes
    // apuntaba a un aria-labelledby sin ningún id real que lo respaldara).
    if (alt) modalEl.setAttribute("aria-label", alt);
  }

  modalEl.addEventListener("show.bs.modal", function (event) {
    var trigger = event.relatedTarget;
    var index = trigger ? parseInt(trigger.getAttribute("data-cert-index"), 10) : NaN;
    showAt(isNaN(index) ? 0 : index);
  });

  modalEl.querySelector(".cert-modal-prev").addEventListener("click", function () {
    showAt(currentIndex - 1);
  });

  modalEl.querySelector(".cert-modal-next").addEventListener("click", function () {
    showAt(currentIndex + 1);
  });

  modalEl.addEventListener("keydown", function (event) {
    if (event.key === "ArrowLeft") showAt(currentIndex - 1);
    if (event.key === "ArrowRight") showAt(currentIndex + 1);
  });
})();
