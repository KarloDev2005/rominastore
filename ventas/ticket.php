<?php
require_once '../includes/config.php';
requerirAutenticacion();

if(!isset($_SESSION['ultima_venta'])){ header('Location: nueva_venta.php'); exit; }
$id_venta=(int)$_SESSION['ultima_venta'];
unset($_SESSION['ultima_venta']);

$sv=$conn->prepare("SELECT v.*,u.nombre usuario,CASE WHEN v.id_cliente IS NULL THEN 'CONTADO' ELSE c.nombre END cliente
    FROM ventas v JOIN usuarios u ON v.id_usuario=u.id_usuario
    LEFT JOIN clientes c ON v.id_cliente=c.id_cliente WHERE v.id_venta=?");
$sv->bind_param("i",$id_venta);
$sv->execute();
$venta=$sv->get_result()->fetch_assoc();
if(!$venta){ header('Location: nueva_venta.php'); exit; }

$sd=$conn->prepare("SELECT dv.cantidad,dv.precio_unitario,dv.subtotal,p.nombre
    FROM detalle_venta dv JOIN productos p ON dv.id_producto=p.id_producto WHERE dv.id_venta=? ORDER BY p.nombre");
$sd->bind_param("i",$id_venta);
$sd->execute();
$detalles=$sd->get_result()->fetch_all(MYSQLI_ASSOC);
$total_pzas=array_sum(array_column($detalles,'cantidad'));

layoutStart('Ticket #'.str_pad($id_venta,4,'0',STR_PAD_LEFT),'pos',[['label'=>'Punto de Venta','url'=>'ventas/nueva_venta.php'],['label'=>'Ticket']]);
?>

<style>
.ticket-page{display:flex;flex-direction:column;align-items:center;gap:1rem;padding-bottom:2rem}
.ticket-actions{display:flex;gap:.65rem;flex-wrap:wrap;justify-content:center;margin-bottom:.5rem}
.ticket{
  background:var(--blanco);width:320px;border-radius:14px;
  box-shadow:var(--sombra-md);border:1px solid var(--gris-200);
  font-family:var(--mono);overflow:hidden;
}
.tk-head{background:var(--gris-900);color:#fff;padding:1.1rem;text-align:center}
.tk-store{font-size:1.2rem;font-weight:900;font-family:var(--fuente);letter-spacing:1px}
.tk-sub{font-size:.72rem;opacity:.6;font-family:var(--fuente);margin-top:2px}
.tk-meta{padding:.75rem 1rem;border-bottom:2px dashed var(--gris-100)}
.tk-row{display:flex;justify-content:space-between;font-size:.75rem;margin-bottom:.2rem;color:var(--gris-700)}
.tk-row:last-child{margin:0}
.tk-label{color:var(--gris-400)}
.tk-val{font-weight:700;color:var(--gris-900)}
.tk-items{padding:.75rem 1rem;border-bottom:2px dashed var(--gris-100)}
.tk-items-hdr{display:grid;grid-template-columns:3fr 1fr 1.5fr;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--gris-400);padding-bottom:.4rem;border-bottom:1px solid var(--gris-100);margin-bottom:.4rem}
.tk-item{display:grid;grid-template-columns:3fr 1fr 1.5fr;font-size:.78rem;padding:.25rem 0;border-bottom:1px solid var(--gris-50)}
.tk-item:last-child{border-bottom:none}
.tk-item .tn{font-weight:700;color:var(--gris-900);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tk-item .ts{text-align:right;font-weight:700;color:var(--verde)}
.tk-total{padding:.9rem 1rem;background:var(--verde-bg);display:flex;justify-content:space-between;align-items:center}
.tk-total .tl{font-size:.8rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--gris-700);font-family:var(--fuente)}
.tk-total .tv{font-size:1.5rem;font-weight:900;color:var(--verde)}
.tk-pie{padding:.8rem 1rem;text-align:center;font-size:.73rem;color:var(--gris-400);font-family:var(--fuente)}
.tk-pie strong{display:block;color:var(--gris-900);margin-bottom:1px}

@media print{
  *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .topbar-hamburger,.topbar-breadcrumb,.topbar-actions,.ticket-actions,.sidebar,.sidebar-overlay{display:none!important}
  .main-content{margin-left:0!important}
  .main-topbar{display:none!important}
  .page-area{padding:0!important}
  .ticket-page{padding:0}
  .ticket{width:80mm;box-shadow:none;border-radius:0;border:none}
  .tk-head{background:#000!important;color:#fff!important}
  .tk-total{background:#eee!important}
}
</style>

<div class="ticket-page">
  <div class="ticket-actions">
    <button onclick="window.print()" class="btn btn-verde btn-lg">🖨️ Imprimir Ticket</button>
    <a href="nueva_venta.php" class="btn btn-azul btn-lg">+ Nueva Venta</a>
  </div>

  <div class="ticket">
    <div class="tk-head">
      <div class="tk-store">🛒 ROMINASTORE</div>
      <div class="tk-sub">Abarrotes Romina</div>
    </div>
    <div class="tk-meta">
      <div class="tk-row"><span class="tk-label">Ticket #</span><span class="tk-val"><?=str_pad($id_venta,6,'0',STR_PAD_LEFT)?></span></div>
      <div class="tk-row"><span class="tk-label">Fecha</span><span class="tk-val"><?=date('d/m/Y H:i',strtotime($venta['fecha']))?></span></div>
      <div class="tk-row"><span class="tk-label">Atendió</span><span class="tk-val"><?=e($venta['usuario'])?></span></div>
      <div class="tk-row"><span class="tk-label">Cliente</span><span class="tk-val"><?=e($venta['cliente'])?></span></div>
    </div>
    <div class="tk-items">
      <div class="tk-items-hdr"><span>Producto</span><span style="text-align:center">Cant</span><span style="text-align:right">Total</span></div>
      <?php foreach($detalles as $d): ?>
      <div class="tk-item">
        <span class="tn" title="<?=e($d['nombre'])?>"><?=e($d['nombre'])?></span>
        <span style="text-align:center;color:var(--gris-700)"><?=$d['cantidad']?></span>
        <span class="ts"><?=dinero($d['subtotal'])?></span>
      </div>
      <?php endforeach ?>
    </div>
    <div class="tk-total">
      <span class="tl">Total (<?=$total_pzas?> art.)</span>
      <span class="tv"><?=dinero($venta['total'])?></span>
    </div>
    <div class="tk-pie">
      <strong>¡Gracias por su compra!</strong>
      Visítenos pronto · RominaStore
    </div>
  </div>
</div>

<?php layoutEnd(); ?>