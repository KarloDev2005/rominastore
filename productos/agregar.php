<?php

require_once '../includes/config.php';
requerirAutenticacion();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $stock  = (int)($_POST['stock']  ?? 0);

    if ($nombre === '' || $precio <= 0) {
        $error = 'Nombre y precio son obligatorios.';
    } else {
        $s = $conn->prepare("INSERT INTO productos(nombre,precio,stock) VALUES(?,?,?)");
        $s->bind_param("sdi", $nombre, $precio, $stock);
        if ($s->execute()) {
            $id_nuevo = $conn->insert_id;

            /* Subir imagen si se proporcionó */
            if (!empty($_FILES['imagen']['name'])) {
                $res = subirImagenProducto($_FILES['imagen'], $id_nuevo);
                if ($res['ok']) {
                    $ruta = $res['ruta'];
                    $s2 = $conn->prepare("UPDATE productos SET imagen=? WHERE id_producto=?");
                    $s2->bind_param("si", $ruta, $id_nuevo);
                    $s2->execute();
                } else {
                    /* Producto guardado, pero imagen falló */
                    flashSet('aviso', "Producto creado, pero la imagen no se pudo guardar: " . $res['msg']);
                    header('Location: listar.php'); exit;
                }
            }
            flashSet('exito', "Producto «{$nombre}» creado correctamente.");
            header('Location: listar.php'); exit;
        } else {
            $error = 'Error al guardar: ' . $conn->error;
        }
    }
}

layoutStart('Agregar Producto', 'productos', [
    ['label'=>'Productos','url'=>'productos/listar.php'],
    ['label'=>'Agregar']
]);
?>

<div class="page-head fade-up">
  <div class="page-title"><i class="fa-solid fa-plus"></i> Agregar Producto</div>
</div>

<div style="max-width:540px" class="fade-up delay-1">
  <div class="card">
    <div class="card-body">
      <?php if($error): ?><div class="alerta alerta-error"><i class="fa-solid fa-xmark"></i> <?=e($error)?></div><?php endif ?>

      <form method="POST" enctype="multipart/form-data" class="con-spinner">
        <!-- Datos básicos -->
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-tag"></i> Nombre del producto</label>
          <input type="text" name="nombre" class="form-control"
                 required placeholder="Ej: Coca-Cola 600ml"
                 value="<?=e($_POST['nombre']??'')?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-dollar-sign"></i> Precio de venta</label>
            <input type="number" step="0.01" name="precio" class="form-control"
                   required placeholder="0.00" value="<?=e($_POST['precio']??'')?>">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-cubes"></i> Stock inicial</label>
            <input type="number" name="stock" class="form-control"
                   placeholder="0" value="<?=e($_POST['stock']??0)?>">
          </div>
        </div>

        <!-- Sección imagen -->
        <div class="form-group" id="img-section">
          <label class="form-label"><i class="fa-solid fa-image"></i> Imagen del producto</label>
          <div class="file-upload-area" id="uploadArea">
            <input type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp"
                   onchange="previsualizarImagen(this)">
            <div id="uploadPlaceholder">
              <div class="file-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
              <div class="file-upload-txt">Haz clic o arrastra una imagen aquí</div>
              <div class="file-upload-sub">JPG, PNG, WEBP · Máx. 5 MB · Se recortará a 300×300 px</div>
            </div>
            <img id="imgPreview" src="" alt="Preview"
                 style="display:none;width:120px;height:120px;object-fit:cover;border-radius:12px;border:2px solid var(--p1-b);margin:0 auto">
          </div>
        </div>

        <div style="display:flex;gap:.65rem;margin-top:.5rem">
          <button type="submit" class="btn btn-verde btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Producto
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