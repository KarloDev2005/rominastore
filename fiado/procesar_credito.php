<?php

require_once '../includes/config.php';
require_once '../includes/whatsapp.php';
requerirAutenticacion();

if (empty($_SESSION['carrito_credito']) || empty($_SESSION['cliente_credito'])) {
    header('Location: venta_credito.php'); exit;
}

$cliente_id = (int)$_SESSION['cliente_credito'];
$total = 0.0;
foreach ($_SESSION['carrito_credito'] as $item)
    $total += round($item['precio'] * $item['cantidad'], 2);

$conn->begin_transaction();
try {
    /* ── Re-verificar stock ── */
    foreach ($_SESSION['carrito_credito'] as $item) {
        $s = $conn->prepare("SELECT stock, nombre FROM productos WHERE id_producto = ? FOR UPDATE");
        $s->bind_param("i", $item['id']); $s->execute();
        $p = $s->get_result()->fetch_assoc();
        if (!$p) throw new Exception("Producto ID {$item['id']} no encontrado.");
        if ($item['cantidad'] > $p['stock'])
            throw new Exception("Stock insuficiente para «{$p['nombre']}» (disponible: {$p['stock']}).");
    }

    /* ── Insertar venta ── */
    $sv = $conn->prepare("INSERT INTO ventas(total,id_cliente,id_usuario) VALUES(?,?,?)");
    $sv->bind_param("dii", $total, $cliente_id, $_SESSION['usuario_id']);
    $sv->execute();
    $id_venta = $conn->insert_id;

    /* ── Detalle + stock ── */
    $sd = $conn->prepare("INSERT INTO detalle_venta(id_venta,id_producto,cantidad,precio_unitario,subtotal) VALUES(?,?,?,?,?)");
    $ss = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
    foreach ($_SESSION['carrito_credito'] as $item) {
        $sub = round($item['precio'] * $item['cantidad'], 2);
        $sd->bind_param("iiidd", $id_venta, $item['id'], $item['cantidad'], $item['precio'], $sub);
        $sd->execute();
        $ss->bind_param("ii", $item['cantidad'], $item['id']);
        $ss->execute();
    }

    /* ── Actualizar adeudo ── */
    $sa = $conn->prepare("UPDATE clientes SET adeudo = adeudo + ? WHERE id_cliente = ?");
    $sa->bind_param("di", $total, $cliente_id);
    $sa->execute();

    /* ── Obtener datos del cliente ── */
    $sc = $conn->prepare("SELECT nombre, adeudo FROM clientes WHERE id_cliente = ?");
    $sc->bind_param("i", $cliente_id); $sc->execute();
    $cliente = $sc->get_result()->fetch_assoc();

    $conn->commit();

    /* ── Preparar items para WhatsApp ── */
    $items_wsp = [];
    foreach ($_SESSION['carrito_credito'] as $item) {
        $items_wsp[] = [
            'nombre'   => $item['nombre'],
            'cantidad' => $item['cantidad'],
            'precio'   => $item['precio'],
            'subtotal' => round($item['precio'] * $item['cantidad'], 2),
        ];
    }

    /* ── Enviar notificación WhatsApp (plantilla) ── */
    $wsp_result = wspNotificarFiado(
        $cliente['nombre'],
        $items_wsp,
        $total,
        $cliente['adeudo']
    );

    /* ── Limpiar sesión ── */
    $_SESSION['carrito_credito'] = [];
    unset($_SESSION['cliente_credito']);

    /* Mensaje de éxito según resultado de WhatsApp */
    if ($wsp_result['ok']) {
        flashSet('exito', "Venta a crédito de " . dinero($total) . " registrada. ✅ Notificación WhatsApp enviada.");
    } else {
        $wsp_error = $wsp_result['body']['error']['message'] ?? ('HTTP ' . $wsp_result['code']);
        flashSet('exito', "Venta de " . dinero($total) . " registrada. ⚠️ WhatsApp no pudo enviarse: " . $wsp_error);
    }

    header('Location: consultar_adeudo.php?cliente=' . $cliente_id); exit;

} catch (Exception $e) {
    $conn->rollback();
    flashSet('error', 'Error al procesar la venta: ' . $e->getMessage());
    header('Location: venta_credito.php'); exit;
}