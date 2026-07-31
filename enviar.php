<?php
/**
 * The Petit Club — endpoint del formulario de contacto.
 * Envía por SMTP autenticado (buzón del dominio) al correo destino definido
 * en config.php (fuera del repositorio; ver config.sample.php).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$fallo = static function (int $codigo, string $error): void {
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'error' => $error]);
    exit;
};
$exito = static function (): void {
    echo json_encode(['ok' => true]);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $fallo(405, 'metodo');
}

// Solo peticiones del propio sitio (si el navegador envía Origin/Referer).
$host = $_SERVER['HTTP_HOST'] ?? '';
foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
    if (!empty($_SERVER[$h]) && $host !== '') {
        $oh = parse_url($_SERVER[$h], PHP_URL_HOST);
        if ($oh !== null && strcasecmp((string) $oh, (string) parse_url('http://' . $host, PHP_URL_HOST)) !== 0) {
            $fallo(403, 'origen');
        }
    }
}

$config = __DIR__ . '/config.php';
if (!is_file($config)) {
    $fallo(500, 'configuracion');
}
$cfg = require $config;

// Honeypot: los bots lo rellenan; se responde éxito sin enviar nada.
if (!empty($_POST['web'])) {
    $exito();
}
// Tiempo mínimo de relleno (3 s) si el cliente mandó su marca de tiempo.
$t = (int) ($_POST['t'] ?? 0);
if ($t > 0) {
    $transcurrido = (microtime(true) * 1000) - $t;
    if ($transcurrido >= 0 && $transcurrido < 3000) {
        $exito();
    }
}

// Rate limit sencillo por IP: máx. 5 envíos por 10 minutos.
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$bucket = sys_get_temp_dir() . '/tpc-form-' . hash('sha256', $ip) . '.json';
$ahora = time();
$marcas = [];
if (is_file($bucket)) {
    $marcas = json_decode((string) file_get_contents($bucket), true) ?: [];
}
$marcas = array_values(array_filter($marcas, static fn ($m) => is_int($m) && $ahora - $m < 600));
if (count($marcas) >= 5) {
    header('Retry-After: 600');
    $fallo(429, 'demasiados');
}
$marcas[] = $ahora;
@file_put_contents($bucket, json_encode($marcas), LOCK_EX);

// Validación estricta de entradas.
$campo = static fn (string $k, int $max): string => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
$nombre   = $campo('nombre', 100);
$email    = $campo('email', 200);
$telefono = $campo('telefono', 30);
$tipo     = $campo('tipo', 40);
$fecha    = $campo('fecha', 20);
$peques   = $campo('peques', 5);
$mensaje  = $campo('mensaje', 4000);

if ($nombre === '' || $mensaje === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fallo(422, 'datos');
}
$tiposValidos = ['Petit Birthday', 'Petit Wedding', 'Petit Celebrations', 'Petit Care', 'Petit Business', 'Otra idea'];
if (!in_array($tipo, $tiposValidos, true)) {
    $tipo = 'Sin especificar';
}
if ($telefono !== '' && !preg_match('/^[0-9+()\s.\-]{6,30}$/', $telefono)) {
    $telefono = '';
}
if ($fecha !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m)) {
    $fecha = $m[3] . '/' . $m[2] . '/' . $m[1];
} elseif ($fecha !== '') {
    $fecha = '';
}
$peques = preg_match('/^\d{1,3}$/', $peques) ? $peques : '';

// Correo HTML con la estética de la marca.
$e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$fila = static function (string $etiqueta, string $valor) use ($e): string {
    if ($valor === '') {
        return '';
    }
    return '<tr>'
        . '<td style="padding:10px 14px;border-bottom:1px solid #E6DECB;font-family:Georgia,serif;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#B0864C;white-space:nowrap;vertical-align:top">' . $etiqueta . '</td>'
        . '<td style="padding:10px 14px;border-bottom:1px solid #E6DECB;font-family:Georgia,serif;font-size:16px;color:#5C4B3D">' . nl2br($e($valor)) . '</td>'
        . '</tr>';
};

$cuerpo = '<!doctype html><html><body style="margin:0;padding:0;background:#F1EBDD">'
    . '<div style="max-width:600px;margin:0 auto;padding:28px 16px">'
    . '<div style="background:#FBF8F0;border:1px solid #E0D7C2;border-radius:12px;overflow:hidden">'
    . '<div style="background:#5A6733;padding:26px 24px;text-align:center">'
    . '<div style="font-family:Georgia,serif;font-size:11px;letter-spacing:4px;color:#CBD5A8;text-transform:uppercase">The Petit Club</div>'
    . '<div style="font-family:Georgia,serif;font-size:24px;color:#F3ECDC;margin-top:6px">&#10024; Nuevo formulario &middot; Posible cliente</div>'
    . '</div>'
    . '<div style="padding:24px">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'
    . $fila('Nombre', $nombre)
    . $fila('Email', $email)
    . $fila('Tel&eacute;fono', $telefono)
    . $fila('Experiencia', $tipo)
    . $fila('Fecha del evento', $fecha)
    . $fila('N&ordm; de peques', $peques)
    . '</table>'
    . '<div style="margin-top:18px;padding:16px 18px;background:#F4EEDF;border:1px solid #E6DECB;border-radius:10px">'
    . '<div style="font-family:Georgia,serif;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#B0864C;margin-bottom:8px">Mensaje</div>'
    . '<div style="font-family:Georgia,serif;font-size:16px;line-height:1.6;color:#5C4B3D">' . nl2br($e($mensaje)) . '</div>'
    . '</div>'
    . '<div style="margin-top:20px;text-align:center">'
    . '<a href="mailto:' . $e($email) . '" style="display:inline-block;background:#5A6733;color:#F3ECDC;font-family:Georgia,serif;font-size:14px;letter-spacing:2px;text-transform:uppercase;text-decoration:none;padding:12px 26px;border-radius:100px">Responder a ' . $e($nombre) . '</a>'
    . '</div>'
    . '</div>'
    . '<div style="padding:14px;text-align:center;font-family:Georgia,serif;font-size:12px;color:#8A7A66;border-top:1px solid #E6DECB">Enviado desde el formulario de contacto de la web &middot; Little Explorers &middot; Barcelona</div>'
    . '</div></div></body></html>';

$texto = "NUEVO FORMULARIO - POSIBLE CLIENTE\n\n"
    . "Nombre: {$nombre}\nEmail: {$email}\n"
    . ($telefono !== '' ? "Telefono: {$telefono}\n" : '')
    . "Experiencia: {$tipo}\n"
    . ($fecha !== '' ? "Fecha del evento: {$fecha}\n" : '')
    . ($peques !== '' ? "No de peques: {$peques}\n" : '')
    . "\nMensaje:\n{$mensaje}\n";

require __DIR__ . '/php/phpmailer/Exception.php';
require __DIR__ . '/php/phpmailer/PHPMailer.php';
require __DIR__ . '/php/phpmailer/SMTP.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $cfg['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $cfg['smtp_user'];
    $mail->Password   = $cfg['smtp_pass'];
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = (int) $cfg['smtp_port'];
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($cfg['smtp_user'], 'The Petit Club · Web');
    $mail->addAddress($cfg['dest']);
    $mail->addReplyTo($email, $nombre);

    $mail->isHTML(true);
    $mail->Subject = 'Nuevo formulario · Posible cliente — ' . $nombre . ' (' . $tipo . ')';
    $mail->Body    = $cuerpo;
    $mail->AltBody = $texto;

    $mail->send();
    $exito();
} catch (Throwable $ex) {
    // Nunca exponer detalles SMTP al cliente.
    $fallo(500, 'envio');
}
