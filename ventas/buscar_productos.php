<?php
require_once '../includes/config.php';
requerirAutenticacion();

$q = isset($_GET['q']) ? $_GET['q'] : '';
$productos = [];
if (strlen($q) >= 2) {
    $like = "%$q%";
    $stmt = $conn->prepare("SELECT id_producto, nombre, precio, stock FROM productos WHERE nombre LIKE ? LIMIT 20");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}
header('Content-Type: application/json');
echo json_encode($productos);
?>