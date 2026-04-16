<?php
/**
 * includes/topbar.php
 * 
 * Partial: barra superior común a todas las páginas internas.
 * Uso: include __DIR__ . '/topbar.php';
 * 
 * Variables opcionales que puede definir el archivo inclusor:
 *   $page_title   → título de la página (aparece en <title>)
 *   $breadcrumbs  → array [['label'=>'...','url'=>'...'], ...]
 */
$_page_title = $page_title ?? 'RominaStore';
$_breadcrumbs = $breadcrumbs ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RominaStore — <?php echo e($_page_title); ?></title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">
</head>
<body>

<nav class="topbar">
  <a href="<?php echo BASE_URL; ?>dashboard.php" class="topbar-brand">
    <div class="brand-icon">🛒</div>
    <div>
      <div class="brand-name">RominaStore</div>
      <div class="brand-sub">POS Abarrotes</div>
    </div>
  </a>
  <div class="topbar-right">
    <span class="topbar-user">👤 <?php echo e(nombreUsuario()); ?></span>
    <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-sm btn-rojo">Salir</a>
  </div>
</nav>

<?php if (!empty($_breadcrumbs)): ?>
<div class="page">
  <nav class="breadcrumb">
    <a href="<?php echo BASE_URL; ?>dashboard.php">Inicio</a>
    <?php foreach ($_breadcrumbs as $bc): ?>
      <span>›</span>
      <?php if (!empty($bc['url'])): ?>
        <a href="<?php echo $bc['url']; ?>"><?php echo e($bc['label']); ?></a>
      <?php else: ?>
        <strong style="color:var(--gris-700)"><?php echo e($bc['label']); ?></strong>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</div>
<?php endif; ?>