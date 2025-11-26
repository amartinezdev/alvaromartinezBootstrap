// lista de palabras a mostrar
const palabras = ["Hey!", "Hola!", "Bonjour!", "Ciao!", "Hallo!", "Olá!", "Namaste!", "Salaam!", "Konnichiwa!"];
const palabrasSpan = document.getElementById("palabras");
let palabrasContent = palabrasSpan.textContent;
let addPalabra = false;
let conta = 0;

// borro palabras y las vuelvo a agregar
function borrarPalabra() {
  // si quedan letras, borro una
  if (palabrasContent.length > 0) {
    palabrasContent = palabrasContent.slice(0, -1);
    palabrasSpan.textContent = palabrasContent;
  } else {
    // si no quedan letras, cambio a agregar
    addPalabra = true;
  }

  if (!addPalabra) {
    setTimeout(borrarPalabra, 200);
  } else {
    setTimeout(agregarPalabra, 300);
  }
}

// agrego palabras letra a letra
function agregarPalabra() {
  // si quedan letras por agregar, agrego una
  if (palabrasContent.length < palabras[conta].length) {
    // agrego una letra
    palabrasContent = palabras[conta].slice(0, palabrasContent.length + 1);
    palabrasSpan.textContent = palabrasContent;
  } else {
    // si ya no quedan letras por agregar, cambio a borrar
    addPalabra = false;
    conta++;
    // reinicio el contador para que siga cogiendo palabras
    if (conta >= palabras.length) {
      conta = 0;
    }
  }

  if (addPalabra) {
    // agrego letras cada 100ms
    setTimeout(agregarPalabra, 100);
  } else {
    // espero 2 segundos antes de borrar
    setTimeout(borrarPalabra, 2000);
  }
}

agregarPalabra();
