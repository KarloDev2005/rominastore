<?php
require_once '../includes/config.php';
requerirAdmin();

$error=$exito='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=limpiar($_POST['nombre']??'');
    $contrasena=$_POST['contrasena']??'';
    $rol=$_POST['rol']==='admin'?'admin':'cajero';
    if($nombre===''||$contrasena===''){
        $error='Nombre y contraseña son obligatorios.';
    }elseif(strlen($contrasena)<6){
        $error='La contraseña debe tener al menos 6 caracteres.';
    }else{
        $hash=password_hash($contrasena,PASSWORD_DEFAULT);
        $s=$conn->prepare("INSERT INTO usuarios(nombre,contrasena,rol)VALUES(?,?,?)");
        $s->bind_param("sss",$nombre,$hash,$rol);
        if($s->execute()){
            flashSet('exito',"Usuario «{$nombre}» creado correctamente.");
            header('Location: listar.php');exit;
        }else{
            $error='Error al crear: '.$conn->error;
        }
    }
}

layoutStart('Agregar Usuario','usuarios',[['label'=>'Usuarios','url'=>'usuarios/listar.php'],['label'=>'Agregar']]);
?>

<div class="page-head">
  <div class="page-title">➕ Agregar Usuario</div>
</div>

<div style="max-width:480px">
  <div class="card">
    <div class="card-body">
      <?php if($error): ?><div class="alerta alerta-error">✕ <?=e($error)?></div><?php endif ?>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Nombre de usuario</label>
          <input type="text" name="nombre" class="form-control" required value="<?=e($_POST['nombre']??'')?>">
        </div>
        <div class="form-group">
          <label class="form-label">Contraseña (mínimo 6 caracteres)</label>
          <input type="password" name="contrasena" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Rol</label>
          <select name="rol" class="form-control">
            <option value="cajero">Cajero — solo ventas e inventario</option>
            <option value="admin">Administrador — acceso completo</option>
          </select>
        </div>
        <div style="display:flex;gap:.65rem;flex-wrap:wrap">
          <button type="submit" class="btn btn-verde">✓ Guardar Usuario</button>
          <a href="listar.php" class="btn btn-gris">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php layoutEnd(); ?>