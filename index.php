<?php
require_once 'includes/config.php';
if (isset($_SESSION['usuario_id'])) { header('Location: dashboard.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = limpiar($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($nombre === '' || $password === '') {
        $error = 'Completa usuario y contraseña.';
    } else {
        $stmt = $conn->prepare("SELECT id_usuario,nombre,contrasena,rol FROM usuarios WHERE nombre=?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        if ($u && password_verify($password, $u['contrasena'])) {
            $_SESSION['usuario_id']     = $u['id_usuario'];
            $_SESSION['usuario_nombre'] = $u['nombre'];
            $_SESSION['rol']            = $u['rol'];
            header('Location: dashboard.php'); exit();
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Iniciar Sesión — Abarrotes Romina</title>
  <link rel="icon" type="image/png" href="img/icono.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --p1:#7c1fa0;--p2:#9b35bc;--p3:#6b1a8c;
      --m1:#c4257a;--m2:#e84da0;
      --cream:#faf8f5;--blanco:#ffffff;
      --gris-100:#f0edf5;--gris-200:#e0d8ea;--gris-400:#9b8ab0;--gris-700:#3a2a50;
      --fuente:'Nunito',sans-serif;
    }
    html,body{height:100%;font-family:var(--fuente);overflow:hidden}

    /* ── Outer shell — fondo dividido igual que la referencia ── */
    .outer{
      min-height:100vh;
      display:flex;align-items:center;justify-content:center;
      background:
        linear-gradient(135deg,rgba(124,31,160,.15) 0%,rgba(196,37,122,.08) 100%),
        var(--cream);
      padding:1.5rem;
    }

    /* ── Card principal ── */
    .card-login{
      display:grid;
      grid-template-columns:1fr 1fr;
      width:100%;max-width:860px;min-height:520px;
      border-radius:28px;
      overflow:hidden;
      box-shadow:0 32px 80px rgba(124,31,160,.22),0 8px 32px rgba(0,0,0,.1);
      animation:cardIn .55s cubic-bezier(.2,.9,.3,1) both;
    }
    @keyframes cardIn{
      from{opacity:0;transform:translateY(24px) scale(.97)}
      to  {opacity:1;transform:translateY(0) scale(1)}
    }

    /* ── Panel izquierdo (morado) ── */
    .panel-izq{
      background:linear-gradient(160deg,var(--p1) 0%,var(--p3) 45%,#3d1278 100%);
      display:flex;flex-direction:column;
      align-items:center;justify-content:center;
      padding:2.5rem 2rem;
      position:relative;
      overflow:hidden;
    }

    /* Círculos decorativos de fondo */
    .panel-izq::before{
      content:'';position:absolute;
      width:340px;height:340px;border-radius:50%;
      background:rgba(255,255,255,.05);
      top:-120px;left:-120px;
    }
    .panel-izq::after{
      content:'';position:absolute;
      width:220px;height:220px;border-radius:50%;
      background:rgba(196,37,122,.18);
      bottom:-70px;right:-70px;
    }

    /* Líneas decorativas (tipo la imagen de referencia) */
    .deco-lines{
      position:absolute;bottom:40px;left:50%;transform:translateX(-50%);
      display:flex;gap:8px;
    }
    .deco-line{
      border-radius:99px;opacity:.35;
      background:linear-gradient(135deg,var(--m1),var(--m2));
    }
    .dl1{width:6px;height:70px;transform:rotate(-20deg) translateY(10px)}
    .dl2{width:6px;height:110px;transform:rotate(-20deg)}
    .dl3{width:6px;height:80px;transform:rotate(-20deg) translateY(15px)}

    /* ── LOGO CIRCULAR (icono con fondo blanco) ── */
    .logo-circle-wrap{
      position:relative;z-index:1;
      margin-bottom:1.6rem;
    }
    .logo-circle{
      width:130px;height:130px;border-radius:50%;
      background:var(--blanco);
      display:flex;align-items:center;justify-content:center;
      box-shadow:
        0 0 0 8px rgba(255,255,255,.12),
        0 0 0 16px rgba(255,255,255,.06),
        0 16px 48px rgba(0,0,0,.3);
      animation:logoFloat 4s ease-in-out infinite;
      transition:.3s;
    }
    @keyframes logoFloat{
      0%,100%{transform:translateY(0)}
      50%{transform:translateY(-8px)}
    }
    .logo-circle img{
      width:90px;height:90px;object-fit:contain;
      border-radius:50%;
    }
    /* Anillo giratorio decorativo */
    .logo-ring{
      position:absolute;inset:-8px;border-radius:50%;
      border:2px dashed rgba(255,255,255,.18);
      animation:ringRotate 20s linear infinite;
    }
    @keyframes ringRotate{to{transform:rotate(360deg)}}

    /* Textos del panel izquierdo */
    .izq-title{
      font-size:1.35rem;font-weight:900;color:#fff;
      text-align:center;letter-spacing:-.3px;
      position:relative;z-index:1;margin-bottom:.3rem;
    }
    .izq-sub{
      font-size:.8rem;color:rgba(255,255,255,.5);
      text-align:center;font-weight:500;
      position:relative;z-index:1;
    }

    /* Features / puntos */
    .feats{
      margin-top:1.6rem;display:flex;flex-direction:column;gap:.5rem;
      position:relative;z-index:1;width:100%;
    }
    .feat{
      display:flex;align-items:center;gap:.6rem;
      font-size:.78rem;color:rgba(255,255,255,.65);font-weight:600;
    }
    .fdot{
      width:28px;height:28px;border-radius:8px;
      background:rgba(255,255,255,.1);
      display:flex;align-items:center;justify-content:center;
      font-size:.85rem;flex-shrink:0;
    }

    /* ── Panel derecho (blanco / formulario) ── */
    .panel-der{
      background:var(--blanco);
      display:flex;flex-direction:column;
      align-items:center;justify-content:center;
      padding:2.5rem 2.2rem;
    }

    .form-head{margin-bottom:1.8rem;text-align:left;width:100%}
    .form-head h2{
      font-size:1.5rem;font-weight:900;color:var(--gris-700);letter-spacing:-.3px;
    }
    .form-head p{font-size:.82rem;color:var(--gris-400);margin-top:.25rem}

    /* Tab decorativo (inactivo — referencia) */
    .tab-bar{display:flex;gap:1rem;margin-bottom:1.6rem;width:100%}
    .tab{
      font-size:.88rem;font-weight:800;color:var(--gris-400);
      padding:.3rem 0;cursor:default;
      border-bottom:2.5px solid transparent;
      transition:.2s;
    }
    .tab.activo{color:var(--p1);border-color:var(--p1)}

    /* Error */
    .error-box{
      background:#fdf0f7;border:1px solid rgba(196,37,122,.2);
      border-left:3px solid var(--m1);
      border-radius:10px;padding:.7rem 1rem;
      font-size:.82rem;font-weight:700;color:var(--m1);
      margin-bottom:1.1rem;width:100%;
      display:flex;align-items:center;gap:.5rem;
      animation:shake .3s ease;
    }
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}

    /* Campos */
    .field{margin-bottom:1.1rem;width:100%}
    .field label{
      display:block;font-size:.72rem;font-weight:800;
      color:var(--p1);margin-bottom:.35rem;
      text-transform:uppercase;letter-spacing:.4px;
    }
    .field-wrap{position:relative}
    .field input{
      width:100%;padding:.72rem 2.8rem .72rem 1rem;
      border:none;border-bottom:2px solid var(--gris-200);
      background:transparent;
      font-family:var(--fuente);font-size:.92rem;color:var(--gris-700);
      outline:none;transition:.2s;border-radius:0;
    }
    .field input:focus{border-color:var(--p1)}
    .field input::placeholder{color:var(--gris-200)}
    .toggle-pw{
      position:absolute;right:.3rem;top:50%;transform:translateY(-50%);
      background:none;border:none;cursor:pointer;
      font-size:.88rem;color:var(--gris-400);padding:4px;transition:.15s;
    }
    .toggle-pw:hover{color:var(--p1)}

    /* Botón */
    .btn-ingresar{
      width:100%;padding:.82rem;
      background:linear-gradient(135deg,var(--p1),var(--m1));
      color:#fff;border:none;border-radius:12px;
      font-family:var(--fuente);font-size:.95rem;font-weight:900;
      cursor:pointer;letter-spacing:.3px;
      box-shadow:0 6px 22px rgba(124,31,160,.35);
      transition:all .22s;margin-top:.4rem;
    }
    .btn-ingresar:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(124,31,160,.5)}
    .btn-ingresar:active{transform:translateY(0)}

    /* Footer del form */
    .form-footer{
      margin-top:1.5rem;text-align:center;
      font-size:.72rem;color:var(--gris-400);
    }

    /* Responsive */
    @media(max-width:680px){
      .card-login{grid-template-columns:1fr}
      .panel-izq{display:none}
      .outer{padding:0}
      .card-login{border-radius:0;min-height:100vh}
      .panel-der{padding:2rem 1.5rem}
      html,body{overflow:auto}
    }
  </style>
</head>
<body>
<div class="outer">
  <div class="card-login">

    <!-- ── Panel izquierdo ── -->
    <div class="panel-izq">
      <div class="deco-lines">
        <div class="deco-line dl1"></div>
        <div class="deco-line dl2"></div>
        <div class="deco-line dl3"></div>
      </div>

      <!-- Icono circular con fondo blanco -->
      <div class="logo-circle-wrap">
        <div class="logo-ring"></div>
        <div class="logo-circle">
          <img src="img/icono.png" alt="Abarrotes Romina">
        </div>
      </div>

      <div class="izq-title">Abarrotes Romina</div>
      <div class="izq-sub">Sistema POS · Gestión integral</div>

      <div class="feats">
        <div class="feat"><div class="fdot">🛒</div>Punto de venta rápido</div>
        <div class="feat"><div class="fdot">💳</div>Control de fiados y adeudos</div>
        <div class="feat"><div class="fdot">📱</div>Notificaciones WhatsApp</div>
        <div class="feat"><div class="fdot">📊</div>Reportes y estadísticas</div>
      </div>
    </div>

    <!-- ── Panel derecho (formulario) ── -->
    <div class="panel-der">
      <div style="width:100%;max-width:320px">

        <div class="form-head">
          <h2>Iniciar sesión</h2>
          <p>Ingresa con tus credenciales asignadas</p>
        </div>

        <!-- Tab decorativo (como la referencia) -->
        <div class="tab-bar">
          <div class="tab activo">Acceso</div>
          <div class="tab">Sistema POS</div>
        </div>

        <?php if($error): ?>
          <div class="error-box">⛔ <?=e($error)?></div>
        <?php endif ?>

        <form method="POST" autocomplete="off">
          <div class="field">
            <label for="usuario">Usuario</label>
            <div class="field-wrap">
              <input type="text" id="usuario" name="usuario"
                     placeholder="Nombre de usuario"
                     value="<?=e($_POST['usuario']??'')?>"
                     required autofocus>
            </div>
          </div>

          <div class="field">
            <label for="password">Contraseña</label>
            <div class="field-wrap">
              <input type="password" id="password" name="password"
                     placeholder="••••••••" required>
              <button type="button" class="toggle-pw" onclick="togglePw()" id="eyeBtn">👁</button>
            </div>
          </div>

          <button type="submit" class="btn-ingresar">Ingresar →</button>
        </form>

        <div class="form-footer">
          Uso exclusivo del personal autorizado<br>
          <strong style="color:var(--p1)">Abarrotes Romina</strong> · POS v3.0
        </div>
      </div>
    </div>

  </div>
</div>

<script>
function togglePw(){
  const i=document.getElementById('password'),b=document.getElementById('eyeBtn');
  i.type=i.type==='password'?'text':'password';
  b.textContent=i.type==='password'?'👁':'🙈';
}
</script>
</body>
</html>