<?php
require_once '../includes/config.php';
requerirAutenticacion();

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=limpiar($_POST['nombre']??'');
    $telefono=limpiar($_POST['telefono']??'');
    if($nombre===''){$error='El nombre es obligatorio.';}
    else{
        $s=$conn->prepare("INSERT INTO clientes(nombre,telefono)VALUES(?,?)");
        $s->bind_param("ss",$nombre,$telefono);
        if($s->execute()){flashSet('exito',"Cliente «{$nombre}» agregado.");header('Location: listar.php');exit;}
        else{$error='Error: '.$conn->error;}
    }
}
layoutStart('Agregar Cliente','clientes',[['label'=>'Clientes','url'=>'clientes/listar.php'],['label'=>'Agregar']]);
?>
<div class="page-head"><div class="page-title">➕ Agregar Cliente</div></div>
<div style="max-width:480px">
  <div class="card"><div class="card-body">
    <?php if($error): ?><div class="alerta alerta-error">✕ <?=e($error)?></div><?php endif ?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Nombre completo</label>
        <input type="text" name="nombre" class="form-control" required value="<?=e($_POST['nombre']??'')?>"></div>
      <div class="form-group"><label class="form-label">Teléfono (opcional)</label>
        <input type="text" name="telefono" class="form-control" value="<?=e($_POST['telefono']??'')?>"></div>
      <div style="display:flex;gap:.65rem">
        <button type="submit" class="btn btn-verde">✓ Guardar</button>
        <a href="listar.php" class="btn btn-gris">Cancelar</a>
      </div>
    </form>
  </div></div>
</div>
<?php layoutEnd(); ?>