<?php
require_once 'includes/config.php';
requerirAutenticacion();

$hoy     = date('Y-m-d');
$mes_ini = date('Y-m-01');

/* ── Métricas ── */
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

/* ── Ventas últimos 7 días (para gráfica) ── */
$ventas_semana = [];
$labels_semana = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $label = date('D', strtotime($fecha)); // Lun, Mar...
    $r = $conn->query("SELECT COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha)='$fecha'");
    $ventas_semana[] = round($r->fetch_assoc()['t'], 2);
    $labels_semana[] = $label;
}

/* ── Ventas últimos 6 meses (para gráfica de barras) ── */
$ventas_meses = [];
$labels_meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes_f = date('Y-m', strtotime("-$i months"));
    $ini = $mes_f . '-01';
    $fin = date('Y-m-t', strtotime($ini));
    $label = date('M', strtotime($ini));
    $r = $conn->query("SELECT COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha) BETWEEN '$ini' AND '$fin'");
    $ventas_meses[] = round($r->fetch_assoc()['t'], 2);
    $labels_meses[] = $label;
}

/* ── Últimas ventas (lista) ── */
$ultimas = $conn->query("SELECT v.id_venta,v.fecha,v.total,
    CASE WHEN v.id_cliente IS NULL THEN 'Contado' ELSE c.nombre END cliente,
    u.nombre usuario
    FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario
    LEFT JOIN clientes c ON v.id_cliente=c.id_cliente
    ORDER BY v.fecha DESC LIMIT 5");

/* ── Top productos más vendidos ── */
$top_prod = $conn->query("SELECT p.nombre, SUM(dv.cantidad) total_uds, SUM(dv.subtotal) total_monto
    FROM detalle_venta dv JOIN productos p ON dv.id_producto=p.id_producto
    GROUP BY dv.id_producto ORDER BY total_uds DESC LIMIT 5");

layoutStart('Dashboard', '', []);
?>

<style>
/* ─────────────────────────────────
   VARIABLES LOCALES DASHBOARD
───────────────────────────────── */
:root{
  --p1-soft:rgba(124,31,160,.08);
  --m1-soft:rgba(196,37,122,.08);
  --cream:#faf8f5;
}

/* ── Banner superior con logo ── */
.dash-banner{
  background:linear-gradient(135deg,var(--oscuro) 0%,var(--oscuro-2) 55%,var(--morado) 100%);
  border-radius:20px;padding:1.4rem 1.8rem;margin-bottom:1.3rem;
  display:flex;align-items:center;justify-content:space-between;gap:1.5rem;
  overflow:hidden;position:relative;
  box-shadow:var(--sombra-lg);
  animation:fadeUp .4s ease both;
}
.dash-banner::before{
  content:'';position:absolute;inset:0;
  background:url('img/logo_full.jpeg') right -30px center/300px no-repeat;
  opacity:.05;pointer-events:none;
}
.banner-left h2{font-size:1.25rem;font-weight:900;color:#fff;letter-spacing:-.2px}
.banner-left p {font-size:.78rem;color:rgba(255,255,255,.5);margin-top:.2rem;font-weight:500}
.banner-logo{
  height:72px;object-fit:contain;flex-shrink:0;
  border-radius:50%;border:3px solid rgba(255,255,255,.15);
  background:#fff;padding:4px;
  animation:floatY 4s ease-in-out infinite;
}
@keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}

/* ── Alerta stock ── */
.alerta-stock{
  display:flex;align-items:center;gap:.75rem;
  padding:.75rem 1.1rem;
  background:#fffbeb;border:1px solid #fde68a;border-left:4px solid var(--naranja);
  border-radius:12px;margin-bottom:1.2rem;
  font-size:.82rem;color:#92400e;font-weight:700;
  animation:fadeUp .4s .05s ease both;
}

/* ── GRID PRINCIPAL ── */
.dash-main{
  display:grid;
  grid-template-columns:1fr 340px;
  gap:1.1rem;
  align-items:start;
}

/* ── Métricas row ── */
.metrics-row{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:.85rem;
  margin-bottom:1.1rem;
}
.metric-card{
  background:var(--blanco);
  border-radius:16px;
  padding:1.1rem 1.2rem;
  border:1px solid var(--gris-200);
  box-shadow:var(--sombra);
  transition:var(--trans);
  cursor:default;
  position:relative;
  overflow:hidden;
}
.metric-card::before{
  content:'';position:absolute;
  top:-20px;right:-20px;
  width:80px;height:80px;border-radius:50%;
  opacity:.08;
}
.metric-card.morado::before{background:var(--morado)}
.metric-card.magenta::before{background:var(--magenta)}
.metric-card.verde::before{background:var(--verde)}
.metric-card.rojo::before{background:var(--rojo)}
.metric-card:hover{transform:translateY(-3px);box-shadow:var(--sombra-md)}
.mc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem}
.mc-icon{
  width:38px;height:38px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;font-size:1.1rem;
  flex-shrink:0;
}
.ic-morado{background:var(--p1-soft)}
.ic-magenta{background:var(--m1-soft)}
.ic-verde{background:rgba(21,128,61,.08)}
.ic-rojo{background:rgba(220,38,38,.08)}
.mc-badge{font-size:.65rem;font-weight:800;padding:.2rem .6rem;border-radius:99px}
.badge-up{background:#f0fdf4;color:#166534}
.badge-down{background:#fef2f2;color:#dc2626}
.mc-valor{font-size:1.45rem;font-weight:900;color:var(--oscuro);font-family:var(--mono);line-height:1.1}
.mc-label{font-size:.72rem;color:var(--gris-400);font-weight:600;margin-top:.2rem}

/* ── Cards de gráficas ── */
.chart-card{
  background:var(--blanco);border-radius:16px;border:1px solid var(--gris-200);
  box-shadow:var(--sombra);overflow:hidden;margin-bottom:1.1rem;
}
.chart-card-header{
  padding:.9rem 1.2rem;border-bottom:1px solid var(--gris-100);
  display:flex;align-items:center;justify-content:space-between;
}
.chart-card-header h3{font-size:.88rem;font-weight:800;color:var(--oscuro)}
.chart-card-header .ch-sub{font-size:.72rem;color:var(--gris-400);font-weight:600}
.chart-body{padding:1rem 1.2rem}
.chart-wrap{position:relative;height:160px}

/* ── Panel derecho ── */
.right-panel{display:flex;flex-direction:column;gap:1.1rem}

/* Card ventas del día (grande, con acento) */
.today-card{
  background:linear-gradient(135deg,var(--morado),var(--oscuro-3));
  border-radius:18px;padding:1.4rem 1.3rem;
  color:#fff;box-shadow:var(--sombra-morado);
  position:relative;overflow:hidden;
}
.today-card::after{
  content:'💰';font-size:4rem;
  position:absolute;right:-10px;bottom:-10px;opacity:.12;
  animation:floatY 3s ease-in-out infinite;
}
.today-label{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;opacity:.65;margin-bottom:.3rem}
.today-valor{font-size:2rem;font-weight:900;font-family:var(--mono);letter-spacing:-.5px}
.today-sub{font-size:.75rem;opacity:.6;margin-top:.3rem}

/* Lista de ventas recientes */
.recent-card{
  background:var(--blanco);border-radius:16px;border:1px solid var(--gris-200);
  box-shadow:var(--sombra);overflow:hidden;flex:1;
}
.rc-header{
  padding:.85rem 1.1rem;border-bottom:1px solid var(--gris-100);
  display:flex;align-items:center;justify-content:space-between;
}
.rc-header h3{font-size:.85rem;font-weight:800;color:var(--oscuro)}
.rc-list{padding:.5rem .6rem}
.rc-item{
  display:flex;align-items:center;gap:.75rem;
  padding:.6rem .6rem;border-radius:10px;
  transition:background .15s;cursor:default;
}
.rc-item:hover{background:var(--morado-bg)}
.rc-avatar{
  width:36px;height:36px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:.85rem;
  background:var(--p1-soft);font-weight:800;color:var(--morado);
}
.rc-info{flex:1;min-width:0}
.rc-nombre{font-size:.8rem;font-weight:800;color:var(--oscuro);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rc-meta{font-size:.68rem;color:var(--gris-400);margin-top:1px}
.rc-monto{font-family:var(--mono);font-size:.82rem;font-weight:800;color:var(--verde);white-space:nowrap}

/* Top productos */
.top-prod-list{padding:.5rem .6rem}
.tp-item{
  display:flex;align-items:center;gap:.75rem;
  padding:.55rem .6rem;border-radius:10px;
}
.tp-num{
  width:24px;height:24px;border-radius:7px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:.7rem;font-weight:900;color:#fff;
  background:linear-gradient(135deg,var(--morado),var(--magenta));
}
.tp-bar-wrap{flex:1}
.tp-nombre{font-size:.78rem;font-weight:700;color:var(--oscuro);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tp-bar-bg{height:5px;background:var(--gris-100);border-radius:99px;overflow:hidden}
.tp-bar{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--morado),var(--magenta))}
.tp-uds{font-size:.72rem;font-weight:700;color:var(--gris-400);white-space:nowrap}

/* Accesos rápidos */
.quick-grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem;
  background:var(--blanco);border-radius:16px;border:1px solid var(--gris-200);
  box-shadow:var(--sombra);padding:.9rem;
}
.qb{
  display:flex;flex-direction:column;align-items:center;gap:.35rem;
  padding:.75rem .4rem;background:var(--gris-50);
  border-radius:12px;text-decoration:none;
  color:var(--gris-700);font-size:.7rem;font-weight:800;
  text-align:center;line-height:1.3;
  transition:var(--trans);border:1.5px solid transparent;
}
.qb:hover{
  background:var(--morado-bg);border-color:var(--morado-borde);
  color:var(--morado);transform:translateY(-2px);
}
.qb .qi{font-size:1.3rem;display:block;transition:transform .2s}
.qb:hover .qi{transform:scale(1.2)}

/* Animaciones stagger */
.dash-main{animation:fadeUp .4s .1s ease both}
.right-panel>*:nth-child(1){animation:fadeUp .35s .15s ease both;opacity:0;animation-fill-mode:both}
.right-panel>*:nth-child(2){animation:fadeUp .35s .2s ease both;opacity:0;animation-fill-mode:both}
.right-panel>*:nth-child(3){animation:fadeUp .35s .25s ease both;opacity:0;animation-fill-mode:both}

@media(max-width:880px){
  .dash-main{grid-template-columns:1fr}
  .right-panel{flex-direction:row;flex-wrap:wrap}
  .right-panel>*{flex:1;min-width:240px}
}
@media(max-width:550px){.metrics-row{grid-template-columns:1fr}}
</style>

<!-- Banner -->
<div class="dash-banner">
  <div class="banner-left">
    <h2>¡Hola, <?=e(nombreUsuario())?>! 👋</h2>
    <p><?=date('l j \d\e F \d\e Y')?> · <?=ucfirst(rolActual())?></p>
  </div>
  <img src="img/icono.png" alt="Romina" class="banner-logo">
</div>

<?php if($stock_bajo>0||$agotados>0): ?>
<div class="alerta-stock">
  ⚠️
  <?php if($agotados>0): ?><strong><?=$agotados?> agotado<?=$agotados>1?'s':''?></strong>&nbsp;<?php endif ?>
  <?php if($stock_bajo>0): ?>· <strong><?=$stock_bajo?></strong> con stock bajo (≤5 uds).<?php endif ?>
  <a href="inventario/consultar.php" style="margin-left:auto;color:var(--naranja);font-weight:900;text-decoration:none">Ver →</a>
</div>
<?php endif ?>

<div class="dash-main">

  <!-- ── Columna izquierda ── -->
  <div>
    <!-- Métricas -->
    <div class="metrics-row">
      <a class="metric-card morado" href="reportes/ventas.php?fecha_inicio=<?=$hoy?>&fecha_fin=<?=$hoy?>" style="text-decoration:none;display:block">
        <div class="mc-top">
          <div class="mc-icon ic-morado">💰</div>
          <span class="mc-badge badge-up">↑ Hoy</span>
        </div>
        <div class="mc-valor"><?=dinero($hoy_v['t'])?></div>
        <div class="mc-label"><?=(int)$hoy_v['c']?> ventas registradas</div>
      </a>
      <a class="metric-card magenta" href="reportes/ventas.php" style="text-decoration:none;display:block">
        <div class="mc-top">
          <div class="mc-icon ic-magenta">📅</div>
          <span class="mc-badge badge-up">Mes</span>
        </div>
        <div class="mc-valor"><?=dinero($mes_v['t'])?></div>
        <div class="mc-label"><?=(int)$mes_v['c']?> ventas del mes</div>
      </a>
      <a class="metric-card rojo" href="fiado/consultar_adeudo.php" style="text-decoration:none;display:block">
        <div class="mc-top">
          <div class="mc-icon ic-rojo">💳</div>
          <span class="mc-badge badge-down">Cobrar</span>
        </div>
        <div class="mc-valor"><?=dinero($deuda['t'])?></div>
        <div class="mc-label"><?=(int)$deuda['c']?> clientes con adeudo</div>
      </a>
      <a class="metric-card verde" href="inventario/consultar.php" style="text-decoration:none;display:block">
        <div class="mc-top">
          <div class="mc-icon ic-verde">📦</div>
          <?php if($agotados>0): ?>
            <span class="mc-badge badge-down">⚠ <?=$agotados?> agotados</span>
          <?php else: ?>
            <span class="mc-badge badge-up">OK</span>
          <?php endif ?>
        </div>
        <div class="mc-valor"><?=$stock_bajo?></div>
        <div class="mc-label">productos con stock bajo</div>
      </a>
    </div>

    <!-- Gráfica línea — ventas 7 días -->
    <div class="chart-card">
      <div class="chart-card-header">
        <div>
          <h3>📈 Ventas últimos 7 días</h3>
          <div class="ch-sub">Histórico diario</div>
        </div>
        <a href="reportes/ventas.php" class="btn btn-sm btn-gris">Ver reporte</a>
      </div>
      <div class="chart-body">
        <div class="chart-wrap">
          <canvas id="chartSemana"></canvas>
        </div>
      </div>
    </div>

    <!-- Gráfica barras — ventas 6 meses -->
    <div class="chart-card">
      <div class="chart-card-header">
        <div>
          <h3>📊 Ventas por mes</h3>
          <div class="ch-sub">Últimos 6 meses</div>
        </div>
      </div>
      <div class="chart-body">
        <div class="chart-wrap">
          <canvas id="chartMeses"></canvas>
        </div>
      </div>
    </div>

    <!-- Top productos -->
    <div class="chart-card">
      <div class="chart-card-header">
        <h3>🏆 Productos más vendidos</h3>
        <a href="reportes/ventas.php" class="btn btn-sm btn-gris">Detalles</a>
      </div>
      <?php
      $top_arr = [];
      while($tp = $top_prod->fetch_assoc()) $top_arr[] = $tp;
      $max_uds = $top_arr ? max(array_column($top_arr,'total_uds')) : 1;
      ?>
      <div class="top-prod-list">
        <?php if(empty($top_arr)): ?>
          <div class="empty-state"><div class="ei">📦</div><p>Sin datos de ventas aún.</p></div>
        <?php else: ?>
          <?php foreach($top_arr as $i=>$tp):
            $pct = $max_uds>0 ? round(($tp['total_uds']/$max_uds)*100) : 0;
          ?>
          <div class="tp-item">
            <div class="tp-num"><?=$i+1?></div>
            <div class="tp-bar-wrap">
              <div class="tp-nombre"><?=e($tp['nombre'])?></div>
              <div class="tp-bar-bg">
                <div class="tp-bar" style="width:<?=$pct?>%;transition:width .8s ease <?=($i*.1)?>s"></div>
              </div>
            </div>
            <div class="tp-uds"><?=$tp['total_uds']?> uds</div>
          </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>
  </div>

  <!-- ── Panel derecho ── -->
  <div class="right-panel">

    <!-- Card ventas del día (acento) -->
    <div class="today-card">
      <div class="today-label">Ventas de hoy</div>
      <div class="today-valor"><?=dinero($hoy_v['t'])?></div>
      <div class="today-sub"><?=(int)$hoy_v['c']?> transacciones · <?=date('d M Y')?></div>
    </div>

    <!-- Últimas ventas -->
    <div class="recent-card">
      <div class="rc-header">
        <h3>🕐 Recientes</h3>
        <a href="reportes/ventas.php" class="btn btn-sm btn-gris">Ver todas</a>
      </div>
      <div class="rc-list">
        <?php if($ultimas->num_rows===0): ?>
          <div class="empty-state" style="padding:1.5rem"><div class="ei">🧾</div><p>Sin ventas aún.</p></div>
        <?php else: ?>
          <?php while($v=$ultimas->fetch_assoc()):
            $ini_cli = strtoupper(substr($v['cliente'],0,1));
          ?>
          <div class="rc-item">
            <div class="rc-avatar"><?=$ini_cli?></div>
            <div class="rc-info">
              <div class="rc-nombre"><?=e($v['cliente'])?></div>
              <div class="rc-meta"><?=date('d/m H:i',strtotime($v['fecha']))?> · <?=e($v['usuario'])?></div>
            </div>
            <div class="rc-monto">+<?=dinero($v['total'])?></div>
          </div>
          <?php endwhile ?>
        <?php endif ?>
      </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="quick-grid">
      <a href="ventas/nueva_venta.php"      class="qb"><span class="qi">🛒</span>Nueva Venta</a>
      <a href="fiado/venta_credito.php"     class="qb"><span class="qi">💳</span>Dar Fiado</a>
      <a href="fiado/consultar_adeudo.php"  class="qb"><span class="qi">📋</span>Cobrar</a>
      <a href="productos/agregar.php"       class="qb"><span class="qi">➕</span>Producto</a>
      <a href="clientes/agregar.php"        class="qb"><span class="qi">👤</span>Cliente</a>
      <a href="reportes/ventas.php"         class="qb"><span class="qi">📊</span>Reportes</a>
    </div>

  </div><!-- /right-panel -->

</div><!-- /dash-main -->

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const morado  = '#7c1fa0';
const magenta = '#c4257a';
const moradoA = 'rgba(124,31,160,';
const magentaA= 'rgba(196,37,122,';

/* ── Datos de PHP ── */
const labelsSemana = <?=json_encode($labels_semana)?>;
const datosSemana  = <?=json_encode($ventas_semana)?>;
const labelsMeses  = <?=json_encode($labels_meses)?>;
const datosMeses   = <?=json_encode($ventas_meses)?>;

const chartDefaults = {
  plugins:{ legend:{display:false}, tooltip:{
    backgroundColor:'rgba(26,10,46,.9)',
    titleColor:'#fff', bodyColor:'rgba(255,255,255,.7)',
    padding:10, cornerRadius:8,
    callbacks:{ label: ctx => ' $' + ctx.parsed.y.toLocaleString('es-MX',{minimumFractionDigits:2}) }
  }},
  scales:{
    x:{grid:{display:false},ticks:{color:'#9898aa',font:{family:'Nunito',size:11}}},
    y:{grid:{color:'rgba(0,0,0,.04)',drawBorder:false},ticks:{color:'#9898aa',font:{family:'Nunito',size:11},
      callback: v => '$'+v.toLocaleString('es-MX')}}
  },
  animation:{ duration:900, easing:'easeOutQuart' },
  responsive:true, maintainAspectRatio:false,
};

/* ── Gráfica línea (7 días) ── */
new Chart(document.getElementById('chartSemana'),{
  type:'line',
  data:{
    labels: labelsSemana,
    datasets:[{
      data: datosSemana,
      borderColor: morado,
      borderWidth: 2.5,
      tension: .42,
      pointBackgroundColor: morado,
      pointRadius: 4,
      pointHoverRadius: 6,
      fill: true,
      backgroundColor: ctx => {
        const g = ctx.chart.ctx.createLinearGradient(0,0,0,180);
        g.addColorStop(0, moradoA+'.18)');
        g.addColorStop(1, moradoA+'0)');
        return g;
      }
    }]
  },
  options: chartDefaults
});

/* ── Gráfica barras (6 meses) ── */
new Chart(document.getElementById('chartMeses'),{
  type:'bar',
  data:{
    labels: labelsMeses,
    datasets:[{
      data: datosMeses,
      borderRadius: 8,
      borderSkipped: false,
      backgroundColor: ctx => {
        const g = ctx.chart.ctx.createLinearGradient(0,0,0,180);
        g.addColorStop(0, morado);
        g.addColorStop(1, magenta);
        return g;
      },
      hoverBackgroundColor: magenta,
    }]
  },
  options:{
    ...chartDefaults,
    scales:{
      ...chartDefaults.scales,
      y:{...chartDefaults.scales.y, beginAtZero:true}
    }
  }
});
</script>

<?php layoutEnd(); ?>