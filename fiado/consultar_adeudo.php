<?php
require_once '../includes/config.php';
requerirAutenticacion();

$cliente_id = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
$cliente = null;
if ($cliente_id) {
    $stmt = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
}

// Procesar abono
$error = '';
$exito = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['abono'])) {
    $monto = (float)$_POST['monto'];
    if ($monto > 0 && $cliente) {
        if ($monto > $cliente['adeudo']) {
            $error = "El abono no puede ser mayor al adeudo actual.";
        } else {
            $conn->begin_transaction();
            try {
                // Registrar abono
                $stmt = $conn->prepare("INSERT INTO abonos (monto, id_cliente) VALUES (?, ?)");
                $stmt->bind_param("di", $monto, $cliente_id);
                $stmt->execute();
                // Reducir adeudo
                $stmt2 = $conn->prepare("UPDATE clientes SET adeudo = adeudo - ? WHERE id_cliente = ?");
                $stmt2->bind_param("di", $monto, $cliente_id);
                $stmt2->execute();
                $conn->commit();
                $exito = "Abono de $" . number_format($monto,2) . " registrado.";
                // Actualizar datos del cliente
                $cliente['adeudo'] -= $monto;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error al registrar abono: " . $e->getMessage();
            }
        }
    } else {
        $error = "Monto inválido.";
    }
}

// Lista de clientes con adeudo > 0
$sql = "SELECT id_cliente, nombre, adeudo FROM clientes WHERE adeudo > 0 ORDER BY nombre";
$clientes_deudores = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RominaStore - Consulta de Adeudos</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #5cb85c; color: white; }
        .btn-ver { background: #337ab7; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; }
        .btn-regresar { background: #f0ad4e; display: inline-block; margin-top: 20px; padding: 10px; text-decoration: none; color: white; border-radius: 4px; }
        .adeudo { font-weight: bold; color: #d9534f; }
        .error { color: red; background: #f8d7da; padding: 10px; margin-bottom: 10px; }
        .exito { color: green; background: #d4edda; padding: 10px; margin-bottom: 10px; }
        .form-abono { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 4px; }
        input, button { padding: 5px; margin: 5px; }
        button { background: #5cb85c; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Consulta de Adeudos</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>

        <h3>Clientes con adeudo</h3>
        <table>
            <thead>
                <tr><th>Cliente</th><th>Adeudo</th><th>Acción</th></tr>
            </thead>
            <tbody>
                <?php if ($clientes_deudores->num_rows == 0): ?>
                    <tr><td colspan="3">No hay clientes con adeudo pendiente.</td></tr>
                <?php else: ?>
                    <?php while ($c = $clientes_deudores->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                            <td class="adeudo">$<?php echo number_format($c['adeudo'],2); ?></td>
                            <td><a href="consultar_adeudo.php?cliente=<?php echo $c['id_cliente']; ?>" class="btn-ver">Ver / Abonar</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($cliente): ?>
            <div class="form-abono">
                <h3>Cliente: <?php echo htmlspecialchars($cliente['nombre']); ?></h3>
                <p><strong>Adeudo actual:</strong> $<?php echo number_format($cliente['adeudo'],2); ?></p>
                <form method="POST">
                    <label>Monto a abonar:</label>
                    <input type="number" step="0.01" name="monto" min="0.01" max="<?php echo $cliente['adeudo']; ?>" required>
                    <button type="submit" name="abono">Registrar Abono</button>
                </form>
            </div>
        <?php endif; ?>

        <a href="../dashboard.php" class="btn-regresar">Volver al Dashboard</a>
    </div>
</body>
</html>