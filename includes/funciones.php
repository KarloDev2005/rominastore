<?php
/* ═══════════════════════════════════════════
   ROMINASTORE — Funciones v2
   
   CORRECCIONES:
   - limpiar() SIN real_escape_string (prepared statements ya protegen)
   - Helpers e(), dinero(), csrfInput/Verificar(), flash*()
   - layoutStart() / layoutEnd() para el shell con sidebar
   ═══════════════════════════════════════════ */

/* ─── Redirección ─── */
function redirigir($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

/* ─── Autenticación ─── */
function sesionIniciada()     { return !empty($_SESSION['usuario_id']); }
function tieneRol($rol)       { return isset($_SESSION['rol']) && $_SESSION['rol'] === $rol; }
function nombreUsuario()      { return $_SESSION['usuario_nombre'] ?? 'Invitado'; }
function rolActual()          { return $_SESSION['rol'] ?? ''; }

function requerirAutenticacion() {
    if (!sesionIniciada()) redirigir('index.php');
}
function requerirAdmin() {
    requerirAutenticacion();
    if (!tieneRol('admin')) redirigir('dashboard.php');
}

/* ─── Sanitización ────────────────────────────────────────────────────────
   limpiar() NO usa real_escape_string.
   Los prepared statements (bind_param) ya protegen contra SQL injection.
   Usar ambos juntos rompía: "O'Brien" → "O\'Brien" en BD.
   ─────────────────────────────────────────────────────────────────────── */
function limpiar($dato) { return trim(strip_tags((string)$dato)); }

/* Output seguro (htmlspecialchars) */
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

/* Formato monetario */
function dinero($monto) { return '$' . number_format((float)$monto, 2); }

/* ─── CSRF ────────────────────────────────────────────────────────────── */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}
function csrfVerificar() {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('<p style="font-family:sans-serif;color:#dc2626;padding:2rem">Token CSRF inválido. <a href="javascript:history.back()">Volver</a></p>');
    }
}

/* ─── Flash messages ──────────────────────────────────────────────────── */
function flashSet($tipo, $msg) { $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $msg]; }
function flashGet() { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function flashHtml() {
    $f = flashGet();
    if (!$f) return '';
    $clases = ['exito'=>'alerta-exito','error'=>'alerta-error','info'=>'alerta-info','aviso'=>'alerta-aviso'];
    $iconos = ['exito'=>'✓','error'=>'✕','info'=>'ℹ','aviso'=>'⚠'];
    $c = $clases[$f['tipo']] ?? 'alerta-info';
    $i = $iconos[$f['tipo']] ?? 'ℹ';
    return '<div class="alerta ' . $c . '">' . $i . ' ' . e($f['msg']) . '</div>';
}

/* ─── Layout con sidebar ──────────────────────────────────────────────── */
function layoutStart($titulo = 'RominaStore', $activo = '', $breadcrumbs = []) {
    $base = BASE_URL;
    $user = e(nombreUsuario());
    $rol  = rolActual();

    /* Inicial del usuario para avatar */
    $inicial = strtoupper(substr($user, 0, 1));

    /* Menú de navegación */
    $nav = [
        ['url' => 'ventas/nueva_venta.php',        'icon' => '🛒', 'label' => 'Punto de Venta',    'key' => 'pos'],
        ['url' => 'fiado/venta_credito.php',        'icon' => '💳', 'label' => 'Ventas a Crédito',  'key' => 'credito'],
        ['url' => 'fiado/consultar_adeudo.php',     'icon' => '📋', 'label' => 'Adeudos',            'key' => 'adeudos'],
        ['url' => 'productos/listar.php',           'icon' => '📦', 'label' => 'Productos',          'key' => 'productos'],
        ['url' => 'inventario/consultar.php',       'icon' => '🏬', 'label' => 'Inventario',         'key' => 'inventario'],
        ['url' => 'clientes/listar.php',            'icon' => '👥', 'label' => 'Clientes',           'key' => 'clientes'],
        ['url' => 'reportes/ventas.php',            'icon' => '📊', 'label' => 'Reportes',           'key' => 'reportes'],
    ];
    if ($rol === 'admin') {
        $nav[] = ['url' => 'usuarios/listar.php', 'icon' => '⚙️', 'label' => 'Usuarios', 'key' => 'usuarios'];
    }

    /* Breadcrumbs HTML */
    $bcHtml = '';
    if (!empty($breadcrumbs)) {
        $bcHtml .= '<a href="' . $base . 'dashboard.php">Inicio</a>';
        foreach ($breadcrumbs as $bc) {
            $bcHtml .= '<span class="sep">›</span>';
            if (!empty($bc['url'])) {
                $bcHtml .= '<a href="' . $base . $bc['url'] . '">' . e($bc['label']) . '</a>';
            } else {
                $bcHtml .= '<span class="current">' . e($bc['label']) . '</span>';
            }
        }
    }

    echo '<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . e($titulo) . ' — RominaStore</title>
  <link rel="stylesheet" href="' . $base . 'css/app.css">
</head>
<body>
<div class="app-shell">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-icon">🛒</div>
      <div class="logo-txt">
        <div class="logo-name">RominaStore</div>
        <div class="logo-sub">Abarrotes Romina</div>
      </div>
    </div>

    <nav class="sidebar-nav">';

    foreach ($nav as $item) {
        $eActivo = $activo === $item['key'] ? ' activo' : '';
        echo '
      <a href="' . $base . $item['url'] . '" class="nav-item' . $eActivo . '">
        <span class="nav-icon">' . $item['icon'] . '</span>
        <span class="nav-label">' . $item['label'] . '</span>
      </a>';
    }

    echo '
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="user-avatar">' . $inicial . '</div>
        <div class="user-info">
          <div class="user-name">' . $user . '</div>
          <div class="user-role">' . ucfirst($rol) . '</div>
        </div>
      </div>
      <a href="' . $base . 'logout.php" class="btn-salir">⏻ Cerrar sesión</a>
    </div>
  </aside>

  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- Contenido -->
  <div class="main-content">
    <header class="main-topbar">
      <button class="topbar-hamburger" onclick="toggleSidebar()" aria-label="Menú">☰</button>
      <nav class="topbar-breadcrumb">' . $bcHtml . '</nav>
      <div class="topbar-actions"></div>
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
}, 3800);
</script>
</body>
</html>';
}