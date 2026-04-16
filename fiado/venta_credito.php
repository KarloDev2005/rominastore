<?php
require_once '../includes/config.php';
requerirAutenticacion();

// Inicializar carrito (usaremos el mismo formato que en ventas, pero separado por sesión)
if (!isset($_SESSION['carrito_credito'])) {
    $_SESSION['carrito_credito'] = [];
}
$cliente_id = isset($_SESSION['cliente_credito']) ? $_SESSION['cliente_credito'] : 0;
$cliente_nombre = '';
if ($cliente_id) {
    $stmt = $conn->prepare("SELECT nombre FROM clientes WHERE id_cliente = ?");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $stmt->bind_result($cliente_nombre);
    $stmt->fetch();
    $stmt->close();
}

// Procesar agregar producto
$error = '';
$exito = '';
if (isset($_POST['agregar'])) {
    $id = (int)$_POST['id_producto'];
    $cantidad = (int)$_POST['cantidad'];
    if ($id > 0 && $cantidad > 0) {
        $stmt = $conn->prepare("SELECT id_producto, nombre, precio, stock FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($producto = $result->fetch_assoc()) {
            if ($cantidad > $producto['stock']) {
                $error = "No hay suficiente stock de {$producto['nombre']}. Disponible: {$producto['stock']}";
            } else {
                if (isset($_SESSION['carrito_credito'][$id])) {
                    $nueva_cant = $_SESSION['carrito_credito'][$id]['cantidad'] + $cantidad;
                    if ($nueva_cant > $producto['stock']) {
                        $error = "Stock insuficiente. Máximo: {$producto['stock']}";
                    } else {
                        $_SESSION['carrito_credito'][$id]['cantidad'] = $nueva_cant;
                        $_SESSION['carrito_credito'][$id]['precio'] = $producto['precio'];
                        $exito = "Cantidad actualizada.";
                    }
                } else {
                    $_SESSION['carrito_credito'][$id] = [
                        'id' => $producto['id_producto'],
                        'nombre' => $producto['nombre'],
                        'precio' => $producto['precio'],
                        'cantidad' => $cantidad
                    ];
                    $exito = "Producto agregado.";
                }
            }
        } else {
            $error = "Producto no encontrado.";
        }
    } else {
        $error = "Cantidad inválida.";
    }
}

// Eliminar producto
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if (isset($_SESSION['carrito_credito'][$id])) {
        unset($_SESSION['carrito_credito'][$id]);
        $exito = "Producto eliminado.";
    }
    header('Location: venta_credito.php');
    exit();
}

// Vaciar carrito
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito_credito'] = [];
    header('Location: venta_credito.php');
    exit();
}

// Seleccionar cliente
if (isset($_GET['cliente'])) {
    $cliente_id = (int)$_GET['cliente'];
    if ($cliente_id > 0) {
        $_SESSION['cliente_credito'] = $cliente_id;
        header('Location: venta_credito.php');
        exit();
    }
}

// Cambiar cliente (botón)
if (isset($_GET['cambiar_cliente'])) {
    unset($_SESSION['cliente_credito']);
    header('Location: venta_credito.php');
    exit();
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito_credito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>RominaStore - Venta a Crédito</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        h2 { text-align: center; }
        .cliente-info { background: #e7f3fe; padding: 10px; border-left: 5px solid #2196F3; margin-bottom: 20px; }
        .flex { display: flex; gap: 20px; flex-wrap: wrap; }
        .productos { flex: 2; }
        .carrito { flex: 1; background: #f9f9f9; padding: 15px; border-radius: 8px; }
        .buscar { margin-bottom: 20px; }
        #buscarInput { width: 100%; padding: 10px; font-size: 16px; }
        #resultados { margin-top: 20px; }
        .producto-item { border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center; }
        .btn-agregar { background: #5cb85c; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .btn-eliminar { background: #d9534f; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .btn-finalizar { background: #337ab7; padding: 10px 20px; font-size: 16px; margin-top: 10px; display: block; width: 100%; text-align: center; text-decoration: none; color: white; border-radius: 4px; }
        .btn-vaciar { background: #f0ad4e; display: inline-block; margin-bottom: 10px; padding: 5px 10px; text-decoration: none; color: white; border-radius: 4px; }
        .total { font-size: 20px; font-weight: bold; margin-top: 10px; text-align: right; }
        .error { color: red; background: #f8d7da; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .exito { color: green; background: #d4edda; padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .carrito-item { margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .btn-cambiar { background: #f0ad4e; padding: 5px 10px; text-decoration: none; color: white; border-radius: 4px; margin-left: 10px; }
    </style>
    <script>
        function buscarProductos() {
            let query = document.getElementById('buscarInput').value;
            if (query.length < 2) {
                document.getElementById('resultados').innerHTML = '';
                return;
            }
            fetch('../ventas/buscar_productos.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    data.forEach(p => {
                        html += `<div class="producto-item">
                                    <span>${p.nombre} - $${p.precio} (Stock: ${p.stock})</span>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_producto" value="${p.id_producto}">
                                        <input type="number" name="cantidad" value="1" min="1" max="${p.stock}" style="width:60px;">
                                        <button type="submit" name="agregar" class="btn-agregar">Agregar</button>
                                    </form>
                                 </div>`;
                    });
                    document.getElementById('resultados').innerHTML = html;
                });
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Venta a Crédito (Fiado)</h2>
        <div class="cliente-info">
            <?php if ($cliente_id): ?>
                <strong>Cliente seleccionado:</strong> <?php echo htmlspecialchars($cliente_nombre); ?>
                <a href="venta_credito.php?cambiar_cliente=1" class="btn-cambiar">Cambiar cliente</a>
            <?php else: ?>
                <strong>No hay cliente seleccionado.</strong> <a href="../clientes/listar.php?select=1" class="btn-cambiar">Seleccionar cliente</a>
            <?php endif; ?>
        </div>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($exito): ?><div class="exito"><?php echo $exito; ?></div><?php endif; ?>
        <div class="flex">
            <div class="productos">
                <div class="buscar">
                    <input type="text" id="buscarInput" placeholder="Buscar producto por nombre..." onkeyup="buscarProductos()">
                </div>
                <div id="resultados"></div>
                <h3>Productos Destacados</h3>
                <?php
                $sql = "SELECT id_producto, nombre, precio, stock FROM productos WHERE stock > 0 ORDER BY nombre LIMIT 20";
                $result = $conn->query($sql);
                while ($p = $result->fetch_assoc()): ?>
                <div class="producto-item">
                    <span><?php echo htmlspecialchars($p['nombre']); ?> - $<?php echo number_format($p['precio'],2); ?> (Stock: <?php echo $p['stock']; ?>)</span>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="id_producto" value="<?php echo $p['id_producto']; ?>">
                        <input type="number" name="cantidad" value="1" min="1" max="<?php echo $p['stock']; ?>" style="width:60px;">
                        <button type="submit" name="agregar" class="btn-agregar">Agregar</button>
                    </form>
                </div>
                <?php endwhile; ?>
            </div>
            <div class="carrito">
                <h3>Carrito de Compra</h3>
                <?php if (!empty($_SESSION['carrito_credito'])): ?>
                    <a href="venta_credito.php?vaciar=1" class="btn-vaciar">Vaciar carrito</a>
                    <?php foreach ($_SESSION['carrito_credito'] as $id => $item): ?>
                        <div class="carrito-item">
                            <strong><?php echo htmlspecialchars($item['nombre']); ?></strong><br>
                            Cantidad: <?php echo $item['cantidad']; ?><br>
                            Precio unitario: $<?php echo number_format($item['precio'],2); ?><br>
                            Subtotal: $<?php echo number_format($item['precio'] * $item['cantidad'],2); ?><br>
                            <a href="venta_credito.php?eliminar=<?php echo $id; ?>" class="btn-eliminar" style="font-size:12px;">Eliminar</a>
                        </div>
                    <?php endforeach; ?>
                    <div class="total">Total: $<?php echo number_format($total,2); ?></div>
                    <?php if ($cliente_id): ?>
                        <a href="procesar_credito.php" class="btn-finalizar">Registrar Venta a Crédito</a>
                    <?php else: ?>
                        <p style="color:red;">Selecciona un cliente para continuar.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No hay productos en el carrito.</p>
                <?php endif; ?>
                <a href="../dashboard.php" style="display:block; margin-top:20px;">← Volver al Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>