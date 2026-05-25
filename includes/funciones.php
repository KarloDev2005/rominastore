<?php

function redirigir($url) { header("Location: " . BASE_URL . $url); exit(); }

function sesionIniciada() { return !empty($_SESSION['usuario_id']); }
function tieneRol($rol)   { return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol; }
function nombreUsuario()  { return $_SESSION['usuario_nombre'] ?? 'Invitado'; }
function rolActual()      { return $_SESSION['rol'] ?? ''; }

function requerirAutenticacion() { if (!sesionIniciada()) redirigir('index.php'); }
function requerirAdmin()         { requerirAutenticacion(); if (!tieneRol('admin')) redirigir('dashboard.php'); }


function limpiar($dato) { return trim(strip_tags((string)$dato)); }
function e($str)        { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function dinero($m)     { return '$' . number_format((float)$m, 2); }


function csrfToken() {
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrfInput()    { return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">'; }
function csrfVerificar() {
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;color:#dc2626;padding:2rem">Token inválido. <a href="javascript:history.back()">Volver</a></p>');
    }
}


function flashSet($tipo, $msg) { $_SESSION['flash'] = ['tipo'=>$tipo,'msg'=>$msg]; }
function flashGet()            { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function flashHtml() {
    $f=flashGet(); if(!$f) return '';
    $cls=['exito'=>'alerta-exito','error'=>'alerta-error','info'=>'alerta-info','aviso'=>'alerta-aviso'];
    $ico=['exito'=>'fa-check','error'=>'fa-xmark','info'=>'fa-info-circle','aviso'=>'fa-triangle-exclamation'];
    $c=$cls[$f['tipo']]??'alerta-info';
    $i=$ico[$f['tipo']]??'fa-info-circle';
    return '<div class="alerta '.$c.'"><i class="fa-solid '.$i.'"></i> '.e($f['msg']).'</div>';
}


function subirImagenProducto($file, $id_producto): array {
    $permitidos = ['image/jpeg','image/jpg','image/png','image/webp'];
    if ($file['error'] !== UPLOAD_ERR_OK)
        return ['ok'=>false,'msg'=>'Error al subir el archivo.'];
    $tipo = mime_content_type($file['tmp_name']);
    if (!in_array($tipo, $permitidos))
        return ['ok'=>false,'msg'=>'Formato no permitido. Usa JPG, PNG o WEBP.'];
    if ($file['size'] > 5 * 1024 * 1024)
        return ['ok'=>false,'msg'=>'La imagen no debe superar 5 MB.'];
    $carpeta = __DIR__ . '/../img/productos/';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
    switch ($tipo) {
        case 'image/jpeg': case 'image/jpg': $src=imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $src=imagecreatefrompng($file['tmp_name']); break;
        case 'image/webp': $src=imagecreatefromwebp($file['tmp_name']); break;
        default: return ['ok'=>false,'msg'=>'Tipo no soportado.'];
    }
    $ow=imagesx($src); $oh=imagesy($src);
    $sz=300; $ratio=$ow/$oh;
    $nw=$ratio>1?(int)($sz*$ratio):$sz;
    $nh=$ratio>1?$sz:(int)($sz/$ratio);
    $tmp=imagecreatetruecolor($nw,$nh);
    imagefill($tmp,0,0,imagecolorallocate($tmp,255,255,255));
    imagecopyresampled($tmp,$src,0,0,0,0,$nw,$nh,$ow,$oh);
    $dst=imagecreatetruecolor($sz,$sz);
    imagefill($dst,0,0,imagecolorallocate($dst,255,255,255));
    imagecopy($dst,$tmp,0,0,(int)(($nw-$sz)/2),(int)(($nh-$sz)/2),$sz,$sz);
    $nombre='producto_'.$id_producto.'_'.time().'.jpg';
    imagejpeg($dst,$carpeta.$nombre,85);
    imagedestroy($src); imagedestroy($tmp); imagedestroy($dst);
    return ['ok'=>true,'nombre'=>$nombre,'ruta'=>'img/productos/'.$nombre];
}
function eliminarImagenProducto(string $ruta_rel): void {
    $r=__DIR__.'/../'.$ruta_rel;
    if ($ruta_rel && file_exists($r)) unlink($r);
}
function thumbProducto(?string $ruta, string $nombre='', int $size=48): string {
    $base=BASE_URL;
    if ($ruta && isset($_SERVER['DOCUMENT_ROOT']) && file_exists($_SERVER['DOCUMENT_ROOT'].'/'.ltrim($ruta,'/'))) {
        return '<img src="'.$base.$ruta.'" alt="'.e($nombre).'" class="prod-thumb" style="width:'.$size.'px;height:'.$size.'px" onclick="abrirLightbox(this.src,\''.e($nombre).'\')" title="'.e($nombre).'">';
    }
    // Intento alternativo con BASE_PATH
    if ($ruta && defined('BASE_PATH') && file_exists(BASE_PATH.$ruta)) {
        return '<img src="'.$base.$ruta.'" alt="'.e($nombre).'" class="prod-thumb" style="width:'.$size.'px;height:'.$size.'px" onclick="abrirLightbox(this.src,\''.e($nombre).'\')" title="'.e($nombre).'">';
    }
    return '<div class="prod-thumb-placeholder" style="width:'.$size.'px;height:'.$size.'px" title="Sin imagen"><i class="fa-solid fa-image"></i></div>';
}


function obtenerDeudoresAtraso($conn, int $dias_min=2, int $limite=5): array {
    
    $sql = "SELECT c.id_cliente, c.nombre, c.adeudo,
                   MAX(v.fecha) AS ultima_compra,
                   DATEDIFF(NOW(), MAX(v.fecha)) AS dias_atraso
            FROM clientes c
            JOIN ventas v ON v.id_cliente = c.id_cliente
            WHERE c.adeudo > 0
            GROUP BY c.id_cliente
            HAVING dias_atraso >= ?
            ORDER BY c.adeudo DESC
            LIMIT ?";
    $s = $conn->prepare($sql);
    $s->bind_param("ii", $dias_min, $limite);
    $s->execute();
    $res = $s->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    return $out;
}


function layoutStart($titulo='RominaStore', $activo='', $breadcrumbs=[]) {
    $base = BASE_URL;
    $user = e(nombreUsuario());
    $rol  = rolActual();
    $ini  = strtoupper(substr(strip_tags($user), 0, 2)); // 2 letras para avatar

  
        if ($rol === 'admin') {
        $nav = [
            ['url'=>'dashboard.php',              'icon'=>'fa-solid fa-house',              'label'=>'Inicio',            'key'=>'dashboard'],
            ['url'=>'ventas/nueva_venta.php',     'icon'=>'fa-solid fa-cash-register',      'label'=>'Punto de Venta',    'key'=>'pos'],
            ['url'=>'fiado/venta_credito.php',    'icon'=>'fa-solid fa-credit-card',        'label'=>'Venta a Crédito',   'key'=>'credito'],
            ['url'=>'fiado/consultar_adeudo.php', 'icon'=>'fa-solid fa-file-invoice-dollar','label'=>'Adeudos',           'key'=>'adeudos'],
            ['url'=>'productos/listar.php',       'icon'=>'fa-solid fa-box',               'label'=>'Productos',         'key'=>'productos'],
            ['url'=>'inventario/consultar.php',   'icon'=>'fa-solid fa-warehouse',         'label'=>'Inventario',        'key'=>'inventario'],
            ['url'=>'clientes/listar.php',        'icon'=>'fa-solid fa-users',             'label'=>'Clientes',          'key'=>'clientes'],
            ['url'=>'reportes/ventas.php',        'icon'=>'fa-solid fa-chart-line',        'label'=>'Reportes',          'key'=>'reportes'],
            ['url'=>'ventas/cierre_caja.php',     'icon'=>'fa-solid fa-cash-register',     'label'=>'Cierre de Caja',    'key'=>'cierre'],
            ['url'=>'usuarios/listar.php',        'icon'=>'fa-solid fa-gear',              'label'=>'Usuarios',          'key'=>'usuarios'],
        ];
        } else {
        // Cajero: Dashboard, Punto de Venta, Reportes y Cierre de Caja
        $nav = [
            ['url'=>'dashboard.php',            'icon'=>'fa-solid fa-house',          'label'=>'Inicio',         'key'=>'dashboard'],
            ['url'=>'ventas/nueva_venta.php',   'icon'=>'fa-solid fa-cash-register',  'label'=>'Punto de Venta', 'key'=>'pos'],
            ['url'=>'reportes/ventas.php',      'icon'=>'fa-solid fa-chart-line',     'label'=>'Reportes',       'key'=>'reportes'],
            ['url'=>'ventas/cierre_caja.php',   'icon'=>'fa-solid fa-cash-register',  'label'=>'Cierre de Caja', 'key'=>'cierre'],
        ];
    }



    $bc='';
    if (!empty($breadcrumbs)) {
        $bc .= '<a href="'.$base.'dashboard.php"><i class="fa-solid fa-house"></i> Inicio</a>';
        foreach ($breadcrumbs as $b) {
            $bc .= '<span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:.6rem"></i></span>';
            $bc .= !empty($b['url'])
                ? '<a href="'.$base.$b['url'].'">'.e($b['label']).'</a>'
                : '<span class="current">'.e($b['label']).'</span>';
        }
    }

    echo '<!DOCTYPE html>
<html lang="es" id="htmlRoot">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>'.e($titulo).' — Abarrotes Romina</title>
  <link rel="icon" type="image/png" href="'.$base.'img/icono.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="'.$base.'css/app.css">
  <script>
    /* Aplica dark mode ANTES de render para evitar flash */
    (function(){
      if(localStorage.getItem("darkMode")==="1")
        document.getElementById("htmlRoot").classList.add("dark");
    })();
  </script>
</head>
<body>

<div class="nav-progress" aria-hidden="true"></div>

<!-- Spinner -->
<div class="spinner-overlay" id="spinnerOverlay">
  <div class="spinner-box">
    <div class="spinner-ring"></div>
    <div class="spinner-txt" id="spinnerTxt">Procesando...</div>
  </div>
</div>

<!-- Lightbox -->
<div class="modal-overlay" id="lightboxOverlay" onclick="cerrarLightbox(event)">
  <div class="modal-box" style="position:relative">
    <button class="modal-close" onclick="cerrarLightbox()"><i class="fa-solid fa-xmark"></i></button>
    <img id="lightboxImg" class="modal-img" src="" alt="">
    <div id="lightboxCaption" style="padding:.75rem 1rem;font-family:var(--font-title);font-weight:700;font-size:.9rem;color:var(--txt-secondary);border-top:1px solid var(--border)"></div>
  </div>
</div>

<div class="app-shell">

<!-- ════ SIDEBAR ════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="'.$base.'img/icono.png" alt="Logo Romina" class="logo-img">
    <div class="logo-txt">
      <div class="logo-name">Abarrotes Romina</div>
      <div class="logo-sub">Sistema POS</div>
    </div>
    <button class="sidebar-toggle" onclick="toggleMini()" id="sidebarToggleBtn" title="Colapsar menú">
      <i class="fa-solid fa-angles-left" id="toggleIcon"></i>
    </button>
  </div>

  <nav class="sidebar-nav">';

    $grupos = [
        'Ventas'   => ['pos','credito','adeudos','cierre'],
        'Catálogo' => ['productos','inventario','clientes'],
        'Sistema'  => ['reportes','usuarios'],
    ];
    $grupo_actual='';
    foreach ($nav as $item) {
        foreach ($grupos as $glabel=>$keys) {
            if (in_array($item['key'],$keys)&&$grupo_actual!==$glabel) {
                $grupo_actual=$glabel;
                echo '<div class="nav-section-label"><i class="fa-solid fa-minus" style="font-size:.5rem;margin-right:4px;opacity:.5"></i>'.$glabel.'</div>';
            }
        }
        $cls=$activo===$item['key']?' activo':'';
        echo '<a href="'.$base.$item['url'].'" class="nav-item'.$cls.'" title="'.$item['label'].'">
          <i class="'.$item['icon'].' ni"></i>
          <span class="nl">'.$item['label'].'</span>
        </a>';
    }

    echo '
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar" style="font-size:.72rem">'.$ini.'</div>
      <div class="user-info">
        <div class="user-name">'.$user.'</div>
        <div class="user-role">'.ucfirst($rol).'</div>
      </div>
    </div>
    <a href="'.$base.'logout.php" class="btn-salir">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Cerrar sesión</span>
    </a>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ════ CONTENIDO ════ -->
<div class="main-content" id="mainContent">
  <header class="main-topbar">
    <button class="topbar-hamburger" onclick="toggleSidebar()" aria-label="Menú">
      <i class="fa-solid fa-bars"></i>
    </button>
    <nav class="topbar-breadcrumb">'.$bc.'</nav>
    <div class="topbar-actions">
      <!-- Toggle dark mode -->
      <button class="btn-darkmode" id="darkModeBtn" onclick="toggleDark()" title="Cambiar modo">
        <i class="fa-solid fa-moon" id="darkIcon"></i>
      </button>
      <!-- Avatar con dropdown -->
      <div class="avatar-wrap" id="avatarWrap">
        <button class="avatar-btn" onclick="toggleAvatarMenu()" title="Mi cuenta">'.$ini.'</button>
        <div class="avatar-dropdown" id="avatarDropdown">
          <div class="avatar-dd-header">
            <div class="avatar-dd-name">'.$user.'</div>
            <div class="avatar-dd-role">'.ucfirst($rol).'</div>
          </div>
          <a href="'.$base.'ventas/cierre_caja.php" class="avatar-dd-item">
            <i class="fa-solid fa-cash-register"></i> Cierre de Caja
          </a>
          <button onclick="toggleDark()" class="avatar-dd-item" style="cursor:pointer">
            <i class="fa-solid fa-circle-half-stroke"></i> Cambiar tema
          </button>
          <hr style="margin:.3rem 0;border:none;border-top:1px solid var(--border)">
          <a href="'.$base.'logout.php" class="avatar-dd-item rojo">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión
          </a>
        </div>
      </div>
    </div>
  </header>
  <main class="page-area">';
}

function layoutEnd() {
    echo '
  </main>
</div><!-- /main-content -->
</div><!-- /app-shell -->

<script>
/* ════ DARK MODE ════ */
function toggleDark(){
  const html = document.getElementById("htmlRoot");
  const isDark = html.classList.toggle("dark");
  localStorage.setItem("darkMode", isDark ? "1" : "0");
  const icon = document.getElementById("darkIcon");
  if(icon){
    icon.className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
  }
}
/* Actualizar icono al cargar */
(function(){
  const isDark = localStorage.getItem("darkMode")==="1";
  const icon = document.getElementById("darkIcon");
  if(icon) icon.className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
})();

/* ════ SIDEBAR COLAPSABLE ════ */
let isMini = localStorage.getItem("sidebarMini")==="true";
const sidebar     = document.getElementById("sidebar");
const mainContent = document.getElementById("mainContent");
const toggleIcon  = document.getElementById("toggleIcon");
const overlay     = document.getElementById("sidebarOverlay");

/* Aplica estado sin animación inicial para evitar "flash/reinicio" visual */
function applySidebarMini(mini, animate=true){
  isMini = mini;
  if(!animate){
    sidebar.style.transition = "none";
    mainContent.style.transition = "none";
  }
  if(mini){
    sidebar.classList.add("mini");
    mainContent.classList.add("mini");
    if(toggleIcon){ toggleIcon.classList.remove("fa-angles-left"); toggleIcon.classList.add("fa-angles-right"); }
  } else {
    sidebar.classList.remove("mini");
    mainContent.classList.remove("mini");
    if(toggleIcon){ toggleIcon.classList.remove("fa-angles-right"); toggleIcon.classList.add("fa-angles-left"); }
  }
  if(!animate){
    requestAnimationFrame(()=>{
      sidebar.style.transition = "";
      mainContent.style.transition = "";
    });
  }
  localStorage.setItem("sidebarMini", mini);
}

/* Aplica estado guardado SIN animación al cargar — evita el "reinicio visual" */
if(window.innerWidth > 900) applySidebarMini(isMini, false);

function toggleMini(){ if(window.innerWidth<=900) return; applySidebarMini(!isMini); }

/* Tooltips en modo mini */
sidebar.querySelectorAll(".nav-item").forEach(el=>{
  el.addEventListener("mouseenter",()=>{
    if(!sidebar.classList.contains("mini")) return;
    const tt=document.createElement("div");
    tt.className="_sbtt";
    tt.textContent=el.querySelector(".nl")?.textContent||"";
    Object.assign(tt.style,{
      position:"fixed",zIndex:"9999",
      background:"rgba(26,10,46,.95)",color:"#fff",
      padding:"5px 12px",borderRadius:"8px",
      fontSize:".78rem",fontWeight:"700",
      fontFamily:"var(--font-ui)",whiteSpace:"nowrap",
      pointerEvents:"none",boxShadow:"var(--sh-md)",
      left:"76px",top:(el.getBoundingClientRect().top+8)+"px"
    });
    document.body.appendChild(tt);
    el._tt=tt;
  });
  el.addEventListener("mouseleave",()=>{ el._tt?.remove(); });
});

/* Sidebar móvil */
function toggleSidebar(){
  if(window.innerWidth<=900){
    sidebar.classList.toggle("open");
    overlay.classList.toggle("open");
  } else {
    toggleMini();
  }
}
function closeSidebar(){
  sidebar.classList.remove("open");
  overlay.classList.remove("open");
}

/* ════ AVATAR DROPDOWN ════ */
function toggleAvatarMenu(){
  document.getElementById("avatarDropdown").classList.toggle("open");
}
document.addEventListener("click",e=>{
  const wrap=document.getElementById("avatarWrap");
  if(wrap&&!wrap.contains(e.target))
    document.getElementById("avatarDropdown")?.classList.remove("open");
});

/* ════ LIGHTBOX ════ */
function abrirLightbox(src,caption){
  document.getElementById("lightboxImg").src=src;
  document.getElementById("lightboxCaption").textContent=caption||"";
  document.getElementById("lightboxOverlay").classList.add("open");
}
function cerrarLightbox(e){
  if(!e||e.target===document.getElementById("lightboxOverlay"))
    document.getElementById("lightboxOverlay").classList.remove("open");
}
document.addEventListener("keydown",e=>{ if(e.key==="Escape") cerrarLightbox(); });

/* ════ SPINNER ════ */
function mostrarSpinner(txt="Procesando..."){
  document.getElementById("spinnerTxt").textContent=txt;
  document.getElementById("spinnerOverlay").classList.add("show");
}
function ocultarSpinner(){ document.getElementById("spinnerOverlay").classList.remove("show"); }
document.querySelectorAll("form.con-spinner").forEach(f=>{
  f.addEventListener("submit",()=>mostrarSpinner());
});

function activarTransicionPagina(){
  document.body.classList.add("is-navigating");
  document.querySelector(".page-area")?.setAttribute("aria-busy","true");
}

function finalizarTransicionPagina(){
  document.body.classList.remove("is-navigating");
  document.querySelector(".page-area")?.removeAttribute("aria-busy");
}

function puedeCargarDinamico(url){
  if(!("fetch" in window)||!("DOMParser" in window)||!("history" in window)) return false;
  if(url.origin!==location.origin) return false;
  if(url.searchParams.has("csv")) return false;
  if(url.pathname.endsWith("/logout.php")||url.pathname.endsWith("/index.php")) return false;
  return true;
}

function actualizarNavActivo(doc){
  const nuevoActivo=doc.querySelector(".nav-item.activo");
  document.querySelectorAll(".nav-item").forEach(n=>n.classList.remove("activo"));
  if(!nuevoActivo) return;
  const destino=new URL(nuevoActivo.getAttribute("href"),location.href);
  document.querySelectorAll(".nav-item[href]").forEach(n=>{
    const actual=new URL(n.getAttribute("href"),location.href);
    if(actual.pathname===destino.pathname) n.classList.add("activo");
  });
}

async function ejecutarScriptsPagina(contenedor){
  const scripts=[...contenedor.querySelectorAll("script")];
  for(const old of scripts){
    await new Promise(resolve=>{
      const s=document.createElement("script");
      [...old.attributes].forEach(attr=>s.setAttribute(attr.name,attr.value));
      s.async=false;
      if(old.src){
        s.onload=resolve;
        s.onerror=resolve;
        s.src=old.src;
      }else{
        s.textContent=old.textContent;
      }
      old.replaceWith(s);
      if(!old.src) resolve();
    });
  }
}

function programarAlertas(){
  setTimeout(()=>{
    document.querySelectorAll(".alerta").forEach(el=>{
      el.style.transition="opacity .4s,transform .4s";
      el.style.opacity="0"; el.style.transform="translateY(-5px)";
      setTimeout(()=>el.remove(),400);
    });
  },3800);
}

async function cargarPagina(url, opciones={}){
  const destino=new URL(url,location.href);
  if(!puedeCargarDinamico(destino)){
    location.href=destino.href;
    return;
  }

  const metodo=(opciones.method||"GET").toUpperCase();
  const fetchOpts={
    method:metodo,
    credentials:"same-origin",
    headers:{"X-Requested-With":"fetch"}
  };
  if(opciones.body) fetchOpts.body=opciones.body;

  activarTransicionPagina();
  try{
    const res=await fetch(destino.href,fetchOpts);
    const html=await res.text();
    const doc=new DOMParser().parseFromString(html,"text/html");
    const nuevaArea=doc.querySelector(".page-area");
    if(!nuevaArea){
      location.href=res.url||destino.href;
      return;
    }

    const area=document.querySelector(".page-area");
    const nuevaBc=doc.querySelector(".topbar-breadcrumb");
    const bc=document.querySelector(".topbar-breadcrumb");
    if(bc&&nuevaBc) bc.innerHTML=nuevaBc.innerHTML;
    document.title=doc.title||document.title;
    area.innerHTML=nuevaArea.innerHTML;
    actualizarNavActivo(doc);
    closeSidebar();
    await ejecutarScriptsPagina(area);
    programarAlertas();

    const finalUrl=res.url||destino.href;
    if(opciones.push) history.pushState({pjax:true},"",finalUrl);
    else history.replaceState({pjax:true},"",finalUrl);
  }catch(err){
    location.href=destino.href;
  }finally{
    requestAnimationFrame(()=>finalizarTransicionPagina());
  }
}
window.cargarPagina=cargarPagina;

document.addEventListener("click",e=>{
  const a=e.target.closest("a[href]");
  if(!a||e.defaultPrevented||e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey) return;
  if(a.target==="_blank"||a.hasAttribute("download")||a.dataset.noTransition==="1") return;
  const raw=a.getAttribute("href")||"";
  if(!raw||raw.startsWith("#")||raw.startsWith("javascript:")||raw.startsWith("mailto:")) return;
  const url=new URL(raw,location.href);
  if(!puedeCargarDinamico(url)) return;
  e.preventDefault();
  cargarPagina(url.href,{push:a.dataset.ajaxAction!=="1"});
});

document.addEventListener("submit",e=>{
  const form=e.target.closest("form[data-ajax=\"main\"]");
  if(!form) return;
  const method=(form.method||"GET").toUpperCase();
  const enctype=(form.enctype||"").toLowerCase();
  if(enctype.includes("multipart")) return;
  e.preventDefault();
  const action=new URL(form.getAttribute("action")||location.href,location.href);
  const data=new FormData(form);
  if(method==="GET"){
    data.forEach((value,key)=>action.searchParams.set(key,value));
    cargarPagina(action.href,{push:false});
  }else{
    cargarPagina(action.href,{method:"POST",body:data,push:false});
  }
});

window.addEventListener("popstate",()=>cargarPagina(location.href,{push:false}));

window.addEventListener("pageshow",()=>finalizarTransicionPagina());

/* ════ TOAST ════ */
function mostrarToast(msg="¡Agregado!", icono="✓", duracion=2000){
  const t=document.createElement("div");
  t.className="toast-pop";
  t.innerHTML=`<span class="toast-check">${icono}</span><span>${msg}</span>`;
  document.body.appendChild(t);
  setTimeout(()=>{ t.classList.add("hide"); setTimeout(()=>t.remove(),350); }, duracion);
}

/* ════ AUTO-OCULTAR ALERTAS ════ */
programarAlertas();
</script>
</body>
</html>';
}
