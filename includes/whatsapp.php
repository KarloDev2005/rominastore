<?php

define('WSP_PHONE_ID',  '1129069736946295');
define('WSP_TOKEN',     'EAAecS3E8AysBRSdSX8OZCZA8k734dn2sFikZAazSqDC0pt68gvhe8DgTj4W7U5sizpHcCr3NEoFJcmX273234jNDMqPuUvIgfq666Ykf9wK7yIwzrcqWpFXPzWPlnZBtsG9mSGBvY9Wf4ZA4lVMgEEIkFaqGqEO3wqUqfNLvqa1C6fJYyNojpHKp9soKXyz0xbqU5pjvQOZBFslao9SdmpELdnSziyqe5D94393WBlQmWAxfFB8cFngHET9KGXnPZAixLof4bLUw01WQIK8g7FNoQZDZD');
define('WSP_API_VER',   'v25.0');
define('WSP_DESTINO',   '522871246175');  /* +52 287 124 6175 */
define('WSP_PLANTILLA', 'notificacion_fiado_interno');
define('WSP_IDIOMA',    'es_MX');

/**
 * Envía un mensaje usando plantilla aprobada de Meta.
 *
 * @param  array  $parametros  Lista de strings para {{1}}..{{N}}
 * @return array               ['ok'=>bool, 'code'=>int, 'body'=>array]
 */
function wspEnviarPlantilla(array $parametros): array {
    $url = 'https://graph.facebook.com/' . WSP_API_VER . '/' . WSP_PHONE_ID . '/messages';

    /* Construir componentes del body */
    $components = [];
    if (!empty($parametros)) {
        $params_formateados = [];
        foreach ($parametros as $valor) {
            $params_formateados[] = [
                'type' => 'text',
                'text' => (string)$valor,
            ];
        }
        $components[] = [
            'type'       => 'body',
            'parameters' => $params_formateados,
        ];
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => WSP_DESTINO,
        'type'              => 'template',
        'template'          => [
            'name'       => WSP_PLANTILLA,
            'language'   => ['code' => WSP_IDIOMA],
            'components' => $components,
        ],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WSP_TOKEN,
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $respuesta   = curl_exec($ch);
    $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error  = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log('[WhatsApp] cURL error: ' . $curl_error);
        return ['ok' => false, 'code' => 0, 'body' => ['error' => $curl_error]];
    }

    $decoded = json_decode($respuesta, true) ?? [];
    $ok      = ($codigo_http >= 200 && $codigo_http < 300);

    if (!$ok) {
        error_log('[WhatsApp] HTTP ' . $codigo_http . ' → ' . $respuesta);
    }

    return ['ok' => $ok, 'code' => $codigo_http, 'body' => $decoded];
}

/**
 * Notifica al dueño: "X pidió fiado por estos productos"
 *
 * Parámetros de la plantilla notificacion_fiado_interno:
 *   {{1}} Nombre cliente
 *   {{2}} Fecha
 *   {{3}} Productos (una línea, separados por " | ")
 *   {{4}} Total esta venta
 *   {{5}} Adeudo total del cliente
 *
 * @param  string $cliente      Nombre del cliente
 * @param  array  $items        [['nombre'=>..., 'cantidad'=>..., 'subtotal'=>...], ...]
 * @param  float  $total_venta  Total de esta venta
 * @param  float  $adeudo_nuevo Adeudo total después de esta compra
 * @return array
 */
function wspNotificarFiado(string $cliente, array $items, float $total_venta, float $adeudo_nuevo): array {
    /* Formatear productos en UNA sola línea (límite ~160 chars para WhatsApp) */
    $lineas = [];
    foreach ($items as $it) {
        $lineas[] = $it['nombre'] . ' x' . $it['cantidad'] . ' = $' . number_format($it['subtotal'], 2);
    }
    /* Si es muy largo, resumir */
    $productos_str = implode(' | ', $lineas);
    if (mb_strlen($productos_str) > 200) {
        $productos_str = mb_substr($productos_str, 0, 197) . '...';
    }

    return wspEnviarPlantilla([
        $cliente,                                /* {{1}} */
        date('d/m/Y H:i'),                       /* {{2}} */
        $productos_str,                          /* {{3}} */
        '$' . number_format($total_venta, 2),    /* {{4}} */
        '$' . number_format($adeudo_nuevo, 2),   /* {{5}} */
    ]);
}

/**
 * Notifica al dueño que un cliente realizó un abono.
 * Usa la misma plantilla con parámetros adaptados.
 *
 * {{1}} Cliente
 * {{2}} Fecha
 * {{3}} "Abono realizado"
 * {{4}} Monto del abono
 * {{5}} Adeudo restante
 */
function wspNotificarAbono(string $cliente, float $abono, float $adeudo_resta): array {
    return wspEnviarPlantilla([
        $cliente,
        date('d/m/Y H:i'),
        'Abono realizado',
        '$' . number_format($abono, 2),
        '$' . number_format($adeudo_resta, 2),
    ]);
}