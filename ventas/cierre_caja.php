<?php
/* ventas/cierre_caja.php — Cierre de Caja con Arqueo Inteligente */
require_once '../includes/config.php';
requerirAutenticacion();

$hoy = date('Y-m-d');
$turno_ini = date('Y-m-d 00:00:00');
$turno_fin = date('Y-m-d 23:59:59');

/* ── Ventas del día ── */
$rv = $conn->query("SELECT COUNT(*) cnt, COALESCE(SUM(total),0) total,
    COUNT(CASE WHEN id_cliente IS NULL THEN 1 END) contado_cnt,
    COALESCE(SUM(CASE WHEN id_cliente IS NULL THEN total ELSE 0 END),0) contado_total,
    COUNT(CASE WHEN id_cliente IS NOT NULL THEN 1 END) credito_cnt,
    COALESCE(SUM(CASE WHEN id_cliente IS NOT NULL THEN total ELSE 0 END),0) credito_total
    FROM ventas WHERE fecha BETWEEN '$turno_ini' AND '$turno_fin'");
$vd = $rv->fetch_assoc();

/* ── Abonos cobrados hoy ── */
$ra = $conn->query("SELECT COALESCE(SUM(monto),0) total FROM abonos
    WHERE DATE(fecha)='$hoy'");
$abonos_hoy = $ra->fetch_assoc()['total'];

/* ── Total efectivo esperado en caja ── */
// Solo ventas contado + abonos cobrados = efectivo en caja
$efectivo_sistema = round($vd['contado_total'] + $abonos_hoy, 2);

/* ── Historial últimos cierres ── */
$rh = $conn->query("SELECT * FROM cierres_caja ORDER BY fecha DESC LIMIT 5");
$historial = $rh ? $rh->fetch_all(MYSQLI_ASSOC) : [];

/* ── Guardar cierre ── */
$resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_cierre'])) {
    $efectivo_declarado = (float)$_POST['efectivo_declarado'];
    $nota               = limpiar($_POST['nota'] ?? '');
    $diferencia         = round($efectivo_declarado - $efectivo_sistema, 2);
    $estado             = $diferencia == 0 ? 'ok' : ($diferencia > 0 ? 'exceso' : 'faltante');

    // Guardar en tabla (si existe)
    $s = $conn->prepare("INSERT INTO cierres_caja
        (fecha, ventas_contado, ventas_credito, abonos, efectivo_sistema,
         efectivo_declarado, diferencia, estado, nota, id_usuario)
        VALUES (NOW(),?,?,?,?,?,?,?,?,?)");
    if ($s) {
        $s->bind_param("ddddddssi",
            $vd['contado_total'], $vd['credito_total'],
            $abonos_hoy, $efectivo_sistema,
            $efectivo_declarado, $diferencia,
            $estado, $nota, $_SESSION['usuario_id']
        );
        $s->execute();
    }
    $resultado = compact('efectivo_declarado','diferencia','estado');
}

layoutStart('Cierre de Caja','cierre',[['label'=>'Cierre de Caja']]);
?>

<style>
.caja-header-card{
  background:linear-gradient(135deg,#1a0a2e,#3d1a78);
  border-radius:18px;padding:1.4rem 1.8rem;margin-bottom:1.3rem;
  color:#fff;box-shadow:var(--sh-p1);
  display:flex;align-items:center;justify-content:space-between;
}
.ch-title{font-family:var(--font-title);font-size:1.15rem;font-weight:800;color:#fff}
.ch-sub{font-size:.8rem;color:rgba(255,255,255,.5);margin-top:.2rem}
.ch-icon{font-size:2.5rem;opacity:.7}

.caja-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;align-items:start}

/* Resumen del turno */
.turno-row{display:flex;align-items:center;justify-content:space-between;padding:.65rem 0;border-bottom:1px solid var(--border)}
.turno-row:last-child{border-bottom:none}
.turno-label{font-family:var(--font-ui);font-size:.82rem;color:var(--txt-secondary)}
.turno-val  {font-family:var(--font-mono) !important;font-size:.9rem;font-weight:700;color:var(--txt-primary);font-feature-settings:"tnum" 1}
.turno-val.verde{color:var(--verde)}
.turno-val.rojo {color:var(--rojo)}
.turno-total{display:flex;align-items:center;justify-content:space-between;margin-top:.6rem;padding-top:.6rem;border-top:2px solid var(--border)}
.turno-total .lbl{font-family:var(--font-ui);font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--txt-muted)}
.turno-total .val{font-family:var(--font-mono) !important;font-size:1.35rem;font-weight:900;color:var(--txt-primary);font-feature-settings:"tnum" 1}

/* Formulario de arqueo */
.arqueo-card{background:var(--bg-card);border-radius:14px;border:1px solid var(--border-card);box-shadow:var(--sh-sm);overflow:hidden}
.arqueo-header{
  background:linear-gradient(135deg,#1a0a2e,#2d1458);
  padding:.85rem 1.1rem;color:#fff;
  font-family:var(--font-title);font-size:.9rem;font-weight:700;
}
.arqueo-body{padding:1.2rem}

/* Resultado del arqueo con colores */
.resultado-box{
  border-radius:14px;padding:1.4rem;text-align:center;
  margin-top:1rem;transition:all .4s ease;
}
.resultado-ok     {background:var(--verde-bg);border:2px solid var(--verde-b);}
.resultado-exceso {background:var(--azul-bg); border:2px solid var(--azul-b); }
.resultado-faltante{background:var(--rojo-bg); border:2px solid var(--rojo-b); }
.r-icon {font-size:2.5rem;margin-bottom:.5rem}
.r-label{font-family:var(--font-ui);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.8}
.r-valor{font-family:var(--font-mono) !important;font-size:2rem;font-weight:900;margin:.25rem 0;font-feature-settings:"tnum" 1}
.resultado-ok      .r-label,.resultado-ok      .r-valor{color:var(--verde)}
.resultado-exceso  .r-label,.resultado-exceso  .r-valor{color:var(--azul)}
.resultado-faltante.r-label,.resultado-faltante .r-valor{color:var(--rojo)}
.r-msg{font-size:.82rem;font-weight:600;opacity:.85}

/* Live preview del arqueo */
.live-arqueo{
  background:var(--p1-bg);border:1.5px solid var(--p1-b);border-radius:11px;
  padding:.85rem 1rem;margin-top:.75rem;
  display:flex;align-items:center;justify-content:space-between;
  transition:background .3s,border-color .3s;
}
.live-arqueo.ok      {background:var(--verde-bg);border-color:var(--verde-b)}
.live-arqueo.exceso  {background:var(--azul-bg); border-color:var(--azul-b) }
.live-arqueo.faltante{background:var(--rojo-bg); border-color:var(--rojo-b) }
.la-label{font-family:var(--font-ui);font-size:.75rem;font-weight:700;color:var(--txt-secondary)}
.la-val  {font-family:var(--font-mono) !important;font-size:1.1rem;font-weight:800;font-feature-settings:"tnum" 1}
.la-val.ok       {color:var(--verde)}
.la-val.exceso   {color:var(--azul)}
.la-val.faltante {color:var(--rojo)}

/* Historial */
.hist-item{display:flex;align-items:center;gap:.75rem;padding:.65rem .75rem;border-radius:10px;border-bottom:1px solid var(--border);transition:background .12s}
.hist-item:last-child{border-bottom:none}
.hist-item:hover{background:var(--p1-bg)}
.hist-fecha{font-size:.78rem;color:var(--txt-muted);font-family:var(--font-body)}
.hist-dif{font-family:var(--font-mono) !important;font-size:.82rem;font-weight:700;white-space:nowrap;font-feature-settings:"tnum" 1}
.hist-badge{flex-shrink:0}

@media(max-width:700px){.caja-grid{grid-template-columns:1fr}}
</style>

<!-- Header del turno -->
<div class="caja-header-card fade-up">
  <div>
    <div class="ch-title"><i class="fa-solid fa-cash-register" style="margin-right:.4rem"></i>Cierre de Caja</div>
    <div class="ch-sub">Turno del <?=date('d \d\e F Y')?> · Arqueo inteligente</div>
  </div>
  <div class="ch-icon">🔒</div>
</div>

<div class="caja-grid">

  <!-- ── Resumen del turno ── -->
  <div>
    <div class="card fade-up delay-1" style="margin-bottom:.85rem">
      <div class="card-header" style="background:linear-gradient(135deg,#1a0a2e,#2d1458)">
        <h3 style="color:#fff;font-size:.88rem"><i class="fa-solid fa-chart-bar" style="margin-right:.35rem"></i>Resumen del Turno</h3>
      </div>
      <div class="card-body">
        <div class="turno-row">
          <span class="turno-label"><i class="fa-solid fa-circle-check" style="color:var(--verde);margin-right:.35rem"></i>Ventas contado</span>
          <div>
            <span class="turno-val verde"><?=dinero($vd['contado_total'])?></span>
            <span style="font-size:.72rem;color:var(--txt-muted);margin-left:.35rem">(<?=(int)$vd['contado_cnt']?> ventas)</span>
          </div>
        </div>
        <div class="turno-row">
          <span class="turno-label"><i class="fa-solid fa-credit-card" style="color:var(--azul);margin-right:.35rem"></i>Ventas a crédito</span>
          <div>
            <span class="turno-val" style="color:var(--azul)"><?=dinero($vd['credito_total'])?></span>
            <span style="font-size:.72rem;color:var(--txt-muted);margin-left:.35rem">(<?=(int)$vd['credito_cnt']?>)</span>
          </div>
        </div>
        <div class="turno-row">
          <span class="turno-label"><i class="fa-solid fa-hand-holding-dollar" style="color:var(--naranja);margin-right:.35rem"></i>Abonos cobrados hoy</span>
          <span class="turno-val" style="color:var(--naranja)"><?=dinero($abonos_hoy)?></span>
        </div>
        <div class="turno-row">
          <span class="turno-label"><i class="fa-solid fa-shopping-cart" style="color:var(--p1);margin-right:.35rem"></i>Total ventas del día</span>
          <span class="turno-val"><?=dinero($vd['total'])?></span>
        </div>
        <div class="turno-total">
          <span class="lbl"><i class="fa-solid fa-wallet" style="margin-right:.3rem"></i>Esperado en caja</span>
          <span class="val"><?=dinero($efectivo_sistema)?></span>
        </div>
        <p style="font-size:.72rem;color:var(--txt-muted);margin-top:.5rem">
          <i class="fa-solid fa-circle-info"></i>
          Contado (<?=dinero($vd['contado_total'])?>) + Abonos (<?=dinero($abonos_hoy)?>)
        </p>
      </div>
    </div>

    <!-- Historial -->
    <?php if(!empty($historial)): ?>
    <div class="card fade-up delay-2">
      <div class="card-header"><h3>🕐 Últimos cierres</h3></div>
      <div>
        <?php foreach($historial as $h):
          $est=$h['estado']??'ok';
          $bc=$est==='ok'?'badge-verde':($est==='exceso'?'badge-azul':'badge-rojo');
          $tex=$est==='ok'?'Exacto':($est==='exceso'?'Sobrante':'Faltante');
        ?>
        <div class="hist-item">
          <div style="flex:1">
            <div class="hist-fecha"><?=date('d/m/Y H:i',strtotime($h['fecha']))?></div>
            <?php if(!empty($h['nota'])): ?><div style="font-size:.72rem;color:var(--txt-muted)"><?=e($h['nota'])?></div><?php endif ?>
          </div>
          <div class="hist-dif <?=$est==='faltante'?'dinero-rojo':($est==='exceso'?'':'dinero-verde')?>"><?=dinero(abs($h['diferencia']))?></div>
          <span class="badge <?=$bc?> hist-badge"><?=$tex?></span>
        </div>
        <?php endforeach ?>
      </div>
    </div>
    <?php endif ?>
  </div>

  <!-- ── Formulario de arqueo ── -->
  <div class="fade-up delay-2">
    <div class="arqueo-card">
      <div class="arqueo-header">
        <i class="fa-solid fa-scale-balanced" style="margin-right:.4rem"></i>Declarar Efectivo
      </div>
      <div class="arqueo-body">
        <?php if($resultado): ?>
          <!-- Resultado guardado -->
          <div class="alerta alerta-exito"><i class="fa-solid fa-check"></i> Cierre registrado correctamente.</div>
          <div class="resultado-box resultado-<?=htmlspecialchars($resultado['estado'])?>">
            <div class="r-icon"><?=$resultado['estado']==='ok'?'✅':($resultado['estado']==='exceso'?'💰':'⚠️')?></div>
            <div class="r-label"><?=$resultado['estado']==='ok'?'Cuadre perfecto':($resultado['estado']==='exceso'?'Dinero de más':'Faltante en caja')?></div>
            <div class="r-valor"><?=dinero(abs($resultado['diferencia']))?></div>
            <div class="r-msg">
              <?php
              if($resultado['estado']==='ok') echo 'El efectivo cuadra exactamente con el sistema.';
              elseif($resultado['estado']==='exceso') echo 'Tienes $'.number_format(abs($resultado['diferencia']),2).' más de lo registrado.';
              else echo 'Faltan $'.number_format(abs($resultado['diferencia']),2).' con respecto al sistema.';
              ?>
            </div>
          </div>
          <div style="margin-top:1rem">
            <a href="nueva_venta.php" class="btn btn-morado btn-block"><i class="fa-solid fa-plus"></i> Nueva Venta</a>
          </div>
        <?php else: ?>
          <form method="POST" id="formCierre">
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-dollar-sign"></i> Efectivo que tienes en caja</label>
              <input type="number" name="efectivo_declarado" id="efectivoInput"
                     class="form-control" step="0.01" min="0"
                     placeholder="0.00" required
                     oninput="calcArqueo(<?=$efectivo_sistema?>)"
                     style="font-family:var(--font-mono);font-size:1.1rem;font-weight:700;text-align:right">
            </div>
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-note-sticky"></i> Nota / Observación (opcional)</label>
              <input type="text" name="nota" class="form-control" placeholder="Ej: Turno mañana, faltó cambio…">
            </div>

            <!-- Preview en tiempo real -->
            <div class="live-arqueo" id="liveArqueo" style="display:none">
              <div>
                <div class="la-label" id="laLabel">Diferencia</div>
                <div class="la-val" id="laVal">—</div>
              </div>
              <div style="font-size:1.5rem" id="laIcon">⚖️</div>
            </div>

            <div style="margin-top:1rem">
              <button type="submit" name="guardar_cierre" class="btn btn-morado btn-block btn-lg con-spinner">
                <i class="fa-solid fa-lock"></i> Registrar Cierre de Caja
              </button>
            </div>
          </form>

          <!-- Referencia -->
          <div style="background:var(--p1-bg);border-radius:10px;padding:.85rem;margin-top:1rem;font-size:.78rem">
            <div style="font-family:var(--font-ui);font-weight:700;color:var(--p1);margin-bottom:.4rem">
              <i class="fa-solid fa-circle-info"></i> Referencia del sistema
            </div>
            <div style="color:var(--txt-secondary);display:flex;justify-content:space-between;padding:.2rem 0">
              <span>Esperado en caja</span>
              <span style="font-family:var(--font-mono);font-weight:700"><?=dinero($efectivo_sistema)?></span>
            </div>
          </div>
        <?php endif ?>
      </div>
    </div>
  </div>

</div>

<script>
const esperado = <?=$efectivo_sistema?>;

function calcArqueo(esperado){
  const ef = parseFloat(document.getElementById('efectivoInput').value)||0;
  const div = document.getElementById('liveArqueo');
  const lbl = document.getElementById('laLabel');
  const val = document.getElementById('laVal');
  const ico = document.getElementById('laIcon');
  if(!ef){ div.style.display='none'; return; }
  div.style.display='flex';
  const diff = +(ef - esperado).toFixed(2);
  div.className='live-arqueo';
  if(diff===0){
    div.classList.add('ok');
    lbl.textContent='¡Cuadre perfecto!'; ico.textContent='✅';
    val.className='la-val ok'; val.textContent=dinero(0);
  } else if(diff>0){
    div.classList.add('exceso');
    lbl.textContent='Tienes de más'; ico.textContent='💰';
    val.className='la-val exceso'; val.textContent='+'+dinero(diff);
  } else {
    div.classList.add('faltante');
    lbl.textContent='Te falta en caja'; ico.textContent='⚠️';
    val.className='la-val faltante'; val.textContent='-'+dinero(Math.abs(diff));
  }
}
function dinero(n){ return '$'+parseFloat(n).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2}); }
</script>

<?php layoutEnd(); ?>