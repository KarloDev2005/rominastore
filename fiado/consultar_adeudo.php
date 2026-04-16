<?php
require_once '../includes/config.php';
requerirAutenticacion();

$cliente_id = isset($_GET['cliente'])?(int)$_GET['cliente']:0;
$cliente    = null;
if($cliente_id){
    $s=$conn->prepare("SELECT * FROM clientes WHERE id_cliente=?");
    $s->bind_param("i",$cliente_id);
    $s->execute();
    $cliente=$s->get_result()->fetch_assoc();
}

$error=$exito='';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['abono'])){
    $monto=(float)$_POST['monto'];
    if($monto<=0){ $error="El monto debe ser mayor a $0."; }
    elseif(!$cliente){ $error="Cliente no válido."; }
    elseif($monto>$cliente['adeudo']+0.001){ $error="El abono no puede superar el adeudo (".dinero($cliente['adeudo']).")."; }
    else{
        $conn->begin_transaction();
        try{
            $s=$conn->prepare("INSERT INTO abonos(monto,id_cliente)VALUES(?,?)");
            $s->bind_param("di",$monto,$cliente_id);
            $s->execute();
            $s2=$conn->prepare("UPDATE clientes SET adeudo=adeudo-? WHERE id_cliente=?");
            $s2->bind_param("di",$monto,$cliente_id);
            $s2->execute();
            $conn->commit();
            $exito="Abono de ".dinero($monto)." registrado.";
            $cliente['adeudo']=max(0,$cliente['adeudo']-$monto);
        }catch(Exception $e){
            $conn->rollback();
            $error="Error al registrar. Intenta de nuevo.";
        }
    }
}

// Deudores
$deudores=$conn->query("SELECT id_cliente,nombre,telefono,adeudo FROM clientes WHERE adeudo>0 ORDER BY adeudo DESC,nombre");

// Historial de abonos
$abonos_list=[];
if($cliente){
    $sa=$conn->prepare("SELECT fecha,monto FROM abonos WHERE id_cliente=? ORDER BY fecha DESC LIMIT 10");
    $sa->bind_param("i",$cliente_id);
    $sa->execute();
    $rab=$sa->get_result();
    while($ab=$rab->fetch_assoc()) $abonos_list[]=$ab;
}

layoutStart('Adeudos','adeudos',[['label'=>'Adeudos y Abonos']]);
?>

<style>
.adeudo-layout{display:grid;grid-template-columns:1fr 340px;gap:1.1rem;align-items:start}
.cliente-panel{position:sticky;top:calc(56px + 1.1rem)}
.monto-grande{font-family:var(--mono);font-size:2rem;font-weight:900;color:var(--rojo);line-height:1}
.monto-grande.cero{color:var(--verde)}
.quick-abonos{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem}
@media(max-width:760px){.adeudo-layout{grid-template-columns:1fr}.cliente-panel{position:static}}
</style>

<div class="page-head">
  <div class="page-title">💳 Adeudos y Abonos</div>
  <div class="page-subtitle">Consulta y registra pagos de clientes con crédito</div>
</div>

<?php if($error): ?><div class="alerta alerta-error">✕ <?=e($error)?></div><?php endif ?>
<?php if($exito): ?><div class="alerta alerta-exito">✓ <?=e($exito)?></div><?php endif ?>

<div class="adeudo-layout">

  <!-- Tabla de deudores -->
  <div class="card">
    <div class="card-header">
      <h3>Clientes con adeudo</h3>
      <span class="badge badge-rojo"><?=$deudores->num_rows?></span>
    </div>
    <div class="tabla-wrap">
      <table class="tabla">
        <thead><tr><th>Cliente</th><th>Teléfono</th><th style="text-align:right">Adeudo</th><th></th></tr></thead>
        <tbody>
          <?php if($deudores->num_rows===0): ?>
            <tr><td colspan="4"><div class="empty-state"><div class="ei">✅</div><p>Sin adeudos pendientes</p></div></td></tr>
          <?php else: ?>
            <?php while($c=$deudores->fetch_assoc()): ?>
            <tr style="<?=$c['id_cliente']==$cliente_id?'background:var(--verde-bg)':''?>">
              <td><strong><?=e($c['nombre'])?></strong></td>
              <td><?=e($c['telefono']?:'—')?></td>
              <td style="text-align:right" class="num dinero-rojo"><?=dinero($c['adeudo'])?></td>
              <td><a href="?cliente=<?=$c['id_cliente']?>" class="btn btn-sm btn-azul">Ver / Abonar</a></td>
            </tr>
            <?php endwhile ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Panel del cliente -->
  <div class="cliente-panel">
    <?php if($cliente): ?>
    <div class="card" style="margin-bottom:.75rem">
      <div class="card-header" style="background:var(--gris-900);color:#fff">
        <h3>👤 <?=e($cliente['nombre'])?></h3>
        <?php if($cliente['telefono']): ?>
          <span style="font-size:.75rem;opacity:.6"><?=e($cliente['telefono'])?></span>
        <?php endif ?>
      </div>
      <div class="card-body" style="text-align:center">
        <div style="font-size:.68rem;color:var(--gris-400);text-transform:uppercase;letter-spacing:.5px">Adeudo actual</div>
        <div class="monto-grande <?=$cliente['adeudo']<=0?'cero':''?>" style="margin:.3rem 0 1rem">
          <?=dinero($cliente['adeudo'])?>
        </div>
        <?php if($cliente['adeudo']>0.005): ?>
        <form method="POST" style="text-align:left">
          <div class="form-group">
            <label class="form-label">Monto a abonar</label>
            <input type="number" name="monto" class="form-control" id="inpMonto"
                   step="0.01" min="0.01" max="<?=round($cliente['adeudo'],2)?>"
                   placeholder="0.00" required>
          </div>
          <div class="quick-abonos">
            <?php foreach([50,100,200,500] as $m): if($m<=$cliente['adeudo']): ?>
              <button type="button" class="btn btn-sm btn-gris"
                      onclick="document.getElementById('inpMonto').value='<?=$m?>'">+<?=dinero($m)?></button>
            <?php endif; endforeach ?>
            <button type="button" class="btn btn-sm btn-naranja"
                    onclick="document.getElementById('inpMonto').value='<?=round($cliente['adeudo'],2)?>'">Todo</button>
          </div>
          <button type="submit" name="abono" class="btn btn-verde btn-block btn-lg" style="margin-top:.75rem">✓ Registrar Abono</button>
        </form>
        <?php else: ?>
          <div class="alerta alerta-exito">✓ Sin adeudo pendiente</div>
        <?php endif ?>
      </div>
    </div>
    <?php if(!empty($abonos_list)): ?>
    <div class="card">
      <div class="card-header"><h3>Últimos abonos</h3></div>
      <div class="tabla-wrap">
        <table class="tabla">
          <thead><tr><th>Fecha</th><th style="text-align:right">Monto</th></tr></thead>
          <tbody>
            <?php foreach($abonos_list as $ab): ?>
            <tr>
              <td><?=date('d/m/Y H:i',strtotime($ab['fecha']))?></td>
              <td style="text-align:right" class="num dinero-verde"><?=dinero($ab['monto'])?></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif ?>
    <?php else: ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state"><div class="ei">👈</div><p>Selecciona un cliente de la tabla para ver su adeudo.</p></div>
      </div>
    </div>
    <?php endif ?>
  </div>

</div>

<?php layoutEnd(); ?>