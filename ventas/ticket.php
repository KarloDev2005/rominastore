<?php
require_once '../includes/config.php';
requerirAutenticacion();

if (!isset($_SESSION['ultima_venta'])) {
    header('Location: nueva_venta.php');
    exit();
}

$id_venta = $_SESSION['ultima_venta'];
unset($_SESSION['ultima_venta']);

$stmt = $conn->prepare("SELECT v.*, u.nombre as usuario FROM ventas v JOIN usuarios u ON v.id_usuario = u.id_usuario WHERE v.id_venta = ?");
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();

$stmt2 = $conn->prepare("SELECT dv.*, p.nombre FROM detalle_venta dv JOIN productos p ON dv.id_producto = p.id_producto WHERE dv.id_venta = ?");
$stmt2->bind_param("i", $id_venta);
$stmt2->execute();
$detalles = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Venta</title>
    <style>
        body { font-family: monospace; margin: 0; padding: 20px; }
        .ticket { max-width: 300px; margin: auto; border: 1px dashed #000; padding: 10px; }
        .centro { text-align: center; }
        hr { margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; }
        .total { font-weight: bold; font-size: 1.2em; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="centro">
            <h2>RominaStore</h2>
            <p>Abarrotes Romina</p>
            <p>Fecha: <?php echo date('d/m/Y H:i:s', strtotime($venta['fecha'])); ?></p>
            <p>Ticket #<?php echo $venta['id_venta']; ?></p>
            <p>Atendió: <?php echo htmlspecialchars($venta['usuario']); ?></p>
        </div>
        <hr>
         <table>
            <thead><tr><th>Cant</th><th>Producto</th><th>Precio</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php while($det = $detalles->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $det['cantidad']; ?></td>
                    <td><?php echo htmlspecialchars($det['nombre']); ?></td>
                    <td>$<?php echo number_format($det['precio_unitario'],2); ?></td>
                    <td>$<?php echo number_format($det['subtotal'],2); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <hr>
        <div class="centro total">TOTAL: $<?php echo number_format($venta['total'],2); ?></div>
        <hr>
        <div class="centro">¡Gracias por su compra!</div>
    </div>
    <div class="no-print centro">
        <button onclick="window.print();">Imprimir Ticket</button>
        <br><br>
        <a href="nueva_venta.php">Nueva Venta</a> |
        <a href="../dashboard.php">Dashboard</a>
    </div>
</body>
</html>