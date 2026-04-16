<?php
/* ═══════════════════════════════════════════════════
   ventas/nueva_venta.php — Punto de Venta (POS)

   BUG CORREGIDO: form.submit() NO envía el name del botón.
   Solución: input hidden name="agregar" value="1" siempre presente.

   FUNCIONALIDADES NUEVAS:
   - Búsqueda con autocomplete
   - Editar cantidad con +/- por GET (sin ambigüedad de formularios)
   - Calcular cambio / efectivo recibido
   - Stock re-verificado en tiempo real
   ═══════════════════════════════════════════════════ */

require_once '../includes/config.php';
requerirAutenticacion();

if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

$error = '';
$exito = '';

/* ─── Ajustar cantidad por GET (?qty_id=X&qty_n=N) ─── */
if (isset($_GET['qty_id'], $_GET['qty_n'])) {
    $id  = (int)$_GET['qty_id'];
    $n   = (int)$_GET['qty_n'];
    if (isset($_SESSION['carrito'][$id])) {
        if ($n <= 0) {
            unset($_SESSION['carrito'][$id]);
        } else {
            $stmt = $conn->prepare("SELECT stock FROM productos WHERE id_producto=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && $n > $row['stock']) {
                $n = $row['stock'];
            }
            $_SESSION['carrito'][$id]['cantidad'] = $n;
        }
    }
    header('Location: nueva_venta.php');
    exit();
}

/* ─── Eliminar del carrito (?eliminar=ID) ─── */
if (isset($_GET['eliminar'])) {
    unset($_SESSION['carrito'][(int)$_GET['eliminar']]);
    header('Location: nueva_venta.php');
    exit();
}

/* ─── Vaciar carrito ─── */
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito'] = [];
    header('Location: nueva_venta.php');
    exit();
}

/* ─── Agregar producto (POST)
   FIX: el form tiene <input hidden name="agregar" value="1">
   form.submit() de JS NO envía el name de los botones.
   ─────────────────────────────────────────────────────── */
if (isset($_POST['agregar'])) {
    $id       = (int)$_POST['id_producto'];
    $cantidad = max(1, (int)$_POST['cantidad']);

    if ($id > 0) {
        $stmt = $conn->prepare("SELECT id_producto, nombre, precio, stock FROM productos WHERE id_producto=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();

        if ($p) {
            $ya_en_carrito  = $_SESSION['carrito'][$id]['cantidad'] ?? 0;
            $total_deseado  = $ya_en_carrito + $cantidad;
            if ($total_deseado > $p['stock']) {
                $error = "Stock insuficiente para «{$p['nombre']}» (disponible: {$p['stock']}).";
            } else {
                $_SESSION['carrito'][$id] = [
                    'id'       => $p['id_producto'],
                    'nombre'   => $p['nombre'],
                    'precio'   => (float)$p['precio'],
                    'cantidad' => $total_deseado,
                ];
                $exito = "✓ «{$p['nombre']}» agregado.";
            }
        } else {
            $error = "Producto no encontrado.";
        }
    }
}

/* ─── Totales ─── */
$total = 0;
$total_pzas = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total      += $item['precio'] * $item['cantidad'];
    $total_pzas += $item['cantidad'];
}

/* ─── Productos para la grilla ─── */
$sql_grid = "SELECT id_producto, nombre, precio, stock
             FROM productos WHERE stock > 0 ORDER BY nombre LIMIT 60";
$grid = $conn->query($sql_grid);

layoutStart('Punto de Venta', 'pos', [['label' => 'Punto de Venta']]);
?>

<style>
/* ── Layout POS ── */
.pos-wrap{display:grid;grid-template-columns:1fr 320px;gap:1.1rem;align-items:start}

/* ── Búsqueda ── */
.search-wrap{position:relative;margin-bottom:1rem}
.search-wrap input{
  width:100%;padding:.75rem 1rem .75rem 2.8rem;
  border:2px solid var(--gris-300);border-radius:10px;
  font-family:var(--fuente);font-size:.95rem;background:var(--blanco);
  color:var(--gris-900);outline:none;transition:var(--trans);
}
.search-wrap input:focus{border-color:var(--verde);box-shadow:0 0 0 4px rgba(22,163,74,.1)}
.search-wrap .si{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--gris-400);font-size:.95rem;pointer-events:none}

/* Autocomplete */
#ac{
  position:absolute;top:calc(100% + 4px);left:0;right:0;
  background:var(--blanco);border:1.5px solid var(--gris-200);
  border-radius:10px;box-shadow:var(--sombra-md);
  z-index:200;max-height:300px;overflow-y:auto;display:none;
}
.ac-item{
  display:flex;align-items:center;justify-content:space-between;
  padding:.65rem 1rem;cursor:pointer;border-bottom:1px solid var(--gris-100);
  gap:.75rem;transition:background .12s;
}
.ac-item:last-child{border-bottom:none}
.ac-item:hover{background:var(--verde-bg)}
.ac-nombre{font-weight:700;font-size:.85rem;color:var(--gris-900)}
.ac-info{font-size:.73rem;color:var(--gris-400)}
.ac-precio{font-family:var(--mono);font-weight:700;font-size:.9rem;color:var(--verde);white-space:nowrap}

/* Grilla de productos */
.sec-titulo{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--gris-400);margin-bottom:.6rem;display:flex;align-items:center;gap:.5rem}
.sec-titulo::after{content:'';flex:1;height:1px;background:var(--gris-200)}
.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.65rem}

.prod-card{
  background:var(--blanco);border:1.5px solid var(--gris-200);border-radius:11px;
  padding:.85rem .8rem;cursor:pointer;transition:var(--trans);position:relative;
  display:flex;flex-direction:column;gap:.3rem;user-select:none;
}
.prod-card:hover{border-color:var(--verde);transform:translateY(-2px);box-shadow:0 6px 20px rgba(22,163,74,.14)}
.prod-card:active{transform:translateY(0)}
.prod-card.agotado{opacity:.45;cursor:not-allowed;pointer-events:none}

.prod-nombre{font-size:.83rem;font-weight:800;color:var(--gris-900);line-height:1.3;margin-top:.4rem}
.prod-precio{font-family:var(--mono);font-size:.95rem;font-weight:700;color:var(--verde)}
.prod-stock{position:absolute;top:.45rem;right:.45rem;font-size:.63rem;font-weight:800;padding:.15rem .45rem;border-radius:99px}
.ps-ok  {background:var(--verde-bg);color:var(--verde)}
.ps-bajo{background:var(--naranja-bg);color:var(--naranja)}
.ps-cero{background:var(--rojo-bg);color:var(--rojo)}
.prod-add{
  margin-top:auto;padding:.35rem;background:var(--verde-bg);color:var(--verde);
  border:none;border-radius:6px;font-size:.75rem;font-weight:800;
  cursor:pointer;font-family:var(--fuente);transition:var(--trans);width:100%;
}
.prod-card:hover .prod-add{background:var(--verde);color:#fff}

/* ── Carrito ── */
.carrito-wrap{
  background:var(--blanco);border-radius:var(--card-radio);
  border:1.5px solid var(--gris-200);box-shadow:var(--sombra-md);
  position:sticky;top:calc(56px + 1.1rem);
  display:flex;flex-direction:column;max-height:calc(100vh - 80px);overflow:hidden;
}
.car-header{
  background:var(--gris-900);color:#fff;padding:.85rem 1rem;
  display:flex;align-items:center;justify-content:space-between;
}
.car-header h3{font-size:.88rem;font-weight:800}
.car-cnt{background:var(--verde);color:#fff;font-size:.68rem;font-weight:800;padding:.15rem .55rem;border-radius:99px}

.car-body{flex:1;overflow-y:auto;padding:.6rem}
.car-vacio{padding:2rem .5rem;text-align:center;color:var(--gris-400)}
.car-vacio .cv-icon{font-size:1.8rem;margin-bottom:.5rem}
.car-vacio p{font-size:.8rem}

.car-item{
  background:var(--gris-50);border:1px solid var(--gris-100);
  border-radius:8px;padding:.6rem .75rem;margin-bottom:.45rem;
  animation:slideIn .18s ease;
}
@keyframes slideIn{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
.ci-nombre{font-size:.82rem;font-weight:800;color:var(--gris-900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.3rem}
.ci-row{display:flex;align-items:center;justify-content:space-between}
.ci-qty{display:flex;align-items:center;gap:.3rem}
.qb{
  width:22px;height:22px;border:none;background:var(--gris-200);
  border-radius:5px;cursor:pointer;font-size:.85rem;font-weight:700;
  display:flex;align-items:center;justify-content:center;color:var(--gris-700);
  transition:var(--trans);text-decoration:none;line-height:1;
}
.qb:hover{background:var(--verde);color:#fff}
.qb.del:hover{background:var(--rojo);color:#fff}
.qv{font-family:var(--mono);font-size:.82rem;font-weight:700;min-width:22px;text-align:center;color:var(--gris-900)}
.ci-sub{font-family:var(--mono);font-size:.88rem;font-weight:700;color:var(--verde)}

/* Footer carrito */
.car-footer{border-top:1px solid var(--gris-100);padding:.85rem 1rem;background:var(--blanco)}
.cf-linea{display:flex;justify-content:space-between;font-size:.78rem;color:var(--gris-500);margin-bottom:.2rem}
.cf-total{display:flex;justify-content:space-between;align-items:center;border-top:2px solid var(--gris-900);margin-top:.45rem;padding-top:.5rem}
.cf-total .lbl{font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--gris-700)}
.cf-total .monto{font-family:var(--mono);font-size:1.45rem;font-weight:900;color:var(--gris-900)}
.btn-cobrar{
  display:flex;align-items:center;justify-content:center;gap:.4rem;
  width:100%;padding:.75rem;margin-top:.65rem;
  background:linear-gradient(135deg,var(--verde),var(--verde-h));
  color:#fff;border:none;border-radius:10px;
  font-family:var(--fuente);font-size:.92rem;font-weight:800;
  cursor:pointer;text-decoration:none;
  box-shadow:var(--sombra-verde);transition:var(--trans);
}
.btn-cobrar:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(22,163,74,.4)}

/* Efectivo / cambio */
.efectivo-box{margin-top:.7rem;border-top:1px dashed var(--gris-200);padding-top:.65rem}
.efectivo-box label{font-size:.72rem;font-weight:700;color:var(--gris-600);display:block;margin-bottom:.3rem}
.efectivo-input{
  width:100%;padding:.5rem .75rem;font-family:var(--mono);font-size:.95rem;font-weight:700;
  border:1.5px solid var(--gris-300);border-radius:8px;outline:none;
  color:var(--gris-900);transition:var(--trans);background:var(--blanco);
}
.efectivo-input:focus{border-color:var(--azul);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.cambio-row{display:flex;justify-content:space-between;align-items:center;margin-top:.4rem;min-height:22px}
.cambio-label{font-size:.75rem;font-weight:700;color:var(--gris-500)}
.cambio-valor{font-family:var(--mono);font-size:.95rem;font-weight:800;color:var(--azul)}
.cambio-falta{color:var(--rojo) !important}

.link-vaciar{display:block;text-align:center;font-size:.72rem;color:var(--gris-400);margin-top:.5rem;cursor:pointer;text-decoration:none;transition:color .15s}
.link-vaciar:hover{color:var(--rojo)}

/* Responsive */
@media(max-width:860px){
  .pos-wrap{grid-template-columns:1fr}
  .carrito-wrap{position:static;max-height:none}
}
@media(max-width:480px){
  .prod-grid{grid-template-columns:repeat(2,1fr)}
}
</style>

<?php if($error): ?><div class="alerta alerta-error"><?=e($error)?></div><?php endif ?>
<?php if($exito): ?><div class="alerta alerta-exito"><?=e($exito)?></div><?php endif ?>

<div class="pos-wrap">

  <!-- ── PANEL PRODUCTOS ── -->
  <div>

    <!-- Búsqueda -->
    <div class="search-wrap">
      <span class="si">🔍</span>
      <input type="text" id="buscarInput" placeholder="Buscar producto…" autocomplete="off">
      <div id="ac"></div>
    </div>

    <p class="sec-titulo">Productos (<?=$grid->num_rows?>)</p>

    <div class="prod-grid" id="prodGrid">
      <?php
      $rows_grid = [];
      while ($p = $grid->fetch_assoc()) $rows_grid[] = $p;
      foreach ($rows_grid as $p):
        $sc = $p['stock'] > 10 ? 'ps-ok' : ($p['stock'] > 0 ? 'ps-bajo' : 'ps-cero');
      ?>
      <div class="prod-card <?=$p['stock']==0?'agotado':''?>"
           data-id="<?=$p['id_producto']?>"
           data-nombre="<?=e($p['nombre'])?>"
           data-precio="<?=$p['precio']?>"
           data-stock="<?=$p['stock']?>"
           onclick="agregarDesdeGrid(this)">
        <span class="prod-stock <?=$sc?>"><?=$p['stock']?>u</span>
        <div class="prod-nombre"><?=e($p['nombre'])?></div>
        <div class="prod-precio"><?=dinero($p['precio'])?></div>
        <button class="prod-add" type="button">+ Agregar</button>
      </div>
      <?php endforeach ?>
    </div>

  </div>

  <!-- ── CARRITO ── -->
  <div class="carrito-wrap">
    <div class="car-header">
      <h3>🛒 Carrito</h3>
      <span class="car-cnt"><?=$total_pzas?></span>
    </div>

    <div class="car-body">
      <?php if(empty($_SESSION['carrito'])): ?>
        <div class="car-vacio">
          <div class="cv-icon">🛒</div>
          <p>Carrito vacío.<br>Haz clic en un producto para agregar.</p>
        </div>
      <?php else: ?>
        <?php foreach($_SESSION['carrito'] as $id => $item):
          $q = $item['cantidad'];
        ?>
        <div class="car-item">
          <div class="ci-nombre" title="<?=e($item['nombre'])?>"><?=e($item['nombre'])?></div>
          <div class="ci-row">
            <div class="ci-qty">
              <!-- − (cantidad - 1) -->
              <a href="?qty_id=<?=$id?>&qty_n=<?=max(0,$q-1)?>"
                 class="qb del" title="<?=$q<=1?'Eliminar':'Reducir'?>">
                <?=$q<=1?'✕':'−'?>
              </a>
              <span class="qv"><?=$q?></span>
              <!-- + (cantidad + 1) -->
              <a href="?qty_id=<?=$id?>&qty_n=<?=$q+1?>"
                 class="qb" title="Aumentar">+</a>
            </div>
            <div class="ci-sub"><?=dinero($item['precio']*$q)?></div>
          </div>
        </div>
        <?php endforeach ?>
      <?php endif ?>
    </div>

    <?php if(!empty($_SESSION['carrito'])): ?>
    <div class="car-footer">
      <div class="cf-linea"><span>Artículos</span><span><?=$total_pzas?> pzas</span></div>
      <div class="cf-total">
        <span class="lbl">Total</span>
        <span class="monto" id="totalMonto"><?=dinero($total)?></span>
      </div>

      <!-- Efectivo y cambio -->
      <div class="efectivo-box">
        <label for="efectivo">Efectivo recibido</label>
        <input type="number" id="efectivo" class="efectivo-input"
               placeholder="<?=dinero($total)?>" step="0.01" min="0"
               oninput="calcCambio(<?=$total?>)">
        <div class="cambio-row">
          <span class="cambio-label" id="cambioLabel">Cambio</span>
          <span class="cambio-valor" id="cambioValor">—</span>
        </div>
      </div>

      <a href="procesar_venta.php" class="btn-cobrar">
        ✓ Cobrar <?=dinero($total)?>
      </a>

      <a href="?vaciar=1" class="link-vaciar"
         onclick="return confirm('¿Vaciar el carrito?')">🗑 Vaciar carrito</a>
    </div>
    <?php endif ?>
  </div>

</div>

<!-- Formulario oculto para agregar — BUG FIX: name="agregar" en hidden input, NO en botón -->
<form method="POST" id="fAgregar" style="display:none">
  <input type="hidden" name="agregar" value="1"><!-- ← FIX CRÍTICO -->
  <input type="hidden" name="id_producto" id="fId">
  <input type="hidden" name="cantidad"    id="fQty" value="1">
</form>

<script>
/* ── Agregar producto ── */
function agregarProducto(id, qty=1){
  document.getElementById('fId').value  = id;
  document.getElementById('fQty').value = qty;
  document.getElementById('fAgregar').submit();
}

function agregarDesdeGrid(card){
  if(card.classList.contains('agotado')) return;
  const stock = parseInt(card.dataset.stock);
  if(stock===0) return;
  card.style.opacity='.6';
  agregarProducto(card.dataset.id, 1);
}

/* ── Autocomplete de búsqueda ── */
const inp = document.getElementById('buscarInput');
const ac  = document.getElementById('ac');
let tmr;

inp.addEventListener('input', ()=>{
  const q = inp.value.trim();
  // Filtrar grilla en tiempo real
  document.querySelectorAll('#prodGrid .prod-card').forEach(c=>{
    c.style.display = !q || c.dataset.nombre.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
  // Autocomplete para resultados exactos
  clearTimeout(tmr);
  if(q.length < 2){ ac.style.display='none'; return; }
  tmr = setTimeout(()=>{
    fetch('buscar_productos.php?q='+encodeURIComponent(q))
      .then(r=>r.json())
      .then(data=>{
        if(!data.length){ ac.style.display='none'; return; }
        ac.innerHTML = data.map(p=>`
          <div class="ac-item" onclick="agregarProducto(${p.id_producto},1); this.closest('#ac').style.display='none'">
            <div>
              <div class="ac-nombre">${p.nombre}</div>
              <div class="ac-info">Stock: ${p.stock}</div>
            </div>
            <div class="ac-precio">$${parseFloat(p.precio).toFixed(2)}</div>
          </div>`).join('');
        ac.style.display='block';
      });
  }, 220);
});

document.addEventListener('click', e=>{ if(!e.target.closest('.search-wrap')) ac.style.display='none'; });

/* ── Calcular cambio ── */
function calcCambio(total){
  const ef  = parseFloat(document.getElementById('efectivo').value)||0;
  const lbl = document.getElementById('cambioLabel');
  const val = document.getElementById('cambioValor');
  if(ef<=0){ lbl.textContent='Cambio'; val.textContent='—'; val.className='cambio-valor'; return; }
  const diff = ef - total;
  if(diff < 0){
    lbl.textContent = 'Falta';
    val.textContent = '$'+Math.abs(diff).toFixed(2);
    val.className = 'cambio-valor cambio-falta';
  } else {
    lbl.textContent = 'Cambio';
    val.textContent = '$'+diff.toFixed(2);
    val.className = 'cambio-valor';
  }
}
</script>

<?php layoutEnd(); ?>