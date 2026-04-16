<?php
require_once '../includes/config.php';
requerirAutenticacion();

$select_mode = isset($_GET['select']) && $_GET['select']==1;
$buscar = limpiar($_GET['buscar'] ?? '');

$sql = "SELECT * FROM clientes";
$wheres = [];
if ($buscar) $wheres[] = "nombre LIKE '%" . $conn->real_escape_string($buscar) . "%'";
if ($wheres) $sql .= " WHERE " . implode(' AND ', $wheres);
$sql .= " ORDER BY nombre ASC";
$resultado = $conn->query($sql);

$flash = flashGet();
layoutStart('Clientes', 'clientes', [['label' => 'Clientes']]);
?>

<div class="page-head">
  <div class="page-title">👥 Clientes</div>
  <div class="page-subtitle"><?=$select_mode?'Selecciona un cliente para la venta a crédito':'Administración de clientes con crédito'?></div>
</div>

<?php if($select_mode): ?>
  <div class="alerta alerta-info">
    ℹ Modo selección — haz clic en "Seleccionar" para asignar el cliente a la venta.
    <a href="../fiado/venta_credito.php" style="margin-left:auto;color:var(--azul);font-weight:800;text-decoration:none">✕ Cancelar</a>
  </div>
<?php endif ?>
<?php if($flash): ?><div class="alerta alerta-<?=$flash['tipo']?>"><?=e($flash['msg'])?></div><?php endif ?>

<div style="display:flex;gap:.75rem;margin-bottom:1.1rem;flex-wrap:wrap;align-items:flex-end">
  <form method="GET" style="display:contents">
    <?php if($select_mode): ?><input type="hidden" name="select" value="1"><?php endif ?>
    <div class="form-group" style="margin:0;flex:1;min-width:180px">
      <label class="form-label">Buscar cliente</label>
      <input type="text" name="buscar" class="form-control" placeholder="Nombre…" value="<?=e($buscar)?>">
    </div>
    <button type="submit" class="btn btn-verde">🔍</button>
    <?php if($buscar): ?><a href="listar.php<?=$select_mode?'?select=1':''?>" class="btn btn-gris">✕</a><?php endif ?>
  </form>
  <?php if(!$select_mode): ?>
    <a href="agregar.php" class="btn btn-verde" style="margin-left:auto">+ Agregar Cliente</a>
  <?php endif ?>
</div>

<div class="card">
  <div class="tabla-wrap">
    <table class="tabla">
      <thead>
        <tr>
          <th>Nombre</th><th>Teléfono</th><th>Adeudo</th>
          <?php if($select_mode): ?><th></th><?php else: ?><th>Acciones</th><?php endif ?>
        </tr>
      </thead>
      <tbody>
        <?php if($resultado->num_rows===0): ?>
          <tr><td colspan="4"><div class="empty-state"><div class="ei">👥</div><p>No hay clientes registrados.</p></div></td></tr>
        <?php else: ?>
          <?php while($c=$resultado->fetch_assoc()): ?>
          <tr>
            <td><strong><?=e($c['nombre'])?></strong></td>
            <td><?=e($c['telefono']?:'—')?></td>
            <td>
              <?php if($c['adeudo']>0): ?>
                <span class="badge badge-rojo dinero"><?=dinero($c['adeudo'])?></span>
              <?php else: ?>
                <span class="badge badge-verde">Sin deuda</span>
              <?php endif ?>
            </td>
            <td>
              <?php if($select_mode): ?>
                <a href="../fiado/venta_credito.php?cliente=<?=$c['id_cliente']?>" class="btn btn-sm btn-verde">Seleccionar</a>
              <?php else: ?>
                <div style="display:flex;gap:.3rem;flex-wrap:wrap">
                  <a href="../fiado/consultar_adeudo.php?cliente=<?=$c['id_cliente']?>" class="btn btn-sm btn-azul">💳 Adeudo</a>
                  <a href="editar.php?id=<?=$c['id_cliente']?>" class="btn btn-sm btn-naranja">✏</a>
                  <a href="eliminar.php?id=<?=$c['id_cliente']?>"
                     class="btn btn-sm btn-rojo"
                     onclick="return confirm('¿Eliminar a <?=e($c['nombre'])?>?')">🗑</a>
                </div>
              <?php endif ?>
            </td>
          </tr>
          <?php endwhile ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<?php layoutEnd(); ?>