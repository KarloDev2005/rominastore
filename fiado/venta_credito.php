<?php

require_once '../includes/config.php';
requerirAutenticacion();

if (!isset($_SESSION['carrito_credito'])) $_SESSION['carrito_credito'] = [];
$cliente_id   = $_SESSION['cliente_credito'] ?? 0;
$cliente_nombre = '';

if ($cliente_id) {
    $s = $conn->prepare("SELECT nombre FROM clientes WHERE id_cliente=?");
    $s->bind_param("i", $cliente_id);
    $s->execute();
    $s->bind_result($cliente_nombre);
    $s->fetch();
    $s->close();
}

$error = $exito = '';

/* Seleccionar cliente via GET */
if (isset($_GET['cliente'])) {
    $cid = (int)$_GET['cliente'];
    if ($cid > 0) {
        $_SESSION['cliente_credito'] = $cid;
        header('Location: venta_credito.php'); exit;
    }
}

/* Cambiar cliente */
if (isset($_GET['cambiar_cliente'])) {
    unset($_SESSION['cliente_credito']);
    header('Location: venta_credito.php'); exit;
}

/* Ajustar cantidad por GET */
if (isset($_GET['qty_id'], $_GET['qty_n'])) {
    $id = (int)$_GET['qty_id'];
    $n  = (int)$_GET['qty_n'];
    if (isset($_SESSION['carrito_credito'][$id])) {
        if ($n <= 0) unset($_SESSION['carrito_credito'][$id]);
        else {
            $st = $conn->prepare("SELECT stock FROM productos WHERE id_producto=?");
            $st->bind_param("i",$id); $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $_SESSION['carrito_credito'][$id]['cantidad'] = $row ? min($n,$row['stock']) : $n;
        }
    }
    header('Location: venta_credito.php'); exit;
}

/* Vaciar */
if (isset($_GET['vaciar'])) {
    $_SESSION['carrito_credito'] = [];
    header('Location: venta_credito.php'); exit;
}

/* Agregar producto — FIX: input hidden name="agregar" */
if (isset($_POST['agregar'])) {
    $id  = (int)$_POST['id_producto'];
    $qty = max(1,(int)$_POST['cantidad']);
    if ($id > 0) {
        $sp = $conn->prepare("SELECT id_producto,nombre,precio,stock FROM productos WHERE id_producto=?");
        $sp->bind_param("i",$id); $sp->execute();
        $p  = $sp->get_result()->fetch_assoc();
        if ($p) {
            $ya   = $_SESSION['carrito_credito'][$id]['cantidad'] ?? 0;
            $total_d = $ya + $qty;
            if ($total_d > $p['stock']) {
                $error = "Stock insuficiente para «{$p['nombre']}» (disponible: {$p['stock']}).";
            } else {
                $_SESSION['carrito_credito'][$id] = [
                    'id'=>$p['id_producto'],'nombre'=>$p['nombre'],
                    'precio'=>(float)$p['precio'],'cantidad'=>$total_d,
                ];
                $exito = "✓ «{$p['nombre']}» agregado.";
            }
        } else { $error = "Producto no encontrado."; }
    }
}

$total = 0; $total_pzas = 0;
foreach ($_SESSION['carrito_credito'] as $item) {
    $total      += $item['precio'] * $item['cantidad'];
    $total_pzas += $item['cantidad'];
}

$grid = $conn->query("SELECT id_producto,nombre,precio,stock FROM productos WHERE stock>0 ORDER BY nombre LIMIT 60");

layoutStart('Ventas a Crédito', 'credito', [['label'=>'Ventas a Crédito']]);
?>

<style>
/* Mismo estilo POS reutilizado */
.pos-wrap{display:grid;grid-template-columns:1fr 320px;gap:1.1rem;align-items:start}
.search-wrap{position:relative;margin-bottom:1rem}
.search-wrap input{width:100%;padding:.75rem 1rem .75rem 2.8rem;border:2px solid var(--gris-300);border-radius:10px;font-family:var(--fuente);font-size:.95rem;background:var(--blanco);color:var(--gris-900);outline:none;transition:var(--trans)}
.search-wrap input:focus{border-color:var(--verde);box-shadow:0 0 0 4px rgba(22,163,74,.1)}
.search-wrap .si{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--gris-400);pointer-events:none}
#ac2{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--blanco);border:1.5px solid var(--gris-200);border-radius:10px;box-shadow:var(--sombra-md);z-index:200;max-height:280px;overflow-y:auto;display:none}
.ac-item{display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;cursor:pointer;border-bottom:1px solid var(--gris-100);gap:.75rem;transition:background .12s}
.ac-item:hover{background:var(--verde-bg)}
.ac-item:last-child{border-bottom:none}
.ac-nombre{font-weight:700;font-size:.85rem;color:var(--gris-900)}
.ac-info{font-size:.73rem;color:var(--gris-400)}
.ac-precio{font-family:var(--mono);font-weight:700;font-size:.9rem;color:var(--verde);white-space:nowrap}
.sec-titulo{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;color:var(--gris-400);margin-bottom:.6rem;display:flex;align-items:center;gap:.5rem}
.sec-titulo::after{content:'';flex:1;height:1px;background:var(--gris-200)}
.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.65rem}
.prod-card{background:var(--blanco);border:1.5px solid var(--gris-200);border-radius:11px;padding:.85rem .8rem;cursor:pointer;transition:var(--trans);position:relative;display:flex;flex-direction:column;gap:.3rem}
.prod-card:hover{border-color:var(--verde);transform:translateY(-2px);box-shadow:0 6px 20px rgba(22,163,74,.14)}
.prod-card.agotado{opacity:.45;cursor:not-allowed;pointer-events:none}
.prod-nombre{font-size:.83rem;font-weight:800;color:var(--gris-900);line-height:1.3;margin-top:.4rem}
.prod-precio{font-family:var(--mono);font-size:.95rem;font-weight:700;color:var(--verde)}
.prod-stock{position:absolute;top:.45rem;right:.45rem;font-size:.63rem;font-weight:800;padding:.15rem .45rem;border-radius:99px}
.ps-ok{background:var(--verde-bg);color:var(--verde)}
.ps-bajo{background:var(--naranja-bg);color:var(--naranja)}
.prod-add{margin-top:auto;padding:.35rem;background:var(--verde-bg);color:var(--verde);border:none;border-radius:6px;font-size:.75rem;font-weight:800;cursor:pointer;font-family:var(--fuente);transition:var(--trans);width:100%}
.prod-card:hover .prod-add{background:var(--verde);color:#fff}

/* Carrito crédito */
.carrito-wrap{background:var(--blanco);border-radius:var(--card-radio);border:1.5px solid var(--gris-200);box-shadow:var(--sombra-md);position:sticky;top:calc(56px + 1.1rem);display:flex;flex-direction:column;max-height:calc(100vh - 80px);overflow:hidden}
.car-header{background:var(--gris-900);color:#fff;padding:.85rem 1rem;display:flex;align-items:center;justify-content:space-between}
.car-header h3{font-size:.88rem;font-weight:800}
.car-cnt{background:var(--naranja);color:#fff;font-size:.68rem;font-weight:800;padding:.15rem .55rem;border-radius:99px}
.car-body{flex:1;overflow-y:auto;padding:.6rem}
.car-vacio{padding:2rem .5rem;text-align:center;color:var(--gris-400)}
.car-vacio .cv-icon{font-size:1.8rem;margin-bottom:.5rem}
.car-vacio p{font-size:.8rem}
.car-item{background:var(--gris-50);border:1px solid var(--gris-100);border-radius:8px;padding:.6rem .75rem;margin-bottom:.45rem}
.ci-nombre{font-size:.82rem;font-weight:800;color:var(--gris-900);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:.3rem}
.ci-row{display:flex;align-items:center;justify-content:space-between}
.ci-qty{display:flex;align-items:center;gap:.3rem}
.qb{width:22px;height:22px;border:none;background:var(--gris-200);border-radius:5px;cursor:pointer;font-size:.85rem;font-weight:700;display:flex;align-items:center;justify-content:center;color:var(--gris-700);transition:var(--trans);text-decoration:none;line-height:1}
.qb:hover{background:var(--verde);color:#fff}
.qb.del:hover{background:var(--rojo);color:#fff}
.qv{font-family:var(--mono);font-size:.82rem;font-weight:700;min-width:22px;text-align:center;color:var(--gris-900)}
.ci-sub{font-family:var(--mono);font-size:.88rem;font-weight:700;color:var(--verde)}
.car-footer{border-top:1px solid var(--gris-100);padding:.85rem 1rem;background:var(--blanco)}
.cf-linea{display:flex;justify-content:space-between;font-size:.78rem;color:var(--gris-500);margin-bottom:.2rem}
.cf-total{display:flex;justify-content:space-between;align-items:center;border-top:2px solid var(--gris-900);margin-top:.45rem;padding-top:.5rem}
.cf-total .lbl{font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--gris-700)}
.cf-total .monto{font-family:var(--mono);font-size:1.45rem;font-weight:900;color:var(--gris-900)}
.btn-cobrar{display:flex;align-items:center;justify-content:center;gap:.4rem;width:100%;padding:.75rem;margin-top:.65rem;background:linear-gradient(135deg,var(--naranja),#b45309);color:#fff;border:none;border-radius:10px;font-family:var(--fuente);font-size:.92rem;font-weight:800;cursor:pointer;text-decoration:none;box-shadow:0 4px 16px rgba(217,119,6,.3);transition:var(--trans)}
.btn-cobrar:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(217,119,6,.4)}
.btn-cobrar:disabled,.btn-cobrar.disabled{opacity:.5;cursor:not-allowed;pointer-events:none}
.link-vaciar{display:block;text-align:center;font-size:.72rem;color:var(--gris-400);margin-top:.5rem;cursor:pointer;text-decoration:none;transition:color .15s}
.link-vaciar:hover{color:var(--rojo)}

/* Banner cliente */
.cliente-banner{
  display:flex;align-items:center;gap:.75rem;
  background:var(--azul-bg);border:1px solid var(--azul-borde);border-radius:10px;
  padding:.85rem 1rem;margin-bottom:1rem;
}
.cb-icon{font-size:1.3rem}
.cb-info{flex:1;line-height:1.3}
.cb-nombre{font-size:.9rem;font-weight:800;color:var(--gris-900)}
.cb-label{font-size:.73rem;color:var(--gris-500)}
.cb-actions{display:flex;gap:.4rem}

/* WSP badge */
.wsp-notice{display:flex;align-items:center;gap:.5rem;font-size:.73rem;color:#166534;background:var(--verde-bg);padding:.5rem .75rem;border-radius:7px;margin-top:.5rem;font-weight:600}

@media(max-width:860px){.pos-wrap{grid-template-columns:1fr}.carrito-wrap{position:static;max-height:none}}
@media(max-width:480px){.prod-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<?php if($error): ?><div class="alerta alerta-error"><?=e($error)?></div><?php endif ?>
<?php if($exito): ?><div class="alerta alerta-exito"><?=e($exito)?></div><?php endif ?>

<!-- Banner cliente seleccionado -->
<div class="cliente-banner">
  <span class="cb-icon"><?=$cliente_id?'👤':'❓'?></span>
  <div class="cb-info">
    <?php if($cliente_id): ?>
      <div class="cb-nombre"><?=e($cliente_nombre)?></div>
      <div class="cb-label">Cliente seleccionado para esta venta a crédito</div>
    <?php else: ?>
      <div class="cb-nombre" style="color:var(--rojo)">Sin cliente asignado</div>
      <div class="cb-label">Selecciona un cliente antes de registrar el fiado</div>
    <?php endif ?>
  </div>
  <div class="cb-actions">
    <?php if($cliente_id): ?>
      <a href="?cambiar_cliente=1" class="btn btn-sm btn-gris">↩ Cambiar</a>
    <?php else: ?>
      <a href="../clientes/listar.php?select=1" class="btn btn-sm btn-azul">Seleccionar cliente →</a>
    <?php endif ?>
  </div>
</div>

<div class="pos-wrap">

  <!-- Productos -->
  <div>
    <div class="search-wrap">
      <span class="si">🔍</span>
      <input type="text" id="buscarInput2" placeholder="Buscar producto…" autocomplete="off">
      <div id="ac2"></div>
    </div>
    <p class="sec-titulo">Productos (<?=$grid->num_rows?>)</p>
    <div class="prod-grid" id="prodGrid2">
      <?php
      $rows_grid=[];
      while($p=$grid->fetch_assoc()) $rows_grid[]=$p;
      foreach($rows_grid as $p):
        $sc=$p['stock']>10?'ps-ok':'ps-bajo';
      ?>
      <div class="prod-card <?=$p['stock']==0?'agotado':''?>"
           data-id="<?=$p['id_producto']?>"
           data-nombre="<?=e($p['nombre'])?>"
           data-precio="<?=$p['precio']?>"
           data-stock="<?=$p['stock']?>"
           onclick="agregarDesdeGrid2(this)">
        <span class="prod-stock <?=$sc?>"><?=$p['stock']?>u</span>
        <div class="prod-nombre"><?=e($p['nombre'])?></div>
        <div class="prod-precio"><?=dinero($p['precio'])?></div>
        <button class="prod-add" type="button">+ Agregar</button>
      </div>
      <?php endforeach ?>
    </div>
  </div>

  <!-- Carrito de crédito -->
  <div class="carrito-wrap">
    <div class="car-header">
      <h3>💳 Carrito Crédito</h3>
      <span class="car-cnt"><?=$total_pzas?></span>
    </div>
    <div class="car-body">
      <?php if(empty($_SESSION['carrito_credito'])): ?>
        <div class="car-vacio">
          <div class="cv-icon">💳</div>
          <p>Carrito vacío.<br>Agrega productos y asigna un cliente.</p>
        </div>
      <?php else: ?>
        <?php foreach($_SESSION['carrito_credito'] as $id => $item): $q=$item['cantidad']; ?>
        <div class="car-item">
          <div class="ci-nombre" title="<?=e($item['nombre'])?>"><?=e($item['nombre'])?></div>
          <div class="ci-row">
            <div class="ci-qty">
              <a href="?qty_id=<?=$id?>&qty_n=<?=max(0,$q-1)?>" class="qb del"><?=$q<=1?'✕':'−'?></a>
              <span class="qv"><?=$q?></span>
              <a href="?qty_id=<?=$id?>&qty_n=<?=$q+1?>" class="qb">+</a>
            </div>
            <div class="ci-sub"><?=dinero($item['precio']*$q)?></div>
          </div>
        </div>
        <?php endforeach ?>
      <?php endif ?>
    </div>

    <?php if(!empty($_SESSION['carrito_credito'])): ?>
    <div class="car-footer">
      <div class="cf-linea"><span>Artículos</span><span><?=$total_pzas?> pzas</span></div>
      <div class="cf-total">
        <span class="lbl">Total Fiado</span>
        <span class="monto"><?=dinero($total)?></span>
      </div>

      <?php if($cliente_id): ?>
        <a href="procesar_credito.php" class="btn-cobrar">💳 Registrar Fiado <?=dinero($total)?></a>
        <div class="wsp-notice">📱 Se enviará notificación por WhatsApp</div>
      <?php else: ?>
        <div class="alerta alerta-aviso" style="margin-top:.6rem;font-size:.78rem">⚠ Selecciona un cliente para continuar</div>
        <a href="../clientes/listar.php?select=1" class="btn btn-azul btn-block" style="margin-top:.5rem">👥 Seleccionar cliente</a>
      <?php endif ?>

      <a href="?vaciar=1" class="link-vaciar" onclick="return confirm('¿Vaciar el carrito?')">🗑 Vaciar carrito</a>
    </div>
    <?php endif ?>
  </div>

</div>

<!-- Formulario oculto — FIX: input hidden name="agregar" -->
<form method="POST" id="fAgregar2" style="display:none">
  <input type="hidden" name="agregar" value="1">
  <input type="hidden" name="id_producto" id="fId2">
  <input type="hidden" name="cantidad"    id="fQty2" value="1">
</form>

<script>
function agregarProducto2(id,qty=1){
  document.getElementById('fId2').value=id;
  document.getElementById('fQty2').value=qty;
  document.getElementById('fAgregar2').submit();
}
function agregarDesdeGrid2(card){
  if(card.classList.contains('agotado')) return;
  if(!card.dataset.stock||parseInt(card.dataset.stock)===0) return;
  card.style.opacity='.6';
  agregarProducto2(card.dataset.id,1);
}

const inp2=document.getElementById('buscarInput2');
const ac2=document.getElementById('ac2');
let tmr2;
inp2.addEventListener('input',()=>{
  const q=inp2.value.trim();
  document.querySelectorAll('#prodGrid2 .prod-card').forEach(c=>{
    c.style.display=!q||c.dataset.nombre.toLowerCase().includes(q.toLowerCase())?'':'none';
  });
  clearTimeout(tmr2);
  if(q.length<2){ac2.style.display='none';return;}
  tmr2=setTimeout(()=>{
    fetch('../ventas/buscar_productos.php?q='+encodeURIComponent(q))
      .then(r=>r.json())
      .then(data=>{
        if(!data.length){ac2.style.display='none';return;}
        ac2.innerHTML=data.map(p=>`
          <div class="ac-item" onclick="agregarProducto2(${p.id_producto},1);this.closest('#ac2').style.display='none'">
            <div><div class="ac-nombre">${p.nombre}</div><div class="ac-info">Stock: ${p.stock}</div></div>
            <div class="ac-precio">$${parseFloat(p.precio).toFixed(2)}</div>
          </div>`).join('');
        ac2.style.display='block';
      });
  },220);
});
document.addEventListener('click',e=>{if(!e.target.closest('.search-wrap')) ac2.style.display='none';});
</script>

<?php layoutEnd(); ?>