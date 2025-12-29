<?php
// ===============================
// CONFIGURACIÓN
// ===============================
$destino = "ventas@fortemetal.com.ar"; // <-- CAMBIAR
$asunto  = "Nueva solicitud de cotización – Forte Metal";

// ===============================
// VALIDACIÓN BÁSICA
// ===============================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /");
  exit;
}

$nombre     = trim($_POST["nombre"] ?? "");
$empresa    = trim($_POST["empresa"] ?? "");
$telefono   = trim($_POST["telefono"] ?? "");
$localidad  = trim($_POST["localidad"] ?? "");
$categoria  = trim($_POST["categoria"] ?? "");
$detalle    = trim($_POST["detalle"] ?? "");
$obs        = trim($_POST["obs"] ?? "");

if ($nombre === "" || $telefono === "" || $localidad === "" || $categoria === "" || $detalle === "") {
  die("Faltan campos obligatorios.");
}

// ===============================
// CUERPO DEL MAIL
// ===============================
$mensaje = "
NUEVA SOLICITUD DE COTIZACIÓN

Nombre y apellido:
$nombre

Empresa:
" . ($empresa ?: "—") . "

WhatsApp / Teléfono:
$telefono

Localidad / Zona:
$localidad

Qué quiere cotizar:
$categoria

Detalle / Lista de materiales:
$detalle

Observaciones:
" . ($obs ?: "—") . "

--------------------------------
Enviado desde el sitio web
";

// ===============================
// HEADERS
// ===============================
$headers = "From: Forte Metal <no-reply@fortemetal.com.ar>\r\n";
$headers .= "Reply-To: $telefono\r\n";
$headers .= "MIME-Version: 1.0\r\n";

// ===============================
// ARCHIVO ADJUNTO (si existe)
// ===============================
if (!empty($_FILES["archivo"]["name"])) {

  $archivoTmp  = $_FILES["archivo"]["tmp_name"];
  $archivoNom  = basename($_FILES["archivo"]["name"]);
  $archivoTipo = $_FILES["archivo"]["type"];

  $contenido = chunk_split(base64_encode(file_get_contents($archivoTmp)));
  $boundary  = md5(time());

  $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

  $cuerpo  = "--$boundary\r\n";
  $cuerpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $cuerpo .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
  $cuerpo .= $mensaje . "\r\n";

  $cuerpo .= "--$boundary\r\n";
  $cuerpo .= "Content-Type: $archivoTipo; name=\"$archivoNom\"\r\n";
  $cuerpo .= "Content-Disposition: attachment; filename=\"$archivoNom\"\r\n";
  $cuerpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
  $cuerpo .= $contenido . "\r\n";
  $cuerpo .= "--$boundary--";

} else {
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $cuerpo = $mensaje;
}

// ===============================
// ENVÍO
// ===============================
if (mail($destino, $asunto, $cuerpo, $headers)) {
  header("Location: /gracias.html");
  exit;
} else {
  die("Error al enviar el formulario. Intente nuevamente.");
}
