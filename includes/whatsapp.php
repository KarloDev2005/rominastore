<?php
/* ═══════════════════════════════════════════════════
   includes/whatsapp.php — WhatsApp Cloud API Helper

   Credenciales (Meta Business / WhatsApp Cloud API)
   ═══════════════════════════════════════════════════ */

define('WSP_PHONE_ID',   '1129069736946295');
define('WSP_WABA_ID',    '967609229076020');
define('WSP_TOKEN',      'EAAecS3E8AysBRLzY4l9ICOWX3cbd10ktRPMgt2ja1z1oGviVFOYFgr8Szjnr0uQ9nIQTxUjR0jmjskIMn63wZB4xWM6eZAaTXk9nF2I5s4yCy5Rk8ZCqgkY9VRZADR29YoIwsZAhykr3o5Ka2aBVZBgbMfZC89HPiLwOJjZA2BE26A9RcKzzpSJ4nI1LWt98FMHZBjU0IdeeF2FEduOjcClfLzrLTJ9UcwZAffLMENIrqEA5IURvSRUXyniaJzTI3sh8fY1HxDsSf95VHkZB7YlcoDf');

/* Número del dueño — recibe todas las notificaciones */
define('WSP_OWNER',      '522871246175'); // +52 287 124 6175 en formato internacional sin +

/* Número de prueba (usado en dev / sandbox de Meta) */
define('WSP_TEST_NUM',   '15556389536');

/**
 * Envía un mensaje de texto simple al número del dueño.
 *
 * @param  string $mensaje  Texto a enviar (max ~4096 chars)
 * @return array            ['ok'=>bool, 'response'=>array|string]
 */
function wspEnviar(string $mensaje): array {
    $url  = 'https://graph.facebook.com/v19.0/' . WSP_PHONE_ID . '/messages';
    $body = json_encode([
        'messaging_product' => 'whatsapp',
        'to'                => WSP_OWNER,
        'type'              => 'text',
        'text'              => ['body' => $mensaje],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WSP_TOKEN,
        ],
        CURLOPT_TIMEOUT        => 8,
    ]);

    $resp    = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($resp, true);
    return ['ok' => ($httpCode >= 200 && $httpCode < 300), 'response' => $decoded ?? $resp];
}

/**
 * Notifica al dueño que un cliente pidió fiado.
 *
 * @param  string $cliente   Nombre del cliente
 * @param  array  $items     Array de ['nombre'=>..., 'cantidad'=>..., 'precio'=>..., 'subtotal'=>...]
 * @param  float  $total     Total de la venta
 * @param  float  $adeudo_nuevo  Adeudo total del cliente después de esta venta
 * @return array
 */
function wspNotificarFiado(string $cliente, array $items, float $total, float $adeudo_nuevo): array {
    $fecha = date('d/m/Y H:i');
    $lineas = '';
    foreach ($items as $it) {
        $lineas .= "\n  • {$it['nombre']} x{$it['cantidad']} = $" . number_format($it['subtotal'], 2);
    }

    $msg = "🛒 *RominaStore — Venta a Crédito*\n"
         . "━━━━━━━━━━━━━━━━━━━━\n"
         . "👤 Cliente: *{$cliente}*\n"
         . "📅 Fecha: {$fecha}\n"
         . "━━━━━━━━━━━━━━━━━━━━\n"
         . "*Productos:*{$lineas}\n"
         . "━━━━━━━━━━━━━━━━━━━━\n"
         . "💵 Esta venta: *$" . number_format($total, 2) . "*\n"
         . "📋 Adeudo total: *$" . number_format($adeudo_nuevo, 2) . "*";

    return wspEnviar($msg);
}

/**
 * Notifica al dueño que un cliente realizó un abono.
 *
 * @param  string $cliente      Nombre del cliente
 * @param  float  $abono        Monto abonado
 * @param  float  $adeudo_resta Adeudo restante
 * @return array
 */
function wspNotificarAbono(string $cliente, float $abono, float $adeudo_resta): array {
    $fecha = date('d/m/Y H:i');
    $estado = $adeudo_resta <= 0 ? '✅ ¡Cuenta saldada!' : "📋 Restante: *$" . number_format($adeudo_resta, 2) . "*";

    $msg = "💳 *RominaStore — Abono Recibido*\n"
         . "━━━━━━━━━━━━━━━━━━━━\n"
         . "👤 Cliente: *{$cliente}*\n"
         . "📅 Fecha: {$fecha}\n"
         . "━━━━━━━━━━━━━━━━━━━━\n"
         . "💵 Abono: *$" . number_format($abono, 2) . "*\n"
         . "{$estado}";

    return wspEnviar($msg);
}