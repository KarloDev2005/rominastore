<?php
require_once '../includes/config.php';
requerirAutenticacion();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $sql = "DELETE FROM productos WHERE id_producto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header('Location: listar.php');
exit();
?>