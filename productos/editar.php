<?php
require_once '../includes/config.php';
requerirAutenticacion();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: listar.php');
    exit();
}

// Obtener datos actuales
$sql = "SELECT * FROM productos WHERE id_producto = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();

if (!$producto) {
    header('Location: listar.php');
    exit();
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);

    if ($nombre == '' || $precio <= 0) {
        $error = 'Nombre y precio son obligatorios.';
    } else {
        $sql_update = "UPDATE productos SET nombre=?, precio=?, stock=? WHERE id_producto=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sdii", $nombre, $precio, $stock, $id);
        if ($stmt_update->execute()) {
            $exito = 'Producto actualizado correctamente.';
            // Actualizar variable local para mostrar nuevos valores
            $producto['nombre'] = $nombre;
            $producto['precio'] = $precio;
            $producto['stock'] = $stock;
        } else {
            $error = 'Error al actualizar: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RominaStore - Editar Producto</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        input, button { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #f0ad4e; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .exito { color: green; }
        a { display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Producto</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre:</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($producto['nombre']); ?>">
            <label>Precio:</label>
            <input type="number" step="0.01" name="precio" required value="<?php echo $producto['precio']; ?>">
            <label>Stock:</label>
            <input type="number" name="stock" value="<?php echo $producto['stock']; ?>">
            <button type="submit">Actualizar Producto</button>
        </form>
        <a href="listar.php">← Volver a lista de productos</a>
    </div>
</body>
</html>