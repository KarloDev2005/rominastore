<?php

require_once '../includes/config.php';
requerirAutenticacion();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: listar.php'); exit; }

$s = $conn->prepare("SELECT * FROM productos WHERE id_producto=?");
$s->bind_param("i",$id); $s->execute();
$producto = $s->get_result()->fetch_assoc();
if (!$producto) { header('Location: listar.php'); exit; }

$error = '';

/* Eliminar imagen */
if (isset($_GET['del_img'])) {
    eliminarImagenProducto($producto['imagen'] ?? '');
    $s2 = $conn->prepare("UPDATE productos SET imagen=NULL WHERE id_producto=?");
    $s2->bind_param("i",$id); $s2->execute();
    flashSet('exito','Imagen eliminada.');
    header("Location: editar.php?id=$id"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $stock  = (int)($_POST['stock']  ?? 0);

    if ($nombre === '' || $precio <= 0) {
        $error = 'Nombre y precio son obligatorios.';
    } else {
        $imagen_ruta = $producto['imagen']; // mantener la actual

        /* Nueva imagen */
        if (!empty($_FILES['imagen']['name'])) {
            $res = subirImagenProducto($_FILES['imagen'], $id);
            if ($res['ok']) {
                /* Borrar imagen anterior */
                if ($imagen_ruta) eliminarImagenProducto($imagen_ruta);
                $imagen_ruta = $res['ruta'];
            } else {
                $error = "Imagen no válida: " . $res['msg'];
            }
        }

        if (!$error) {
            $s2 = $conn->prepare("UPDATE productos SET nombre=?,precio=?,stock=?,imagen=? WHERE id_producto=?");
            $s2->bind_param("sdssi",$nombre,$precio,$stock,$imagen_ruta,$id);
            if ($s2->execute()) {
                $producto = array_merge($producto,['nombre'=>$nombre,'precio'=>$precio,'stock'=>$stock,'imagen'=>$imagen_ruta]);
                flashSet('exito',"Producto «{$nombre}» actualizado.");
                header("Location: listar.php"); exit;
            } else {
                $error = 'Error al guardar: ' . $conn->error;
            }
        }
    }
}

layoutStart('Editar Producto','productos',[
    ['label'=>'Productos','url'=>'productos/listar.php'],
    ['label'=>'Editar: '.e($producto['nombre'])]
]);
?>

<div class="page-head fade-up">
  <div class="page-title"><i class="fa-solid fa-pen"></i> Editar Producto</div>
</div>

<div style="max-width:540px" class="fade-up delay-1">
  <div class="card">
    <div class="card-body">
      <?php if($error): ?><div class="alerta alerta-error"><i class="fa-solid fa-xmark"></i> <?=e($error)?></div><?php endif ?>

      <form method="POST" enctype="multipart/form-data" class="con-spinner">
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-tag"></i> Nombre</label>
          <input type="text" name="nombre" class="form-control" required value="<?=e($producto['nombre'])?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-dollar-sign"></i> Precio</label>
            <input type="number" step="0.01" name="precio" class="form-control" required value="<?=$producto['precio']?>">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-cubes"></i> Stock</label>
            <input type="number" name="stock" class="form-control" value="<?=$producto['stock']?>">
          </div>
        </div>

        <!-- Imagen actual -->
        <div class="form-group" id="img-section">
          <label class="form-label"><i class="fa-solid fa-image"></i> Imagen del producto</label>

          <?php if($producto['imagen']): ?>
          <div style="display:flex;align-items:center;gap:1rem;background:var(--p1-bg);border:1px solid var(--p1-b);border-radius:12px;padding:.85rem;margin-bottom:.75rem">
            <img src="<?=BASE_URL.$producto['imagen']?>"
                 alt="<?=e($producto['nombre'])?>"
                 style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:2px solid var(--p1-b);cursor:pointer"
                 onclick="abrirLightbox('<?=BASE_URL.$producto['imagen']?>','<?=e($producto['nombre'])?>')">
            <div style="flex:1">
              <div style="font-size:.8rem;font-weight:700;color:var(--g700);margin-bottom:.3rem">Imagen actual</div>
              <div style="font-size:.72rem;color:var(--g400);margin-bottom:.5rem">Haz clic en la imagen para ampliar</div>
              <a href="editar.php?id=<?=$id?>&del_img=1"
                 class="btn btn-sm btn-rojo"
                 onclick="return confirm('¿Eliminar la imagen actual?')">
                <i class="fa-solid fa-trash"></i> Eliminar imagen
              </a>
            </div>
          </div>
          <?php endif ?>

          <div class="file-upload-area" id="uploadArea">
            <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp" onchange="previsualizarImagen(this)">
            <div id="uploadPlaceholder">
              <div class="file-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
              <div class="file-upload-txt">
                <?=$producto['imagen']?'Cambiar imagen — arrastra o haz clic':'Subir imagen — arrastra o haz clic'?>
              </div>
              <div class="file-upload-sub">JPG, PNG, WEBP · Máx. 5 MB · Se recortará a 300×300 px</div>
            </div>
            <img id="imgPreview" src="" alt="Preview"
                 style="display:none;width:120px;height:120px;object-fit:cover;border-radius:12px;border:2px solid var(--p1-b);margin:0 auto">
          </div>
        </div>

        <div style="display:flex;gap:.65rem;margin-top:.5rem">
          <button type="submit" class="btn btn-naranja btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Actualizar
          </button>
          <a href="listar.php" class="btn btn-gris btn-lg">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function previsualizarImagen(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('imgPreview');
    const ph   = document.getElementById('uploadPlaceholder');
    prev.src = e.target.result;
    prev.style.display = 'block';
    ph.style.display   = 'none';
  };
  reader.readAsDataURL(file);
}
</script>

<?php layoutEnd(); ?>