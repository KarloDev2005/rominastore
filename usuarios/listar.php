<?php
require_once '../includes/config.php';
requerirAdmin(); // solo admin

$sql = "SELECT id_usuario, nombre, rol, fecha_creacion FROM usuarios ORDER BY id_usuario";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RominaStore - Usuarios</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #5cb85c; color: white; }
        .btn { display: inline-block; padding: 5px 10px; margin: 2px; text-decoration: none; border-radius: 4px; color: white; }
        .btn-agregar { background: #5cb85c; padding: 10px; margin-bottom: 15px; display: inline-block; }
        .btn-editar { background: #f0ad4e; }
        .btn-eliminar { background: #d9534f; }
        .btn-volver { background: #337ab7; margin-top: 20px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gestión de Usuarios</h2>
        <a href="agregar.php" class="btn btn-agregar">+ Agregar Usuario</a>
        <table>
            <thead><tr><th>ID</th><th>Nombre</th><th>Rol</th><th>Fecha Registro</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php while($u = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $u['id_usuario']; ?></td>
                    <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                    <td><?php echo ucfirst($u['rol']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($u['fecha_creacion'])); ?></td>
                    <td>
                        <a href="editar.php?id=<?php echo $u['id_usuario']; ?>" class="btn btn-editar">Editar</a>
                        <a href="eliminar.php?id=<?php echo $u['id_usuario']; ?>" class="btn btn-eliminar" onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="../dashboard.php" class="btn btn-volver">Volver al Dashboard</a>
    </div>
</body>
</html>