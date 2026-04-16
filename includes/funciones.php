<?php
// Redirigir a una página
function redirigir($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Verificar si el usuario ha iniciado sesión
function sesionIniciada() {
    return isset($_SESSION['usuario_id']);
}

// Verificar si el usuario tiene un rol específico
function tieneRol($rol) {
    return isset($_SESSION['rol']) && $_SESSION['rol'] == $rol;
}

// Requerir autenticación (si no hay sesión, redirige al login)
function requerirAutenticacion() {
    if (!sesionIniciada()) {
        redirigir('index.php');
    }
}

// Requerir rol de administrador
function requerirAdmin() {
    requerirAutenticacion();
    if (!tieneRol('admin')) {
        redirigir('dashboard.php'); // o mostrar error
    }
}

// Limpiar datos de entrada
function limpiar($dato) {
    global $conn;
    return $conn->real_escape_string(trim($dato));
}

// Obtener el nombre del usuario actual
function nombreUsuario() {
    return $_SESSION['usuario_nombre'] ?? 'Invitado';
}
?>