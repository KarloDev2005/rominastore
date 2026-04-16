<?php
require_once '../includes/config.php';
requerirAutenticacion();

$select_mode = isset($_GET['select']) && $_GET['select'] == 1;

$sql = "SELECT * FROM clientes ORDER BY nombre ASC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RominaStore - Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #5cb85c; color: white; }
        .btn { display: inline-block; padding: 5px 10px; margin: 2px; text-decoration: none; border-radius: 4px; color: white; }
        .btn-agregar { background-color: #5cb85c; padding: 10px; margin-bottom: 15px; display: inline-block; }
        .btn-editar { background-color: #f0ad4e; }
        .btn-eliminar { background-color: #d9534f; }
        .btn-seleccionar { background-color: #337ab7; }
        .btn-volver { background-color: #337ab7; margin-top: 20px; display: inline-block; }
        .adeudo { font-weight: bold; color: #d9534f; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestión de Clientes</h2>
        <a href="agregar.php" class="btn btn-agregar">+ Agregar Cliente</a>
        <?php if ($select_mode): ?>
            <div style="margin-bottom: 15px;">
                <strong>Modo selección:</strong> Haz clic en "Seleccionar" para elegir un cliente para la venta a crédito.
                <a href="../fiado/venta_credito.php" class="btn btn-editar" style="margin-left: 10px;">Cancelar</a>
            </div>
        <?php endif; ?>
        <table>
            <thead>
                <tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Adeudo</th>
                <?php if ($select_mode): ?><th>Seleccionar</th><?php endif; ?>
                <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($cliente = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $cliente['id_cliente']; ?></td>
                    <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                    <td class="adeudo">$<?php echo number_format($cliente['adeudo'], 2); ?></td>
                    <?php if ($select_mode): ?>
                    <td>
                        <a href="../fiado/venta_credito.php?cliente=<?php echo $cliente['id_cliente']; ?>" class="btn btn-seleccionar">Seleccionar</a>
                    </td>
                    <?php endif; ?>
                    <td>
                        <a href="editar.php?id=<?php echo $cliente['id_cliente']; ?>" class="btn btn-editar">Editar</a>
                        <a href="eliminar.php?id=<?php echo $cliente['id_cliente']; ?>" class="btn btn-eliminar" onclick="return confirm('¿Eliminar este cliente? Se perderá su historial de compras.')">Eliminar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($resultado->num_rows == 0): ?>
                <tr><td colspan="<?php echo $select_mode ? 6 : 5; ?>">No hay clientes registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="../dashboard.php" class="btn btn-volver">Volver al Dashboard</a>
    </div>
</body>
</html>