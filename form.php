<?php
// form.php — Formulario Metales con adjunto opcional

// ==========================
// CONFIG
// ==========================
$TO_EMAIL = "ignaciosoraka@gmail.com";        // <-- CAMBIAR
$FROM_EMAIL = "no-reply@tudominio.com";     // <-- CAMBIAR (ideal del mismo dominio)
$SUBJECT_BASE = "Nueva solicitud de cotización - Metales";

$REDIRECT_OK  = "gracias.html";             // <-- CAMBIAR si querés
$REDIRECT_ERR = "index.html?error=1";       // <-- CAMBIAR si querés

// Tamaño máximo del archivo (ej: 5 MB)
$MAX_FILE_BYTES = 5 * 1024 * 1024;

// Extensiones permitidas
$ALLOWED_EXT = ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx'];

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
$nombre    = safe($_POST['nombre'] ?? '');
$empresa   = safe($_POST['empresa'] ?? '');
$telefono  = safe($_POST['telefono'] ?? '');
$localidad = safe($_POST['localidad'] ?? '');
$categoria = safe($_POST['categoria'] ?? '');
$detalle   = safe($_POST['detalle'] ?? '');
$obs       = safe($_POST['obs'] ?? '');

// Validaciones server-side
$errors = [];
if ($nombre === '') $errors[] = "Falta nombre";
if ($telefono === '') $errors[] = "Falta teléfono";
if ($localidad === '') $errors[] = "Falta localidad";
if ($categoria === '' || $categoria === 'Seleccioná una opción') $errors[] = "Falta categoría";
if ($detalle === '') $errors[] = "Falta detalle/lista";

if (!empty($errors)) {
  header("Location: $REDIRECT_ERR");
  exit;
}

// Info extra
$ip    = $_SERVER['REMOTE_ADDR'] ?? '';
$ua    = $_SERVER['HTTP_USER_AGENT'] ?? '';
$fecha = date("Y-m-d H:i:s");

// Asunto final
$subject = $SUBJECT_BASE . " | " . $categoria . " | " . $localidad;

// Cuerpo texto (plano)
$bodyText =
"Solicitud de cotización - Metales\n\n" .
"Nombre: $nombre\n" .
"Empresa: " . ($empresa !== '' ? $empresa : '—') . "\n" .
"Teléfono/WhatsApp: $telefono\n" .
"Localidad/Zona: $localidad\n" .
"Categoría: $categoria\n\n" .
"Detalle/Lista:\n$detalle\n\n" .
"Observaciones: " . ($obs !== '' ? $obs : '—') . "\n\n" .
"Fecha: $fecha\n" .
"IP: $ip\n" .
"UA: $ua\n";

// ==========================
// Adjuntos (opcional)
// ==========================
$hasFile = isset($_FILES['archivo']) && ($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

$headers = [];
$headers[] = "From: Web Metales <{$FROM_EMAIL}>";
$headers[] = "MIME-Version: 1.0";
$headers[] = "X-Mailer: PHP/" . phpversion();

$message = "";
$ok = false;

if ($hasFile) {
  // Validar upload
  $fileErr  = $_FILES['archivo']['error'];
  $fileSize = (int)($_FILES['archivo']['size'] ?? 0);
  $tmpPath  = $_FILES['archivo']['tmp_name'] ?? '';
  $origName = $_FILES['archivo']['name'] ?? 'archivo';

  if ($fileErr !== UPLOAD_ERR_OK) {
    // Si falla el upload, enviamos igual SIN adjunto
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $ok = mail($TO_EMAIL, $subject, $bodyText, implode("\r\n", $headers));
    header("Location: " . ($ok ? $REDIRECT_OK : $REDIRECT_ERR));
    exit;
  }

  if ($fileSize <= 0 || $fileSize > $MAX_FILE_BYTES) {
    // Muy grande o inválido -> enviamos sin adjunto
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $bodyText .= "\n\n[Adjunto no enviado: tamaño inválido o excede el máximo permitido]";
    $ok = mail($TO_EMAIL, $subject, $bodyText, implode("\r\n", $headers));
    header("Location: " . ($ok ? $REDIRECT_OK : $REDIRECT_ERR));
    exit;
  }

  // Validar extensión
  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  if (!in_array($ext, $ALLOWED_EXT, true)) {
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $bodyText .= "\n\n[Adjunto no enviado: extensión no permitida ($ext)]";
    $ok = mail($TO_EMAIL, $subject, $bodyText, implode("\r\n", $headers));
    header("Location: " . ($ok ? $REDIRECT_OK : $REDIRECT_ERR));
    exit;
  }

  // Leer el archivo y armar multipart
  $fileData = file_get_contents($tmpPath);
  if ($fileData === false) {
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $bodyText .= "\n\n[Adjunto no enviado: no se pudo leer el archivo]";
    $ok = mail($TO_EMAIL, $subject, $bodyText, implode("\r\n", $headers));
    header("Location: " . ($ok ? $REDIRECT_OK : $REDIRECT_ERR));
    exit;
  }

  $boundary = "==Multipart_Boundary_x" . md5((string)microtime()) . "x";

  $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

  // Parte texto
  $message  = "--{$boundary}\r\n";
  $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
  $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
  $message .= $bodyText . "\r\n\r\n";

  // Parte adjunto
  $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
  $contentType = "application/octet-stream"; // suficiente para adjuntos variados

  $message .= "--{$boundary}\r\n";
  $message .= "Content-Type: {$contentType}; name=\"{$safeName}\"\r\n";
  $message .= "Content-Disposition: attachment; filename=\"{$safeName}\"\r\n";
  $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
  $message .= chunk_split(base64_encode($fileData)) . "\r\n";
  $message .= "--{$boundary}--\r\n";

  $ok = mail($TO_EMAIL, $subject, $message, implode("\r\n", $headers));

} else {
  // Sin adjunto
  $headers[] = "Content-Type: text/plain; charset=UTF-8";
  $ok = mail($TO_EMAIL, $subject, $bodyText, implode("\r\n", $headers));
}

header("Location: " . ($ok ? $REDIRECT_OK : $REDIRECT_ERR));
exit;
