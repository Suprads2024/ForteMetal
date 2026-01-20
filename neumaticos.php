<?php
// neumaticos.php

// ==========================
// CONFIGURACIÓN
// ==========================

// 1) A qué email querés recibir las consultas:
$TO_EMAIL = "grupoforte.mkt@gmail.com"; // <-- CAMBIAR

// 2) Asunto base:
$SUBJECT_BASE = "Nueva consulta de neumáticos";

// 3) Email "From" (ideal que sea del mismo dominio para evitar spam):
$FROM_EMAIL = "no-reply@tudominio.com"; // <-- CAMBIAR (o dejalo si existe)

// 4) Página a donde volver (opcional):
$REDIRECT_OK = "gracias.html";   // o "index.html?ok=1"
$REDIRECT_ERR = "index.html?error=1";

// ==========================
// HELPERS
// ==========================
function clean_text($value) {
  $value = trim((string)$value);
  $value = str_replace(["\r", "\n"], " ", $value);
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_post() {
  return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
}

// ==========================
// MAIN
// ==========================
if (!is_post()) {
  http_response_code(405);
  echo "Método no permitido";
  exit;
}

// Honeypot opcional (si querés lo agregamos en el HTML)
// si agregás: <input type="text" name="website" style="display:none">
// y se completa, lo tratamos como bot.
if (!empty($_POST['website'])) {
  header("Location: $REDIRECT_OK");
  exit;
}

// Campos
$nombre           = clean_text($_POST['nombre'] ?? '');
$telefono         = clean_text($_POST['telefono'] ?? '');
$ciudad_provincia = clean_text($_POST['ciudad_provincia'] ?? '');
$tipo_vehiculo    = clean_text($_POST['tipo_vehiculo'] ?? '');
$medida           = clean_text($_POST['medida'] ?? '');
$uso_principal    = clean_text($_POST['uso_principal'] ?? '');
$mensaje          = clean_text($_POST['mensaje'] ?? '');

// Validación mínima (los required del HTML ayudan, pero el server manda)
$errors = [];
if ($nombre === '') $errors[] = "Falta nombre";
if ($telefono === '') $errors[] = "Falta teléfono";
if ($ciudad_provincia === '') $errors[] = "Falta ciudad/provincia";
if ($tipo_vehiculo === '' || $tipo_vehiculo === 'Seleccioná una opción') $errors[] = "Falta tipo de vehículo";
if ($uso_principal === '' || $uso_principal === 'Seleccioná una opción') $errors[] = "Falta uso principal";

if (!empty($errors)) {
  header("Location: $REDIRECT_ERR");
  exit;
}

// Datos extra útiles
$ip       = $_SERVER['REMOTE_ADDR'] ?? '';
$ua       = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fecha    = date("Y-m-d H:i:s");

// Asunto final (sumo ciudad para identificar rápido)
$subject = $SUBJECT_BASE . " | " . $ciudad_provincia;

// Cuerpo en HTML
$html = "
  <div style='font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111;'>
    <h2 style='margin:0 0 12px;'>Nueva consulta desde el formulario</h2>
    <table cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; max-width:720px;'>
      <tr><td style='border:1px solid #ddd; width:220px;'><b>Nombre</b></td><td style='border:1px solid #ddd;'>$nombre</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Teléfono / WhatsApp</b></td><td style='border:1px solid #ddd;'>$telefono</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Ciudad / Provincia</b></td><td style='border:1px solid #ddd;'>$ciudad_provincia</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Tipo de vehículo</b></td><td style='border:1px solid #ddd;'>$tipo_vehiculo</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Medida</b></td><td style='border:1px solid #ddd;'>" . ($medida !== '' ? $medida : '—') . "</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Uso principal</b></td><td style='border:1px solid #ddd;'>$uso_principal</td></tr>
      <tr><td style='border:1px solid #ddd; vertical-align:top;'><b>Mensaje</b></td><td style='border:1px solid #ddd;'>" . ($mensaje !== '' ? nl2br($mensaje) : '—') . "</td></tr>
    </table>

    <p style='margin:14px 0 0; color:#555; font-size:12px;'>
      <b>Fecha:</b> $fecha<br>
      <b>IP:</b> $ip<br>
      <b>User Agent:</b> $ua
    </p>
  </div>
";

// Cuerpo en texto plano (por si el cliente no soporta HTML)
$text = "Nueva consulta desde el formulario\n\n"
  . "Nombre: $nombre\n"
  . "Teléfono/WhatsApp: $telefono\n"
  . "Ciudad/Provincia: $ciudad_provincia\n"
  . "Tipo de vehículo: $tipo_vehiculo\n"
  . "Medida: " . ($medida !== '' ? $medida : '—') . "\n"
  . "Uso principal: $uso_principal\n"
  . "Mensaje: " . ($mensaje !== '' ? $mensaje : '—') . "\n\n"
  . "Fecha: $fecha\nIP: $ip\nUA: $ua\n";

// Headers (HTML)
$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-type: text/html; charset=UTF-8";
$headers[] = "From: FORTE Neumáticos <{$FROM_EMAIL}>";
$headers[] = "Reply-To: {$FROM_EMAIL}"; // si querés reply-to al cliente, necesito un campo email
$headers[] = "X-Mailer: PHP/" . phpversion();

$ok = mail($TO_EMAIL, $subject, $html, implode("\r\n", $headers));

// Si falla HTML, probamos texto plano como fallback
if (!$ok) {
  $headers_text = [];
  $headers_text[] = "MIME-Version: 1.0";
  $headers_text[] = "Content-type: text/plain; charset=UTF-8";
  $headers_text[] = "From: FORTE Neumáticos <{$FROM_EMAIL}>";
  $headers_text[] = "X-Mailer: PHP/" . phpversion();
  $ok = mail($TO_EMAIL, $subject, $text, implode("\r\n", $headers_text));
}

if ($ok) {
  header("Location: $REDIRECT_OK");
  exit;
} else {
  header("Location: $REDIRECT_ERR");
  exit;
}
