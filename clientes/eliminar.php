<?php
require_once '../includes/config.php';
requerirAutenticacion();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    // Eliminar cliente (las ventas y abonos quedarán con id_cliente = NULL por ON DELETE SET NULL)
    $sql = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header('Location: listar.php');
exit();
?>