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

/* ── Adeudos con atraso (NOTIFICACIONES) ── */
$deudores_atraso = obtenerDeudoresAtraso($conn, 2, 5);

/* ── Últimas ventas ── */
$ultimas = $conn->query("SELECT v.id_venta,v.fecha,v.total,
    CASE WHEN v.id_cliente IS NULL THEN 'Contado' ELSE c.nombre END cliente,
    u.nombre usuario
    FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario
    LEFT JOIN clientes c ON v.id_cliente=c.id_cliente
    ORDER BY v.fecha DESC LIMIT 5");

/* ── Gráficas ── */
$ventas_semana=[]; $labels_semana=[];
for($i=6;$i>=0;$i--){
    $f=date('Y-m-d',strtotime("-{$i} days"));
    $r=$conn->query("SELECT COALESCE(SUM(total),0) t FROM ventas WHERE DATE(fecha)='$f'");
    $ventas_semana[]=round($r->fetch_assoc()['t'],2);
    $labels_semana[]=date('D',strtotime($f));
}

/* ── Top productos ── */
$top_prod=$conn->query("SELECT p.nombre,SUM(dv.cantidad) u,SUM(dv.subtotal) m
    FROM detalle_venta dv JOIN productos p ON dv.id_producto=p.id_producto
    GROUP BY dv.id_producto ORDER BY u DESC LIMIT 5");
$top_arr=[]; while($r=$top_prod->fetch_assoc()) $top_arr[]=$r;
$max_u=$top_arr?max(array_column($top_arr,'u')):1;

layoutStart('Inicio','dashboard',[]);
?>

<style>
/* ── Banner ── */
.dash-banner{
  background:linear-gradient(135deg,#1a0a2e 0%,#2d1458 55%,#7c1fa0 100%);
  border-radius:18px;padding:1.4rem 1.8rem;
  margin-bottom:1.3rem;
  display:flex;align-items:center;justify-content:space-between;gap:1.5rem;
  overflow:hidden;position:relative;
  box-shadow:var(--sh-p1);
  animation:fadeUp .4s ease both;
}
.dash-banner::before{
  content:'';position:absolute;inset:0;
  background:url('img/logo_full.jpeg') right -30px center/300px no-repeat;
  opacity:.05;pointer-events:none;
}
.banner-left h2{font-family:var(--font-title);font-size:1.25rem;font-weight:900;color:#fff;letter-spacing:-.2px}
.banner-left p{font-size:.78rem;color:rgba(255,255,255,.5);margin-top:.2rem}
.banner-logo{height:72px;object-fit:contain;flex-shrink:0;border-radius:50%;border:3px solid rgba(255,255,255,.18);background:#fff;padding:4px;animation:floatY 4s ease-in-out infinite}
@keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

/* ── Notificaciones de adeudo ── */
.notif-panel{margin-bottom:1.2rem;animation:fadeUp .4s .05s ease both}
.notif-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:.7rem}
.notif-header h3{font-family:var(--font-title);font-size:.9rem;font-weight:700;color:var(--rojo);display:flex;align-items:center;gap:.4rem}
.notif-dot{width:8px;height:8px;border-radius:50%;background:var(--rojo);animation:pulse 1.5s ease-in-out infinite}
@keyframes pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.4);opacity:.6}}

/* ── Alerta stock ── */
.alerta-stock{display:flex;align-items:center;gap:.75rem;padding:.75rem 1.1rem;background:var(--naranja-bg);border:1px solid var(--naranja-b);border-left:4px solid var(--naranja);border-radius:11px;margin-bottom:1.2rem;font-size:.82rem;color:#d97706;font-weight:700;animation:fadeUp .4s .1s ease both}

/* ── Grid principal ── */
.dash-main{display:grid;grid-template-columns:1fr 320px;gap:1.1rem;align-items:start;animation:fadeUp .4s .15s ease both}
.metrics-row{display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:1.1rem}

/* Chart card */
.chart-card{background:var(--bg-card);border-radius:14px;border:1px solid var(--border-card);box-shadow:var(--sh-sm);overflow:hidden;margin-bottom:.85rem;transition:var(--t-base)}
.chart-card:hover{box-shadow:var(--sh-md)}
.chart-card-header{padding:.85rem 1.1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.chart-card-header h3{font-family:var(--font-title);font-size:.87rem;font-weight:700;color:var(--txt-primary)}
.ch-sub{font-size:.72rem;color:var(--txt-muted)}
.chart-body{padding:.85rem 1rem}

/* Top productos */
.tp-item{display:flex;align-items:center;gap:.65rem;padding:.5rem .55rem;border-radius:8px;transition:var(--t-fast)}
.tp-item:hover{background:var(--p1-bg)}
.tp-num{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:900;color:#fff;background:linear-gradient(135deg,var(--p1),var(--m1));flex-shrink:0}
.tp-nombre{font-size:.78rem;font-weight:700;color:var(--txt-primary);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tp-bar-bg{height:5px;background:var(--border);border-radius:99px;overflow:hidden}
.tp-bar{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--p1),var(--m1));transition:width 1s ease}
.tp-uds{font-family:var(--font-mono);font-size:.72rem;font-weight:700;color:var(--txt-muted);white-space:nowrap}

/* Panel derecho */
.right-panel{display:flex;flex-direction:column;gap:.85rem}
.today-card{background:linear-gradient(135deg,#1a0a2e,#3d1a78);border-radius:16px;padding:1.25rem 1.3rem;color:#fff;position:relative;overflow:hidden;box-shadow:var(--sh-p1)}
.today-card::after{content:'💰';font-size:3.5rem;position:absolute;right:-5px;bottom:-5px;opacity:.1}
.t-label{font-family:var(--font-ui);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;opacity:.65;margin-bottom:.25rem}
.t-valor{font-family:var(--font-mono) !important;font-size:1.8rem;font-weight:700;color:#fff;font-feature-settings:"tnum" 1}
.t-sub{font-size:.72rem;opacity:.5;margin-top:.2rem}

.recent-card{background:var(--bg-card);border-radius:14px;border:1px solid var(--border-card);box-shadow:var(--sh-sm);overflow:hidden}
.rc-header{padding:.75rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.rc-header h3{font-family:var(--font-title);font-size:.85rem;font-weight:700;color:var(--txt-primary)}
.rc-item{display:flex;align-items:center;gap:.7rem;padding:.6rem 1rem;border-bottom:1px solid var(--border);transition:background .12s}
.rc-item:last-child{border-bottom:none}
.rc-item:hover{background:var(--p1-bg)}
.rc-av{width:32px;height:32px;border-radius:9px;background:var(--p1-bg);display:flex;align-items:center;justify-content:center;font-family:var(--font-title);font-size:.75rem;font-weight:800;color:var(--p1);flex-shrink:0}
.rc-n{font-family:var(--font-ui);font-size:.8rem;font-weight:700;color:var(--txt-primary)}
.rc-m{font-size:.7rem;color:var(--txt-muted)}
.rc-money{font-family:var(--font-mono) !important;font-size:.82rem;font-weight:700;color:var(--verde);white-space:nowrap;font-feature-settings:"tnum" 1}

/* Quick grid */
.quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.55rem;background:var(--bg-card);border-radius:14px;border:1px solid var(--border-card);box-shadow:var(--sh-sm);padding:.85rem}
.qb{display:flex;flex-direction:column;align-items:center;gap:.3rem;padding:.75rem .35rem;background:var(--bg-app);border-radius:11px;text-decoration:none;color:var(--txt-secondary);font-family:var(--font-ui);font-size:.68rem;font-weight:700;text-align:center;line-height:1.3;transition:var(--t-base);border:1.5px solid transparent}
.qb:hover{background:var(--p1-bg);border-color:var(--p1-b);color:var(--p1);transform:translateY(-2px)}
.qb .qi{font-size:1.3rem;display:block;transition:transform .2s}
.qb:hover .qi{transform:scale(1.18)}

@media(max-width:800px){.dash-main{grid-template-columns:1fr}}
@media(max-width:550px){.metrics-row{grid-template-columns:1fr}}
</style>

<!-- Banner -->
<div class="dash-banner">
  <div class="banner-left">
    <h2>¡Hola, <?=e(nombreUsuario())?>! 👋</h2>
    <p><?=date('l j \d\e F \d\e Y')?> · <?=ucfirst(rolActual())?></p>
  </div>
  <img src="img/icono.png" alt="Logo" class="banner-logo">
</div>

<!-- ══ NOTIFICACIONES DE ADEUDO ══ -->
<?php if(!empty($deudores_atraso)): ?>
<div class="notif-panel">
  <div class="notif-header">
    <h3><span class="notif-dot"></span> Adeudos con atraso (<?=count($deudores_atraso)?>)</h3>
    <a href="fiado/consultar_adeudo.php" class="btn btn-sm btn-rojo">Ver todos</a>
  </div>
  <?php foreach($deudores_atraso as $d): ?>
  <div class="debt-notif">
    <div class="debt-icon">⚠️</div>
    <div class="debt-info">
      <div class="debt-name"><?=e($d['nombre'])?></div>
      <div class="debt-detail">
        <?=(int)$d['dias_atraso']?> día<?=$d['dias_atraso']!=1?'s':''?> desde su última compra
        · <a href="fiado/consultar_adeudo.php?cliente=<?=$d['id_cliente']?>" style="color:var(--rojo);font-weight:700;text-decoration:none">Cobrar →</a>
      </div>
    </div>
    <div class="debt-monto"><?=dinero($d['adeudo'])?></div>
  </div>
  <?php endforeach ?>
</div>
<?php endif ?>

<?php if($stock_bajo>0||$agotados>0): ?>
<div class="alerta-stock">
  <i class="fa-solid fa-triangle-exclamation" style="font-size:1.1rem"></i>
  <?php if($agotados>0): ?><strong><?=$agotados?> agotado<?=$agotados>1?'s':''?></strong>&nbsp;<?php endif ?>
  <?php if($stock_bajo>0): ?>· <strong><?=$stock_bajo?></strong> con stock bajo (≤5).<?php endif ?>
  <a href="inventario/consultar.php" style="margin-left:auto;color:var(--naranja);font-weight:900;text-decoration:none">Ver inventario →</a>
</div>
<?php endif ?>

<!-- Métricas -->
<div class="metrics-row fade-up delay-2" style="grid-template-columns:repeat(auto-fit,minmax(168px,1fr))">
  <a class="metric-card morado" href="reportes/ventas.php?fecha_inicio=<?=$hoy?>&fecha_fin=<?=$hoy?>" style="text-decoration:none;display:block">
    <div class="metric-icon">💰</div>
    <div class="metric-label">Ventas de hoy</div>
    <div class="metric-valor"><?=dinero($hoy_v['t'])?></div>
    <div class="metric-sub"><?=(int)$hoy_v['c']?> transacciones</div>
  </a>
  <a class="metric-card magenta" href="reportes/ventas.php" style="text-decoration:none;display:block">
    <div class="metric-icon">📅</div>
    <div class="metric-label">Ventas del mes</div>
    <div class="metric-valor"><?=dinero($mes_v['t'])?></div>
    <div class="metric-sub"><?=(int)$mes_v['c']?> ventas</div>
  </a>
  <a class="metric-card rojo" href="fiado/consultar_adeudo.php" style="text-decoration:none;display:block">
    <div class="metric-icon">💳</div>
    <div class="metric-label">Por cobrar</div>
    <div class="metric-valor"><?=dinero($deuda['t'])?></div>
    <div class="metric-sub"><?=(int)$deuda['c']?> clientes</div>
  </a>
  <a class="metric-card naranja" href="inventario/consultar.php" style="text-decoration:none;display:block">
    <div class="metric-icon">📦</div>
    <div class="metric-label">Agotados</div>
    <div class="metric-valor num"><?=$agotados?></div>
    <div class="metric-sub"><?=$stock_bajo?> con stock bajo</div>
  </a>
</div>

<div class="dash-main">
  <!-- Columna izquierda -->
  <div>
    <div class="chart-card">
      <div class="chart-card-header">
        <h3><i class="fa-solid fa-chart-line" style="color:var(--p1);margin-right:.35rem"></i>Ventas últimos 7 días</h3>
        <span class="ch-sub">Histórico diario</span>
      </div>
      <div class="chart-body"><canvas id="chartSemana" height="140"></canvas></div>
    </div>

    <div class="chart-card">
      <div class="chart-card-header">
        <h3><i class="fa-solid fa-trophy" style="color:var(--naranja);margin-right:.35rem"></i>Productos más vendidos</h3>
      </div>
      <div class="chart-body">
        <?php if(empty($top_arr)): ?>
          <div class="empty-state" style="padding:1rem"><div class="ei">📦</div><p>Sin datos de ventas aún.</p></div>
        <?php else: ?>
          <?php foreach($top_arr as $i=>$tp): $pct=$max_u>0?round(($tp['u']/$max_u)*100):0; ?>
          <div class="tp-item">
            <div class="tp-num"><?=$i+1?></div>
            <div style="flex:1;min-width:0">
              <div class="tp-nombre"><?=e($tp['nombre'])?></div>
              <div class="tp-bar-bg"><div class="tp-bar" style="width:<?=$pct?>%"></div></div>
            </div>
            <div class="tp-uds"><?=$tp['u']?> uds</div>
          </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>
  </div>

  <!-- Panel derecho -->
  <div class="right-panel">
    <div class="today-card">
      <div class="t-label">Ventas de hoy</div>
      <div class="t-valor"><?=dinero($hoy_v['t'])?></div>
      <div class="t-sub"><?=(int)$hoy_v['c']?> transacciones · <?=date('d M Y')?></div>
    </div>

    <div class="recent-card">
      <div class="rc-header">
        <h3>🕐 Recientes</h3>
        <a href="reportes/ventas.php" class="btn btn-sm btn-gris">Ver todas</a>
      </div>
      <?php if($ultimas->num_rows===0): ?>
        <div class="empty-state" style="padding:1.5rem"><div class="ei">🧾</div><p>Sin ventas aún.</p></div>
      <?php else: ?>
        <?php while($v=$ultimas->fetch_assoc()): $ini_c=strtoupper(substr($v['cliente'],0,1)); ?>
        <div class="rc-item">
          <div class="rc-av"><?=$ini_c?></div>
          <div style="flex:1;min-width:0">
            <div class="rc-n"><?=e($v['cliente'])?></div>
            <div class="rc-m"><?=date('d/m H:i',strtotime($v['fecha']))?> · <?=e($v['usuario'])?></div>
          </div>
          <div class="rc-money">+<?=dinero($v['total'])?></div>
        </div>
        <?php endwhile ?>
      <?php endif ?>
    </div>

    <div class="quick-grid">
      <a href="ventas/nueva_venta.php"     class="qb"><span class="qi">🛒</span>Nueva Venta</a>
      <a href="fiado/venta_credito.php"    class="qb"><span class="qi">💳</span>Dar Fiado</a>
      <a href="fiado/consultar_adeudo.php" class="qb"><span class="qi">📋</span>Cobrar</a>
      <a href="productos/agregar.php"      class="qb"><span class="qi">➕</span>Producto</a>
      <a href="clientes/agregar.php"       class="qb"><span class="qi">👤</span>Cliente</a>
      <a href="ventas/cierre_caja.php"     class="qb"><span class="qi">🔒</span>Cerrar Caja</a>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const morado='#7c1fa0',magenta='#c4257a';
const isDark=document.getElementById("htmlRoot").classList.contains("dark");
const gridColor=isDark?'rgba(255,255,255,.06)':'rgba(0,0,0,.04)';
const tickColor=isDark?'#7a6890':'#9888b0';

Chart.defaults.font.family='JetBrains Mono';

const cfg={
  plugins:{legend:{display:false},tooltip:{
    backgroundColor:'rgba(26,10,46,.92)',titleColor:'#fff',bodyColor:'rgba(255,255,255,.75)',
    padding:10,cornerRadius:8,
    callbacks:{label:c=>' $'+c.parsed.y.toLocaleString('es-MX',{minimumFractionDigits:2})}
  }},
  scales:{
    x:{grid:{display:false},ticks:{color:tickColor,font:{size:11}}},
    y:{grid:{color:gridColor,drawBorder:false},ticks:{color:tickColor,font:{size:11},
      callback:v=>'$'+v.toLocaleString('es-MX')}}
  },
  animation:{duration:900,easing:'easeOutQuart'},
  responsive:true,maintainAspectRatio:false
};

new Chart(document.getElementById('chartSemana'),{
  type:'line',
  data:{
    labels:<?=json_encode($labels_semana)?>,
    datasets:[{
      data:<?=json_encode($ventas_semana)?>,
      borderColor:morado,borderWidth:2.5,tension:.42,
      pointBackgroundColor:morado,pointRadius:4,pointHoverRadius:6,
      fill:true,
      backgroundColor:ctx=>{
        const g=ctx.chart.ctx.createLinearGradient(0,0,0,200);
        g.addColorStop(0,'rgba(124,31,160,.2)');g.addColorStop(1,'rgba(124,31,160,0)');return g;
      }
    }]
  },
  options:{...cfg,scales:{...cfg.scales,y:{...cfg.scales.y,beginAtZero:true}}}
});
</script>

<?php layoutEnd(); ?>