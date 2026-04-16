===================================================
        ROMINASTORE - SISTEMA POS
        PARA ABARROTES ROMINA
===================================================

1. REQUISITOS PREVIOS
---------------------
- Tener instalado XAMPP (o WAMP) con:
  • Apache (servidor web)
  • MySQL (base de datos)
  • PHP (versión 7.4 o superior)
- El servidor Apache debe estar configurado en el puerto 8080 
  (si usas otro puerto, ajusta la constante BASE_URL en includes/config.php)

2. INSTALACIÓN DE ARCHIVOS
---------------------------
- Copia toda la carpeta "rominastore" dentro del directorio raíz de tu servidor web:
  • Para XAMPP: C:\xampp\htdocs\rominastore\
  • Para WAMP:   C:\wamp\www\rominastore\
- Asegúrate de que la estructura de carpetas sea la siguiente:
  rominastore/
  ├─ clientes/
  ├─ css/
  ├─ db/
  ├─ fiado/
  ├─ img/
  ├─ includes/
  ├─ inventario/
  ├─ js/
  ├─ productos/
  ├─ reportes/
  ├─ usuarios/
  ├─ ventas/
  └─ index.php, dashboard.php, logout.php, etc.

3. BASE DE DATOS
----------------
- Abre phpMyAdmin desde: http://localhost:8080/phpmyadmin/
- Crea una nueva base de datos llamada: rominastore
- En la pestaña "SQL", ejecuta el siguiente script completo:

-----[ INICIO DEL SCRIPT SQL ]-----
CREATE DATABASE IF NOT EXISTS rominastore;
USE rominastore;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cajero') NOT NULL DEFAULT 'cajero',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    adeudo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    id_cliente INT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE SET NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

CREATE TABLE detalle_venta (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

CREATE TABLE abonos (
    id_abono INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(10,2) NOT NULL,
    id_cliente INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE CASCADE
);

-- Usuario administrador por defecto (contraseña: admin123)
INSERT INTO usuarios (nombre, contrasena, rol) VALUES 
('Administrador', '$2y$10$e0MYzXyjpz7jJjXQkYJ1lO8qL5FvqZ7QYqQvqZ7QYqQvqZ7QYqQvq', 'admin');

-- Productos de ejemplo (opcional)
INSERT INTO productos (nombre, precio, stock) VALUES 
('Coca-Cola 600ml', 20.00, 50),
('Sabritas 45g', 15.00, 30),
('Leche Lala 1L', 24.50, 15),
('Pan Bimbo', 28.00, 20);

-- Cliente de ejemplo
INSERT INTO clientes (nombre, telefono, adeudo) VALUES 
('Juan Pérez', '5512345678', 0.00);
-----[ FIN DEL SCRIPT SQL ]-----

- Haz clic en "Continuar" para ejecutar el script.
- Verifica que se hayan creado las 6 tablas (usuarios, productos, clientes, ventas, detalle_venta, abonos).

4. CONFIGURACIÓN DE CONEXIÓN
----------------------------
- Abre el archivo: includes/config.php
- Verifica que los datos de conexión sean correctos:
  define('DB_HOST', 'localhost');
  define('DB_USER', 'root');
  define('DB_PASS', '');        (si tienes contraseña, cámbiala)
  define('DB_NAME', 'rominastore');
- Si tu Apache usa un puerto diferente al 8080, modifica también:
  define('BASE_URL', 'http://localhost:8080/rominastore/');

5. PERMISOS DE CARPETAS (opcional)
----------------------------------
- Asegúrate de que las carpetas tengan permisos de lectura/escritura (normalmente no es necesario en Windows).

6. INICIAR EL SERVIDOR
----------------------
- Ejecuta XAMPP Control Panel.
- Activa los servicios: Apache y MySQL.
- Si cambiaste el puerto de Apache, verifica que esté funcionando en http://localhost:8080/

7. ACCEDER AL SISTEMA
---------------------
- Abre tu navegador web.
- Ingresa a: http://localhost:8080/rominastore/
- Verás la pantalla de inicio de sesión.
- Usuario: Administrador
- Contraseña: admin123

8. ROLES Y PERMISOS
-------------------
- Administrador: puede gestionar productos, clientes, usuarios, ventas, inventario, reportes.
- Cajero: solo puede acceder a Punto de Venta, Ventas a Crédito, Inventario y Reportes.

9. SOLUCIÓN DE PROBLEMAS COMUNES
---------------------------------
- Si al abrir el sistema ves "Error de conexión": revisa que MySQL esté corriendo y que los datos de config.php sean correctos.
- Si no cargan las imágenes: verifica que la carpeta "img" exista y contenga los archivos (ventas.jpg, credito.jpg, etc.).
- Si al finalizar una venta no se genera el ticket: revisa que la tabla ventas tenga registros y que el archivo ticket.php esté en la carpeta ventas/.
- Para cambiar la contraseña del administrador, usa el módulo Usuarios (solo accesible por admin) o modifica directamente en la base de datos con password_hash().

10. SOPORTE Y DOCUMENTACIÓN
----------------------------
- El sistema incluye un manual de usuario accesible desde el dashboard (si agregaste la tarjeta correspondiente).
- Para cualquier duda, revisa los comentarios en el código o contacta al desarrollador.

===================================================
          ¡SISTEMA LISTO PARA USAR!
===================================================