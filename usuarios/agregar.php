<?php
require_once '../includes/config.php';
requerirAdmin();

$error = '';
$exito = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $contrasena = $_POST['contrasena'];
    $rol = $_POST['rol'];

    if ($nombre == '' || $contrasena == '') {
        $error = 'Nombre y contraseña son obligatorios.';
    } else {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, contrasena, rol) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $hash, $rol);
        if ($stmt->execute()) {
            $exito = 'Usuario creado correctamente.';
            $_POST = array();
        } else {
            $error = 'Error al crear: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RominaStore - Agregar Usuario</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        input, select, button { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #5cb85c; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .exito { color: green; }
        a { display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Agregar Usuario</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre de usuario:</label>
            <input type="text" name="nombre" required value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            <label>Contraseña:</label>
            <input type="password" name="contrasena" required>
            <label>Rol:</label>
            <select name="rol">
                <option value="cajero">Cajero</option>
                <option value="admin">Administrador</option>
            </select>
            <button type="submit">Guardar Usuario</button>
        </form>
        <a href="listar.php">← Volver a lista de usuarios</a>
    </div>
</body>
</html>