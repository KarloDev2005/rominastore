<?php
require_once '../includes/config.php';
requerirAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: listar.php');
    exit();
}

$stmt = $conn->prepare("SELECT id_usuario, nombre, rol FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
if (!$usuario) {
    header('Location: listar.php');
    exit();
}

$error = '';
$exito = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiar($_POST['nombre']);
    $rol = $_POST['rol'];
    $nueva_contrasena = $_POST['nueva_contrasena'];

    if ($nombre == '') {
        $error = 'El nombre es obligatorio.';
    } else {
        if (!empty($nueva_contrasena)) {
            $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, contrasena=?, rol=? WHERE id_usuario=?");
            $stmt->bind_param("sssi", $nombre, $hash, $rol, $id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, rol=? WHERE id_usuario=?");
            $stmt->bind_param("ssi", $nombre, $rol, $id);
        }
        if ($stmt->execute()) {
            $exito = 'Usuario actualizado correctamente.';
            $usuario['nombre'] = $nombre;
            $usuario['rol'] = $rol;
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
    <title>RominaStore - Editar Usuario</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        input, select, button { width: 100%; padding: 8px; margin: 5px 0; }
        button { background: #f0ad4e; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .exito { color: green; }
        a { display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Usuario</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre de usuario:</label>
            <input type="text" name="nombre" required value="<?php echo htmlspecialchars($usuario['nombre']); ?>">
            <label>Rol:</label>
            <select name="rol">
                <option value="cajero" <?php if($usuario['rol']=='cajero') echo 'selected'; ?>>Cajero</option>
                <option value="admin" <?php if($usuario['rol']=='admin') echo 'selected'; ?>>Administrador</option>
            </select>
            <label>Nueva contraseña (dejar en blanco para mantener actual):</label>
            <input type="password" name="nueva_contrasena">
            <button type="submit">Actualizar Usuario</button>
        </form>
        <a href="listar.php">← Volver a lista de usuarios</a>
    </div>
</body>
</html>