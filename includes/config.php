<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rominastore');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', 'http://localhost:8080/rominastore/');
define('BASE_PATH', 'C:/xampp/htdocs/rominastore/');
// ↑ ajusta la ruta real de tu instalación

// Incluir funciones
require_once __DIR__ . '/funciones.php';
?>