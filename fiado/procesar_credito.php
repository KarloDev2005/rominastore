<?php
require_once '../includes/config.php';
requerirAutenticacion();

if (empty($_SESSION['carrito_credito']) || empty($_SESSION['cliente_credito'])) {
    header('Location: venta_credito.php');
    exit();
}

$cliente_id = $_SESSION['cliente_credito'];
$total = 0;
foreach ($_SESSION['carrito_credito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

$conn->begin_transaction();
try {
    // Insertar venta con cliente
    $stmt_venta = $conn->prepare("INSERT INTO ventas (total, id_cliente, id_usuario) VALUES (?, ?, ?)");
    $stmt_venta->bind_param("dii", $total, $cliente_id, $_SESSION['usuario_id']);
    $stmt_venta->execute();
    $id_venta = $conn->insert_id;

    // Insertar detalles y actualizar stock
    $stmt_detalle = $conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");

    foreach ($_SESSION['carrito_credito'] as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmt_detalle->bind_param("iiidd", $id_venta, $item['id'], $item['cantidad'], $item['precio'], $subtotal);
        $stmt_detalle->execute();

        $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
        $stmt_stock->execute();
    }

    // Actualizar adeudo del cliente
    $stmt_adeudo = $conn->prepare("UPDATE clientes SET adeudo = adeudo + ? WHERE id_cliente = ?");
    $stmt_adeudo->bind_param("di", $total, $cliente_id);
    $stmt_adeudo->execute();

    $conn->commit();

    // Limpiar carrito y cliente seleccionado
    $_SESSION['carrito_credito'] = [];
    $_SESSION['cliente_credito'] = 0;

    $_SESSION['mensaje_credito'] = "Venta a crédito registrada. Total: $" . number_format($total,2);
    header('Location: consultar_adeudo.php?cliente=' . $cliente_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_credito'] = 'Error al procesar la venta a crédito: ' . $e->getMessage();
    header('Location: venta_credito.php');
    exit();
}
?>