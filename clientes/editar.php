<?php
require_once '../includes/config.php';
requerirAutenticacion();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: listar.php');
    exit();
}

// Obtener datos actuales
$sql = "SELECT * FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente = $resultado->fetch_assoc();

if (!$cliente) {
    header('Location: listar.php');
    exit();
}

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $telefono = limpiar($_POST['telefono']);

    if ($nombre == '') {
        $error = 'El nombre es obligatorio.';
    } else {
        $sql_update = "UPDATE clientes SET nombre=?, telefono=? WHERE id_cliente=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $nombre, $telefono, $id);
        if ($stmt_update->execute()) {
            $exito = 'Cliente actualizado correctamente.';
            $cliente['nombre'] = $nombre;
            $cliente['telefono'] = $telefono;
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
    <title>RominaStore - Editar Cliente</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        input, button { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #f0ad4e; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .exito { color: green; }
        .adeudo-info { background: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 4px solid #5cb85c; }
        a { display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Cliente</h2>
        <div class="adeudo-info">
            <strong>Adeudo actual:</strong> $<?php echo number_format($cliente['adeudo'], 2); ?>
            <br><small>(El adeudo se actualiza automáticamente con ventas a crédito y abonos)</small>
        </div>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre:</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($cliente['nombre']); ?>">
            <label>Teléfono:</label>
            <input type="text" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']); ?>">
            <button type="submit">Actualizar Cliente</button>
        </form>
        <a href="listar.php">← Volver a lista de clientes</a>
    </div>
</body>
</html>