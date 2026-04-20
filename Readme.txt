╔══════════════════════════════════════════════════════════════════╗
║          ABARROTES ROMINA — SISTEMA POS v8                      ║
║          Manual Completo de Instalación y Uso                   ║
╚══════════════════════════════════════════════════════════════════╝

Versión : 7.0
Fecha   : Abril 2026
Autor   : Jorge De La Cruz

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ÍNDICE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  1. Requisitos del sistema
  2. Estructura de carpetas
  3. Instalación paso a paso
  4. Configuración de la base de datos (SQL completo)
  5. Configuración de WhatsApp Business
  6. Primer acceso y credenciales
  7. Guía de uso del sistema
  8. Módulos y funcionalidades
  9. Solución de problemas frecuentes
 10. Actualización desde versiones anteriores
 11. Seguridad y mantenimiento


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1. REQUISITOS DEL SISTEMA
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Software requerido:
  ┌─────────────────────────────────────────┐
  │ XAMPP 8.x o superior                   │
  │   • Apache 2.4+                        │
  │   • MySQL 8.0+  (o MariaDB 10.5+)     │
  │   • PHP 8.0+                           │
  └─────────────────────────────────────────┘

  Extensiones PHP necesarias (incluidas en XAMPP):
    ✔ GD       — procesamiento de imágenes
    ✔ cURL     — envío de notificaciones WhatsApp
    ✔ MySQLi   — conexión a base de datos
    ✔ MBString — soporte de caracteres UTF-8

  Cómo verificar extensiones:
    1. Abre http://localhost:8080/dashboard/phpinfo.php
    2. Busca "gd", "curl", "mysqli", "mbstring"
    3. Deben aparecer como "enabled"

 Pasos en XAMPP:

Ve a C:\xampp\php\ y abre php.ini con el Bloc de notas.

Busca ;extension=gd y borra el punto y coma (;) para que quede extension=gd.

Guarda el archivo.

Reinicia Apache desde el Panel de Control de XAMPP (Stop → Start).


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
2. ESTRUCTURA DE CARPETAS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  rominastore/
  │
  ├── css/
  │   └── app.css                  ← Estilos del sistema (v8)
  │
  ├── img/
  │   ├── icono.png                ← Icono R-Romina (sidebar/favicon)
  │   ├── logo_full.jpeg           ← Logo completo (fondo decorativo)
  │   └── productos/               ← Imágenes de productos (auto-generada)
  │
  ├── includes/
  │   ├── config.php               ← Configuración de BD (NO editar)
  │   ├── funciones.php            ← Funciones del sistema (v8)
  │   └── whatsapp.php             ← API de WhatsApp Cloud
  │
  ├── ventas/
  │   ├── nueva_venta.php          ← Punto de venta (POS)
  │   ├── buscar_productos.php     ← Búsqueda AJAX de productos
  │   ├── procesar_venta.php       ← Procesador de ventas
  │   ├── ticket.php               ← Ticket/recibo
  │   └── cierre_caja.php          ← Arqueo y cierre de turno
  │
  ├── fiado/
  │   ├── venta_credito.php        ← Ventas a crédito
  │   ├── procesar_credito.php     ← Procesador + notif. WhatsApp
  │   └── consultar_adeudo.php     ← Adeudos y abonos
  │
  ├── productos/
  │   ├── listar.php               ← Lista con imágenes
  │   ├── agregar.php              ← Alta de producto con imagen
  │   └── editar.php               ← Editar producto / cambiar imagen
  │
  ├── clientes/
  │   ├── listar.php
  │   ├── agregar.php
  │   └── editar.php
  │
  ├── inventario/
  │   └── consultar.php            ← Stock con alertas de nivel bajo
  │
  ├── reportes/
  │   └── ventas.php               ← Reporte con gráficas y CSV
  │
  ├── usuarios/
  │   ├── listar.php
  │   ├── agregar.php
  │   └── editar.php
  │
  ├── dashboard.php                ← Panel principal con métricas
  ├── index.php                    ← Login
  └── logout.php                   ← Cerrar sesión


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
3. INSTALACIÓN PASO A PASO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  PASO 1 — Instalar XAMPP
  ─────────────────────────
  Descarga: https://www.apachefriends.org/
  Instala y abre XAMPP Control Panel.
  Inicia: Apache y MySQL.

  Si el puerto 80 está ocupado (por IIS, Skype, etc.):
    • En XAMPP Config → Apache → httpd.conf
    • Cambia "Listen 80" por "Listen 8080"
    • Reinicia Apache

  PASO 2 — Copiar archivos
  ─────────────────────────
  Copia la carpeta "rominastore" completa a:
    Windows: C:\xampp\htdocs\rominastore\
    Linux:   /opt/lampp/htdocs/rominastore/

  PASO 3 — Configurar la conexión
  ─────────────────────────────────
  Abre el archivo:  rominastore/includes/config.php

  Edita estos valores según tu instalación:

    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');          // Tu contraseña MySQL (en XAMPP suele estar vacía)
    define('DB_NAME', 'rominastore');
    define('BASE_URL', 'http://localhost:8080/rominastore/');
    define('BASE_PATH', 'C:/xampp/htdocs/rominastore/');  // Ruta física del servidor

  PASO 4 — Crear la base de datos
  ─────────────────────────────────
  1. Abre: http://localhost:8080/phpmyadmin/
  2. Clic en "Nueva" → nombre: "rominastore" → Crear
  3. Selecciona la BD → pestaña "SQL"
  4. Pega y ejecuta el script completo del APARTADO 4

  PASO 5 — Crear carpeta de imágenes
  ────────────────────────────────────
  Crea manualmente (si no existe):
    C:\xampp\htdocs\rominastore\img\productos\

  Copia tus imágenes de productos ahí:
    coca_cola.jpg
    leche.png
    sabritas.jpg
    Gael.jpeg

  PASO 6 — Verificar instalación
  ─────────────────────────────────
  Abre: http://localhost:8080/rominastore/
  Debes ver la pantalla de login de Abarrotes Romina.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
4. BASE DE DATOS — SCRIPT SQL COMPLETO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Ejecuta en phpMyAdmin → SQL:

  ─────────────────────────────────────────────────────────────────
  CREATE DATABASE IF NOT EXISTS rominastore
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  USE rominastore;

  -- Usuarios del sistema
  CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    contrasena    VARCHAR(255) NOT NULL,
    rol           ENUM('admin','cajero') NOT NULL DEFAULT 'cajero',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB;

  -- Catálogo de productos
  CREATE TABLE IF NOT EXISTS productos (
    id_producto   INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(150) NOT NULL,
    precio        DECIMAL(10,2) NOT NULL,
    stock         INT NOT NULL DEFAULT 0,
    imagen        VARCHAR(255) NULL DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB;

  -- Clientes con crédito
  CREATE TABLE IF NOT EXISTS clientes (
    id_cliente    INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    telefono      VARCHAR(20) NULL,
    adeudo        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB;

  -- Cabecera de ventas
  CREATE TABLE IF NOT EXISTS ventas (
    id_venta     INT AUTO_INCREMENT PRIMARY KEY,
    fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total        DECIMAL(10,2) NOT NULL,
    id_cliente   INT NULL,
    id_usuario   INT NOT NULL,
    FOREIGN KEY (id_cliente)  REFERENCES clientes(id_cliente) ON DELETE SET NULL,
    FOREIGN KEY (id_usuario)  REFERENCES usuarios(id_usuario)
  ) ENGINE=InnoDB;

  -- Detalle de ventas (líneas)
  CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle      INT AUTO_INCREMENT PRIMARY KEY,
    id_venta        INT NOT NULL,
    id_producto     INT NOT NULL,
    cantidad        INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal        DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta)    REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
  ) ENGINE=InnoDB;

  -- Abonos de clientes con adeudo
  CREATE TABLE IF NOT EXISTS abonos (
    id_abono   INT AUTO_INCREMENT PRIMARY KEY,
    fecha      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto      DECIMAL(10,2) NOT NULL,
    id_cliente INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
  ) ENGINE=InnoDB;

  -- Registro de cierres de caja
  CREATE TABLE IF NOT EXISTS cierres_caja (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    fecha              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ventas_contado     DECIMAL(12,2) NOT NULL DEFAULT 0,
    ventas_credito     DECIMAL(12,2) NOT NULL DEFAULT 0,
    abonos             DECIMAL(12,2) NOT NULL DEFAULT 0,
    efectivo_sistema   DECIMAL(12,2) NOT NULL DEFAULT 0,
    efectivo_declarado DECIMAL(12,2) NOT NULL DEFAULT 0,
    diferencia         DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado             ENUM('ok','exceso','faltante') NOT NULL DEFAULT 'ok',
    nota               VARCHAR(255) NULL,
    id_usuario         INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
  ) ENGINE=InnoDB;

  -- Usuario administrador por defecto (contraseña: admin123)
  INSERT INTO usuarios (nombre, contrasena, rol) VALUES
  ('Administrador',
   '$2y$10$eXaMpLeHaSh.RePlAcEtHiS.WithAreAlHash12345678',
   'admin');

  -- Productos de ejemplo
  INSERT INTO productos (nombre, precio, stock, imagen) VALUES
  ('Coca-Cola 600ml',  20.00, 50, 'img/productos/coca_cola.jpg'),
  ('Sabritas 45g',     15.00, 30, 'img/productos/sabritas.jpg'),
  ('Leche Lala 1L',    24.50, 15, 'img/productos/leche.png'),
  ('Pan Bimbo',        28.00, 20,  NULL);

  -- Cliente de ejemplo
  INSERT INTO clientes (nombre, telefono) VALUES ('Juan Pérez','5512345678');
  ─────────────────────────────────────────────────────────────────

  IMPORTANTE — Generar el hash correcto de la contraseña:
  La contraseña de ejemplo arriba NO es funcional.
  Para crear el hash real, crea un archivo temporal:

    Ruta: C:\xampp\htdocs\gen_hash.php
    Contenido:
      <?php
      echo password_hash('admin123', PASSWORD_DEFAULT);
      ?>

    Abre: http://localhost:8080/gen_hash.php
    Copia el hash generado (empieza con $2y$10$...)
    Pégalo en la columna "contrasena" de la tabla usuarios
    Elimina gen_hash.php después.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
5. CONFIGURACIÓN DE WHATSAPP BUSINESS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  El sistema usa WhatsApp Cloud API de Meta con PLANTILLAS APROBADAS.
  Esto es obligatorio — Meta no permite mensajes libres fuera de la
  ventana de 24 horas.

  CREDENCIALES ACTUALES (en includes/whatsapp.php):
  ┌──────────────────────────────────────────────────────┐
  │ Phone Number ID : 1129069736946295                   │
  │ Destino         : +52 -----------                 │
  │ Plantilla       : notificacion_fiado_interno         │
  │ Idioma          : es_MX                              │
  │ API Version     : v25.0                              │
  └──────────────────────────────────────────────────────┘

  CUÁNDO SE ENVÍAN NOTIFICACIONES:
    • Al registrar una venta a crédito (fiado)
    • Al registrar un abono de cliente

  PARÁMETROS DE LA PLANTILLA:
    {{1}} → Nombre del cliente
    {{2}} → Fecha y hora de la transacción
    {{3}} → Detalle de productos
    {{4}} → Total de la venta/abono
    {{5}} → Adeudo total del cliente

  RENOVAR EL TOKEN DE ACCESO:
    Los tokens de Meta expiran periódicamente.
    Para renovar:
    1. Ir a: https://developers.facebook.com/
    2. Mi app → WhatsApp → Configuración de API
    3. Generar nuevo token permanente
    4. Editar: rominastore/includes/whatsapp.php
    5. Cambiar el valor de WSP_TOKEN

  VERIFICAR QUE FUNCIONA:
    Ejecuta el script Python adjunto:
      python probar_whatsapp.py
    Si recibes el mensaje, la API está activa.


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
6. PRIMER ACCESO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  URL     : http://localhost:8080/rominastore/
  Usuario : Administrador
  Contraseña : admin123

  ┌─────────────────────────────────────────────────────────┐
  │ IMPORTANTE: Cambia la contraseña después del primer     │
  │ acceso desde: Usuarios → Editar → Nueva contraseña      │
  └─────────────────────────────────────────────────────────┘

  ROLES DEL SISTEMA:
    ┌──────────────┬──────────────────────────────────────────┐
    │ Administrador│ Acceso completo a todos los módulos      │
    │              │ Gestión de usuarios, reportes avanzados  │
    ├──────────────┼──────────────────────────────────────────┤
    │ Cajero       │ Punto de venta, ventas a crédito,        │
    │              │ inventario, adeudos                      │
    └──────────────┴──────────────────────────────────────────┘


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
7. GUÍA DE USO — FLUJO DIARIO RECOMENDADO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  AL ABRIR EL TURNO:
  ─────────────────
  1. Iniciar sesión
  2. Revisar el Dashboard: ventas del día, alertas de stock bajo
  3. Si aparecen notificaciones de adeudo, contactar a los clientes

  DURANTE EL TURNO — VENTA AL CONTADO:
  ─────────────────────────────────────
  1. Ir a: Punto de Venta
  2. Buscar productos (barra de búsqueda o hacer clic en la grilla)
  3. Los productos aparecen con imagen, precio y stock disponible
  4. Ingresar el efectivo recibido → el sistema calcula el cambio
  5. Clic en "Cobrar" → se genera el ticket automáticamente
  6. Imprimir o mostrar el ticket al cliente

  DURANTE EL TURNO — VENTA A CRÉDITO (FIADO):
  ─────────────────────────────────────────────
  1. Ir a: Venta a Crédito
  2. Seleccionar el cliente (botón "Seleccionar cliente")
  3. Agregar productos al carrito
  4. Clic en "Registrar Fiado"
  5. El adeudo del cliente se actualiza automáticamente
  6. Se envía notificación WhatsApp al dueño

  COBRAR UN ADEUDO (ABONO):
  ──────────────────────────
  1. Ir a: Adeudos
  2. Seleccionar el cliente en la tabla
  3. Ver el adeudo actual
  4. Ingresar el monto del abono
  5. Clic en "Registrar Abono"
  6. Se envía notificación WhatsApp automáticamente

  AL CERRAR EL TURNO:
  ────────────────────
  1. Ir a: Cierre de Caja (menú lateral)
  2. El sistema muestra el resumen: ventas contado + abonos cobrados
  3. Contar el efectivo en caja físicamente
  4. Ingresar el monto real en "Efectivo en caja"
  5. El sistema calcula la diferencia en tiempo real:
     ✅ Verde = cuadre exacto
     💙 Azul  = tienes de más
     🔴 Rojo  = te falta dinero
  6. Agregar nota si hay diferencia
  7. Clic en "Registrar Cierre"

  GESTIÓN DE INVENTARIO:
  ───────────────────────
  1. Ir a: Inventario
  2. Filtrar por: Todos / OK / Stock bajo / Agotado
  3. Para actualizar stock: clic en "Actualizar stock" del producto
  4. Modifica el número en el campo "Stock"
  5. El Dashboard muestra alertas automáticas cuando hay agotados


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
8. MÓDULOS Y FUNCIONALIDADES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  DASHBOARD
    • Métricas en tiempo real: ventas hoy, mes, adeudos, agotados
    • Notificaciones de clientes con adeudo > 2 días de atraso
    • Gráfica de ventas semanales (Chart.js)
    • Top 5 productos más vendidos
    • Listado de últimas ventas con iniciales del cliente
    • Accesos rápidos a las acciones más frecuentes

  PUNTO DE VENTA (POS)
    • Grilla de productos con imagen, precio y stock
    • Barra de búsqueda con autocomplete en tiempo real (sin recargar)
    • Al hacer clic en la imagen: lightbox de vista previa
    • Carrito lateral con ajuste de cantidades (+/-)
    • Calculadora de cambio (efectivo recibido → cambio)
    • Animación visual al agregar un producto
    • Toast de confirmación "Producto agregado"
    • Ticket imprimible optimizado para papel 80mm (impresora térmica)

  VENTAS A CRÉDITO
    • Selector de cliente con búsqueda
    • Mismo funcionamiento que el POS
    • Notificación WhatsApp automática al registrar

  ADEUDOS Y ABONOS
    • Lista de clientes ordenada por monto de adeudo (mayor primero)
    • Fila resaltada del cliente seleccionado
    • Montos rápidos ($50, $100, $200, $500, Todo)
    • Historial de últimos 10 abonos por cliente
    • Notificación WhatsApp al registrar abono

  PRODUCTOS
    • Lista con thumbnail de imagen
    • Clic en thumbnail → lightbox ampliado
    • Botón "Cambiar imagen" y "Eliminar imagen" por producto
    • Al subir imagen: redimensión automática a 300×300px (crop centrado)
    • Formatos soportados: JPG, PNG, WEBP (máx. 5 MB)

  INVENTARIO
    • Filtros: Todos / OK / Stock bajo (≤5) / Agotado
    • Contadores en cada filtro
    • Fila resaltada según nivel de stock
    • Enlace directo a editar para actualizar stock

  REPORTES
    • Filtro por rango de fechas
    • Métricas: total vendido, ticket promedio, contado vs crédito
    • Paginación (25 registros por página)
    • Exportación a CSV (compatible con Excel)

  CIERRE DE CAJA
    • Resumen del turno: contado + crédito + abonos
    • Cálculo de efectivo esperado
    • Preview en tiempo real al ingresar el monto declarado
    • Resultado visual con colores: exacto / sobrante / faltante
    • Historial de últimos 5 cierres

  USUARIOS (solo Administrador)
    • Crear cajeros y administradores
    • Cambiar contraseña
    • No se puede eliminar al único admin

  FUNCIONES DE INTERFAZ
    • Modo oscuro / claro con toggle (guardado en localStorage)
    • Sidebar colapsable (icono o expandido, estado guardado)
    • Sidebar colapsable en móvil con overlay
    • Tooltips en modo colapsado
    • Spinner de carga en formularios
    • Alertas que se ocultan automáticamente a los 4 segundos
    • Animaciones de entrada en los elementos de cada página


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
9. SOLUCIÓN DE PROBLEMAS FRECUENTES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  ERROR: "Error de conexión a la base de datos"
  ─────────────────────────────────────────────
  • Verifica que MySQL esté corriendo en XAMPP Control Panel
  • Revisa las credenciales en includes/config.php
  • Asegúrate de que la base de datos "rominastore" existe

  ERROR: "No cargan las imágenes de productos"
  ─────────────────────────────────────────────
  • Verifica que existe la carpeta: img/productos/
  • Verifica que las imágenes estén en esa carpeta
  • Verifica el valor de BASE_PATH en config.php

  ERROR: "La imagen no se sube"
  ─────────────────────────────
  • Verifica que la extensión GD esté habilitada en PHP
  • Verifica permisos de escritura en img/productos/
  • En Windows suele funcionar sin configuración adicional

  ERROR: "WhatsApp no envía notificaciones"
  ──────────────────────────────────────────
  • El token de acceso puede haber expirado → renuévalo
  • Verifica que la extensión cURL esté habilitada
  • Verifica la conexión a internet del servidor
  • Ejecuta probar_whatsapp.py para diagnóstico

  ERROR: "El carrito no funciona / no agrega productos"
  ──────────────────────────────────────────────────────
  • Este error fue corregido en v5: el formulario ahora usa
    <input type="hidden" name="agregar" value="1">
    en lugar de name en el botón submit.
  • Si persiste: verifica que el archivo es la versión v5+

  ERROR: "Página en blanco"
  ──────────────────────────
  • Activa la visualización de errores en PHP:
    En php.ini: display_errors = On
  • O agrega al inicio de config.php:
    ini_set('display_errors', 1); error_reporting(E_ALL);

  PROBLEMA: "El sidebar se 'reinicia' al cambiar de página"
  ───────────────────────────────────────────────────────────
  • Solucionado en v6+: el estado se guarda en localStorage
    y se aplica SIN animación al cargar (evita el parpadeo)

  PROBLEMA: "La contraseña del administrador no funciona"
  ────────────────────────────────────────────────────────
  • Genera un nuevo hash ejecutando actualizar_password.php
    o usa el método del PASO 4 para regenerarlo


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
10. ACTUALIZACIÓN DESDE VERSIONES ANTERIORES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Si ya tienes una versión anterior instalada:

  1. Haz BACKUP de tu base de datos en phpMyAdmin → Exportar
  2. Copia los archivos nuevos (NO borres includes/config.php)
  3. Ejecuta en phpMyAdmin → SQL:

    -- Agregar columna imagen si no existe
    ALTER TABLE productos
      ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL AFTER stock;

    -- Crear tabla de cierres de caja si no existe
    CREATE TABLE IF NOT EXISTS cierres_caja (
      id INT AUTO_INCREMENT PRIMARY KEY,
      fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
      ventas_contado DECIMAL(12,2) DEFAULT 0,
      ventas_credito DECIMAL(12,2) DEFAULT 0,
      abonos DECIMAL(12,2) DEFAULT 0,
      efectivo_sistema DECIMAL(12,2) DEFAULT 0,
      efectivo_declarado DECIMAL(12,2) DEFAULT 0,
      diferencia DECIMAL(12,2) DEFAULT 0,
      estado ENUM('ok','exceso','faltante') DEFAULT 'ok',
      nota VARCHAR(255) NULL,
      id_usuario INT NOT NULL,
      FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ) ENGINE=InnoDB;

    -- Asignar imágenes existentes
    UPDATE productos SET imagen='img/productos/coca_cola.jpg'
      WHERE LOWER(nombre) LIKE '%coca%' AND imagen IS NULL;
    UPDATE productos SET imagen='img/productos/leche.png'
      WHERE LOWER(nombre) LIKE '%leche%' AND imagen IS NULL;
    UPDATE productos SET imagen='img/productos/sabritas.jpg'
      WHERE LOWER(nombre) LIKE '%sabrita%' AND imagen IS NULL;
    UPDATE productos SET imagen='img/productos/Gael.jpeg'
      WHERE LOWER(nombre) LIKE '%gael%' AND imagen IS NULL;

  4. Crea la carpeta img/productos/ si no existe
  5. Copia tus imágenes a img/productos/
  6. Listo


━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
11. SEGURIDAD Y MANTENIMIENTO
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  MEDIDAS DE SEGURIDAD IMPLEMENTADAS:
    ✓ Prepared statements (protección contra SQL injection)
    ✓ htmlspecialchars() en todo el output (protección XSS)
    ✓ password_hash() con bcrypt para contraseñas
    ✓ Verificación de sesión en todas las páginas
    ✓ Control de roles (admin vs cajero)
    ✓ Validación de tipos de archivo antes de subir imágenes
    ✓ Límite de tamaño en imágenes (5 MB)
    ✓ Validación de stock antes de procesar ventas (FOR UPDATE)

  MANTENIMIENTO RECOMENDADO:
    ┌──────────────────────────────────────────────────────┐
    │ DIARIO   : Hacer cierre de caja al terminar turno   │
    │ SEMANAL  : Revisar productos con stock bajo         │
    │ MENSUAL  : Exportar reporte CSV como respaldo       │
    │ MENSUAL  : Hacer backup de la base de datos         │
    │ TRIMESTRAL: Verificar y renovar token de WhatsApp   │
    └──────────────────────────────────────────────────────┘

  CÓMO HACER BACKUP DE LA BASE DE DATOS:
    1. Abrir phpMyAdmin → seleccionar "rominastore"
    2. Pestaña "Exportar" → Formato: SQL
    3. Guardar el archivo en lugar seguro (no en la misma PC)

  ARCHIVOS QUE NUNCA DEBES ELIMINAR:
    • includes/config.php
    • img/icono.png
    • img/logo_full.jpeg
    • img/productos/ (y su contenido)

  ARCHIVOS QUE PUEDES ELIMINAR (son de diagnóstico):
    • actualizar_password.php
    • prueba.php
    • gen_hash.php (si lo creaste)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            ABARROTES ROMINA — Sistema POS v8
            Desarrollado para gestión interna
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━