<?php
require_once 'includes/config.php';

// Generar hash para la contraseña "admin123"
$hash = password_hash('admin123', PASSWORD_DEFAULT);

// Actualizar la contraseña del usuario Administrador
$sql = "UPDATE usuarios SET contrasena = '$hash' WHERE nombre = 'Administrador'";

if ($conn->query($sql) === TRUE) {
    echo "✅ Contraseña actualizada correctamente.<br>";
    echo "Ahora puedes iniciar sesión con:<br>";
    echo "Usuario: <strong>Administrador</strong><br>";
    echo "Contraseña: <strong>admin123</strong><br>";
} else {
    echo "❌ Error al actualizar: " . $conn->error;
}
?>