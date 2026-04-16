<?php
/* ventas/procesar_venta.php
   FIX: Re-verifica stock con FOR UPDATE antes de insertar.
   Previene overselling en ventas concurrentes.
*/
require_once '../includes/config.php';
requerirAutenticacion();

if(empty($_SESSION['carrito'])){
    header('Location: nueva_venta.php'); exit;
}

$total=0;
foreach($_SESSION['carrito'] as $item) $total+=round($item['precio']*$item['cantidad'],2);

$conn->begin_transaction();
try{
    // Re-verificar stock
    foreach($_SESSION['carrito'] as $item){
        $s=$conn->prepare("SELECT stock,nombre FROM productos WHERE id_producto=? FOR UPDATE");
        $s->bind_param("i",$item['id']);
        $s->execute();
        $p=$s->get_result()->fetch_assoc();
        if(!$p) throw new Exception("Producto ID {$item['id']} no encontrado.");
        if($item['cantidad']>$p['stock'])
            throw new Exception("Stock insuficiente para «{$p['nombre']}» (solicitado: {$item['cantidad']}, disponible: {$p['stock']}).");
    }

    // Insertar venta
    $sv=$conn->prepare("INSERT INTO ventas(total,id_cliente,id_usuario)VALUES(?,NULL,?)");
    $sv->bind_param("di",$total,$_SESSION['usuario_id']);
    $sv->execute();
    $id_venta=$conn->insert_id;

    // Detalle + stock
    $sd=$conn->prepare("INSERT INTO detalle_venta(id_venta,id_producto,cantidad,precio_unitario,subtotal)VALUES(?,?,?,?,?)");
    $ss=$conn->prepare("UPDATE productos SET stock=stock-? WHERE id_producto=?");
    foreach($_SESSION['carrito'] as $item){
        $sub=round($item['precio']*$item['cantidad'],2);
        $sd->bind_param("iiidd",$id_venta,$item['id'],$item['cantidad'],$item['precio'],$sub);
        $sd->execute();
        $ss->bind_param("ii",$item['cantidad'],$item['id']);
        $ss->execute();
    }

    $conn->commit();
    $_SESSION['ultima_venta']=$id_venta;
    $_SESSION['carrito']=[];
    header('Location: ticket.php'); exit;

}catch(Exception $e){
    $conn->rollback();
    flashSet('error','No se pudo procesar la venta: '.$e->getMessage());
    header('Location: nueva_venta.php'); exit;
}