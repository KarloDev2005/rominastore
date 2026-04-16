<?php
require_once '../includes/config.php';
requerirAutenticacion();

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin']    ?? date('Y-m-d');
$fecha_inicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) ? $fecha_inicio : date('Y-m-01');
$fecha_fin    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)    ? $fecha_fin    : date('Y-m-d');

/* ─── Métricas con SQL SUM (sin iterar en PHP) ─── */
$sm = $conn->prepare("SELECT COUNT(*) total, COALESCE(SUM(total),0) monto, COALESCE(AVG(total),0) promedio,
    SUM(id_cliente IS NULL) contado, SUM(id_cliente IS NOT NULL) credito
    FROM ventas WHERE DATE(fecha) BETWEEN ? AND ?");
$sm->bind_param("ss",$fecha_inicio,$fecha_fin);
$sm->execute();
$metricas = $sm->get_result()->fetch_assoc();

/* ─── Exportar CSV ─── */
if(isset($_GET['csv'])){
    $sc=$conn->prepare("SELECT v.id_venta,DATE_FORMAT(v.fecha,'%d/%m/%Y %H:%i') fecha,
        CASE WHEN v.id_cliente IS NULL THEN 'CONTADO' ELSE c.nombre END cliente,
        u.nombre usuario,v.total FROM ventas v
        LEFT JOIN clientes c ON v.id_cliente=c.id_cliente
        JOIN usuarios u ON v.id_usuario=u.id_usuario
        WHERE DATE(v.fecha) BETWEEN ? AND ? ORDER BY v.fecha DESC");
    $sc->bind_param("ss",$fecha_inicio,$fecha_fin);
    $sc->execute();
    $rows=$sc->get_result();
    header('Content-Type:text/csv;charset=utf-8');
    header('Content-Disposition:attachment;filename="ventas_'.$fecha_inicio.'_'.$fecha_fin.'.csv"');
    $o=fopen('php://output','w');
    fprintf($o,chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($o,['ID','Fecha','Cliente','Usuario','Total']);
    while($r=$rows->fetch_assoc()) fputcsv($o,$r);
    fclose($o);exit;
}

/* ─── Paginación ─── */
$por_pag = 25;
$pagina  = max(1,(int)($_GET['pagina']??1));
$offset  = ($pagina-1)*$por_pag;
$total_pags = (int)ceil($metricas['total']/$por_pag);

$sq=$conn->prepare("SELECT v.id_venta,v.fecha,v.total,
    CASE WHEN v.id_cliente IS NULL THEN 'CONTADO' ELSE c.nombre END cliente,
    u.nombre usuario,
    CASE WHEN v.id_cliente IS NULL THEN 0 ELSE 1 END es_credito
    FROM ventas v LEFT JOIN clientes c ON v.id_cliente=c.id_cliente
    JOIN usuarios u ON v.id_usuario=u.id_usuario
    WHERE DATE(v.fecha) BETWEEN ? AND ? ORDER BY v.fecha DESC LIMIT ? OFFSET ?");
$sq->bind_param("ssii",$fecha_inicio,$fecha_fin,$por_pag,$offset);
$sq->execute();
$resultado=$sq->get_result();

layoutStart('Reportes', 'reportes', [['label'=>'Reportes'],['label'=>'Ventas']]);
?>

<style>
.filtros-bar{
  display:flex;gap:.65rem;margin-bottom:1.25rem;flex-wrap:wrap;align-items:flex-end;
  background:var(--blanco);border:1px solid var(--gris-200);border-radius:var(--card-radio);
  padding:.9rem 1rem;
}
.filtros-bar .form-group{margin:0}
.pag-wrap{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-top:.8rem}
.pag-info{font-size:.78rem;color:var(--gris-400)}
.pag-btns{display:flex;gap:.3rem}
</style>

<div class="page-head">
  <div class="page-title">📊 Reporte de Ventas</div>
  <div class="page-subtitle">Historial de transacciones por período</div>
</div>

<!-- Métricas -->
<div class="metrics-row">
  <div class="metric-card verde">
    <div class="metric-label">Total vendido</div>
    <div class="metric-valor"><?=dinero($metricas['monto'])?></div>
    <div class="metric-sub"><?=(int)$metricas['total']?> transacciones</div>
  </div>
  <div class="metric-card azul">
    <div class="metric-label">Ticket promedio</div>
    <div class="metric-valor"><?=dinero($metricas['promedio'])?></div>
    <div class="metric-sub">por venta</div>
  </div>
  <div class="metric-card naranja">
    <div class="metric-label">Ventas contado</div>
    <div class="metric-valor"><?=(int)$metricas['contado']?></div>
  </div>
  <div class="metric-card rojo">
    <div class="metric-label">Ventas crédito</div>
    <div class="metric-valor"><?=(int)$metricas['credito']?></div>
  </div>
</div>

<!-- Filtros -->
<div class="filtros-bar">
  <form method="GET" style="display:contents">
    <div class="form-group">
      <label class="form-label">Desde</label>
      <input type="date" name="fecha_inicio" class="form-control" value="<?=e($fecha_inicio)?>">
    </div>
    <div class="form-group">
      <label class="form-label">Hasta</label>
      <input type="date" name="fecha_fin" class="form-control" value="<?=e($fecha_fin)?>">
    </div>
    <button type="submit" class="btn btn-verde">🔍 Filtrar</button>
  </form>
  <a href="?fecha_inicio=<?=date('Y-m-d')?>&fecha_fin=<?=date('Y-m-d')?>" class="btn btn-gris">Hoy</a>
  <a href="?fecha_inicio=<?=date('Y-m-01')?>&fecha_fin=<?=date('Y-m-d')?>" class="btn btn-gris">Este mes</a>
  <a href="?fecha_inicio=<?=e($fecha_inicio)?>&fecha_fin=<?=e($fecha_fin)?>&csv=1"
     class="btn btn-naranja" style="margin-left:auto">⬇ Exportar CSV</a>
</div>

<!-- Tabla -->
<div class="card">
  <div class="tabla-wrap">
    <table class="tabla">
      <thead>
        <tr><th>#</th><th>Fecha / Hora</th><th>Cliente</th><th>Usuario</th><th style="text-align:right">Total</th><th>Tipo</th></tr>
      </thead>
      <tbody>
        <?php if($resultado->num_rows===0): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="ei">📭</div><p>Sin ventas en el período.</p></div></td></tr>
        <?php else: ?>
          <?php while($v=$resultado->fetch_assoc()): ?>
          <tr>
            <td class="num" style="color:var(--gris-400);font-size:.74rem"><?=str_pad($v['id_venta'],4,'0',STR_PAD_LEFT)?></td>
            <td><?=date('d/m/Y H:i',strtotime($v['fecha']))?></td>
            <td><?=e($v['cliente'])?></td>
            <td><?=e($v['usuario'])?></td>
            <td style="text-align:right" class="num dinero-verde"><?=dinero($v['total'])?></td>
            <td>
              <?php if($v['es_credito']): ?>
                <span class="badge badge-naranja">Crédito</span>
              <?php else: ?>
                <span class="badge badge-verde">Contado</span>
              <?php endif ?>
            </td>
          </tr>
          <?php endwhile ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Paginación -->
<?php if($total_pags>1): ?>
<div class="pag-wrap">
  <span class="pag-info">Página <?=$pagina?> de <?=$total_pags?> (<?=(int)$metricas['total']?> registros)</span>
  <div class="pag-btns">
    <?php if($pagina>1): ?>
      <a href="?fecha_inicio=<?=e($fecha_inicio)?>&fecha_fin=<?=e($fecha_fin)?>&pagina=<?=$pagina-1?>" class="btn btn-sm btn-gris">← Anterior</a>
    <?php endif ?>
    <?php
    $s=max(1,$pagina-2);$end=min($total_pags,$pagina+2);
    for($pg=$s;$pg<=$end;$pg++):
    ?>
      <a href="?fecha_inicio=<?=e($fecha_inicio)?>&fecha_fin=<?=e($fecha_fin)?>&pagina=<?=$pg?>"
         class="btn btn-sm <?=$pg===$pagina?'btn-verde':'btn-gris'?>"><?=$pg?></a>
    <?php endfor ?>
    <?php if($pagina<$total_pags): ?>
      <a href="?fecha_inicio=<?=e($fecha_inicio)?>&fecha_fin=<?=e($fecha_fin)?>&pagina=<?=$pagina+1?>" class="btn btn-sm btn-gris">Siguiente →</a>
    <?php endif ?>
  </div>
</div>
<?php endif ?>

<?php layoutEnd(); ?>