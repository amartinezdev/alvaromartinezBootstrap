<?php

session_start();
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$nombre = htmlspecialchars($_POST['nombre'] ?? '');
$apellidos = htmlspecialchars($_POST['apellidos'] ?? '');
$email = htmlspecialchars($_POST['email'] ?? '');
$asunto = htmlspecialchars($_POST['asunto'] ?? '');
$mensaje = htmlspecialchars($_POST['mensaje'] ?? '');

$error_mensaje = "";

if (empty($nombre) || empty($apellidos) || empty($email) || empty($asunto) || empty($mensaje)) {
    $error_mensaje = "Todos los campos son obligatorios.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_mensaje = "El email no es válido.";
} else {
    $smtpHost = env('SMTP_HOST', 'smtp.gmail.com');
    $smtpUser = env('SMTP_USERNAME');
    $smtpPass = env('SMTP_PASSWORD');
    $smtpPort = env('SMTP_PORT', 587);
    $smtpSecure = env('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS);
    $mailTo = env('MAIL_TO', 'alvaromartinezdev@gmail.com');

    if (!$smtpUser || !$smtpPass) {
        $error_mensaje = "Configuración SMTP incompleta. Revisa tu archivo .env.";
    } else {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = $smtpPort;

            $mail->setFrom($smtpUser, 'Contacto Web');
            $mail->addReplyTo($email, $nombre . ' ' . $apellidos);
            $mail->addAddress($mailTo, 'Álvaro Martínez');

            $mail->isHTML(true);
            $mail->Subject = 'Nuevo mensaje desde el formulario de contacto: ' . $asunto;
            $mail->Body = "<html><head><title>Nuevo mensaje de contacto</title></head><body>"
                . "<h2>Nuevo mensaje desde el sitio web</h2>"
                . "<p><strong>Nombre:</strong> $nombre $apellidos</p>"
                . "<p><strong>Email:</strong> $email</p>"
                . "<p><strong>Asunto:</strong> $asunto</p>"
                . "<p><strong>Mensaje:</strong></p>"
                . "<p>$mensaje</p>"
                . "</body></html>";
            $mail->AltBody = "Nombre: $nombre $apellidos\nEmail: $email\nAsunto: $asunto\nMensaje: $mensaje";

            $mail->send();
            $_SESSION['mensaje_enviado'] = true;
        } catch (Exception $e) {
            $_SESSION['error_mensaje'] = 'Error al enviar el mensaje: ' . $e->getMessage();
        }
    }
}

if (!empty($error_mensaje)) {
    $_SESSION['error_mensaje'] = $error_mensaje;
}

header("Location: index.php#contact");
exit;
