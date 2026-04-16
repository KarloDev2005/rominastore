<?php
require_once '../includes/config.php';
requerirAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0 && $id != $_SESSION['usuario_id']) {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header('Location: listar.php');
exit();
?>