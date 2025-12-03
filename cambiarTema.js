const cambiarTema = document.getElementById("cambiarTema");
const html = document.documentElement;
const inicio = document.querySelector("#inicio");

/*
  Función que cambia el tema de la página
  gracias a bootstrap
*/
function changeTheme() {
  const temaActual = html.getAttribute("data-bs-theme");

  if (temaActual == "dark") {
    html.setAttribute("data-bs-theme", "light");
    cambiarTema.textContent = "☀️";
    inicio.style.backgroundColor = "rgba(0, 0, 0, 0.6)";
    inicio.style.backgroundBlendMode = "darken";
  } else {
    html.setAttribute("data-bs-theme", "dark");
    cambiarTema.textContent = "🌙";
    inicio.style.backgroundColor = "rgba(0, 0, 0, 0.9)";
    inicio.style.backgroundBlendMode = "darken";
  }
}
