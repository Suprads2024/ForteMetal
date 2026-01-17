<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitización y validación básica
    $necesitas = htmlspecialchars(trim($_POST['necesitas'] ?? ''));
    $proceso   = htmlspecialchars(trim($_POST['proceso'] ?? ''));
    $material  = htmlspecialchars(trim($_POST['material'] ?? ''));
    $zona      = htmlspecialchars(trim($_POST['zona'] ?? ''));
    $whatsapp  = htmlspecialchars(trim($_POST['whatsapp'] ?? ''));

    // Validación obligatoria
    if (!$necesitas || !$proceso || !$material || !$zona || !$whatsapp) {
        http_response_code(400);
        echo "Por favor completá todos los campos obligatorios.";
        exit;
    }

    // Configuración del correo
    $to      = "info@cristalsur.com.ar"; // Cambiá esto por tu correo
    $subject = "Nuevo pedido de cotización desde la web";

    $headers  = "From: Web - Cotizador <info@cristalsur.com.ar>\r\n";
    $headers .= "Reply-To: info@cristalsur.com.ar\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Cuerpo del mensaje
    $body  = "Nuevo pedido de cotización:\n\n";
    $body .= "¿Qué necesitás?: $necesitas\n";
    $body .= "Proceso: $proceso\n";
    $body .= "Material a soldar: $material\n";
    $body .= "Zona / Ciudad: $zona\n";
    $body .= "WhatsApp: $whatsapp\n";

    // Enviar el correo
    if (mail($to, $subject, $body, $headers)) {
        // Redirige a página de agradecimiento si querés
        header("Location: gracias.html");
        exit;
    } else {
        http_response_code(500);
        echo "Error al enviar el formulario. Intentá nuevamente.";
    }
} else {
    http_response_code(403);
    echo "Acceso no permitido.";
}
?>
