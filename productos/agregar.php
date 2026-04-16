<?php
require_once '../includes/config.php';
requerirAutenticacion();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);

    if ($nombre == '' || $precio <= 0) {
        $error = 'Nombre y precio son obligatorios.';
    } else {
        $sql = "INSERT INTO productos (nombre, precio, stock) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdi", $nombre, $precio, $stock);
        if ($stmt->execute()) {
            $exito = 'Producto agregado correctamente.';
            // Limpiar formulario
            $_POST = array();
        } else {
            $error = 'Error al agregar: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RominaStore - Agregar Producto</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        input, button { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #5cb85c; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .exito { color: green; }
        a { display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Agregar Producto</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre:</label>
            <input type="text" name="nombre" required value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            <label>Precio:</label>
            <input type="number" step="0.01" name="precio" required value="<?php echo isset($_POST['precio']) ? $_POST['precio'] : ''; ?>">
            <label>Stock inicial:</label>
            <input type="number" name="stock" value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : 0; ?>">
            <button type="submit">Guardar Producto</button>
        </form>
        <a href="listar.php">← Volver a lista de productos</a>
    </div>
</body>
</html>