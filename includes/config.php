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

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$projectDir = basename(dirname(__DIR__));
$projectSegment = '/' . $projectDir . '/';

if (strpos($scriptName, $projectSegment) !== false) {
    $basePathUrl = substr($scriptName, 0, strpos($scriptName, $projectSegment) + strlen($projectSegment));
} else {
    $basePathUrl = '/';
}

define('BASE_URL', $scheme . '://' . $host . $basePathUrl);
define('BASE_PATH', rtrim(str_replace('\\', '/', dirname(__DIR__)), '/') . '/');

// Incluir funciones
require_once __DIR__ . '/funciones.php';
?>
