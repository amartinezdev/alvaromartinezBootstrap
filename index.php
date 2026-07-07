<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

session_start();

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

function env(string $name, $default = null)
{
  $value = getenv($name);
  if ($value !== false) {
    return $value;
  }
  if (array_key_exists($name, $_ENV) && $_ENV[$name] !== null) {
    return $_ENV[$name];
  }
  if (array_key_exists($name, $_SERVER) && $_SERVER[$name] !== null) {
    return $_SERVER[$name];
  }
  return $default;
}

function recordSubmissionAttempt(): void
{
  if (!isset($_SESSION['contact_submission_timestamps']) || !is_array($_SESSION['contact_submission_timestamps'])) {
    $_SESSION['contact_submission_timestamps'] = [];
  }

  $timestamps = $_SESSION['contact_submission_timestamps'];
  $timestamps[] = time();
  $cutoff = time() - 600;
  $_SESSION['contact_submission_timestamps'] = array_values(array_filter($timestamps, static fn($timestamp) => $timestamp >= $cutoff));
}

function isRateLimited(int $maxAttempts = 3, int $windowSeconds = 600): bool
{
  if (!isset($_SESSION['contact_submission_timestamps']) || !is_array($_SESSION['contact_submission_timestamps'])) {
    return false;
  }

  $cutoff = time() - $windowSeconds;
  $recent = array_filter($_SESSION['contact_submission_timestamps'], static fn($timestamp) => $timestamp >= $cutoff);
  $_SESSION['contact_submission_timestamps'] = array_values($recent);

  return count($recent) >= $maxAttempts;
}

$mensaje_enviado = false;
$error_mensaje = "";

if (isset($_SESSION['mensaje_enviado'])) {
  $mensaje_enviado = $_SESSION['mensaje_enviado'];
  unset($_SESSION['mensaje_enviado']);
}
if (isset($_SESSION['error_mensaje'])) {
  $error_mensaje = $_SESSION['error_mensaje'];
  unset($_SESSION['error_mensaje']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Honeypot anti-spam: campo oculto que solo rellenan los bots
  if (!empty($_POST['website'])) {
    header('Location: /#contact');
    exit;
  }

  recordSubmissionAttempt();

  // Recibir los datos del formulario
  $nombre = htmlspecialchars($_POST['nombre']);
  $apellidos = htmlspecialchars($_POST['apellidos']);
  $email = htmlspecialchars($_POST['email']);
  $asunto = htmlspecialchars($_POST['asunto']);
  $mensaje = htmlspecialchars($_POST['mensaje']);

  // Validar que los campos requeridos no estén vacíos
  if (empty($nombre) || empty($apellidos) || empty($email) || empty($asunto) || empty($mensaje)) {
    $error_mensaje = "Todos los campos son obligatorios.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_mensaje = "El email no es válido.";
  } elseif (isRateLimited()) {
    $error_mensaje = "Has enviado demasiados mensajes en poco tiempo. Intenta de nuevo en 10 minutos.";
  } else {
    $smtpHost = env('SMTP_HOST', 'smtp.gmail.com');
    $smtpUser = env('SMTP_USERNAME');
    $smtpPass = env('SMTP_PASSWORD');
    $smtpPort = env('SMTP_PORT', 587);
    $smtpSecureStr = env('SMTP_SECURE', 'tls');
    switch ($smtpSecureStr) {
      case 'ssl':
        $smtpSecure = PHPMailer::ENCRYPTION_SMTPS;
        break;
      case 'tls':
      default:
        $smtpSecure = PHPMailer::ENCRYPTION_STARTTLS;
        break;
    }
    $mailTo = env('MAIL_TO', 'alvaromartinezdev@gmail.com');

    if (!$smtpUser || !$smtpPass) {
      $error_mensaje = "Configuración SMTP incompleta. Revisa tu archivo .env.";
    } else {
      $mail = new PHPMailer(true);

      try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = $smtpSecure;
        $mail->Port = $smtpPort;

        // Destinatarios
        $mail->setFrom($smtpUser, 'Contacto Web');
        $mail->addReplyTo($email, $nombre . ' ' . $apellidos);
        $mail->addAddress($mailTo, 'Álvaro Martínez');

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Nuevo mensaje desde el formulario de contacto: ' . $asunto;
        $mail->Body = "
        <html>
        <head>
            <title>Nuevo mensaje de contacto</title>
        </head>
        <body>
            <h2>Nuevo mensaje desde el sitio web</h2>
            <p><strong>Nombre:</strong> $nombre $apellidos</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Asunto:</strong> $asunto</p>
            <p><strong>Mensaje:</strong></p>
            <p>$mensaje</p>
        </body>
        </html>
        ";
        $mail->AltBody = "Nombre: $nombre $apellidos\nEmail: $email\nAsunto: $asunto\nMensaje: $mensaje";

        $mail->send();
        $_SESSION['mensaje_enviado'] = true;
        header('Location: /#contact');
        exit;
      } catch (Exception $e) {
        $error_mensaje = "Error al enviar el mensaje: " . $e->getMessage();
        header('Location: /#contact');
      }
    }
  }
}
?>
<!doctype html>
<html lang="es" data-bs-theme="dark">

<head>
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-TGNZRFLL');</script>
  <!-- End Google Tag Manager -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Álvaro Martínez | Desarrollador Full Stack en Murcia</title>
  <meta name="description" content="Álvaro Martínez, desarrollador Full Stack Junior en Murcia. Especializado en Java, Spring Boot, JavaScript, React, Node.js, PHP y MySQL. Descubre mis proyectos, certificados y contacta conmigo." />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://alvaromartinez.dev/" />

  <!-- Open Graph -->
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="Álvaro Martínez | Portfolio" />
  <meta property="og:url" content="https://alvaromartinez.dev/" />
  <meta property="og:title" content="Álvaro Martínez | Desarrollador Full Stack en Murcia" />
  <meta property="og:description" content="Desarrollador Full Stack Junior en Murcia. Java, Spring Boot, React, Node.js, PHP, MySQL. Proyectos, certificados y contacto." />
  <meta property="og:image" content="https://alvaromartinez.dev/img/og-cover.jpg" />
  <meta property="og:image:alt" content="Portfolio de Álvaro Martínez, desarrollador Full Stack en Murcia" />
  <meta property="og:locale" content="es_ES" />

  <!-- Twitter Cards -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Álvaro Martínez | Desarrollador Full Stack en Murcia" />
  <meta name="twitter:description" content="Desarrollador Full Stack Junior en Murcia. Java, Spring Boot, React, Node.js, PHP, MySQL." />
  <meta name="twitter:image" content="https://alvaromartinez.dev/img/og-cover.jpg" />
  <meta name="twitter:image:alt" content="Portfolio de Álvaro Martínez, desarrollador Full Stack en Murcia" />

  <meta name="theme-color" content="#0d0d0d" media="(prefers-color-scheme: dark)" />
  <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />
  <link rel="manifest" href="manifest.webmanifest" />

  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
    crossorigin="anonymous" />
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"
    defer></script>
  <link rel="icon" href="img/am_bw.png" type="image/png" sizes="32x32" />
  <link rel="apple-touch-icon" href="img/am_bw.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="styles.css" />

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Person",
        "@id": "https://alvaromartinez.dev/#person",
        "name": "Álvaro Martínez",
        "alternateName": "alvaromartinezdev",
        "url": "https://alvaromartinez.dev/",
        "image": {
          "@type": "ImageObject",
          "@id": "https://alvaromartinez.dev/#personimage",
          "url": "https://alvaromartinez.dev/img/alvaro-martinez.webp",
          "caption": "Álvaro Martínez, desarrollador Full Stack en Murcia"
        },
        "description": "Desarrollador Full Stack Junior en Murcia, especializado en Java, Spring Boot, JavaScript, React y Node.js. Portfolio con proyectos y experiencia.",
        "jobTitle": "Desarrollador Full Stack",
        "hasOccupation": {
          "@type": "Occupation",
          "name": "Desarrollador Full Stack",
          "occupationLocation": {
            "@type": "City",
            "name": "Murcia, España"
          }
        },
        "homeLocation": {
          "@type": "Place",
          "name": "Murcia, España"
        },
        "knowsAbout": [
          "Java",
          "Spring Boot",
          "JavaScript",
          "React",
          "Node.js",
          "Express",
          "PHP",
          "Laravel",
          "REST APIs",
          "MySQL",
          "Git",
          "Docker",
          "HTML",
          "CSS",
          "Responsive Web Design"
        ],
        "knowsLanguage": "es",
        "email": "mailto:alvaromartinezdev@gmail.com",
        "sameAs": [
          "https://github.com/amartinezdev",
          "https://www.linkedin.com/in/alvaromartinezdev"
        ],
        "mainEntityOfPage": {
          "@id": "https://alvaromartinez.dev/#webpage"
        }
      },
      {
        "@type": "WebSite",
        "@id": "https://alvaromartinez.dev/#website",
        "name": "Álvaro Martínez | Portfolio",
        "url": "https://alvaromartinez.dev/",
        "inLanguage": "es",
        "publisher": {
          "@id": "https://alvaromartinez.dev/#person"
        }
      },
      {
        "@type": "WebPage",
        "@id": "https://alvaromartinez.dev/#webpage",
        "url": "https://alvaromartinez.dev/",
        "name": "Álvaro Martínez | Desarrollador Full Stack en Murcia",
        "description": "Álvaro Martínez, desarrollador Full Stack Junior en Murcia. Especializado en Java, Spring Boot, JavaScript, React, Node.js, PHP y MySQL.",
        "isPartOf": {
          "@id": "https://alvaromartinez.dev/#website"
        },
        "about": {
          "@id": "https://alvaromartinez.dev/#person"
        },
        "primaryImageOfPage": {
          "@id": "https://alvaromartinez.dev/#personimage"
        },
        "inLanguage": "es"
      }
    ]
  }
  </script>

  <script>
    (function () {
      var guardado = localStorage.getItem("theme");
      var prefiereClaro = window.matchMedia("(prefers-color-scheme: light)").matches;
      var tema = guardado || (prefiereClaro ? "light" : "dark");
      document.documentElement.setAttribute("data-bs-theme", tema);
    })();
  </script>
</head>

<body class="vh-100">
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TGNZRFLL"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
    <symbol id="icon-github" viewBox="0 0 16 16">
      <path
        d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8" />
    </symbol>
    <symbol id="icon-linkedin" viewBox="0 0 16 16">
      <path
        d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z" />
    </symbol>
    <symbol id="icon-envelope" viewBox="0 0 16 16">
      <path
        d="M2 2A2 2 0 0 0 .05 3.555L8 8.414l7.95-4.859A2 2 0 0 0 14 2zm-2 9.8V4.698l5.803 3.546zm6.761-2.97-6.57 4.026A2 2 0 0 0 2 14h6.256A4.5 4.5 0 0 1 8 12.5a4.49 4.49 0 0 1 1.606-3.446l-.367-.225L8 9.586zM16 9.671V4.697l-5.803 3.546.338.208A4.5 4.5 0 0 1 12.5 8c1.414 0 2.675.652 3.5 1.671" />
      <path
        d="M15.834 12.244c0 1.168-.577 2.025-1.587 2.025-.503 0-1.002-.228-1.12-.648h-.043c-.118.416-.543.643-1.015.643-.77 0-1.259-.542-1.259-1.434v-.529c0-.844.481-1.4 1.26-1.4.585 0 .87.333.953.63h.03v-.568h.905v2.19c0 .272.18.42.411.42.315 0 .639-.415.639-1.39v-.118c0-1.277-.95-2.326-2.484-2.326h-.04c-1.582 0-2.64 1.067-2.64 2.724v.157c0 1.867 1.237 2.654 2.57 2.654h.045c.507 0 .935-.07 1.18-.18v.731c-.219.1-.643.175-1.237.175h-.044C10.438 16 9 14.82 9 12.646v-.214C9 10.36 10.421 9 12.485 9h.035c2.12 0 3.314 1.43 3.314 3.034zm-4.04.21v.227c0 .586.227.8.581.8.31 0 .564-.17.564-.743v-.367c0-.516-.275-.708-.572-.708-.346 0-.573.245-.573.791" />
    </symbol>
    <symbol id="icon-browser-chrome" viewBox="0 0 16 16">
      <path
        fill-rule="evenodd"
        d="M16 8a8 8 0 0 1-7.022 7.94l1.902-7.098a3 3 0 0 0 .05-1.492A3 3 0 0 0 10.237 6h5.511A8 8 0 0 1 16 8M0 8a8 8 0 0 0 7.927 8l1.426-5.321a3 3 0 0 1-.723.255 3 3 0 0 1-1.743-.147 3 3 0 0 1-1.043-.7L.633 4.876A8 8 0 0 0 0 8m5.004-.167L1.108 3.936A8.003 8.003 0 0 1 15.418 5H8.066a3 3 0 0 0-1.252.243 2.99 2.99 0 0 0-1.81 2.59M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4" />
    </symbol>
    <symbol id="icon-download" viewBox="0 0 16 16">
      <path
        d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5" />
      <path
        d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z" />
    </symbol>
  </svg>
  <nav class="navbar navbar-expand-md bg-dark navbar-dark fixed-top">
    <a class="navbar-brand ms-4 mt-0 position-absolute" href="#inicio">
      <img src="img/am_wb.png" alt="Álvaro Martínez logo" width="50" height="50" />
    </a>
    <button
      class="navbar-toggler d-lg-none ms-auto"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#collapse"
      aria-controls="collapse"
      aria-expanded="false"
      aria-label="activar navegacion">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse mt-2" id="collapse">
      <ul class="navbar-nav mt-2 mt-lg-0 mx-auto gap-0 gap-md-4 text-center">
        <li class="nav-item">
          <a class="nav-link text-white" href="#inicio" aria-current="page">Inicio <span class="visually-hidden">(current)</span></a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="#about">Sobre mí</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="#projects">Proyectos</a>
        </li>

        <li class="nav-item">
          <a class="nav-link text-white" href="#contact">Contacto</a>
        </li>
      </ul>
      <button class="btn position-absolute top-50 end-0 translate-middle-y me-4" id="cambiarTema" onclick="changeTheme()" aria-pressed="false" aria-label="Alternar tema" title="Alternar tema" type="button">
        <svg class="theme-icon moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
        </svg>
        <svg class="theme-icon sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
          <path d="M8 4.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z"/>
          <path d="M8 0a.5.5 0 0 1 .5.5V2a.5.5 0 0 1-1 0V.5A.5.5 0 0 1 8 0zm0 14a.5.5 0 0 1 .5.5V16a.5.5 0 0 1-1 0v-1.5A.5.5 0 0 1 8 14zM2.343 2.343a.5.5 0 0 1 .707 0L4.5 3.793a.5.5 0 1 1-.707.707L2.343 3.05a.5.5 0 0 1 0-.707zm9.95 9.95a.5.5 0 0 1 .707 0l1.45 1.45a.5.5 0 0 1-.707.707l-1.45-1.45a.5.5 0 0 1 0-.707zM0 8a.5.5 0 0 1 .5-.5H2a.5.5 0 0 1 0 1H.5A.5.5 0 0 1 0 8zm14 0a.5.5 0 0 1 .5-.5H16a.5.5 0 0 1 0 1h-1.5A.5.5 0 0 1 14 8zM2.343 13.657a.5.5 0 0 1 0-.707l1.45-1.45a.5.5 0 1 1 .707.707l-1.45 1.45a.5.5 0 0 1-.707 0zm9.95-9.95a.5.5 0 0 1 0-.707l1.45-1.45a.5.5 0 1 1 .707.707l-1.45 1.45a.5.5 0 0 1-.707 0z"/>
        </svg>
      </button>
    </div>
    <div></div>
  </nav>

  <div class="container-fluid">
    <!--  HEADER  -->
    <header class="row min-vh-100 d-flex align-items-center text-center" id="inicio">
      <div class="col-12 text-white">
        <h2>👋<span id="palabras">Hey!</span> Soy</h2>
        <h1 class="display-4">Álvaro Martínez</h1>
        <h2>Desarrollador Full Stack en Murcia</h2>
        <a class="btn boton mt-5 py-2 px-4" href="#about" role="button">Sobre mí</a>
      </div>
    </header>
  </div>

  <!-- <div class="col-12 d-none d-lg-block" id="about"></div> -->

  <!-- <div class="w-100 altura d-block" ></div> -->

  <div class="container h-auto align-items-center justify-content-center d-flex flex-column mt-5 mb-4" id="about">
    <!--  ABOUT  -->
    <section class="row d-flex align-items-center text-center mt-5 mt-lg-2 mb-5">
      <div class="col-12 mb-4 text-center col-md-4 col-xl-5 text-md-end">
        <img src="img/alvaro-martinez.webp" alt="Álvaro Martínez, desarrollador Full Stack en Murcia" class="img-fluid rounded-5" style="width: 200px" width="400" height="471" />
      </div>
      <div class="col-12 text-start col-md-8 col-lg-5 align-self-center">
        <h2 class="text-body-emphasis">Sobre mí</h2>
      <p class="lead text-body-emphasis">
Desarrollador Full Stack Junior de Murcia, graduado en DAW. Trabajo con <code>Java</code>, <code>Spring Boot</code>, <code>JavaScript</code>, <code>React</code>, <code>Node.js</code> y <code>SQL</code>, desarrollando aplicaciones web completas desde la base de datos hasta la interfaz de usuario.
        </p>
        <div class="d-flex flex-column align-items-center align-items-md-start">
          <nav class="nav mb-3 align-items-center gap-2">
            <a class="nav-link icono text-body-emphasis" href="https://github.com/amartinezdev/" target="_blank" title="GitHub">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" class="bi bi-github" fill="currentColor" aria-hidden="true">
                <use href="#icon-github"></use>
              </svg>
            </a>
            <a class="nav-link icono text-body-emphasis" href="https://www.linkedin.com/in/alvaromartinezdev" target="_blank" title="LinkedIn">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" class="bi bi-linkedin" fill="currentColor" aria-hidden="true">
                <use href="#icon-linkedin"></use>
              </svg>
            </a>
            <a class="nav-link icono text-body-emphasis" href="mailto:alvaromartinezdev@gmail.com" title="Correo electrónico">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" class="bi bi-envelope-at-fill" fill="currentColor" aria-hidden="true">
                <use href="#icon-envelope"></use>
              </svg>
            </a>
          </nav>
          <a class="btn boton-card filled cv-link" href="ALVARO_MARTINEZ_CV.pdf" download="ALVARO_MARTINEZ_CV.pdf">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-download" aria-hidden="true">
              <use href="#icon-download"></use>
            </svg>
            Descargar CV
          </a>
        </div>
      </div>
    </section>
  </div>

  <!--  DIVIDER 1  -->
  <div class="col-12 d-none d-lg-block mt-5 mt-lg-2"></div>
  <!-- <div class="w-100 h-25 d-block d-md-none"></div> -->

  <!--  PROYECTOS  -->
  <div class="container projects-section mb-0 mb-md-5 mt-1" id="projects">
    <section class="row mt-4">
      <div class="col-12 mb-3 text-center mt-1 mt-xl-2">
        <h2 class="text-body-emphasis text-start text-md-center">Proyectos</h2>
      </div>

      <div class="col-12">
        <div class="row">
          <!-- CARD 1 -->
          <div class="col-12 align-items-center justify-content-center d-flex col-xxl-6">
            <div class="card bg-secondary-subtle mb-2 mt-3" style="max-width: 700px">
              <div class="row g-0 row-cols-1 row-cols-md-2">
                <div class="col-md-6">
                  <a href="https://github.com/amartinezdev/restaurante">
                    <img
                      src="img/restaurante.webp"
                      srcset="img/restaurante-350.webp 350w, img/restaurante.webp 700w"
                      sizes="(max-width: 767px) 100vw, 350px"
                      class="img-fluid rounded-start-3"
                      alt="Captura del proyecto Restaurante, desarrollado con PHP y MySQL"
                      width="700"
                      height="525"
                      loading="lazy" />
                  </a>
                </div>
                <div class="col-md-6 text-center">
                  <div class="card-body text-start">
                    <a href="https://github.com/amartinezdev/restaurante" target="_blank" class="text-light-emphasis">
                      <h3 class="card-title h5 text-body-emphasis">Restaurante</h3>
                      <p class="card-text text-start mt-4 text-body-emphasis small">
                        Proyecto práctico de un restaurante desarrollado con <span class="php badge bg-dark-subtle">PHP</span> y
                        <span class="mysql badge bg-dark-subtle">MySQL</span>. Simula la funcionalidad de un restaurante real.
                      </p>
                    </a>
                  </div>
                  <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <a href="https://github.com/amartinezdev/restaurante" target="_blank" class="btn boton-card">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-github" aria-hidden="true">
                        <use href="#icon-github"></use>
                      </svg>
                      Github
                    </a>
                    <a href="https://amartinezdev.github.io/iOScalculator/" target="_blank" class="btn boton-card disabled">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-browser-chrome" aria-hidden="true">
                        <use href="#icon-browser-chrome"></use>
                      </svg>
                      Preview
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD 2 -->
          <div class="col-12 align-items-center justify-content-center d-flex col-xxl-6">
            <div class="card bg-secondary-subtle mb-2 mt-3" style="max-width: 700px">
              <div class="row g-0 row-cols-1 row-cols-md-2">
                <div class="col-md-6">
                  <a href="https://amartinezdev.github.io/iOScalculator/">
                    <img
                      src="img/calculator.webp"
                      srcset="img/calculator-350.webp 350w, img/calculator.webp 700w"
                      sizes="(max-width: 767px) 100vw, 350px"
                      class="img-fluid rounded-start-3"
                      alt="Captura de la calculadora web estilo iOS hecha con JavaScript y CSS"
                      width="700"
                      height="525"
                      loading="lazy" />
                  </a>
                </div>
                <div class="col-md-6 text-center">
                  <div class="card-body text-start">
                    <a href="https://amartinezdev.github.io/iOScalculator/" target="_blank" class="text-light-emphasis">
                      <h3 class="card-title h5 text-body-emphasis">Calculadora</h3>
                      <p class="card-text text-start mt-4 text-body-emphasis small">
                        Este proyecto es una calculadora web totalmente funcional inspirada en la interfaz de la calculadora iOS de Apple.
                        <span class="js badge bg-dark-subtle">JavaScript</span> y <span class="css badge bg-dark-subtle">CSS</span>
                      </p>
                    </a>
                  </div>
                  <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <a href="https://github.com/amartinezdev/iOScalculator" target="_blank" class="btn boton-card">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-github" aria-hidden="true">
                        <use href="#icon-github"></use>
                      </svg>
                      Github
                    </a>
                    <a href="https://amartinezdev.github.io/iOScalculator/" target="_blank" class="btn boton-card">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-browser-chrome" aria-hidden="true">
                        <use href="#icon-browser-chrome"></use>
                      </svg>
                      Preview
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD 3 -->
          <div class="col-12 align-items-center justify-content-center d-flex col-xxl-6">
            <div class="card bg-secondary-subtle mb-2 mt-3" style="max-width: 700px">
              <div class="row g-0 row-cols-1 row-cols-md-2">
                <div class="col-md-6">
                  <a href="https://alvaromartinez.dev/pong">
                    <img
                      src="img/pong.webp"
                      srcset="img/pong-350.webp 350w, img/pong.webp 700w"
                      sizes="(max-width: 767px) 100vw, 350px"
                      class="img-fluid rounded-start-3"
                      alt="Captura del juego Pong recreado con Phaser y Vite"
                      width="700"
                      height="525"
                      loading="lazy" />
                  </a>
                </div>
                <div class="col-md-6 text-center">
                  <div class="card-body text-start">
                    <a href="https://alvaromartinez.dev/pong" target="_blank" class="text-light-emphasis">
                      <h3 class="card-title h5 text-body-emphasis">Ping Pong</h3>
                      <p class="card-text text-start mt-4 text-body-emphasis small">
                        Recreación del clásico Pong, desarrollado con <span class="phaser badge bg-dark-subtle">Phaser</span> e integrado con
                        <span class="vite badge bg-dark-subtle">Vite</span>. Permite partidas locales para dos jugadores.
                      </p>
                    </a>
                  </div>
                  <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <a href="https://github.com/amartinezdev/pong" target="_blank" class="btn boton-card">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-github" aria-hidden="true">
                        <use href="#icon-github"></use>
                      </svg>
                      Github
                    </a>
                    <a href="https://alvaromartinez.dev/pong" target="_blank" class="btn boton-card">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-browser-chrome" aria-hidden="true">
                        <use href="#icon-browser-chrome"></use>
                      </svg>
                      Preview
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD 4 -->
          <div class="col-12 align-items-center justify-content-center d-flex col-xxl-6">
            <div class="card bg-secondary-subtle mb-2 mt-3" style="max-width: 700px">
              <div class="row g-0 row-cols-1 row-cols-md-2">
                <div class="col-md-6">
                  <a href="https://amartinezdev.github.io/etch-a-sketch/">
                    <img
                      src="img/etch-a-sketch.webp"
                      srcset="img/etch-a-sketch-350.webp 350w, img/etch-a-sketch.webp 700w"
                      sizes="(max-width: 767px) 100vw, 350px"
                      class="img-fluid rounded-start-3"
                      alt="Captura del proyecto Etch a Sketch hecho con JavaScript"
                      width="700"
                      height="525"
                      loading="lazy" />
                  </a>
                </div>
                <div class="col-md-6 text-center">
                  <div class="card-body text-start">
                    <a href="https://amartinezdev.github.io/etch-a-sketch/" target="_blank" class="text-light-emphasis">
                      <h3 class="card-title h5 text-body-emphasis">Etch a Sketch</h3>
                      <p class="card-text text-start mt-4 text-body-emphasis small">
                        Este proyecto es una recreación del clásico juego "Etch a Sketch", donde puedes dibujar utilizando un sistema de píxeles.
                        <span class="js badge bg-dark-subtle">JavaScript</span>
                      </p>
                    </a>
                  </div>
                  <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <a href="https://github.com/amartinezdev/etch-a-sketch" target="_blank" class="btn boton-card">
                      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-github" aria-hidden="true">
                        <use href="#icon-github"></use>
                      </svg>
                      Github
                    </a>
                    <a href="https://amartinezdev.github.io/etch-a-sketch/" target="_blank" class="btn boton-card">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-browser-chrome" aria-hidden="true">
                        <use href="#icon-browser-chrome"></use>
                      </svg>
                      Preview
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <!-- CERTIFICADOS -->
  <div class="container">
    <section class="row mt-5 text-center mb-5">
      <div class="col-12">
        <h2 class="text-body-emphasis mb-4">Certificados</h2>

        <div id="myCarousel" class="carousel slide mx-auto certs-carousel" data-bs-ride="carousel">
          <div class="carousel-indicators">
            <button data-bs-target="#myCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Certificado de IA Generativa"></button>
            <button data-bs-target="#myCarousel" data-bs-slide-to="1" aria-label="Certificado de JavaScript en Udemy"></button>
            <button data-bs-target="#myCarousel" data-bs-slide-to="2" aria-label="Certificado de Full-Stack Engineer"></button>
            <button data-bs-target="#myCarousel" data-bs-slide-to="3" aria-label="Certificado de Data Engineer"></button>
            <button data-bs-target="#myCarousel" data-bs-slide-to="4" aria-label="Certificado de Claude Code in Action"></button>
          </div>
          <div class="carousel-inner" role="listbox">
            <div class="carousel-item active">
              <img src="img/certs/AI-gen-sm.png" class="w-100 d-block" alt="Certificado de IA Generativa y su impacto en el negocio" width="700" height="539" loading="lazy" data-bs-toggle="modal" data-bs-target="#modal1" />
              <div class="carousel-caption">
                <span>IA Generativa y su impacto en el negocio</span>
              </div>
            </div>
            <div class="carousel-item">
              <img src="img/certs/udemy_JS_sm.png" class="w-100 d-block" alt="Certificado de JavaScript en Udemy" width="700" height="539" loading="lazy" data-bs-toggle="modal" data-bs-target="#modal2" />
              <div class="carousel-caption">
                <span>JavaScript &middot; Udemy</span>
              </div>
            </div>
            <div class="carousel-item">
              <img src="img/certs/full-stack-sm.png" class="w-100 d-block" alt="Certificado de Full-Stack Engineer" width="700" height="539" loading="lazy" data-bs-toggle="modal" data-bs-target="#modal4" />
              <div class="carousel-caption">
                <span>Full-Stack Engineer</span>
              </div>
            </div>
            <div class="carousel-item">
              <img src="img/certs/data-eng-sm.png" class="w-100 d-block" alt="Certificado de Data Engineer" width="700" height="539" loading="lazy" data-bs-toggle="modal" data-bs-target="#modal3" />
              <div class="carousel-caption">
                <span>Data Engineer</span>
              </div>
            </div>
            <div class="carousel-item">
              <img src="img/certs/claude-sm.png" class="w-100 d-block" alt="Certificado de Claude Code in Action" width="700" height="539" loading="lazy" data-bs-toggle="modal" data-bs-target="#modal5" />
              <div class="carousel-caption">
                <span>Claude Code in Action</span>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#myCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#myCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
          </button>
        </div>
      </div>
    </section>

    <!-- SKILLS -->
    <section class="row mt-5 text-center mb-5">
      <div class="col-12">
        <h3 class="text-body-emphasis h4">Skills</h3>

        <div class="d-flex flex-wrap gap-2 mt-3 justify-content-center mb-5">
          <a href="https://developer.mozilla.org/es/docs/Web/HTML" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/HTML.svg" alt="HTML" title="HTML" width="50" height="50" loading="lazy" />
          </a>
          <a href="https://www.w3schools.com/css/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/CSS.svg" alt="CSS" title="CSS" width="50" height="50" loading="lazy" />
          </a>
          <a href="https://www.w3schools.com/css/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/JavaScript.svg" alt="JavaScript" title="JavaScript" width="50" height="50" loading="lazy" />
          </a>

          <a href="https://nodejs.org/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/NodeJS-Dark.svg" alt="Node.js" title="Node.js" width="50" height="50" loading="lazy" />
          </a>

          <a href="https://es.react.dev/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/React-Dark.svg" alt="React" title="React" width="50" height="50" loading="lazy" />
          </a>

          
          <a href="https://expressjs.com/es/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/ExpressJS-Dark.svg" alt="ExpressJS" title="ExpressJS" width="50" height="50" loading="lazy" />
          </a>

          <a href="https://www.java.com/es/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/Java-Dark.svg" alt="Java" title="Java" width="50" height="50" loading="lazy" />
          </a>


          <a href="https://spring.io/projects/spring-boot/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/Spring-Dark.svg" alt="Spring" title="Spring Boot" width="50" height="50" loading="lazy" />
          </a>


          <div class="w-100 d-none d-sm-block"></div>
          <a href="https://www.php.net/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/PHP-Dark.svg" alt="PHP" title="PHP" width="50" height="50" loading="lazy" />
          </a>

          <a href="https://laravel.com/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/Laravel-Dark.svg" alt="Laravel" title="Laravel" width="50" height="50" loading="lazy" />
          </a>
          <a href="https://mysql.com/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/MySQL-Dark.svg" alt="MySQL" title="MySQL" width="50" height="50" loading="lazy" />
          </a>
          
          <a href="https://tailwindcss.com/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/TailwindCSS-Dark.svg" alt="Tailwind CSS" title="TailwindCSS" width="50" height="50" loading="lazy" />
          </a>

          <a href="https://git-scm.com/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/Git.svg" alt="Git" title="Git" width="50" height="50" loading="lazy" />
          </a>
          <a href="https://github.com/amartinezdev/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/Github-Dark.svg" alt="GitHub" title="GitHub" width="50" height="50" loading="lazy" />
          </a>
          <a href="https://www.docker.com/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/Docker.svg" alt="Docker" title="Docker" width="50" height="50" loading="lazy" />
          </a>
          <a href="https://astro.build/" class="icono" target="_blank">
            <img class="skillIcons" src="img/icons/Astro.svg" alt="Astro" title="Astro" width="50" height="50" loading="lazy" />
          </a>
        </div>
      </div>
    </section>
  </div>

  <!-- Modal 1 -->
  <div class="modal fade" id="modal1" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        <img src="img/certs/AI-gen.png" class="w-100" alt="Certificado de IA Generativa y su impacto en el negocio" width="1225" height="943" loading="lazy" />
      </div>
    </div>
  </div>

  <!-- Modal 2 -->
  <div class="modal fade" id="modal2" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        <img src="img/certs/udemy_JS.jpg" class="w-100" alt="Certificado de JavaScript en Udemy" width="1600" height="1190" loading="lazy" />
      </div>
    </div>
  </div>

  <!-- Modal 3 -->
  <div class="modal fade" id="modal3" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        <img src="img/certs/data-eng.png" class="w-100" alt="Certificado de Data Engineer" width="1227" height="947" loading="lazy" />
      </div>
    </div>
  </div>

  <!-- Modal 4 -->
  <div class="modal fade" id="modal4" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        <img src="img/certs/full-stack-eng.png" class="w-100" alt="Certificado de Full-Stack Engineer" width="1228" height="946" loading="lazy" />
      </div>
    </div>
  </div>

  <!-- Modal 5 -->
  <div class="modal fade" id="modal5" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
        <img src="img/certs/claude.png" class="w-100" alt="Certificado de Claude Code in Action" width="1228" height="949" loading="lazy" />
      </div>
    </div>
  </div>

  <!--  DIVIDER 2  -->
  <div class="col-12 d-none d-lg-block mt-lg-2" id="contact"></div>

  <!--  CONTACTO  -->
  <div class="container mb-5">
    <section class="row mb-5 mt-lg-2 mt-xl-2 justify-content-center">
      <div class="col-12 text-start text-md-center">
        <h2>Contacto</h2>
      </div>
      <?php if ($mensaje_enviado): ?>
        <div class="col-12 col-md-10 col-lg-8 col-xl-6 mx-auto">
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>¡Mensaje enviado!</strong> Gracias por contactarme. Me pondré en contacto contigo pronto.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </div>
      <?php elseif (!empty($error_mensaje)): ?>
        <div class="col-12 col-md-10 col-lg-8 col-xl-6 mx-auto">
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($error_mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        </div>
      <?php endif; ?>
      <?php if (!$mensaje_enviado): ?>
        <form method="POST">
          <input type="text" name="website" id="website" style="position: absolute; left: -9999px" tabindex="-1" autocomplete="off" aria-hidden="true" />
          <div class="row justify-content-center">
            <div class="col-12 d-flex align-items-center justify-content-between col-md-10 col-lg-10 col-xl-8 col-xxl-6">
              <div class="col form-floating mb-1 m-3">
                <input type="text" class="form-control" name="nombre" id="nombre" placeholder="" required />
                <label for="nombre">Nombre</label>
              </div>
              <div class="col form-floating mb-1 m-3">
                <input type="text" class="form-control" name="apellidos" id="apellidos" placeholder="" required />
                <label for="apellidos">Apellidos</label>
              </div>
            </div>
          </div>
          <div class="row justify-content-center">
            <div class="col-12 align-items-center justify-content-center col-md-10 col-lg-10 col-xl-8 col-xxl-6">
              <div class="form-floating mb-1 m-3">
                <input type="email" class="form-control" name="email" id="email" placeholder="" required />
                <label for="email">Email</label>
              </div>
            </div>
          </div>
          <div class="row justify-content-center">
            <div class="col-12 align-items-center justify-content-center col-md-10 col-lg-10 col-xl-8 col-xxl-6">
              <div class="form-floating mb-1 m-3">
                <input type="text" class="form-control" name="asunto" id="asunto" placeholder="" required />
                <label for="asunto">Asunto</label>
              </div>
            </div>
          </div>

          <div class="row justify-content-center">
            <div class="col-12 align-items-center justify-content-center col-md-10 col-lg-10 col-xl-8 col-xxl-6">
              <div class="form-floating mb-1 m-3">
                <textarea class="form-control" placeholder="" id="mensaje" name="mensaje" rows="5" style="height: 150px" required></textarea>
                <label for="mensaje">Mensaje</label>
              </div>
            </div>
          </div>
          <div class="row justify-content-center">
            <div class="d-grid col-12 col-md-10 col-lg-10 col-xl-8 col-xxl-6">
              <button type="submit" class="btn m-3">Enviar</button>
            </div>
          </div>
        </form>
      <?php endif; ?>
    </section>
  </div>

  <div class="container-fluid mb-4">
    <footer class="row mt-5">
      <div class="col-12 text-center mt-5 d-flex flex-column justify-content-center align-items-center">
        <nav class="nav justify-content-center justify-content-md-start mb-3 align-items-center gap-2">
          <a class="nav-link icono text-body-emphasis" href="https://github.com/amartinezdev/" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" class="bi bi-github" fill="currentColor" aria-hidden="true">
              <use href="#icon-github"></use>
            </svg>
          </a>
          <a class="nav-link icono text-body-emphasis" href="https://www.linkedin.com/in/alvaromartinezdev" target="_blank">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" class="bi bi-linkedin" fill="currentColor" aria-hidden="true">
              <use href="#icon-linkedin"></use>
            </svg>
          </a>
          <a class="nav-link icono text-body-emphasis" href="mailto:alvaromartinezdev@gmail.com">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" class="bi bi-envelope-at-fill" fill="currentColor" aria-hidden="true">
              <use href="#icon-envelope"></use>
            </svg>
          </a>
        </nav>
        <p class="mb-3">&copy; 2025 Álvaro Martínez. Casi todos los derechos reservados.</p>
      </div>
    </footer>
  </div>

  <script src="palabras.js"></script>
  <script src="cambiarTema.js"></script>
</body>

</html>