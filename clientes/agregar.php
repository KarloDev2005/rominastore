<?php
require_once '../includes/config.php';
requerirAutenticacion();

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $telefono = limpiar($_POST['telefono']);

    if ($nombre == '') {
        $error = 'El nombre es obligatorio.';
    } else {
        $sql = "INSERT INTO clientes (nombre, telefono) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nombre, $telefono);
        if ($stmt->execute()) {
            $exito = 'Cliente agregado correctamente.';
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
    <title>RominaStore - Agregar Cliente</title>
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
        <h2>Agregar Cliente</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre:</label>
            <input type="text" name="nombre" required value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            <label>Teléfono:</label>
            <input type="text" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
            <button type="submit">Guardar Cliente</button>
        </form>
        <a href="listar.php">← Volver a lista de clientes</a>
    </div>
</body>
</html>