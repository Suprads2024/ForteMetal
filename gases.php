<?php
// form.php — Formulario Soldadura (mejor deliverability + envío en HTML estilo tabla)

// ==========================
// CONFIG
// ==========================
$TO_EMAIL      = "grupoforte.mkt@gmail.com";          // a dónde llega
$FROM_EMAIL    = "no-reply@tudominio.com";           // IDEAL: no-reply@forteindustrial.net
$FROM_NAME     = "FORTE Soldadura";                  // lo que querés que se vea
$SUBJECT_BASE  = "Nueva solicitud de cotización - Soldadura";

$REDIRECT_OK   = "gracias.html";
$REDIRECT_ERR  = "index.html?error=1";

// ==========================
// HELPERS
// ==========================
function clean_text($v) {
  $v = trim((string)$v);
  $v = str_replace(["\r", "\n"], " ", $v);
  return $v;
}
function safe($v) {
  return htmlspecialchars(clean_text($v), ENT_QUOTES, 'UTF-8');
}
function is_post() {
  return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

// ==========================
// MAIN
// ==========================
if (!is_post()) {
  http_response_code(405);
  echo "Método no permitido";
  exit;
}

// Campos
$necesitas = safe($_POST['necesitas'] ?? '');
$proceso   = safe($_POST['proceso'] ?? '');
$material  = safe($_POST['material'] ?? '');
$zona      = safe($_POST['zona'] ?? '');
$whatsapp  = safe($_POST['whatsapp'] ?? '');

// Validaciones server-side
$errors = [];
if ($necesitas === '' || $necesitas === 'Seleccioná una opción') $errors[] = "Falta qué necesitás";
if ($proceso   === '' || $proceso   === 'Seleccioná un proceso') $errors[] = "Falta proceso";
if ($material  === '' || $material  === 'Seleccioná un material') $errors[] = "Falta material";
if ($zona      === '') $errors[] = "Falta zona";
if ($whatsapp  === '') $errors[] = "Falta WhatsApp";

if (!empty($errors)) {
  header("Location: $REDIRECT_ERR");
  exit;
}

// Info extra
$ip    = $_SERVER['REMOTE_ADDR'] ?? '';
$ua    = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fecha = date("Y-m-d H:i:s");

// Asunto final (más informativo)
$subject = $SUBJECT_BASE . " | " . $necesitas . " | " . $zona;

// ==========================
// CUERPOS (HTML + fallback texto)
// ==========================

// Cuerpo en HTML (tabla estilo Neumáticos/Metales)
$html = "
  <div style='font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111;'>
    <h2 style='margin:0 0 12px;'>Nueva solicitud de cotización — Soldadura</h2>

    <table cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; max-width:720px;'>
      <tr><td style='border:1px solid #ddd; width:220px;'><b>¿Qué necesitás?</b></td><td style='border:1px solid #ddd;'>$necesitas</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Proceso</b></td><td style='border:1px solid #ddd;'>$proceso</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Material a soldar</b></td><td style='border:1px solid #ddd;'>$material</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>Zona / Ciudad</b></td><td style='border:1px solid #ddd;'>$zona</td></tr>
      <tr><td style='border:1px solid #ddd;'><b>WhatsApp</b></td><td style='border:1px solid #ddd;'>$whatsapp</td></tr>
    </table>

    <p style='margin:14px 0 0; color:#555; font-size:12px;'>
      <b>Fecha:</b> $fecha<br>
      <b>IP:</b> $ip<br>
      <b>User Agent:</b> $ua
    </p>
  </div>
";

// Fallback texto (por si algún cliente no renderiza HTML)
$bodyText =
"Solicitud de cotización - SOLDADURA\n\n" .
"¿Qué necesitás?: $necesitas\n" .
"Proceso: $proceso\n" .
"Material a soldar: $material\n" .
"Zona / Ciudad: $zona\n" .
"WhatsApp: $whatsapp\n\n" .
"Fecha: $fecha\n" .
"IP: $ip\n" .
"UA: $ua\n";

// ==========================
// HEADERS + ENVÍO
// ==========================
$headers = [];
$headers[] = "From: {$FROM_NAME} <{$FROM_EMAIL}>";
$headers[] = "Reply-To: {$FROM_NAME} <{$FROM_EMAIL}>"; // si tenés un mail real tipo ventas@..., ponelo acá
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/html; charset=UTF-8";
$headers[] = "X-Mailer: PHP/" . phpversion();

// IMPORTANTÍSIMO: envelope sender (mejora SPF y reduce spam)
$additional_params = "-f {$FROM_EMAIL}";

$ok = mail($TO_EMAIL, $subject, $html, implode("\r\n", $headers), $additional_params);

header("Location: " . ($ok ? $REDIRECT_OK : $REDIRECT_ERR));
exit;
?>
