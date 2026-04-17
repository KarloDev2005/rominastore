<?php
require_once 'includes/config.php';

if (isset($_SESSION['usuario_id'])) { header('Location: dashboard.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = limpiar($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '' || $password === '') {
        $error = 'Por favor completa usuario y contraseña.';
    } else {
        $stmt = $conn->prepare("SELECT id_usuario, nombre, contrasena, rol FROM usuarios WHERE nombre = ?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();

        if ($u && password_verify($password, $u['contrasena'])) {
            $_SESSION['usuario_id']     = $u['id_usuario'];
            $_SESSION['usuario_nombre'] = $u['nombre'];
            $_SESSION['rol']            = $u['rol'];
            header('Location: dashboard.php'); exit();
        } else {
            $error = 'Usuario o contraseña incorrectos. Verifica tus datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión — Abarrotes Romina</title>
  <link rel="icon" type="image/png" href="img/icono.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --morado:    #7c1fa0;
      --morado-h:  #6b1a8c;
      --magenta:   #c4257a;
      --oscuro:    #1a0a2e;
      --fuente:    'Nunito', sans-serif;
    }

    html, body { height: 100%; font-family: var(--fuente); overflow: hidden; }

    /* ── Fondo con logo de marca ── */
    .bg-layer {
      position: fixed; inset: 0; z-index: 0;
      /* El logotipo completo como fondo muy tenue */
      background:
        linear-gradient(135deg, rgba(26,10,46,.97) 0%, rgba(45,20,88,.95) 50%, rgba(26,10,46,.97) 100%);
    }

    /* Logotipo de fondo decorativo (grande, translúcido) */
    .bg-logo {
      position: fixed;
      right: -80px; bottom: -80px;
      width: 680px; height: 680px;
      object-fit: contain;
      opacity: .04;
      z-index: 0;
      pointer-events: none;
      filter: grayscale(1) invert(1);
      animation: floatBg 8s ease-in-out infinite;
    }
    @keyframes floatBg {
      0%,100% { transform: translateY(0) rotate(-3deg); }
      50%      { transform: translateY(-18px) rotate(-3deg); }
    }

    /* Círculos decorativos */
    .deco {
      position: fixed; border-radius: 50%;
      background: linear-gradient(135deg, var(--morado), var(--magenta));
      opacity: .08; pointer-events: none; z-index: 0;
    }
    .d1 { width: 420px; height: 420px; top: -140px; left: -140px; animation: pulse 6s ease-in-out infinite; }
    .d2 { width: 260px; height: 260px; bottom: 40px; left: 25%; animation: pulse 8s ease-in-out infinite reverse; }
    .d3 { width: 160px; height: 160px; top: 30%; right: 30px; animation: pulse 5s ease-in-out infinite 1s; }
    @keyframes pulse {
      0%,100% { transform: scale(1); opacity: .08; }
      50%      { transform: scale(1.08); opacity: .12; }
    }

    /* ── Shell principal ── */
    .login-shell {
      position: relative; z-index: 1;
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 1.5rem;
    }

    /* ── Tarjeta de login ── */
    .login-card {
      width: 100%; max-width: 420px;
      background: rgba(255,255,255,.06);
      backdrop-filter: blur(20px) saturate(1.5);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 24px;
      padding: 2.4rem 2rem;
      box-shadow:
        0 32px 80px rgba(0,0,0,.5),
        0 0 0 1px rgba(255,255,255,.06) inset;
      animation: cardIn .55s cubic-bezier(0.2, 0.9, 0.3, 1) forwards;
    }
    @keyframes cardIn {
      from { opacity: 0; transform: translateY(28px) scale(.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Logo y cabecera */
    .login-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .logo-wrap {
      display: inline-block;
      margin-bottom: 1.1rem;
      position: relative;
    }
    .logo-icon {
      width: 90px; height: 90px;
      object-fit: contain;
      border-radius: 22px;
      filter: drop-shadow(0 8px 24px rgba(196,37,122,.5));
      animation: logoFloat 4s ease-in-out infinite;
    }
    @keyframes logoFloat {
      0%,100% { transform: translateY(0); }
      50%      { transform: translateY(-6px); }
    }
    .logo-glow {
      position: absolute;
      inset: -12px;
      border-radius: 28px;
      background: radial-gradient(circle, rgba(196,37,122,.25) 0%, transparent 70%);
      animation: glowPulse 3s ease-in-out infinite;
    }
    @keyframes glowPulse {
      0%,100% { opacity: .6; }
      50%      { opacity: 1; }
    }

    .login-header h1 {
      font-size: 1.55rem; font-weight: 900; color: #fff;
      letter-spacing: -.3px; margin-bottom: .2rem;
    }
    .login-header p {
      font-size: .82rem; color: rgba(255,255,255,.45); font-weight: 500;
    }

    /* Error */
    .error-box {
      background: rgba(220,38,38,.15);
      border: 1px solid rgba(220,38,38,.3);
      border-left: 3px solid #dc2626;
      border-radius: 10px;
      padding: .75rem 1rem;
      font-size: .82rem; font-weight: 700;
      color: #fca5a5;
      margin-bottom: 1.2rem;
      display: flex; align-items: center; gap: .5rem;
      animation: shake .3s ease;
    }
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      25%      { transform: translateX(-6px); }
      75%      { transform: translateX(6px); }
    }

    /* Campos */
    .field { margin-bottom: 1rem; }
    .field label {
      display: block;
      font-size: .7rem; font-weight: 800;
      text-transform: uppercase; letter-spacing: .5px;
      color: rgba(255,255,255,.5);
      margin-bottom: .35rem;
    }
    .field-wrap { position: relative; }
    .field-icon {
      position: absolute; left: .95rem; top: 50%;
      transform: translateY(-50%);
      font-size: 1rem; pointer-events: none;
      color: rgba(255,255,255,.3);
      transition: color .2s;
    }
    .field-wrap:focus-within .field-icon { color: rgba(196,37,122,.8); }

    .field input {
      width: 100%;
      padding: .78rem 3rem .78rem 2.85rem;
      background: rgba(255,255,255,.07);
      border: 1.5px solid rgba(255,255,255,.12);
      border-radius: 11px;
      font-family: var(--fuente); font-size: .92rem;
      color: #fff;
      outline: none; transition: all .2s;
    }
    .field input::placeholder { color: rgba(255,255,255,.22); }
    .field input:focus {
      background: rgba(255,255,255,.11);
      border-color: rgba(196,37,122,.6);
      box-shadow: 0 0 0 4px rgba(196,37,122,.12);
    }
    .toggle-pw {
      position: absolute; right: .9rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      font-size: .9rem; cursor: pointer;
      color: rgba(255,255,255,.3); padding: 4px;
      transition: color .15s;
    }
    .toggle-pw:hover { color: rgba(255,255,255,.6); }

    /* Botón principal */
    .btn-ingresar {
      width: 100%;
      padding: .88rem;
      margin-top: .6rem;
      background: linear-gradient(135deg, var(--morado), var(--magenta));
      color: #fff; border: none; border-radius: 12px;
      font-family: var(--fuente); font-size: .98rem; font-weight: 900;
      cursor: pointer; letter-spacing: .2px;
      box-shadow: 0 6px 24px rgba(196,37,122,.4);
      transition: all .22s;
      position: relative; overflow: hidden;
    }
    .btn-ingresar::after {
      content: '';
      position: absolute; inset: 0;
      background: rgba(255,255,255,0);
      transition: background .2s;
    }
    .btn-ingresar:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 32px rgba(196,37,122,.55);
    }
    .btn-ingresar:hover::after { background: rgba(255,255,255,.06); }
    .btn-ingresar:active  { transform: translateY(0); }

    /* Footer */
    .login-footer {
      margin-top: 1.8rem;
      text-align: center;
      font-size: .73rem;
      color: rgba(255,255,255,.2);
    }

    /* Pie con logo completo pequeño */
    .brand-footer {
      display: flex; align-items: center; justify-content: center;
      gap: .5rem; margin-top: .5rem;
    }
    .brand-footer img {
      height: 28px; object-fit: contain; opacity: .35;
      filter: grayscale(1) invert(1);
    }

    /* Responsive */
    @media (max-width: 480px) {
      .login-card { padding: 1.8rem 1.4rem; }
      .bg-logo    { width: 350px; height: 350px; right: -60px; bottom: -60px; }
    }
  </style>
</head>
<body>

<div class="bg-layer"></div>
<!-- Logo de fondo decorativo (el logotipo completo) -->
<img src="img/logo_full.jpeg" alt="" class="bg-logo" aria-hidden="true">
<div class="deco d1"></div>
<div class="deco d2"></div>
<div class="deco d3"></div>

<div class="login-shell">
  <div class="login-card">

    <!-- Cabecera con el icono R-Romina -->
    <div class="login-header">
      <div class="logo-wrap">
        <div class="logo-glow"></div>
        <img src="img/icono.png" alt="Abarrotes Romina" class="logo-icon">
      </div>
      <h1>Abarrotes Romina</h1>
      <p>Sistema de Punto de Venta · Ingresa con tus credenciales</p>
    </div>

    <?php if ($error): ?>
      <div class="error-box">⛔ <?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="field">
        <label for="usuario">Usuario</label>
        <div class="field-wrap">
          <span class="field-icon">👤</span>
          <input
            type="text"
            id="usuario"
            name="usuario"
            placeholder="Nombre de usuario"
            value="<?php echo e($_POST['usuario'] ?? ''); ?>"
            required autofocus
          >
        </div>
      </div>

      <div class="field">
        <label for="password">Contraseña</label>
        <div class="field-wrap">
          <span class="field-icon">🔒</span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
          >
          <button type="button" class="toggle-pw" onclick="togglePw()" id="eyeBtn" title="Mostrar/ocultar">👁</button>
        </div>
      </div>

      <button type="submit" class="btn-ingresar">
        Ingresar al Sistema →
      </button>
    </form>

    <div class="login-footer">
      <div>Sistema POS v3.0 — Uso exclusivo del personal autorizado</div>
      <div class="brand-footer">
        <img src="img/logo_full.jpeg" alt="Abarrotes Romina">
      </div>
    </div>

  </div>
</div>

<script>
function togglePw() {
  const inp = document.getElementById('password');
  const btn = document.getElementById('eyeBtn');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
</script>
</body>
</html>