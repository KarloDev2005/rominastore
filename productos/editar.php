<?php
require_once '../includes/config.php';
requerirAutenticacion();

$id=isset($_GET['id'])?(int)$_GET['id']:0;
if($id<=0){header('Location: listar.php');exit;}
$s=$conn->prepare("SELECT * FROM productos WHERE id_producto=?");
$s->bind_param("i",$id);$s->execute();
$producto=$s->get_result()->fetch_assoc();
if(!$producto){header('Location: listar.php');exit;}

$error=$exito='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=limpiar($_POST['nombre']??'');
    $precio=(float)($_POST['precio']??0);
    $stock=(int)($_POST['stock']??0);
    if($nombre===''||$precio<=0){$error='Nombre y precio son obligatorios.';}
    else{
        $s2=$conn->prepare("UPDATE productos SET nombre=?,precio=?,stock=? WHERE id_producto=?");
        $s2->bind_param("sdii",$nombre,$precio,$stock,$id);
        if($s2->execute()){
            $producto['nombre']=$nombre;$producto['precio']=$precio;$producto['stock']=$stock;
            flashSet('exito',"Producto actualizado.");
            header('Location: listar.php');exit;
        }else{$error='Error: '.$conn->error;}
    }
}

layoutStart('Editar Producto','productos',[['label'=>'Productos','url'=>'productos/listar.php'],['label'=>'Editar']]);
?>
<div class="page-head"><div class="page-title">✏️ Editar Producto</div></div>
<div style="max-width:480px">
  <div class="card"><div class="card-body">
    <?php if($error): ?><div class="alerta alerta-error">✕ <?=e($error)?></div><?php endif ?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" required value="<?=e($producto['nombre'])?>"></div>
      <div class="form-group"><label class="form-label">Precio ($)</label>
        <input type="number" step="0.01" name="precio" class="form-control" required value="<?=$producto['precio']?>"></div>
      <div class="form-group"><label class="form-label">Stock</label>
        <input type="number" name="stock" class="form-control" value="<?=$producto['stock']?>"></div>
      <div style="display:flex;gap:.65rem">
        <button type="submit" class="btn btn-naranja">✓ Actualizar</button>
        <a href="listar.php" class="btn btn-gris">Cancelar</a>
      </div>
    </form>
  </div></div>
</div>
<?php layoutEnd(); ?>