<?php
require_once '../includes/config.php';
requerirAutenticacion();

// Filtros opcionales
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

$sql = "SELECT v.id_venta, v.fecha, v.total, 
               CASE WHEN v.id_cliente IS NULL THEN 'CONTADO' ELSE c.nombre END as cliente,
               u.nombre as usuario
        FROM ventas v
        LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
        JOIN usuarios u ON v.id_usuario = u.id_usuario
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        ORDER BY v.fecha DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$resultado = $stmt->get_result();

// Calcular total general
$total_general = 0;
while ($row = $resultado->fetch_assoc()) {
    $total_general += $row['total'];
}
$resultado->data_seek(0); // reiniciar puntero para mostrar tabla
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RominaStore - Reporte de Ventas</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; }
        form { margin-bottom: 20px; }
        input, button { padding: 5px; margin: 5px; }
        button { background: #5cb85c; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #5cb85c; color: white; }
        .total { font-weight: bold; margin-top: 10px; text-align: right; }
        .btn-volver { background: #337ab7; display: inline-block; margin-top: 20px; padding: 8px 15px; text-decoration: none; color: white; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reporte de Ventas</h2>
        <form method="GET">
            <label>Desde:</label>
            <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
            <label>Hasta:</label>
            <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
            <button type="submit">Filtrar</button>
        </form>

        <table>
            <thead>
                <tr><th>ID Venta</th><th>Fecha</th><th>Cliente</th><th>Atendió</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php while($v = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $v['id_venta']; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($v['fecha'])); ?></td>
                    <td><?php echo htmlspecialchars($v['cliente']); ?></td>
                    <td><?php echo htmlspecialchars($v['usuario']); ?></td>
                    <td>$<?php echo number_format($v['total'],2); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if($resultado->num_rows == 0): ?>
                <tr><td colspan="5">No hay ventas en el período seleccionado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="total">Total ventas: $<?php echo number_format($total_general,2); ?></div>
        <a href="../dashboard.php" class="btn-volver">Volver al Dashboard</a>
    </div>
</body>
</html>