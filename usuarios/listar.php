<?php
require_once '../includes/config.php';
requerirAdmin();

$flash=flashGet();
$sql="SELECT id_usuario,nombre,rol,fecha_creacion FROM usuarios ORDER BY id_usuario";
$resultado=$conn->query($sql);

layoutStart('Usuarios','usuarios',[['label'=>'Usuarios']]);
?>

<div class="page-head">
  <div class="page-title">⚙️ Usuarios</div>
  <div class="page-subtitle">Gestión de administradores y cajeros del sistema</div>
</div>

<?php if($flash): ?><div class="alerta alerta-<?=$flash['tipo']?>"><?=e($flash['msg'])?></div><?php endif ?>

<div style="margin-bottom:1.1rem">
  <a href="agregar.php" class="btn btn-verde">+ Agregar Usuario</a>
</div>

<div class="card">
  <div class="tabla-wrap">
    <table class="tabla">
      <thead>
        <tr><th>ID</th><th>Nombre</th><th>Rol</th><th>Registro</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php if($resultado->num_rows===0): ?>
          <tr><td colspan="5"><div class="empty-state"><div class="ei">👥</div><p>Sin usuarios registrados.</p></div></td></tr>
        <?php else: ?>
          <?php while($u=$resultado->fetch_assoc()): ?>
          <tr>
            <td class="num" style="color:var(--gris-400);font-size:.75rem"><?=$u['id_usuario']?></td>
            <td><strong><?=e($u['nombre'])?></strong>
              <?php if($u['id_usuario']==$_SESSION['usuario_id']): ?>
                <span class="badge badge-verde" style="margin-left:.4rem">Tú</span>
              <?php endif ?>
            </td>
            <td>
              <?php if($u['rol']==='admin'): ?>
                <span class="badge badge-rojo">Administrador</span>
              <?php else: ?>
                <span class="badge badge-gris">Cajero</span>
              <?php endif ?>
            </td>
            <td><?=date('d/m/Y',strtotime($u['fecha_creacion']))?></td>
            <td>
              <div style="display:flex;gap:.3rem;flex-wrap:wrap">
                <a href="editar.php?id=<?=$u['id_usuario']?>" class="btn btn-sm btn-naranja">✏ Editar</a>
                <?php if($u['id_usuario']!=$_SESSION['usuario_id']): ?>
                  <a href="eliminar.php?id=<?=$u['id_usuario']?>"
                     class="btn btn-sm btn-rojo"
                     onclick="return confirm('¿Eliminar a <?=e($u['nombre'])?>?')">🗑 Eliminar</a>
                <?php endif ?>
              </div>
            </td>
          </tr>
          <?php endwhile ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>
</div>

<?php layoutEnd(); ?>