<?php
require_once '../includes/config.php';
requerirAutenticacion();

$filtro = $_GET['filtro'] ?? 'todos';

$sql = "SELECT id_producto, nombre, precio, stock FROM productos";
if ($filtro === 'bajo')    $sql .= " WHERE stock > 0 AND stock <= 5";
elseif ($filtro === 'agotado') $sql .= " WHERE stock = 0";
elseif ($filtro === 'ok')  $sql .= " WHERE stock > 5";
$sql .= " ORDER BY stock ASC, nombre";
$resultado = $conn->query($sql);

// Conteos
$counts = $conn->query("SELECT
  SUM(CASE WHEN stock=0 THEN 1 ELSE 0 END) agotados,
  SUM(CASE WHEN stock>0 AND stock<=5 THEN 1 ELSE 0 END) bajos,
  SUM(CASE WHEN stock>5 THEN 1 ELSE 0 END) ok,
  COUNT(*) total
  FROM productos")->fetch_assoc();

layoutStart('Inventario', 'inventario', [['label' => 'Inventario']]);
?>

<style>
.inv-filtros{display:flex;gap:.5rem;margin-bottom:1.1rem;flex-wrap:wrap}
.inv-filtro{
  padding:.45rem 1rem;border-radius:8px;border:1.5px solid var(--gris-300);
  background:var(--blanco);font-family:var(--fuente);font-size:.8rem;font-weight:700;
  color:var(--gris-700);cursor:pointer;text-decoration:none;transition:var(--trans);
}
.inv-filtro:hover{border-color:var(--verde);color:var(--verde)}
.inv-filtro.activo{background:var(--verde);color:#fff;border-color:var(--verde)}
.inv-filtro.activo-rojo{background:var(--rojo);color:#fff;border-color:var(--rojo)}
.inv-filtro.activo-naranja{background:var(--naranja);color:#fff;border-color:var(--naranja)}
</style>

<div class="page-head">
  <div class="page-title">🏬 Inventario</div>
  <div class="page-subtitle">Estado actual del stock de productos</div>
</div>

<!-- Métricas -->
<div class="metrics-row">
  <div class="metric-card verde">
    <div class="metric-label">Con stock suficiente</div>
    <div class="metric-valor"><?=(int)$counts['ok']?></div>
    <div class="metric-sub">productos</div>
  </div>
  <div class="metric-card naranja">
    <div class="metric-label">Stock bajo (≤5)</div>
    <div class="metric-valor"><?=(int)$counts['bajos']?></div>
    <div class="metric-sub">requieren reabasto</div>
  </div>
  <div class="metric-card rojo">
    <div class="metric-label">Agotados</div>
    <div class="metric-valor"><?=(int)$counts['agotados']?></div>
    <div class="metric-sub">sin stock</div>
  </div>
  <div class="metric-card azul">
    <div class="metric-label">Total productos</div>
    <div class="metric-valor"><?=(int)$counts['total']?></div>
    <div class="metric-sub">en catálogo</div>
  </div>
</div>

<?php if($counts['agotados']>0||$counts['bajos']>0): ?>
<div class="alerta alerta-aviso">
  ⚠ Atención: <?=(int)$counts['agotados']?> producto(s) agotado(s) y <?=(int)$counts['bajos']?> con stock bajo.
  <a href="../productos/listar.php" style="margin-left:.5rem;color:var(--naranja);font-weight:800;text-decoration:none">Actualizar productos →</a>
</div>
<?php endif ?>

<!-- Filtros -->
<div class="inv-filtros">
  <a href="?filtro=todos"   class="inv-filtro <?=$filtro==='todos'?'activo':''?>">Todos (<?=(int)$counts['total']?>)</a>
  <a href="?filtro=ok"      class="inv-filtro <?=$filtro==='ok'?'activo':''?>">✅ OK (<?=(int)$counts['ok']?>)</a>
  <a href="?filtro=bajo"    class="inv-filtro <?=$filtro==='bajo'?'activo-naranja':''?>">⚠ Bajo (<?=(int)$counts['bajos']?>)</a>
  <a href="?filtro=agotado" class="inv-filtro <?=$filtro==='agotado'?'activo-rojo':''?>">❌ Agotado (<?=(int)$counts['agotados']?>)</a>
</div>

<div class="card">
  <div class="tabla-wrap">
    <table class="tabla">
      <thead>
        <tr><th>Producto</th><th>Precio</th><th style="text-align:center">Stock</th><th>Estado</th><th></th></tr>
      </thead>
      <tbody>
        <?php if($resultado->num_rows===0): ?>
          <tr><td colspan="5"><div class="empty-state"><div class="ei">✅</div><p>No hay productos en esta categoría.</p></div></td></tr>
        <?php else: ?>
          <?php while($p=$resultado->fetch_assoc()):
            if($p['stock']==0){$badge='badge-rojo';$txt='Agotado';}
            elseif($p['stock']<=5){$badge='badge-naranja';$txt='Stock bajo';}
            else{$badge='badge-verde';$txt='OK';}
          ?>
          <tr>
            <td><strong><?=e($p['nombre'])?></strong></td>
            <td class="num dinero-verde"><?=dinero($p['precio'])?></td>
            <td style="text-align:center">
              <span class="num" style="font-size:.95rem"><?=$p['stock']?></span>
            </td>
            <td><span class="badge <?=$badge?>"><?=$txt?></span></td>
            <td>
              <a href="../productos/editar.php?id=<?=$p['id_producto']?>" class="btn btn-sm btn-naranja">✏ Actualizar stock</a>
            </td>
          </tr>
          <?php endwhile ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<?php layoutEnd(); ?>