<?php
require_once '../includes/config.php';
requerirAutenticacion();

$sql = "SELECT id_producto, nombre, precio, stock FROM productos ORDER BY nombre";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RominaStore - Inventario</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #5cb85c; color: white; }
        .stock-bajo { color: red; font-weight: bold; }
        .btn-volver { background: #337ab7; display: inline-block; margin-top: 20px; padding: 8px 15px; text-decoration: none; color: white; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Inventario de Productos</h2>
        <table>
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th></tr>
            </thead>
            <tbody>
                <?php while($p = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $p['id_producto']; ?></td>
                    <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td>$<?php echo number_format($p['precio'],2); ?></td>
                    <td class="<?php echo ($p['stock'] <= 5) ? 'stock-bajo' : ''; ?>"><?php echo $p['stock']; ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if($resultado->num_rows == 0): ?>
                <tr><td colspan="4">No hay productos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="../dashboard.php" class="btn-volver">Volver al Dashboard</a>
    </div>
</body>
</html>