<?php
/* ═══════════════════════════════════════════════════════
   ABARROTES ROMINA — Funciones Centrales v3
   ─────────────────────────────────────────────────────
   CORRECCIONES:
   · limpiar() sin real_escape_string (evita doble escape)
   · CSRF helpers
   · layoutStart() con sidebar de marca y logotipo real
   ═══════════════════════════════════════════════════════ */

function redirigir($url) { header("Location: " . BASE_URL . $url); exit(); }

function sesionIniciada() { return !empty($_SESSION['usuario_id']); }
function tieneRol($rol)   { return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol; }
function nombreUsuario()  { return $_SESSION['usuario_nombre'] ?? 'Invitado'; }
function rolActual()      { return $_SESSION['rol'] ?? ''; }

function requerirAutenticacion() {
    if (!sesionIniciada()) redirigir('index.php');
}
function requerirAdmin() {
    requerirAutenticacion();
    if (!tieneRol('admin')) redirigir('dashboard.php');
}

/* Sanitización — SIN real_escape_string (los prepared statements lo hacen solos) */
function limpiar($dato) { return trim(strip_tags((string)$dato)); }
function e($str)        { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function dinero($m)     { return '$' . number_format((float)$m, 2); }

/* ── CSRF ── */
function csrfToken() {
    if (empty($_SESSION['csrf_token']))
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}
function csrfVerificar() {
    if (!hash_equals(csrfToken(), $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;color:#dc2626;padding:2rem">Token inválido. <a href="javascript:history.back()">Volver</a></p>');
    }
}

/* ── Flash messages ── */
function flashSet($tipo, $msg) { $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $msg]; }
function flashGet() { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function flashHtml() {
    $f = flashGet(); if (!$f) return '';
    $m = ['exito'=>'alerta-exito','error'=>'alerta-error','info'=>'alerta-info','aviso'=>'alerta-aviso'];
    $i = ['exito'=>'✓','error'=>'✕','info'=>'ℹ','aviso'=>'⚠'];
    return '<div class="alerta '.($m[$f['tipo']]??'alerta-info').'">'
         . ($i[$f['tipo']]??'ℹ').' '.e($f['msg']).'</div>';
}

/* ═══════════════════════════════════════════════════════
   LAYOUT — Sidebar con logotipo de marca
   ═══════════════════════════════════════════════════════ */
function layoutStart($titulo = 'RominaStore', $activo = '', $breadcrumbs = []) {
    $base  = BASE_URL;
    $user  = e(nombreUsuario());
    $rol   = rolActual();
    $ini   = strtoupper(substr(strip_tags($user), 0, 1));

    /* Menú */
    $nav = [
        ['url'=>'ventas/nueva_venta.php',    'icon'=>'🛒', 'label'=>'Punto de Venta',   'key'=>'pos'],
        ['url'=>'fiado/venta_credito.php',   'icon'=>'💳', 'label'=>'Venta a Crédito',  'key'=>'credito'],
        ['url'=>'fiado/consultar_adeudo.php','icon'=>'📋', 'label'=>'Adeudos',           'key'=>'adeudos'],
        ['url'=>'productos/listar.php',      'icon'=>'📦', 'label'=>'Productos',         'key'=>'productos'],
        ['url'=>'inventario/consultar.php',  'icon'=>'🏬', 'label'=>'Inventario',        'key'=>'inventario'],
        ['url'=>'clientes/listar.php',       'icon'=>'👥', 'label'=>'Clientes',          'key'=>'clientes'],
        ['url'=>'reportes/ventas.php',       'icon'=>'📊', 'label'=>'Reportes',          'key'=>'reportes'],
    ];
    if ($rol === 'admin')
        $nav[] = ['url'=>'usuarios/listar.php','icon'=>'⚙️','label'=>'Usuarios','key'=>'usuarios'];

    /* Breadcrumb HTML */
    $bc = '';
    if (!empty($breadcrumbs)) {
        $bc .= '<a href="'.$base.'dashboard.php">Inicio</a>';
        foreach ($breadcrumbs as $b) {
            $bc .= '<span class="sep">›</span>';
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
  <link rel="stylesheet" href="'.$base.'css/app.css">
</head>
<body>
<div class="app-shell">

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="'.$base.'img/icono.png" alt="Logo Romina" class="logo-img">
    <div class="logo-txt">
      <div class="logo-name">Abarrotes Romina</div>
      <div class="logo-sub">Sistema POS</div>
    </div>
  </div>

  <nav class="sidebar-nav">';

    foreach ($nav as $item) {
        $cls = $activo === $item['key'] ? ' activo' : '';
        echo '
    <a href="'.$base.$item['url'].'" class="nav-item'.$cls.'">
      <span class="ni">'.$item['icon'].'</span>
      <span class="nl">'.$item['label'].'</span>
    </a>';
    }

    echo '
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="user-avatar">'.$ini.'</div>
      <div style="flex:1;min-width:0">
        <div class="user-name">'.$user.'</div>
        <div class="user-role">'.ucfirst($rol).'</div>
      </div>
    </div>
    <a href="'.$base.'logout.php" class="btn-salir">⏻ Cerrar sesión</a>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ══ CONTENIDO ══ -->
<div class="main-content">
  <header class="main-topbar">
    <button class="topbar-hamburger" onclick="toggleSidebar()" aria-label="Menú">☰</button>
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
function toggleSidebar(){
  document.getElementById("sidebar").classList.toggle("open");
  document.getElementById("sidebarOverlay").classList.toggle("open");
}
function closeSidebar(){
  document.getElementById("sidebar").classList.remove("open");
  document.getElementById("sidebarOverlay").classList.remove("open");
}
/* Auto-ocultar alertas */
setTimeout(()=>{
  document.querySelectorAll(".alerta").forEach(el=>{
    el.style.transition="opacity .4s";
    el.style.opacity="0";
    setTimeout(()=>el.remove(),400);
  });
},3800);
</script>
</body>
</html>';
}