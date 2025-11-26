const cambiarTema = document.getElementById("cambiarTema");
const html = document.documentElement;

/*
  Función que cambia el tema de la página
  gracias a bootstrap
*/
function changeTheme() {
  const temaActual = html.getAttribute("data-bs-theme");

  if (temaActual == "dark") {
    html.setAttribute("data-bs-theme", "light");
    cambiarTema.textContent = "☀️";
  } else {
    html.setAttribute("data-bs-theme", "dark");
    cambiarTema.textContent = "🌙";
  }
}
