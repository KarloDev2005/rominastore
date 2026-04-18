<?php
/* =================================================================
   ABARROTES ROMINA — Funciones Centrales v5
   · Sidebar colapsable con FontAwesome
   · Helpers de imagen, CSRF, flash, dinero
   ================================================================= */

/* ── Navegación ── */
function redirigir($url) { header("Location: " . BASE_URL . $url); exit(); }

/* ── Auth ── */
function sesionIniciada() { return !empty($_SESSION['usuario_id']); }
function tieneRol($rol)   { return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol; }
function nombreUsuario()  { return $_SESSION['usuario_nombre'] ?? 'Invitado'; }
function rolActual()      { return $_SESSION['rol'] ?? ''; }

function requerirAutenticacion() { if (!sesionIniciada()) redirigir('index.php'); }
function requerirAdmin()         { requerirAutenticacion(); if (!tieneRol('admin')) redirigir('dashboard.php'); }

/* ── Sanitización ──────────────────────────────────────────────────
   SIN real_escape_string. Los prepared statements protegen solos.
   Usar ambos juntos causaba doble escape en datos con apóstrofos.
   ────────────────────────────────────────────────────────────────── */
function limpiar($dato) { return trim(strip_tags((string)$dato)); }
function e($str)        { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function dinero($m)     { return '$' . number_format((float)$m, 2); }

/* ── CSRF ── */
function csrfToken() {
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrfInput() { return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">'; }
function csrfVerificar() {
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;color:#dc2626;padding:2rem">Token inválido. <a href="javascript:history.back()">Volver</a></p>');
    }
}

/* ── Flash ── */
function flashSet($tipo, $msg) { $_SESSION['flash'] = ['tipo'=>$tipo,'msg'=>$msg]; }
function flashGet()            { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function flashHtml() {
    $f=flashGet(); if(!$f) return '';
    $m=['exito'=>'alerta-exito','error'=>'alerta-error','info'=>'alerta-info','aviso'=>'alerta-aviso'];
    $i=['exito'=>'<i class="fa-solid fa-check"></i>','error'=>'<i class="fa-solid fa-xmark"></i>',
        'info'=>'<i class="fa-solid fa-info-circle"></i>','aviso'=>'<i class="fa-solid fa-triangle-exclamation"></i>'];
    return '<div class="alerta '.($m[$f['tipo']]??'alerta-info').'">'.($i[$f['tipo']]??'').' '.e($f['msg']).'</div>';
}

/* ── Imagen de producto ────────────────────────────────────────────
   Redimensiona y recorta a cuadrado. Requiere GD (incluido en XAMPP)
   ────────────────────────────────────────────────────────────────── */
function subirImagenProducto($file, $id_producto): array {
    $permitidos = ['image/jpeg','image/jpg','image/png','image/webp'];
    $exts       = ['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];

    if ($file['error'] !== UPLOAD_ERR_OK)
        return ['ok'=>false,'msg'=>'Error al subir el archivo.'];

    $tipo = mime_content_type($file['tmp_name']);
    if (!in_array($tipo, $permitidos))
        return ['ok'=>false,'msg'=>'Formato no permitido. Usa JPG, PNG o WEBP.'];

    if ($file['size'] > 5 * 1024 * 1024)
        return ['ok'=>false,'msg'=>'La imagen no debe superar 5 MB.'];

    $ext      = $exts[$tipo] ?? 'jpg';
    $nombre   = 'producto_' . $id_producto . '_' . time() . '.' . $ext;
    $carpeta  = __DIR__ . '/../img/productos/';
    if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
    $destino  = $carpeta . $nombre;

    /* Redimensionar a 300x300 (crop centrado) con GD */
    switch ($tipo) {
        case 'image/jpeg': case 'image/jpg': $src = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $src = imagecreatefrompng($file['tmp_name']); break;
        case 'image/webp': $src = imagecreatefromwebp($file['tmp_name']); break;
        default: return ['ok'=>false,'msg'=>'Tipo no soportado.'];
    }

    $ow = imagesx($src); $oh = imagesy($src);
    $dst_size = 300;
    $ratio    = $ow / $oh;

    if ($ratio > 1) { // más ancha que alta
        $new_h = $dst_size; $new_w = (int)($dst_size * $ratio);
    } else {
        $new_w = $dst_size; $new_h = (int)($dst_size / $ratio);
    }

    $tmp = imagecreatetruecolor($new_w, $new_h);
    // Fondo blanco para PNG
    imagefill($tmp, 0, 0, imagecolorallocate($tmp, 255, 255, 255));
    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $new_w, $new_h, $ow, $oh);

    // Recorte centrado a 300x300
    $dst = imagecreatetruecolor($dst_size, $dst_size);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    $ox = (int)(($new_w - $dst_size) / 2);
    $oy = (int)(($new_h - $dst_size) / 2);
    imagecopy($dst, $tmp, 0, 0, $ox, $oy, $dst_size, $dst_size);

    // Guardar como JPG (calidad 85)
    $nombre_final = 'producto_' . $id_producto . '_' . time() . '.jpg';
    $destino_final = $carpeta . $nombre_final;
    imagejpeg($dst, $destino_final, 85);

    imagedestroy($src); imagedestroy($tmp); imagedestroy($dst);

    return ['ok'=>true,'nombre'=>$nombre_final,'ruta'=>'img/productos/'.$nombre_final];
}

function eliminarImagenProducto(string $ruta_rel): void {
    $ruta = __DIR__ . '/../' . $ruta_rel;
    if ($ruta_rel && file_exists($ruta)) unlink($ruta);
}

function thumbProducto(?string $ruta, string $nombre='', int $size=48): string {
    $base = BASE_URL;
    if ($ruta && file_exists(__DIR__.'/../'.$ruta)) {
        return '<img src="'.$base.$ruta.'?v='.filemtime(__DIR__.'/../'.$ruta).'" 
                     alt="'.e($nombre).'" class="prod-thumb" style="width:'.$size.'px;height:'.$size.'px"
                     onclick="abrirLightbox(this.src, \''.e($nombre).'\')"
                     title="'.e($nombre).'">';
    }
    return '<div class="prod-thumb-placeholder" style="width:'.$size.'px;height:'.$size.'px" title="Sin imagen">
              <i class="fa-solid fa-image"></i>
            </div>';
}

/* ═════════════════════════════════════════════════════════════════
   LAYOUT — Sidebar con FontAwesome, colapsable
   ════════════════════════════════════════════════════════════════= */
function layoutStart($titulo='RominaStore', $activo='', $breadcrumbs=[]) {
    $base = BASE_URL;
    $user = e(nombreUsuario());
    $rol  = rolActual();
    $ini  = strtoupper(substr(strip_tags($user), 0, 1));

    /* Menú con iconos FontAwesome */
    $nav = [
        ['url'=>'dashboard.php',              'icon'=>'fa-solid fa-house',         'label'=>'Dashboard',         'key'=>'dashboard'],
        ['url'=>'ventas/nueva_venta.php',     'icon'=>'fa-solid fa-cash-register',  'label'=>'Punto de Venta',    'key'=>'pos'],
        ['url'=>'fiado/venta_credito.php',    'icon'=>'fa-solid fa-credit-card',   'label'=>'Venta a Crédito',   'key'=>'credito'],
        ['url'=>'fiado/consultar_adeudo.php', 'icon'=>'fa-solid fa-file-invoice-dollar','label'=>'Adeudos',       'key'=>'adeudos'],
        ['url'=>'productos/listar.php',       'icon'=>'fa-solid fa-box',           'label'=>'Productos',         'key'=>'productos'],
        ['url'=>'inventario/consultar.php',   'icon'=>'fa-solid fa-warehouse',     'label'=>'Inventario',        'key'=>'inventario'],
        ['url'=>'clientes/listar.php',        'icon'=>'fa-solid fa-users',         'label'=>'Clientes',          'key'=>'clientes'],
        ['url'=>'reportes/ventas.php',        'icon'=>'fa-solid fa-chart-line',    'label'=>'Reportes',          'key'=>'reportes'],
    ];
    if ($rol==='admin')
        $nav[]=['url'=>'usuarios/listar.php','icon'=>'fa-solid fa-gear','label'=>'Usuarios','key'=>'usuarios'];

    /* Breadcrumb */
    $bc = '';
    if (!empty($breadcrumbs)) {
        $bc .= '<a href="'.$base.'dashboard.php"><i class="fa-solid fa-house"></i></a>';
        foreach ($breadcrumbs as $b) {
            $bc .= '<span class="sep"><i class="fa-solid fa-chevron-right"></i></span>';
            $bc .= !empty($b['url'])
                ? '<a href="'.$base.$b['url'].'">'.e($b['label']).'</a>'
                : '<span class="current">'.e($b['label']).'</span>';
        }
    }

    echo '<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>'.e($titulo).' — Abarrotes Romina</title>
  <link rel="icon" type="image/png" href="'.$base.'img/icono.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="'.$base.'css/app.css">
</head>
<body>

<!-- ═══ SPINNER GLOBAL ═══ -->
<div class="spinner-overlay" id="spinnerOverlay">
  <div class="spinner-box">
    <div class="spinner-ring"></div>
    <div class="spinner-txt">Procesando...</div>
  </div>
</div>

<!-- ═══ LIGHTBOX ═══ -->
<div class="modal-overlay" id="lightboxOverlay" onclick="cerrarLightbox(event)">
  <div class="modal-box" style="position:relative">
    <button class="modal-close" onclick="cerrarLightbox()"><i class="fa-solid fa-xmark"></i></button>
    <img id="lightboxImg" class="modal-img" src="" alt="">
    <div id="lightboxCaption" style="padding:.75rem 1rem;font-family:var(--font-title);font-weight:700;font-size:.9rem;color:var(--g700);border-top:1px solid var(--g200)"></div>
  </div>
</div>

<div class="app-shell">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="'.$base.'img/icono.png" alt="Logo" class="logo-img">
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
        'Ventas'    => ['pos','credito','adeudos'],
        'Catálogo'  => ['productos','inventario','clientes'],
        'Sistema'   => ['reportes','usuarios'],
    ];
    $grupo_actual = '';
    foreach ($nav as $item) {
        // Detectar grupo
        foreach ($grupos as $glabel => $keys) {
            if (in_array($item['key'], $keys) && $grupo_actual !== $glabel) {
                $grupo_actual = $glabel;
                echo '<div class="nav-section-label">'.$glabel.'</div>';
            }
        }
        $cls = $activo===$item['key']?' activo':'';
        echo '<a href="'.$base.$item['url'].'" class="nav-item'.$cls.'" title="'.$item['label'].'">
          <i class="'.$item['icon'].' ni"></i>
          <span class="nl">'.$item['label'].'</span>
        </a>';
    }

    echo '
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">'.$ini.'</div>
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

<!-- ══ CONTENIDO ══ -->
<div class="main-content" id="mainContent">
  <header class="main-topbar">
    <button class="topbar-hamburger" onclick="toggleSidebar()" aria-label="Menú">
      <i class="fa-solid fa-bars"></i>
    </button>
    <nav class="topbar-breadcrumb">'.$bc.'</nav>
  </header>
  <main class="page-area">';
}

function layoutEnd() {
    echo '
  </main>
</div><!-- /main-content -->
</div><!-- /app-shell -->

<script>
/* ══ SIDEBAR COLAPSABLE ══ */
const sidebar       = document.getElementById("sidebar");
const mainContent   = document.getElementById("mainContent");
const toggleIcon    = document.getElementById("toggleIcon");
const overlay       = document.getElementById("sidebarOverlay");

let isMini = localStorage.getItem("sidebarMini") === "true";
if (isMini && window.innerWidth > 900) applySidebarMini(true, false);

function applySidebarMini(mini, save=true){
  isMini = mini;
  if (mini){
    sidebar.classList.add("mini");
    mainContent.classList.add("mini");
    if(toggleIcon) { toggleIcon.classList.remove("fa-angles-left"); toggleIcon.classList.add("fa-angles-right"); }
  } else {
    sidebar.classList.remove("mini");
    mainContent.classList.remove("mini");
    if(toggleIcon) { toggleIcon.classList.remove("fa-angles-right"); toggleIcon.classList.add("fa-angles-left"); }
  }
  if(save) localStorage.setItem("sidebarMini", mini);
}

function toggleMini(){
  if(window.innerWidth<=900) return;
  applySidebarMini(!isMini);
}

/* Tooltips en modo mini */
sidebar.querySelectorAll(".nav-item").forEach(el=>{
  el.addEventListener("mouseenter",()=>{
    if(sidebar.classList.contains("mini")){
      const tt = document.createElement("div");
      tt.className="sidebar-tooltip";
      tt.textContent=el.querySelector(".nl")?.textContent||"";
      Object.assign(tt.style,{
        position:"fixed",zIndex:"9999",
        background:"rgba(26,10,46,.95)",color:"#fff",
        padding:"5px 12px",borderRadius:"8px",fontSize:".78rem",fontWeight:"700",
        fontFamily:"var(--font-ui)",whiteSpace:"nowrap",pointerEvents:"none",
        boxShadow:"var(--sh-md)",left:"76px",
        top:(el.getBoundingClientRect().top+8)+"px"
      });
      document.body.appendChild(tt);
      el._tt=tt;
    }
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

/* ══ LIGHTBOX ══ */
function abrirLightbox(src, caption){
  document.getElementById("lightboxImg").src=src;
  document.getElementById("lightboxCaption").textContent=caption;
  document.getElementById("lightboxOverlay").classList.add("open");
}
function cerrarLightbox(e){
  if(!e||e.target===document.getElementById("lightboxOverlay"))
    document.getElementById("lightboxOverlay").classList.remove("open");
}
document.addEventListener("keydown",e=>{ if(e.key==="Escape") cerrarLightbox(); });

/* ══ SPINNER ══ */
function mostrarSpinner(txt="Procesando..."){
  document.querySelector(".spinner-txt").textContent=txt;
  document.getElementById("spinnerOverlay").classList.add("show");
}
function ocultarSpinner(){ document.getElementById("spinnerOverlay").classList.remove("show"); }

/* Spinner en formularios con class="con-spinner" */
document.querySelectorAll("form.con-spinner").forEach(f=>{
  f.addEventListener("submit",()=>mostrarSpinner());
});

/* ══ TOAST ══ */
function mostrarToast(msg="¡Agregado!", icono="✓"){
  const t=document.createElement("div");
  t.className="toast-pop";
  t.innerHTML=`<span>${icono}</span> ${msg}`;
  document.body.appendChild(t);
  setTimeout(()=>{
    t.classList.add("hide");
    setTimeout(()=>t.remove(),350);
  },2000);
}

/* ══ AUTO-OCULTAR ALERTAS ══ */
setTimeout(()=>{
  document.querySelectorAll(".alerta").forEach(el=>{
    el.style.transition="opacity .4s,transform .4s";
    el.style.opacity="0"; el.style.transform="translateY(-5px)";
    setTimeout(()=>el.remove(),400);
  });
},3800);
</script>
</body>
</html>';
}