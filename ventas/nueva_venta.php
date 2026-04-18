<?php
/* ventas/nueva_venta.php — POS con imágenes, toast "pop", lightbox */
require_once '../includes/config.php';
requerirAutenticacion();

if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

$error = ''; $exito = '';

/* Ajustar cantidad por GET */
if (isset($_GET['qty_id'], $_GET['qty_n'])) {
    $id = (int)$_GET['qty_id']; $n = (int)$_GET['qty_n'];
    if (isset($_SESSION['carrito'][$id])) {
        if ($n <= 0) unset($_SESSION['carrito'][$id]);
        else {
            $st = $conn->prepare("SELECT stock FROM productos WHERE id_producto=?");
            $st->bind_param("i",$id); $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $_SESSION['carrito'][$id]['cantidad'] = $row ? min($n,$row['stock']) : $n;
        }
    }
    header('Location: nueva_venta.php'); exit;
}

if (isset($_GET['eliminar'])) { unset($_SESSION['carrito'][(int)$_GET['eliminar']]); header('Location: nueva_venta.php'); exit; }
if (isset($_GET['vaciar']))   { $_SESSION['carrito'] = []; header('Location: nueva_venta.php'); exit; }

/* Agregar — FIX: hidden input name="agregar" */
if (isset($_POST['agregar'])) {
    $id  = (int)$_POST['id_producto'];
    $qty = max(1,(int)$_POST['cantidad']);
    if ($id > 0) {
        $sp = $conn->prepare("SELECT id_producto,nombre,precio,stock,imagen FROM productos WHERE id_producto=?");
        $sp->bind_param("i",$id); $sp->execute();
        $p = $sp->get_result()->fetch_assoc();
        if ($p) {
            $ya = $_SESSION['carrito'][$id]['cantidad'] ?? 0;
            if ($ya + $qty > $p['stock']) {
                $error = "Stock insuficiente para «{$p['nombre']}» (máx: {$p['stock']}).";
            } else {
                $_SESSION['carrito'][$id] = [
                    'id'=>$p['id_producto'],'nombre'=>$p['nombre'],
                    'precio'=>(float)$p['precio'],'cantidad'=>$ya+$qty,
                    'imagen'=>$p['imagen'],
                ];
                /* Flag para microinteracción JS */
                $_SESSION['ultimo_agregado'] = $p['nombre'];
            }
        } else { $error = "Producto no encontrado."; }
    }
}

$total=0; $total_pzas=0;
foreach ($_SESSION['carrito'] as $it) { $total+=$it['precio']*$it['cantidad']; $total_pzas+=$it['cantidad']; }

/* Grid de productos */
$grid = $conn->query("SELECT id_producto,nombre,precio,stock,imagen FROM productos WHERE stock>0 ORDER BY nombre LIMIT 60");

/* Último agregado para toast */
$ultimo_agregado = $_SESSION['ultimo_agregado'] ?? null;
unset($_SESSION['ultimo_agregado']);

layoutStart('Punto de Venta','pos',[['label'=>'Punto de Venta']]);
?>

<style>
/* ── POS Layout ── */
.pos-wrap{display:grid;grid-template-columns:1fr 330px;gap:1.1rem;align-items:start}

/* Búsqueda */
.search-wrap{position:relative;margin-bottom:1rem}
.search-wrap input{width:100%;padding:.78rem 1rem .78rem 2.9rem;border:2px solid var(--g300);border-radius:11px;font-family:var(--font-body);font-size:.95rem;background:var(--blanco);color:var(--g900);outline:none;transition:var(--t-base)}
.search-wrap input:focus{border-color:var(--p1);box-shadow:0 0 0 4px rgba(124,31,160,.1)}
.search-wrap .si{position:absolute;left:.95rem;top:50%;transform:translateY(-50%);color:var(--g400);pointer-events:none}
#ac{position:absolute;top:calc(100% + 5px);left:0;right:0;background:var(--blanco);border:1.5px solid var(--g200);border-radius:11px;box-shadow:var(--sh-md);z-index:200;max-height:290px;overflow-y:auto;display:none}
.ac-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 1rem;cursor:pointer;border-bottom:1px solid var(--g100);transition:background .12s}
.ac-item:hover{background:var(--p1-bg)}
.ac-item:last-child{border-bottom:none}
.ac-img{width:38px;height:38px;border-radius:8px;object-fit:cover;flex-shrink:0;border:1.5px solid var(--g200)}
.ac-img-ph{width:38px;height:38px;border-radius:8px;background:var(--p1-bg);display:flex;align-items:center;justify-content:center;color:var(--p1);flex-shrink:0}
.ac-nombre{font-weight:700;font-size:.84rem;color:var(--g900)}
.ac-info{font-size:.72rem;color:var(--g400)}
.ac-precio{font-family:var(--font-mono);font-weight:700;font-size:.9rem;color:var(--p1);white-space:nowrap}

/* Grilla de productos */
.sec-titulo{font-family:var(--font-ui);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--g400);margin-bottom:.65rem;display:flex;align-items:center;gap:.5rem}
.sec-titulo::after{content:'';flex:1;height:1px;background:var(--g200)}
.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:.7rem}

.prod-card{
  background:var(--blanco);border:1.5px solid var(--g200);border-radius:13px;
  cursor:pointer;transition:var(--t-base);position:relative;overflow:hidden;
  display:flex;flex-direction:column;
}
.prod-card:hover{border-color:var(--p1);transform:translateY(-3px);box-shadow:0 8px 24px rgba(124,31,160,.15)}
.prod-card:active{transform:translateY(-1px)}
.prod-card.agotado{opacity:.45;cursor:not-allowed;pointer-events:none}

/* Imagen del producto en grilla */
.prod-card-img{
  width:100%;height:120px;object-fit:cover;
  border-radius:11px 11px 0 0;
  cursor:zoom-in;
  transition:transform .25s ease;
}
.prod-card:hover .prod-card-img{transform:scale(1.05)}
.prod-card-img-ph{
  width:100%;height:120px;border-radius:11px 11px 0 0;
  background:linear-gradient(135deg,var(--p1-bg),rgba(196,37,122,.06));
  display:flex;align-items:center;justify-content:center;
  font-size:2.5rem;color:var(--p1-b);
}

.prod-card-body{padding:.75rem .8rem .8rem;flex:1;display:flex;flex-direction:column;gap:.3rem}
.prod-stock-badge{
  position:absolute;top:.5rem;right:.5rem;
  font-size:.62rem;font-weight:800;padding:.15rem .5rem;border-radius:99px;
  font-family:var(--font-ui);
}
.ps-ok  {background:var(--verde-bg);color:var(--verde)}
.ps-bajo{background:var(--naranja-bg);color:var(--naranja)}
.ps-nombre{font-family:var(--font-title);font-size:.82rem;font-weight:700;color:var(--g900);line-height:1.3}
.ps-precio{font-family:var(--font-mono);font-size:.95rem;font-weight:700;color:var(--p1)}
.prod-add-btn{
  margin-top:auto;padding:.38rem;
  background:var(--p1-bg);color:var(--p1);border:none;border-radius:7px;
  font-size:.75rem;font-weight:800;cursor:pointer;font-family:var(--font-ui);
  transition:var(--t-base);width:100%;display:flex;align-items:center;justify-content:center;gap:5px;
}
.prod-card:hover .prod-add-btn{background:var(--p1);color:#fff}

/* ── Carrito ── */
.carrito-wrap{background:var(--blanco);border-radius:14px;border:1.5px solid var(--g200);box-shadow:var(--sh-md);position:sticky;top:calc(58px + 1.1rem);display:flex;flex-direction:column;max-height:calc(100vh - 80px);overflow:hidden}
.car-header{background:linear-gradient(135deg,var(--oscuro),var(--oscuro2));color:#fff;padding:.85rem 1rem;display:flex;align-items:center;justify-content:space-between}
.car-header h3{font-family:var(--font-title);font-size:.88rem;font-weight:700}
.car-cnt{background:var(--m1);color:#fff;font-size:.68rem;font-weight:800;padding:.15rem .55rem;border-radius:99px;font-family:var(--font-ui)}
.car-body{flex:1;overflow-y:auto;padding:.6rem}
.car-vacio{padding:2rem .5rem;text-align:center;color:var(--g400)}
.car-vacio .cv-icon{font-size:1.8rem;margin-bottom:.5rem}
.car-vacio p{font-size:.8rem;font-family:var(--font-body)}
.car-item{background:var(--g50);border:1px solid var(--g100);border-radius:9px;padding:.6rem .75rem;margin-bottom:.45rem;animation:slideIn .2s ease;display:flex;flex-direction:column;gap:.35rem}
@keyframes slideIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.ci-row1{display:flex;align-items:center;gap:.6rem}
.ci-img{width:32px;height:32px;border-radius:7px;object-fit:cover;flex-shrink:0;border:1.5px solid var(--g200)}
.ci-img-ph{width:32px;height:32px;border-radius:7px;background:var(--p1-bg);display:flex;align-items:center;justify-content:center;font-size:.85rem;color:var(--p1);flex-shrink:0}
.ci-nombre{font-family:var(--font-ui);font-size:.8rem;font-weight:700;color:var(--g900);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ci-row2{display:flex;align-items:center;justify-content:space-between}
.ci-qty{display:flex;align-items:center;gap:.3rem}
.qb{width:22px;height:22px;border:none;background:var(--g200);border-radius:5px;cursor:pointer;font-size:.85rem;font-weight:700;display:flex;align-items:center;justify-content:center;color:var(--g700);transition:var(--t-fast);text-decoration:none;line-height:1}
.qb:hover{background:var(--p1);color:#fff}
.qb.del:hover{background:var(--rojo);color:#fff}
.qv{font-family:var(--font-mono);font-size:.82rem;font-weight:700;min-width:22px;text-align:center;color:var(--g900)}
.ci-sub{font-family:var(--font-mono);font-size:.88rem;font-weight:700;color:var(--verde)}
.car-footer{border-top:1px solid var(--g100);padding:.85rem 1rem;background:var(--blanco)}
.cf-linea{display:flex;justify-content:space-between;font-size:.78rem;color:var(--g500);margin-bottom:.2rem;font-family:var(--font-body)}
.cf-total{display:flex;justify-content:space-between;align-items:center;border-top:2px solid var(--g900);margin-top:.45rem;padding-top:.5rem}
.cf-total .lbl{font-family:var(--font-ui);font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--g700)}
.cf-total .monto{font-family:var(--font-mono);font-size:1.45rem;font-weight:900;color:var(--g900)}
.btn-cobrar{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:.78rem;margin-top:.65rem;background:linear-gradient(135deg,var(--p1),var(--m1));color:#fff;border:none;border-radius:11px;font-family:var(--font-ui);font-size:.92rem;font-weight:800;cursor:pointer;text-decoration:none;box-shadow:var(--sh-p1);transition:var(--t-base)}
.btn-cobrar:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(124,31,160,.45)}
.link-vaciar{display:block;text-align:center;font-size:.72rem;color:var(--g400);margin-top:.5rem;cursor:pointer;text-decoration:none;transition:color .15s;font-family:var(--font-body)}
.link-vaciar:hover{color:var(--rojo)}
.ef-box{margin-top:.7rem;border-top:1px dashed var(--g200);padding-top:.65rem}
.ef-lbl{font-family:var(--font-ui);font-size:.72rem;font-weight:700;color:var(--g500);display:block;margin-bottom:.3rem}
.ef-inp{width:100%;padding:.5rem .75rem;font-family:var(--font-mono);font-size:.95rem;font-weight:700;border:1.5px solid var(--g300);border-radius:8px;outline:none;color:var(--g900);transition:var(--t-base);background:var(--blanco)}
.ef-inp:focus{border-color:var(--azul);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.cambio-row{display:flex;justify-content:space-between;align-items:center;margin-top:.4rem;min-height:22px}
.cambio-lbl{font-family:var(--font-ui);font-size:.75rem;font-weight:700;color:var(--g500)}
.cambio-val{font-family:var(--font-mono);font-size:.95rem;font-weight:800;color:var(--azul)}
.cambio-falta{color:var(--rojo)!important}

@media(max-width:860px){.pos-wrap{grid-template-columns:1fr}.carrito-wrap{position:static;max-height:none}}
@media(max-width:480px){.prod-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<?php if($error): ?><div class="alerta alerta-error"><i class="fa-solid fa-xmark"></i> <?=e($error)?></div><?php endif ?>

<div class="pos-wrap">

  <!-- ── PRODUCTOS ── -->
  <div>
    <div class="search-wrap">
      <i class="fa-solid fa-magnifying-glass si"></i>
      <input type="text" id="buscarInput" placeholder="Buscar producto por nombre…" autocomplete="off" autofocus>
      <div id="ac"></div>
    </div>

    <p class="sec-titulo">Productos disponibles (<?=$grid->num_rows?>)</p>

    <div class="prod-grid" id="prodGrid">
      <?php
      $rows_grid=[];
      while($p=$grid->fetch_assoc()) $rows_grid[]=$p;
      foreach($rows_grid as $p):
        $sc = $p['stock']>10?'ps-ok':'ps-bajo';
        $has_img = $p['imagen'] && file_exists(BASE_PATH . $p['imagen']);
      ?>
      <div class="prod-card <?=$p['stock']==0?'agotado':''?>"
           id="pc-<?=$p['id_producto']?>"
           data-id="<?=$p['id_producto']?>"
           data-nombre="<?=e($p['nombre'])?>"
           data-precio="<?=$p['precio']?>"
           data-stock="<?=$p['stock']?>"
           data-img="<?=$has_img ? e(BASE_URL.$p['imagen']) : ''?>"
           onclick="agregarDesdeGrid(this)">
        <span class="prod-stock-badge <?=$sc?>"><?=$p['stock']?>u</span>
        <?php if($has_img): ?>
          <img src="<?=BASE_URL.$p['imagen']?>"
               alt="<?=e($p['nombre'])?>"
               class="prod-card-img"
               onclick="event.stopPropagation();abrirLightbox('<?=BASE_URL.$p['imagen']?>','<?=e($p['nombre'])?>')"
               loading="lazy">
        <?php else: ?>
          <div class="prod-card-img-ph">
            <i class="fa-solid fa-box" style="font-size:2rem;color:var(--p1-b)"></i>
          </div>
        <?php endif ?>
        <div class="prod-card-body">
          <div class="ps-nombre"><?=e($p['nombre'])?></div>
          <div class="ps-precio"><?=dinero($p['precio'])?></div>
          <button class="prod-add-btn" type="button">
            <i class="fa-solid fa-plus"></i> Agregar
          </button>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>

  <!-- ── CARRITO ── -->
  <div class="carrito-wrap">
    <div class="car-header">
      <h3><i class="fa-solid fa-cart-shopping"></i> Carrito</h3>
      <span class="car-cnt"><?=$total_pzas?></span>
    </div>
    <div class="car-body">
      <?php if(empty($_SESSION['carrito'])): ?>
        <div class="car-vacio">
          <div class="cv-icon"><i class="fa-solid fa-cart-shopping"></i></div>
          <p>Carrito vacío. Haz clic en un producto para agregar.</p>
        </div>
      <?php else: ?>
        <?php foreach($_SESSION['carrito'] as $id => $item): $q=$item['cantidad'];
          $has_img = !empty($item['imagen']) && file_exists(BASE_PATH . ($item['imagen']??''));
        ?>
        <div class="car-item">
          <div class="ci-row1">
            <?php if($has_img): ?>
              <img src="<?=BASE_URL.$item['imagen']?>" alt="" class="ci-img"
                   onclick="abrirLightbox('<?=BASE_URL.$item['imagen']?>','<?=e($item['nombre'])?>');event.stopPropagation()">
            <?php else: ?>
              <div class="ci-img-ph"><i class="fa-solid fa-box"></i></div>
            <?php endif ?>
            <div class="ci-nombre"><?=e($item['nombre'])?></div>
          </div>
          <div class="ci-row2">
            <div class="ci-qty">
              <a href="?qty_id=<?=$id?>&qty_n=<?=max(0,$q-1)?>" class="qb del"
                 title="<?=$q<=1?'Eliminar':'Reducir'?>">
                <?=$q<=1?'<i class="fa-solid fa-xmark"></i>':'<i class="fa-solid fa-minus"></i>'?>
              </a>
              <span class="qv"><?=$q?></span>
              <a href="?qty_id=<?=$id?>&qty_n=<?=$q+1?>" class="qb" title="Aumentar">
                <i class="fa-solid fa-plus"></i>
              </a>
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
        <span class="monto"><?=dinero($total)?></span>
      </div>
      <div class="ef-box">
        <label class="ef-lbl" for="efectivo">Efectivo recibido</label>
        <input type="number" id="efectivo" class="ef-inp" placeholder="<?=dinero($total)?>" step="0.01" min="0" oninput="calcCambio(<?=$total?>)">
        <div class="cambio-row">
          <span class="cambio-lbl" id="cambioLabel">Cambio</span>
          <span class="cambio-val" id="cambioValor">—</span>
        </div>
      </div>
      <a href="procesar_venta.php" class="btn-cobrar">
        <i class="fa-solid fa-check"></i> Cobrar <?=dinero($total)?>
      </a>
      <a href="?vaciar=1" class="link-vaciar" onclick="return confirm('¿Vaciar el carrito?')">
        <i class="fa-solid fa-trash"></i> Vaciar carrito
      </a>
    </div>
    <?php endif ?>
  </div>

</div>

<!-- Formulario oculto — FIX: name="agregar" en hidden input -->
<form method="POST" id="fAgregar" style="display:none">
  <input type="hidden" name="agregar" value="1">
  <input type="hidden" name="id_producto" id="fId">
  <input type="hidden" name="cantidad"    id="fQty" value="1">
</form>

<script>
/* ══ Último producto agregado → TOAST ══ */
<?php if($ultimo_agregado): ?>
document.addEventListener("DOMContentLoaded",()=>{
  mostrarToast("¡«<?=addslashes($ultimo_agregado)?>» agregado!", "✓");
});
<?php endif ?>

/* ══ Agregar al carrito ══ */
function agregarProducto(id, qty=1){
  document.getElementById('fId').value  = id;
  document.getElementById('fQty').value = qty;
  document.getElementById('fAgregar').submit();
}

function agregarDesdeGrid(card){
  if(card.classList.contains('agotado')) return;
  /* Microinteracción POP en la tarjeta */
  card.classList.remove('prod-card-pop');
  void card.offsetWidth; // reflow
  card.classList.add('prod-card-pop');
  agregarProducto(card.dataset.id, 1);
}

/* ══ Autocomplete búsqueda ══ */
const inp = document.getElementById('buscarInput');
const acEl = document.getElementById('ac');
let tmr;

inp.addEventListener('input',()=>{
  const q = inp.value.trim();
  /* Filtrar grilla visible */
  document.querySelectorAll('#prodGrid .prod-card').forEach(c=>{
    c.style.display = !q || c.dataset.nombre.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
  /* Autocomplete */
  clearTimeout(tmr);
  if(q.length<2){ acEl.style.display='none'; return; }
  tmr = setTimeout(()=>{
    fetch('buscar_productos.php?q='+encodeURIComponent(q))
      .then(r=>r.json())
      .then(data=>{
        if(!data.length){ acEl.style.display='none'; return; }
        acEl.innerHTML = data.map(p=>{
          const imgHtml = p.imagen
            ? `<img src="<?=BASE_URL?>${p.imagen}" alt="" class="ac-img" onerror="this.style.display='none'">`
            : `<div class="ac-img-ph"><i class="fa-solid fa-box"></i></div>`;
          return `<div class="ac-item" onclick="agregarProducto(${p.id_producto},1);acEl.style.display='none';inp.value=''">
            ${imgHtml}
            <div style="flex:1"><div class="ac-nombre">${p.nombre}</div><div class="ac-info">Stock: ${p.stock}</div></div>
            <div class="ac-precio">$${parseFloat(p.precio).toFixed(2)}</div>
          </div>`;
        }).join('');
        acEl.style.display='block';
      });
  },220);
});
document.addEventListener('click',e=>{ if(!e.target.closest('.search-wrap')) acEl.style.display='none'; });

/* ══ Calcular cambio ══ */
function calcCambio(total){
  const ef  = parseFloat(document.getElementById('efectivo').value)||0;
  const lbl = document.getElementById('cambioLabel');
  const val = document.getElementById('cambioValor');
  if(ef<=0){ lbl.textContent='Cambio'; val.textContent='—'; val.className='cambio-val'; return; }
  const diff = +(ef-total).toFixed(2);
  if(diff<0){
    lbl.textContent='Falta'; val.textContent='$'+Math.abs(diff).toFixed(2); val.className='cambio-val cambio-falta';
  } else {
    lbl.textContent='Cambio'; val.textContent='$'+diff.toFixed(2); val.className='cambio-val';
  }
}
</script>

<?php layoutEnd(); ?>