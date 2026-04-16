<?php
require_once '../includes/config.php';
requerirAutenticacion();

// Consultar todos los productos
$sql = "SELECT * FROM productos ORDER BY nombre ASC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RominaStore - Productos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #5cb85c;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
        }
        .btn-agregar {
            background-color: #5cb85c;
            padding: 10px;
            margin-bottom: 15px;
            display: inline-block;
        }
        .btn-editar {
            background-color: #f0ad4e;
        }
        .btn-eliminar {
            background-color: #d9534f;
        }
        .btn-volver {
            background-color: #337ab7;
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestión de Productos</h2>
        <a href="agregar.php" class="btn btn-agregar">+ Agregar Producto</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($producto = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $producto['id_producto']; ?></td>
                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                    <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                    <td><?php echo $producto['stock']; ?></td>
                    <td>
                        <a href="editar.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-editar">Editar</a>
                        <a href="eliminar.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-eliminar" onclick="return confirm('¿Eliminar este producto?')">Eliminar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($resultado->num_rows == 0): ?>
                <tr>
                    <td colspan="5">No hay productos registrados.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="../dashboard.php" class="btn btn-volver">Volver al Dashboard</a>
    </div>
</body>
</html>