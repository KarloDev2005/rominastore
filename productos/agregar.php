<?php
/* productos/agregar.php — Con zona drag & drop visual */
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
            if (!empty($_FILES['imagen']['name'])) {
                $res = subirImagenProducto($_FILES['imagen'], $id_nuevo);
                if ($res['ok']) {
                    $ruta = $res['ruta'];
                    $s2 = $conn->prepare("UPDATE productos SET imagen=? WHERE id_producto=?");
                    $s2->bind_param("si", $ruta, $id_nuevo); $s2->execute();
                } else {
                    flashSet('aviso', "Producto creado, pero la imagen no se procesó: " . $res['msg']);
                    header('Location: listar.php'); exit;
                }
            }
            flashSet('exito', "Producto «{$nombre}» agregado correctamente.");
            header('Location: listar.php'); exit;
        } else {
            $error = 'Error al guardar: ' . $conn->error;
        }
    }
}

layoutStart('Agregar Producto', 'productos', [
    ['label' => 'Productos', 'url' => 'productos/listar.php'],
    ['label' => 'Agregar']
]);
?>

<div class="page-head fade-up">
  <div class="page-title"><i class="fa-solid fa-plus"></i> Agregar Producto</div>
  <div class="page-subtitle">Completa la información del nuevo producto</div>
</div>

<div style="max-width:540px" class="fade-up delay-1">
  <div class="card">
    <div class="card-body">
      <?php if($error): ?>
        <div class="alerta alerta-error"><i class="fa-solid fa-circle-xmark"></i> <?=e($error)?></div>
      <?php endif ?>

      <form method="POST" enctype="multipart/form-data" class="con-spinner">

        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-tag"></i> Nombre del producto</label>
          <input type="text" name="nombre" class="form-control" required
                 placeholder="Ej: Coca-Cola 600ml"
                 value="<?=e($_POST['nombre']??'')?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-dollar-sign"></i> Precio de venta</label>
            <input type="number" step="0.01" name="precio" class="form-control" required
                   placeholder="0.00" value="<?=e($_POST['precio']??'')?>">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-cubes"></i> Stock inicial</label>
            <input type="number" name="stock" class="form-control"
                   placeholder="0" value="<?=e($_POST['stock']??0)?>">
          </div>
        </div>

        <!-- ZONA DRAG & DROP -->
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-image"></i> Imagen del producto</label>

          <div class="file-drop-zone" id="dropZone">
            <input type="file" name="imagen" id="imgInput"
                   accept=".jpg,.jpeg,.png,.webp">

            <!-- Contenido por defecto -->
            <div class="fdz-content" id="fdzDefault">
              <div class="fdz-icon-wrap">
                <i class="fa-solid fa-cloud-arrow-up"></i>
              </div>
              <div class="fdz-title">Arrastra la imagen aquí</div>
              <div class="fdz-subtitle">
                o haz clic para seleccionar un archivo
              </div>
              <div class="fdz-formats">
                <span class="fdz-badge">JPG</span>
                <span class="fdz-badge">PNG</span>
                <span class="fdz-badge">WEBP</span>
                <span class="fdz-badge" style="background:rgba(124,31,160,.05);border-style:dashed">Máx. 5 MB · 300×300 px</span>
              </div>
            </div>

            <!-- Preview al seleccionar -->
            <div class="fdz-preview" id="fdzPreview">
              <img id="fdzPreviewImg" src="" alt="Vista previa" class="fdz-preview-img">
              <div class="fdz-preview-name" id="fdzPreviewName">archivo.jpg</div>
              <button type="button" class="fdz-change-btn" onclick="resetDrop(event)">
                <i class="fa-solid fa-arrows-rotate"></i> Cambiar imagen
              </button>
            </div>
          </div>
        </div>

        <div style="display:flex;gap:.65rem;margin-top:.35rem">
          <button type="submit" class="btn btn-verde btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Producto
          </button>
          <a href="listar.php" class="btn btn-gris btn-lg">
            <i class="fa-solid fa-xmark"></i> Cancelar
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var dropZone   = document.getElementById('dropZone');
var imgInput   = document.getElementById('imgInput');
var fdzDefault = document.getElementById('fdzDefault');
var fdzPreview = document.getElementById('fdzPreview');
var prevImg    = document.getElementById('fdzPreviewImg');
var prevName   = document.getElementById('fdzPreviewName');

/* ── Selección por archivo ── */
imgInput.addEventListener('change', () => {
  const file = imgInput.files[0];
  if (file) showPreview(file);
});

/* ── Drag events ── */
['dragenter','dragover'].forEach(ev =>
  dropZone.addEventListener(ev, e => {
    e.preventDefault(); dropZone.classList.add('drag-over');
  })
);
['dragleave','dragend'].forEach(ev =>
  dropZone.addEventListener(ev, e => {
    dropZone.classList.remove('drag-over');
  })
);
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file && /^image\/(jpeg|png|webp)$/i.test(file.type)) {
    /* Asignar al input para que el form lo envíe */
    const dt = new DataTransfer();
    dt.items.add(file);
    imgInput.files = dt.files;
    showPreview(file);
  }
});

function showPreview(file) {
  const reader = new FileReader();
  reader.onload = e => {
    prevImg.src = e.target.result;
    prevName.textContent = file.name;
    fdzDefault.style.display = 'none';
    fdzPreview.classList.add('show');
  };
  reader.readAsDataURL(file);
}

function resetDrop(event) {
  event.stopPropagation();
  imgInput.value = '';
  prevImg.src = '';
  fdzPreview.classList.remove('show');
  fdzDefault.style.display = '';
}
</script>

<?php layoutEnd(); ?>
