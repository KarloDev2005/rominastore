<?php
require_once 'includes/config.php';
requerirAutenticacion();

/* ─── Métricas del dashboard ─── */
$hoy = date('Y-m-d');

// Ventas de hoy
$r = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha)='$hoy'");
$hoy_v = $r->fetch_assoc();

// Ventas del mes
$mes_inicio = date('Y-m-01');
$r2 = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha) BETWEEN '$mes_inicio' AND '$hoy'");
$mes_v = $r2->fetch_assoc();

// Clientes con adeudo
$r3 = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(adeudo),0) t FROM clientes WHERE adeudo>0");
$deuda = $r3->fetch_assoc();

// Productos con stock bajo (<=5)
$r4 = $conn->query("SELECT COUNT(*) c FROM productos WHERE stock<=5 AND stock>0");
$stock_bajo = $r4->fetch_assoc()['c'];

// Productos agotados
$r5 = $conn->query("SELECT COUNT(*) c FROM productos WHERE stock=0");
$agotados = $r5->fetch_assoc()['c'];

// Últimas ventas
$ultimas = $conn->query("SELECT v.id_venta,v.fecha,v.total,u.nombre usuario FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario ORDER BY v.fecha DESC LIMIT 5");

layoutStart('Dashboard', '', []);
?>

<style>
.dash-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.4rem}
.dash-card{
  background:var(--blanco);border-radius:14px;padding:1.1rem 1.2rem;
  border:1px solid var(--gris-200);box-shadow:var(--sombra);
  display:flex;align-items:center;gap:1rem;cursor:pointer;
  transition:var(--trans);text-decoration:none;
}
.dash-card:hover{transform:translateY(-3px);box-shadow:var(--sombra-md);border-color:var(--verde)}
.dc-icon{
  width:48px;height:48px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;
}
.dc-icon.verde{background:var(--verde-bg)}
.dc-icon.azul{background:var(--azul-bg)}
.dc-icon.naranja{background:var(--naranja-bg)}
.dc-icon.rojo{background:var(--rojo-bg)}
.dc-body{}
.dc-valor{font-size:1.3rem;font-weight:900;color:var(--gris-900);font-family:var(--mono);line-height:1.1}
.dc-label{font-size:.73rem;font-weight:700;color:var(--gris-400);margin-top:.1rem}

.dash-row{display:grid;grid-template-columns:2fr 1fr;gap:1rem;align-items:start}

.alerta-stock{
  display:flex;align-items:center;gap:.75rem;padding:.8rem 1rem;
  background:var(--naranja-bg);border:1px solid var(--naranja-borde);
  border-radius:10px;margin-bottom:1rem;font-size:.83rem;color:#92400e;font-weight:600;
}

/* Acciones rápidas */
.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:.8rem}
.quick-btn{
  display:flex;flex-direction:column;align-items:center;gap:.3rem;
  padding:.85rem .5rem;background:var(--blanco);border:1.5px solid var(--gris-200);
  border-radius:11px;text-decoration:none;color:var(--gris-700);
  font-size:.75rem;font-weight:700;transition:var(--trans);
  cursor:pointer;
}
.quick-btn:hover{border-color:var(--verde);background:var(--verde-bg);color:var(--verde)}
.quick-btn .qi{font-size:1.3rem}

@media(max-width:700px){.dash-row{grid-template-columns:1fr}}
</style>

<?php if($stock_bajo>0||$agotados>0): ?>
<div class="alerta-stock">
  ⚠️
  <?php if($agotados>0): ?>
    <strong><?=$agotados?> producto<?=$agotados>1?'s':''?> agotado<?=$agotados>1?'s':''?></strong> —
  <?php endif ?>
  <?php if($stock_bajo>0): ?>
    <strong><?=$stock_bajo?></strong> con stock bajo (≤5 unidades).
  <?php endif ?>
  <a href="inventario/consultar.php" style="margin-left:auto;color:var(--naranja);font-weight:800;text-decoration:none">Ver inventario →</a>
</div>
<?php endif ?>

<!-- Métricas -->
<div class="dash-grid">
  <a href="reportes/ventas.php?fecha_inicio=<?=$hoy?>&fecha_fin=<?=$hoy?>" class="dash-card">
    <div class="dc-icon verde">💰</div>
    <div class="dc-body">
      <div class="dc-valor"><?=dinero($hoy_v['t'])?></div>
      <div class="dc-label">Ventas de hoy (<?=(int)$hoy_v['c']?> trans.)</div>
    </div>
  </a>
  <a href="reportes/ventas.php" class="dash-card">
    <div class="dc-icon azul">📅</div>
    <div class="dc-body">
      <div class="dc-valor"><?=dinero($mes_v['t'])?></div>
      <div class="dc-label">Ventas del mes (<?=(int)$mes_v['c']?>)</div>
    </div>
  </a>
  <a href="fiado/consultar_adeudo.php" class="dash-card">
    <div class="dc-icon rojo">💳</div>
    <div class="dc-body">
      <div class="dc-valor"><?=dinero($deuda['t'])?></div>
      <div class="dc-label">Por cobrar (<?=(int)$deuda['c']?> clientes)</div>
    </div>
  </a>
  <a href="inventario/consultar.php" class="dash-card">
    <div class="dc-icon naranja">📦</div>
    <div class="dc-body">
      <div class="dc-valor"><?=$agotados?></div>
      <div class="dc-label">Agotados / <?=$stock_bajo?> con stock bajo</div>
    </div>
  </a>
</div>

<div class="dash-row">

  <!-- Últimas ventas -->
  <div class="card">
    <div class="card-header">
      <h3>Últimas ventas</h3>
      <a href="reportes/ventas.php" class="btn btn-sm btn-gris">Ver todas →</a>
    </div>
    <div class="tabla-wrap">
      <table class="tabla">
        <thead><tr><th>#</th><th>Fecha</th><th>Usuario</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php if($ultimas->num_rows===0): ?>
            <tr><td colspan="4"><div class="empty-state"><p>No hay ventas aún.</p></div></td></tr>
          <?php else: ?>
            <?php while($v=$ultimas->fetch_assoc()): ?>
            <tr>
              <td class="num" style="color:var(--gris-400);font-size:.75rem"><?=str_pad($v['id_venta'],4,'0',STR_PAD_LEFT)?></td>
              <td><?=date('d/m H:i',strtotime($v['fecha']))?></td>
              <td><?=e($v['usuario'])?></td>
              <td style="text-align:right" class="num dinero-verde"><?=dinero($v['total'])?></td>
            </tr>
            <?php endwhile ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Accesos rápidos -->
  <div>
    <div class="card-header" style="background:none;padding:.3rem 0 .6rem"><h3 style="color:var(--gris-500);font-size:.72rem;text-transform:uppercase;letter-spacing:.5px">Acciones rápidas</h3></div>
    <div class="quick-grid">
      <a href="ventas/nueva_venta.php" class="quick-btn"><span class="qi">🛒</span>Nueva Venta</a>
      <a href="fiado/venta_credito.php" class="quick-btn"><span class="qi">💳</span>Venta Fiado</a>
      <a href="productos/agregar.php" class="quick-btn"><span class="qi">➕</span>Agregar Producto</a>
      <a href="clientes/agregar.php" class="quick-btn"><span class="qi">👤</span>Nuevo Cliente</a>
      <a href="fiado/consultar_adeudo.php" class="quick-btn"><span class="qi">📋</span>Cobrar Adeudo</a>
      <a href="reportes/ventas.php" class="quick-btn"><span class="qi">📊</span>Reportes</a>
    </div>
  </div>

</div>

<?php layoutEnd(); ?>