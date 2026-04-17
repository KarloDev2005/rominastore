<?php
require_once '../includes/config.php';
requerirAutenticacion();

$error=$exito='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=limpiar($_POST['nombre']??'');
    $precio=(float)($_POST['precio']??0);
    $stock=(int)($_POST['stock']??0);
    if($nombre===''||$precio<=0){$error='Nombre y precio son obligatorios.';}
    else{
        $s=$conn->prepare("INSERT INTO productos(nombre,precio,stock)VALUES(?,?,?)");
        $s->bind_param("sdi",$nombre,$precio,$stock);
        if($s->execute()){flashSet('exito',"Producto «{$nombre}» agregado.");header('Location: listar.php');exit;}
        else{$error='Error al agregar: '.$conn->error;}
    }
}

layoutStart('Agregar Producto','productos',[['label'=>'Productos','url'=>'productos/listar.php'],['label'=>'Agregar']]);
?>
<div class="page-head"><div class="page-title">➕ Agregar Producto</div></div>
<div style="max-width:480px">
  <div class="card"><div class="card-body">
    <?php if($error): ?><div class="alerta alerta-error">✕ <?=e($error)?></div><?php endif ?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Nombre del producto</label>
        <input type="text" name="nombre" class="form-control" required value="<?=e($_POST['nombre']??'')?>"></div>
      <div class="form-group"><label class="form-label">Precio de venta ($)</label>
        <input type="number" step="0.01" name="precio" class="form-control" required value="<?=e($_POST['precio']??'')?>"></div>
      <div class="form-group"><label class="form-label">Stock inicial</label>
        <input type="number" name="stock" class="form-control" value="<?=e($_POST['stock']??0)?>"></div>
      <div style="display:flex;gap:.65rem">
        <button type="submit" class="btn btn-verde">✓ Guardar</button>
        <a href="listar.php" class="btn btn-gris">Cancelar</a>
      </div>
    </form>
  </div></div>
</div>
<?php layoutEnd(); ?>