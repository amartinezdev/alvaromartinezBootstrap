const cambiarTema = document.getElementById("cambiarTema");
const html = document.documentElement;

/*
  Función que cambia el tema de la página
  gracias a bootstrap, y lo persiste en localStorage
  para que se mantenga entre recargas
*/
function changeTheme() {
  const temaActual = html.getAttribute("data-bs-theme");
  const nuevoTema = temaActual === "dark" ? "light" : "dark";

  html.setAttribute("data-bs-theme", nuevoTema);
  localStorage.setItem("theme", nuevoTema);
  cambiarTema.setAttribute("aria-pressed", String(nuevoTema === "light"));
}

// Sincroniza el botón con el tema ya aplicado por el script inline del <head>
cambiarTema.setAttribute("aria-pressed", String(html.getAttribute("data-bs-theme") === "light"));
