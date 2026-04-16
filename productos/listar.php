<?php
require_once '../includes/config.php';
requerirAutenticacion();

// Búsqueda/filtro
$buscar = limpiar($_GET['buscar'] ?? '');
$sql = "SELECT * FROM productos";
if ($buscar) {
    $like = '%' . $conn->real_escape_string($buscar) . '%';
    $sql .= " WHERE nombre LIKE '$like'";
}
$sql .= " ORDER BY nombre ASC";
$resultado = $conn->query($sql);

// Flash
$flash = flashGet();

layoutStart('Productos', 'productos', [['label' => 'Productos']]);
?>

<style>
.filtros{display:flex;gap:.75rem;margin-bottom:1.1rem;flex-wrap:wrap;align-items:flex-end}
.filtros .form-group{margin:0;flex:1;min-width:180px}
.acciones-tabla{display:flex;gap:.4rem;flex-wrap:wrap}
</style>

<div class="page-head">
  <div class="page-title">📦 Productos</div>
  <div class="page-subtitle">Gestión de catálogo y precios</div>
</div>

<?php if($flash): ?>
  <div class="alerta alerta-<?=$flash['tipo']?>"><?=e($flash['msg'])?></div>
<?php endif ?>

<div class="filtros">
  <form method="GET" style="display:contents">
    <div class="form-group">
      <label class="form-label">Buscar producto</label>
      <input type="text" name="buscar" class="form-control"
             placeholder="Nombre del producto…" value="<?=e($buscar)?>">
    </div>
    <button type="submit" class="btn btn-verde">🔍 Buscar</button>
    <?php if($buscar): ?>
      <a href="listar.php" class="btn btn-gris">✕ Limpiar</a>
    <?php endif ?>
  </form>
  <a href="agregar.php" class="btn btn-verde" style="margin-left:auto">+ Agregar Producto</a>
</div>

<div class="card">
  <div class="tabla-wrap">
    <table class="tabla">
      <thead>
        <tr>
          <th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if($resultado->num_rows===0): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="ei">📭</div><p>No se encontraron productos.</p></div></td></tr>
        <?php else: ?>
          <?php while($p=$resultado->fetch_assoc()):
            $stockClass = $p['stock']==0 ? 'badge-rojo' : ($p['stock']<=5 ? 'badge-naranja' : 'badge-verde');
            $stockTxt   = $p['stock']==0 ? 'Agotado' : ($p['stock']<=5 ? 'Stock bajo' : 'OK');
          ?>
          <tr>
            <td class="num" style="color:var(--gris-400);font-size:.75rem"><?=$p['id_producto']?></td>
            <td><strong><?=e($p['nombre'])?></strong></td>
            <td class="num dinero-verde"><?=dinero($p['precio'])?></td>
            <td class="num"><?=$p['stock']?></td>
            <td><span class="badge <?=$stockClass?>"><?=$stockTxt?></span></td>
            <td>
              <div class="acciones-tabla">
                <a href="editar.php?id=<?=$p['id_producto']?>" class="btn btn-sm btn-naranja">✏ Editar</a>
                <a href="eliminar.php?id=<?=$p['id_producto']?>"
                   class="btn btn-sm btn-rojo"
                   onclick="return confirm('¿Eliminar «<?=e($p['nombre'])?>»?')">🗑</a>
              </div>
            </td>
          </tr>
          <?php endwhile ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<?php layoutEnd(); ?>