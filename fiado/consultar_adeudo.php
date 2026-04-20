<?php

require_once '../includes/config.php';
require_once '../includes/whatsapp.php';
requerirAutenticacion();

$cliente_id = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
$cliente    = null;
if ($cliente_id) {
    $s = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $s->bind_param("i", $cliente_id); $s->execute();
    $cliente = $s->get_result()->fetch_assoc();
}

$error = $exito = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abono'])) {
    $monto = (float)$_POST['monto'];
    if ($monto <= 0)             { $error = "El monto debe ser mayor a \$0."; }
    elseif (!$cliente)           { $error = "Cliente no válido."; }
    elseif ($monto > $cliente['adeudo'] + 0.001)
                                 { $error = "El abono no puede superar el adeudo (" . dinero($cliente['adeudo']) . ")."; }
    else {
        $conn->begin_transaction();
        try {
            $s1 = $conn->prepare("INSERT INTO abonos(monto,id_cliente) VALUES(?,?)");
            $s1->bind_param("di", $monto, $cliente_id); $s1->execute();

            $s2 = $conn->prepare("UPDATE clientes SET adeudo = adeudo - ? WHERE id_cliente = ?");
            $s2->bind_param("di", $monto, $cliente_id); $s2->execute();

            $conn->commit();
            $adeudo_nuevo   = max(0.0, round($cliente['adeudo'] - $monto, 2));
            $cliente['adeudo'] = $adeudo_nuevo;

            /* ── Notificación WhatsApp (plantilla) ── */
            $wsp = wspNotificarAbono($cliente['nombre'], $monto, $adeudo_nuevo);

            if ($wsp['ok']) {
                $exito = "Abono de " . dinero($monto) . " registrado. ✅ Notificación enviada por WhatsApp.";
            } else {
                $wsp_err = $wsp['body']['error']['message'] ?? ('HTTP ' . $wsp['code']);
                $exito   = "Abono de " . dinero($monto) . " registrado. ⚠️ WhatsApp: " . $wsp_err;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al registrar el abono. Intenta de nuevo.";
        }
    }
}

/* Deudores */
$deudores = $conn->query("SELECT id_cliente, nombre, telefono, adeudo
    FROM clientes WHERE adeudo > 0 ORDER BY adeudo DESC, nombre");

/* Historial de abonos */
$abonos_list = [];
if ($cliente) {
    $sa = $conn->prepare("SELECT fecha, monto FROM abonos WHERE id_cliente = ? ORDER BY fecha DESC LIMIT 10");
    $sa->bind_param("i", $cliente_id); $sa->execute();
    $r = $sa->get_result();
    while ($ab = $r->fetch_assoc()) $abonos_list[] = $ab;
}

$flash = flashGet();
layoutStart('Adeudos', 'adeudos', [['label' => 'Adeudos y Abonos']]);
?>

<style>
/* ── Layout ── */
.adeudo-layout{display:grid;grid-template-columns:1fr 340px;gap:1.25rem;align-items:start}
.panel-cliente{position:sticky;top:calc(var(--topbar-h) + 1.25rem)}

/* Monto grande */
.monto-grande{
  font-family:var(--font-mono) !important;
  font-size:2.2rem;font-weight:800;
  color:var(--rojo);line-height:1.1;
  font-feature-settings:"tnum" 1;
  margin:.4rem 0 1.1rem;
}
.monto-grande.cero{color:var(--verde)!important}

/* Botones de monto rápido */
.quick-abonos{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.5rem}

/* Badge de WhatsApp */
.wsp-badge{
  display:flex;align-items:center;gap:.5rem;
  padding:.52rem .85rem;
  background:var(--verde-bg);border:1px solid var(--verde-b);
  border-radius:9px;font-size:.78rem;font-weight:700;color:var(--verde);
  margin-top:.65rem;
}

@media(max-width:760px){.adeudo-layout{grid-template-columns:1fr}.panel-cliente{position:static}}
</style>

<div class="page-head fade-up">
  <div class="page-title"><i class="fa-solid fa-file-invoice-dollar"></i> Adeudos y Abonos</div>
  <div class="page-subtitle">Registra pagos — notificaciones automáticas por WhatsApp</div>
</div>

<?php if($flash): ?><div class="alerta alerta-<?=e($flash['tipo'])?>"><?=e($flash['msg'])?></div><?php endif ?>
<?php if($error): ?><div class="alerta alerta-error"><i class="fa-solid fa-xmark"></i> <?=e($error)?></div><?php endif ?>
<?php if($exito): ?><div class="alerta alerta-exito"><i class="fa-solid fa-check"></i> <?=e($exito)?></div><?php endif ?>

<div class="adeudo-layout">

  <!-- ── Tabla de deudores ── -->
  <div class="card fade-up delay-1">
    <div class="card-header"
         style="background:linear-gradient(135deg,#1a0a2e,#2d1458)">
      <h3 style="color:#fff">
        <i class="fa-solid fa-users" style="margin-right:.4rem"></i>
        Clientes con adeudo
      </h3>
      <span class="badge badge-rojo"><?=$deudores->num_rows?></span>
    </div>
    <div class="tabla-wrap">
      <table class="tabla">
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th style="text-align:right">Adeudo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if($deudores->num_rows===0): ?>
            <tr><td colspan="4">
              <div class="empty-state">
                <div class="ei"><i class="fa-solid fa-circle-check" style="color:var(--verde)"></i></div>
                <p>¡Sin adeudos pendientes!</p>
              </div>
            </td></tr>
          <?php else: ?>
            <?php while($c=$deudores->fetch_assoc()): ?>
            <tr style="<?=$c['id_cliente']==$cliente_id?'background:var(--verde-bg)':''?>">
              <td><strong style="font-family:var(--font-title)"><?=e($c['nombre'])?></strong></td>
              <td style="color:var(--txt-muted)"><?=e($c['telefono']?:'—')?></td>
              <td style="text-align:right" class="dinero dinero-rojo"><?=dinero($c['adeudo'])?></td>
              <td>
                <a href="?cliente=<?=$c['id_cliente']?>" class="btn btn-sm btn-morado">
                  <i class="fa-solid fa-eye"></i> Ver
                </a>
              </td>
            </tr>
            <?php endwhile ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Panel del cliente ── -->
  <div class="panel-cliente">
    <?php if($cliente): ?>

    <!-- Tarjeta principal del cliente -->
    <div class="card fade-up delay-2" style="margin-bottom:.85rem;overflow:hidden">
      <div style="background:linear-gradient(135deg,#1a0a2e,#3d1a78);padding:1rem 1.2rem;display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-family:var(--font-title);font-size:.95rem;font-weight:800;color:#fff">
            <?=e($cliente['nombre'])?>
          </div>
          <?php if($cliente['telefono']): ?>
          <div style="font-size:.72rem;color:rgba(255,255,255,.5);margin-top:1px">
            <i class="fa-solid fa-phone"></i> <?=e($cliente['telefono'])?>
          </div>
          <?php endif ?>
        </div>
        <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-family:var(--font-title);font-size:.9rem;font-weight:800;color:#fff">
          <?=strtoupper(substr($cliente['nombre'],0,1))?>
        </div>
      </div>

      <div style="padding:1.2rem;text-align:center">
        <div style="font-family:var(--font-ui);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--txt-muted)">
          Adeudo actual
        </div>
        <div class="monto-grande <?=$cliente['adeudo']<=0?'cero':''?>">
          <?=dinero($cliente['adeudo'])?>
        </div>

        <?php if($cliente['adeudo']>0.005): ?>
        <form method="POST">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-coins"></i> Monto a abonar</label>
            <input type="number" name="monto" id="inpMonto" class="form-control"
                   step="0.01" min="0.01" max="<?=round($cliente['adeudo'],2)?>"
                   placeholder="$0.00" required
                   style="font-family:var(--font-mono);font-size:1.1rem;font-weight:700;text-align:center">
          </div>

          <!-- Montos rápidos -->
          <div class="quick-abonos">
            <?php foreach([50,100,200,500] as $m): if($m<=$cliente['adeudo']): ?>
              <button type="button" class="btn btn-sm btn-gris"
                      onclick="document.getElementById('inpMonto').value='<?=$m?>'"
                      style="font-family:var(--font-mono)"><?=dinero($m)?></button>
            <?php endif; endforeach ?>
            <button type="button" class="btn btn-sm btn-naranja"
                    onclick="document.getElementById('inpMonto').value='<?=round($cliente['adeudo'],2)?>'"
                    style="font-family:var(--font-mono)">Todo</button>
          </div>

          <button type="submit" name="abono" class="btn btn-verde btn-block btn-lg" style="margin-top:.85rem">
            <i class="fa-solid fa-circle-check"></i> Registrar Abono
          </button>
          <div class="wsp-badge">
            <i class="fa-brands fa-whatsapp" style="font-size:1rem;color:#25D366"></i>
            Se enviará notificación automática por WhatsApp
          </div>
        </form>
        <?php else: ?>
          <div class="alerta alerta-exito" style="margin-top:.5rem">
            <i class="fa-solid fa-circle-check"></i> Sin adeudo pendiente
          </div>
        <?php endif ?>
      </div>
    </div>

    <!-- Historial de abonos -->
    <?php if(!empty($abonos_list)): ?>
    <div class="card fade-up delay-3">
      <div class="card-header">
        <h3><i class="fa-solid fa-clock-rotate-left" style="color:var(--p1);margin-right:.35rem"></i>Últimos abonos</h3>
      </div>
      <div class="tabla-wrap">
        <table class="tabla">
          <thead><tr><th>Fecha</th><th style="text-align:right">Monto</th></tr></thead>
          <tbody>
            <?php foreach($abonos_list as $ab): ?>
            <tr>
              <td style="font-size:.8rem"><?=date('d/m/Y H:i',strtotime($ab['fecha']))?></td>
              <td style="text-align:right" class="dinero dinero-verde"><?=dinero($ab['monto'])?></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif ?>

    <?php else: ?>
    <div class="card fade-up delay-2">
      <div class="card-body">
        <div class="empty-state">
          <div class="ei"><i class="fa-solid fa-hand-point-left" style="color:var(--p1)"></i></div>
          <p>Selecciona un cliente de la tabla para ver su adeudo y registrar abonos.</p>
        </div>
      </div>
    </div>
    <?php endif ?>
  </div>

</div>

<?php layoutEnd(); ?>