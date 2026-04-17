<?php
require_once 'includes/config.php';
requerirAutenticacion();

$hoy       = date('Y-m-d');
$mes_ini   = date('Y-m-01');

$rh = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha)='$hoy'");
$hoy_v = $rh->fetch_assoc();

$rm = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha) BETWEEN '$mes_ini' AND '$hoy'");
$mes_v = $rm->fetch_assoc();

$rd = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(adeudo),0) t FROM clientes WHERE adeudo>0");
$deuda = $rd->fetch_assoc();

$rb = $conn->query("SELECT COUNT(*) c FROM productos WHERE stock<=5 AND stock>0");
$stock_bajo = $rb->fetch_assoc()['c'];

$ra = $conn->query("SELECT COUNT(*) c FROM productos WHERE stock=0");
$agotados = $ra->fetch_assoc()['c'];

$ultimas = $conn->query("SELECT v.id_venta,v.fecha,v.total,
    CASE WHEN v.id_cliente IS NULL THEN 'Contado' ELSE c.nombre END cliente,
    u.nombre usuario
    FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario
    LEFT JOIN clientes c ON v.id_cliente=c.id_cliente
    ORDER BY v.fecha DESC LIMIT 6");

layoutStart('Dashboard', '', []);
?>

<style>
/* Banner de bienvenida con el logotipo completo */
.dash-banner {
  background: linear-gradient(135deg, var(--oscuro) 0%, var(--oscuro-2) 60%, var(--morado-h) 100%);
  border-radius: 18px;
  padding: 1.6rem 2rem;
  margin-bottom: 1.4rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  overflow: hidden;
  position: relative;
  box-shadow: var(--sombra-lg);
  animation: fadeUp .4s ease both;
}
.dash-banner::before {
  content: '';
  position: absolute; inset: 0;
  background: url('img/logo_full.jpeg') right center / 280px no-repeat;
  opacity: .06;
  pointer-events: none;
}
.banner-txt h2 {
  font-size: 1.35rem; font-weight: 900; color: #fff; letter-spacing: -.3px;
}
.banner-txt p {
  font-size: .84rem; color: rgba(255,255,255,.55); margin-top: .25rem;
}
.banner-img {
  height: 80px; object-fit: contain;
  opacity: .9;
  filter: drop-shadow(0 4px 16px rgba(196,37,122,.4));
  flex-shrink: 0;
  animation: logoFloat 4s ease-in-out infinite;
}
@keyframes logoFloat {
  0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)}
}
@keyframes fadeUp {
  from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)}
}

/* Alerta de stock */
.alerta-stock {
  display: flex; align-items: center; gap: .75rem;
  padding: .8rem 1.1rem;
  background: var(--naranja-bg);
  border: 1px solid var(--naranja-b);
  border-left: 4px solid var(--naranja);
  border-radius: 11px;
  margin-bottom: 1.2rem;
  font-size: .83rem; color: #92400e; font-weight: 700;
  animation: fadeUp .4s .1s ease both;
}

/* Grid de métricas */
.metrics-row { animation: fadeUp .4s .15s ease both; }

/* Grid de accesos rápidos */
.quick-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: .65rem;
}
.quick-btn {
  display: flex; flex-direction: column; align-items: center; gap: .45rem;
  padding: 1.1rem .75rem;
  background: var(--blanco);
  border: 1.5px solid var(--gris-200);
  border-radius: 13px;
  text-decoration: none;
  color: var(--gris-700);
  font-size: .8rem; font-weight: 800;
  text-align: center; line-height: 1.3;
  transition: var(--trans);
  box-shadow: var(--sombra);
}
.quick-btn:hover {
  border-color: var(--morado);
  color: var(--morado);
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(124,31,160,.18);
}
.quick-btn .qi {
  font-size: 1.6rem;
  display: block;
  transition: transform .2s;
}
.quick-btn:hover .qi { transform: scale(1.15); }

.dash-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.1rem;
  align-items: start;
  animation: fadeUp .4s .25s ease both;
}

@media(max-width:700px){.dash-row{grid-template-columns:1fr}}
</style>

<!-- Banner de bienvenida -->
<div class="dash-banner">
  <div class="banner-txt">
    <h2>¡Bienvenido, <?=e(nombreUsuario())?>! 👋</h2>
    <p><?=date('l j \d\e F \d\e Y')?> · <?=ucfirst(rolActual())?></p>
  </div>
  <img src="img/icono.png" alt="Logo Romina" class="banner-img">
</div>

<?php if($stock_bajo>0||$agotados>0): ?>
<div class="alerta-stock">
  <span style="font-size:1.3rem">⚠️</span>
  <span>
    <?php if($agotados>0): ?><strong><?=$agotados?> producto<?=$agotados>1?'s':''?> agotado<?=$agotados>1?'s':''?></strong><?php endif ?>
    <?php if($stock_bajo>0): ?> · <strong><?=$stock_bajo?></strong> con stock bajo (≤5).<?php endif ?>
  </span>
  <a href="inventario/consultar.php" style="margin-left:auto;color:var(--naranja);font-weight:900;text-decoration:none">Ver inventario →</a>
</div>
<?php endif ?>

<!-- Métricas -->
<div class="metrics-row">
  <a href="reportes/ventas.php?fecha_inicio=<?=$hoy?>&fecha_fin=<?=$hoy?>" class="metric-card morado" style="text-decoration:none;display:block">
    <div class="metric-icon">💰</div>
    <div class="metric-label">Ventas de hoy</div>
    <div class="metric-valor"><?=dinero($hoy_v['t'])?></div>
    <div class="metric-sub"><?=(int)$hoy_v['c']?> transacciones</div>
  </a>
  <a href="reportes/ventas.php" class="metric-card magenta" style="text-decoration:none;display:block">
    <div class="metric-icon">📅</div>
    <div class="metric-label">Ventas del mes</div>
    <div class="metric-valor"><?=dinero($mes_v['t'])?></div>
    <div class="metric-sub"><?=(int)$mes_v['c']?> ventas</div>
  </a>
  <a href="fiado/consultar_adeudo.php" class="metric-card rojo" style="text-decoration:none;display:block">
    <div class="metric-icon">💳</div>
    <div class="metric-label">Por cobrar</div>
    <div class="metric-valor"><?=dinero($deuda['t'])?></div>
    <div class="metric-sub"><?=(int)$deuda['c']?> clientes con adeudo</div>
  </a>
  <a href="inventario/consultar.php?filtro=agotado" class="metric-card naranja" style="text-decoration:none;display:block">
    <div class="metric-icon">📦</div>
    <div class="metric-label">Productos agotados</div>
    <div class="metric-valor"><?=$agotados?></div>
    <div class="metric-sub"><?=$stock_bajo?> con stock bajo</div>
  </a>
</div>

<div class="dash-row">
  <!-- Últimas ventas -->
  <div class="card fade-up fade-up-3">
    <div class="card-header">
      <h3>🕐 Últimas ventas</h3>
      <a href="reportes/ventas.php" class="btn btn-sm btn-gris">Ver todas →</a>
    </div>
    <div class="tabla-wrap">
      <table class="tabla">
        <thead><tr><th>#</th><th>Fecha</th><th>Cliente</th><th>Usuario</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php if($ultimas->num_rows===0): ?>
            <tr><td colspan="5"><div class="empty-state"><div class="ei">🧾</div><p>Sin ventas registradas aún.</p></div></td></tr>
          <?php else: ?>
            <?php while($v=$ultimas->fetch_assoc()): ?>
            <tr>
              <td class="num" style="color:var(--gris-400);font-size:.73rem"><?=str_pad($v['id_venta'],4,'0',STR_PAD_LEFT)?></td>
              <td><?=date('d/m H:i',strtotime($v['fecha']))?></td>
              <td><?=e($v['cliente'])?></td>
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
  <div class="fade-up fade-up-4">
    <div style="font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:var(--gris-400);margin-bottom:.65rem">Acciones rápidas</div>
    <div class="quick-grid">
      <a href="ventas/nueva_venta.php" class="quick-btn"><span class="qi">🛒</span>Nueva Venta</a>
      <a href="fiado/venta_credito.php" class="quick-btn"><span class="qi">💳</span>Dar Fiado</a>
      <a href="productos/agregar.php" class="quick-btn"><span class="qi">➕</span>Agregar Producto</a>
      <a href="clientes/agregar.php" class="quick-btn"><span class="qi">👤</span>Nuevo Cliente</a>
      <a href="fiado/consultar_adeudo.php" class="quick-btn"><span class="qi">📋</span>Cobrar Adeudo</a>
      <a href="reportes/ventas.php" class="quick-btn"><span class="qi">📊</span>Reportes</a>
    </div>
  </div>
</div>

<?php layoutEnd(); ?>