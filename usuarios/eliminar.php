<?php
require_once '../includes/config.php';
requerirAdmin();

$id=isset($_GET['id'])?(int)$_GET['id']:0;
if($id>0&&$id!=(int)$_SESSION['usuario_id']){
    /* No permitir eliminar al único admin */
    $sc=$conn->query("SELECT COUNT(*) c FROM usuarios WHERE rol='admin'");
    $cnt=$sc->fetch_assoc()['c'];
    $sr=$conn->prepare("SELECT rol FROM usuarios WHERE id_usuario=?");
    $sr->bind_param("i",$id);$sr->execute();
    $u=$sr->get_result()->fetch_assoc();
    if($u&&$u['rol']==='admin'&&$cnt<=1){
        flashSet('error','No puedes eliminar al único administrador del sistema.');
    }else{
        $s=$conn->prepare("DELETE FROM usuarios WHERE id_usuario=?");
        $s->bind_param("i",$id);$s->execute();
        flashSet('exito','Usuario eliminado correctamente.');
    }
}
header('Location: listar.php');exit;