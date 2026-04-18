<?php
/* productos/listar.php — Lista con imágenes, thumbnails y opciones */
require_once '../includes/config.php';
requerirAutenticacion();

$buscar = limpiar($_GET['buscar'] ?? '');

/* Eliminar imagen de producto */
if (isset($_GET['del_img'])) {
    $pid = (int)$_GET['del_img'];
    $s = $conn->prepare("SELECT imagen FROM productos WHERE id_producto=?");
    $s->bind_param("i",$pid); $s->execute();
    $row = $s->get_result()->fetch_assoc();
    if ($row && $row['imagen']) {
        eliminarImagenProducto($row['imagen']);
        $s2 = $conn->prepare("UPDATE productos SET imagen=NULL WHERE id_producto=?");
        $s2->bind_param("i",$pid); $s2->execute();
        flashSet('exito','Imagen eliminada correctamente.');
    }
    header('Location: listar.php'); exit;
}

$sql = "SELECT * FROM productos";
if ($buscar) {
    $like = '%' . $conn->real_escape_string($buscar) . '%';
    $sql .= " WHERE nombre LIKE '$like'";
}
$sql .= " ORDER BY nombre ASC";
$resultado = $conn->query($sql);

layoutStart('Productos', 'productos', [['label'=>'Productos']]);
?>

<style>
.prod-img-cell{text-align:center}
.img-actions{display:flex;gap:.3rem;margin-top:.3rem;justify-content:center;flex-wrap:wrap}
.prod-preview{display:flex;align-items:center;gap:.75rem}
.prod-preview-info{display:flex;flex-direction:column}
.prod-preview-nombre{font-weight:700;color:var(--g900);font-size:.85rem}
.prod-preview-stock{font-size:.72rem;color:var(--g400)}
</style>

<div class="page-head fade-up">
  <div class="page-title"><i class="fa-solid fa-box"></i> Productos</div>
  <div class="page-subtitle">Catálogo de productos con gestión de imágenes</div>
</div>

<?=flashHtml()?>

<div style="display:flex;gap:.75rem;margin-bottom:1.1rem;flex-wrap:wrap;align-items:flex-end" class="fade-up delay-1">
  <form method="GET" style="display:contents">
    <div class="form-group" style="margin:0;flex:1;min-width:180px">
      <label class="form-label">Buscar producto</label>
      <input type="text" name="buscar" class="form-control" placeholder="Nombre…" value="<?=e($buscar)?>">
    </div>
    <button type="submit" class="btn btn-morado"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
    <?php if($buscar): ?><a href="listar.php" class="btn btn-gris"><i class="fa-solid fa-xmark"></i> Limpiar</a><?php endif ?>
  </form>
  <a href="agregar.php" class="btn btn-verde" style="margin-left:auto">
    <i class="fa-solid fa-plus"></i> Agregar Producto
  </a>
</div>

<div class="card fade-up delay-2">
  <div class="tabla-wrap">
    <table class="tabla">
      <thead>
        <tr>
          <th style="width:100px;text-align:center">Imagen</th>
          <th>Producto</th>
          <th>Precio</th>
          <th style="text-align:center">Stock</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if($resultado->num_rows===0): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="ei">📦</div><p>No se encontraron productos.</p></div></td></tr>
        <?php else: ?>
          <?php while($p=$resultado->fetch_assoc()):
            $stockCls = $p['stock']==0 ? 'badge-rojo' : ($p['stock']<=5 ? 'badge-naranja' : 'badge-verde');
            $stockTxt = $p['stock']==0 ? 'Agotado'   : ($p['stock']<=5 ? 'Stock bajo'   : 'OK');
          ?>
          <tr>
            <td class="prod-img-cell">
              <?=thumbProducto($p['imagen'], $p['nombre'], 52)?>
              <div class="img-actions">
                <a href="editar.php?id=<?=$p['id_producto']?>#img-section"
                   class="btn btn-sm btn-morado" title="Cambiar imagen">
                  <i class="fa-solid fa-camera"></i>
                </a>
                <?php if($p['imagen']): ?>
                <a href="listar.php?del_img=<?=$p['id_producto']?>"
                   class="btn btn-sm btn-rojo"
                   title="Eliminar imagen"
                   onclick="return confirm('¿Eliminar imagen de este producto?')">
                  <i class="fa-solid fa-trash"></i>
                </a>
                <?php endif ?>
              </div>
            </td>
            <td>
              <div class="prod-preview">
                <div class="prod-preview-info">
                  <span class="prod-preview-nombre"><?=e($p['nombre'])?></span>
                  <span class="prod-preview-stock">ID #<?=$p['id_producto']?></span>
                </div>
              </div>
            </td>
            <td class="num dinero-verde"><?=dinero($p['precio'])?></td>
            <td style="text-align:center" class="num"><?=$p['stock']?></td>
            <td><span class="badge <?=$stockCls?>"><?=$stockTxt?></span></td>
            <td>
              <div style="display:flex;gap:.3rem;flex-wrap:wrap">
                <a href="editar.php?id=<?=$p['id_producto']?>" class="btn btn-sm btn-naranja">
                  <i class="fa-solid fa-pen"></i> Editar
                </a>
                <a href="eliminar.php?id=<?=$p['id_producto']?>"
                   class="btn btn-sm btn-rojo"
                   onclick="return confirm('¿Eliminar «<?=e($p['nombre'])?>»?')">
                  <i class="fa-solid fa-trash"></i>
                </a>
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