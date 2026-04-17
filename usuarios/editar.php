<?php
require_once '../includes/config.php';
requerirAdmin();

$id=isset($_GET['id'])?(int)$_GET['id']:0;
if($id<=0){header('Location: listar.php');exit;}
$s=$conn->prepare("SELECT id_usuario,nombre,rol FROM usuarios WHERE id_usuario=?");
$s->bind_param("i",$id);$s->execute();
$usuario=$s->get_result()->fetch_assoc();
if(!$usuario){header('Location: listar.php');exit;}

$error=$exito='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=limpiar($_POST['nombre']??'');
    $rol=$_POST['rol']==='admin'?'admin':'cajero';
    $nueva_pw=$_POST['nueva_contrasena']??'';
    if($nombre===''){$error='El nombre es obligatorio.';}
    elseif($nueva_pw!==''&&strlen($nueva_pw)<6){$error='La contraseña nueva debe tener al menos 6 caracteres.';}
    else{
        if($nueva_pw!==''){
            $hash=password_hash($nueva_pw,PASSWORD_DEFAULT);
            $s=$conn->prepare("UPDATE usuarios SET nombre=?,contrasena=?,rol=? WHERE id_usuario=?");
            $s->bind_param("sssi",$nombre,$hash,$rol,$id);
        }else{
            $s=$conn->prepare("UPDATE usuarios SET nombre=?,rol=? WHERE id_usuario=?");
            $s->bind_param("ssi",$nombre,$rol,$id);
        }
        if($s->execute()){
            flashSet('exito',"Usuario «{$nombre}» actualizado.");
            header('Location: listar.php');exit;
        }else{$error='Error al actualizar: '.$conn->error;}
    }
}

layoutStart('Editar Usuario','usuarios',[['label'=>'Usuarios','url'=>'usuarios/listar.php'],['label'=>'Editar']]);
?>

<div class="page-head">
  <div class="page-title">✏️ Editar Usuario</div>
</div>

<div style="max-width:480px">
  <div class="card">
    <div class="card-body">
      <?php if($error): ?><div class="alerta alerta-error">✕ <?=e($error)?></div><?php endif ?>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Nombre de usuario</label>
          <input type="text" name="nombre" class="form-control" required value="<?=e($usuario['nombre'])?>">
        </div>
        <div class="form-group">
          <label class="form-label">Rol</label>
          <select name="rol" class="form-control">
            <option value="cajero" <?=$usuario['rol']==='cajero'?'selected':''?>>Cajero — solo ventas e inventario</option>
            <option value="admin"  <?=$usuario['rol']==='admin' ?'selected':''?>>Administrador — acceso completo</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nueva contraseña <small style="font-weight:400;color:var(--gris-400)">(dejar en blanco para no cambiar)</small></label>
          <input type="password" name="nueva_contrasena" class="form-control" placeholder="••••••">
        </div>
        <div style="display:flex;gap:.65rem;flex-wrap:wrap">
          <button type="submit" class="btn btn-naranja">✓ Actualizar</button>
          <a href="listar.php" class="btn btn-gris">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php layoutEnd(); ?>