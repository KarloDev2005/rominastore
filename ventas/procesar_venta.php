<?php
require_once '../includes/config.php';
requerirAutenticacion();

if (empty($_SESSION['carrito'])) {
    header('Location: nueva_venta.php');
    exit();
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

$conn->begin_transaction();
try {
    // Insertar venta
    $stmt_venta = $conn->prepare("INSERT INTO ventas (total, id_cliente, id_usuario) VALUES (?, NULL, ?)");
    $stmt_venta->bind_param("di", $total, $_SESSION['usuario_id']);
    $stmt_venta->execute();
    $id_venta = $conn->insert_id;

    $stmt_detalle = $conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");

    foreach ($_SESSION['carrito'] as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $stmt_detalle->bind_param("iiidd", $id_venta, $item['id'], $item['cantidad'], $item['precio'], $subtotal);
        $stmt_detalle->execute();

        $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
        $stmt_stock->execute();
    }

    $conn->commit();
    $_SESSION['ultima_venta'] = $id_venta;
    $_SESSION['carrito'] = [];
    header('Location: ticket.php');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_venta'] = 'Error al procesar la venta: ' . $e->getMessage();
    header('Location: nueva_venta.php');
    exit();
}
?>