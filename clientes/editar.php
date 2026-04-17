<?php
require_once '../includes/config.php';
requerirAutenticacion();

$id=isset($_GET['id'])?(int)$_GET['id']:0;
if($id<=0){header('Location: listar.php');exit;}
$s=$conn->prepare("SELECT * FROM clientes WHERE id_cliente=?");
$s->bind_param("i",$id);$s->execute();
$cliente=$s->get_result()->fetch_assoc();
if(!$cliente){header('Location: listar.php');exit;}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=limpiar($_POST['nombre']??'');
    $telefono=limpiar($_POST['telefono']??'');
    if($nombre===''){$error='El nombre es obligatorio.';}
    else{
        $s2=$conn->prepare("UPDATE clientes SET nombre=?,telefono=? WHERE id_cliente=?");
        $s2->bind_param("ssi",$nombre,$telefono,$id);
        if($s2->execute()){flashSet('exito',"Cliente actualizado.");header('Location: listar.php');exit;}
        else{$error='Error: '.$conn->error;}
    }
}
layoutStart('Editar Cliente','clientes',[['label'=>'Clientes','url'=>'clientes/listar.php'],['label'=>'Editar']]);
?>
<div class="page-head"><div class="page-title">✏️ Editar Cliente</div></div>
<div style="max-width:480px">
  <div class="card"><div class="card-body">
    <div style="background:var(--naranja-bg);border:1px solid var(--naranja-borde);border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#92400e;font-weight:600">
      💰 Adeudo actual: <strong><?=dinero($cliente['adeudo'])?></strong>
      <span style="opacity:.7;font-weight:400;display:block;margin-top:2px">Se actualiza automáticamente con ventas y abonos.</span>
    </div>
    <?php if($error): ?><div class="alerta alerta-error">✕ <?=e($error)?></div><?php endif ?>
    <form method="POST">
      <div class="form-group"><label class="form-label">Nombre completo</label>
        <input type="text" name="nombre" class="form-control" required value="<?=e($cliente['nombre'])?>"></div>
      <div class="form-group"><label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="<?=e($cliente['telefono'])?>"></div>
      <div style="display:flex;gap:.65rem">
        <button type="submit" class="btn btn-naranja">✓ Actualizar</button>
        <a href="listar.php" class="btn btn-gris">Cancelar</a>
      </div>
    </form>
  </div></div>
</div>
<?php layoutEnd(); ?>