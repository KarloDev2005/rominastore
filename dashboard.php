<?php
require_once 'includes/config.php';
requerirAutenticacion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>RominaStore - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9edf2 100%);
            min-height: 100vh;
        }

        /* Encabezado moderno */
        .header {
            background: linear-gradient(135deg, #1e2a3a 0%, #0f1724 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            font-size: 2rem;
            background: rgba(255,255,255,0.15);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            backdrop-filter: blur(4px);
        }

        .logo-text h1 {
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            background: linear-gradient(120deg, #fff, #b0c4de);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .logo-text p {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 2px;
        }

        .user-info {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(8px);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
        }

        .user-info:hover {
            background: rgba(255,255,255,0.2);
        }

        .user-name {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: 0.2s;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        /* Contenedor de tarjetas */
        .dashboard-container {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 1.5rem 2rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.8rem;
            justify-items: center;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 1.5rem 1rem;
            width: 100%;
            max-width: 240px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 30px -12px rgba(0,0,0,0.2);
            border-color: rgba(0,0,0,0.1);
        }

        .card-icon-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }

        .card:hover .card-icon-img {
            transform: scale(1.05);
        }

        .card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0.8rem 0 0.4rem;
            color: #1e293b;
        }

        .card p {
            font-size: 0.85rem;
            color: #5b6e8c;
            line-height: 1.4;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 700px) {
            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }
            .user-info {
                align-self: flex-end;
                margin-top: 0.5rem;
            }
            .cards-grid {
                gap: 1rem;
            }
            .card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-container">
            <div class="logo-area">
                <div class="logo-icon">🛒</div>
                <div class="logo-text">
                    <h1>RominaStore</h1>
                    <p>POS Abarrotes Romina</p>
                </div>
            </div>
            <div class="user-info">
                <span class="user-name">👤 <?php echo nombreUsuario(); ?></span>
                <a href="logout.php" class="logout-btn">Cerrar sesión</a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="cards-grid">
            <div class="card" onclick="location.href='ventas/nueva_venta.php'">
                <img src="img/ventas.jpg" alt="Punto de Venta" class="card-icon-img">
                <h3>Punto de Venta</h3>
                <p>Registrar ventas al contado</p>
            </div>
            <div class="card" onclick="location.href='fiado/venta_credito.php'">
                <img src="img/credito.jpg" alt="Ventas a Crédito" class="card-icon-img">
                <h3>Ventas a Crédito</h3>
                <p>Registrar fiado y consultar adeudos</p>
            </div>
            <div class="card" onclick="location.href='productos/listar.php'">
                <img src="img/productos.jpg" alt="Productos" class="card-icon-img">
                <h3>Productos</h3>
                <p>Gestionar productos y precios</p>
            </div>
            <div class="card" onclick="location.href='inventario/consultar.php'">
                <img src="img/inventario.png" alt="Inventario" class="card-icon-img">
                <h3>Inventario</h3>
                <p>Consultar stock disponible</p>
            </div>
            <div class="card" onclick="location.href='clientes/listar.php'">
                <img src="img/clientes.jpg" alt="Clientes" class="card-icon-img">
                <h3>Clientes</h3>
                <p>Administrar clientes para crédito</p>
            </div>
            <?php if (tieneRol('admin')): ?>
            <div class="card" onclick="location.href='usuarios/listar.php'">
                <img src="img/usuarios.png" alt="Usuarios" class="card-icon-img">
                <h3>Usuarios</h3>
                <p>Gestionar administradores y cajeros</p>
            </div>
            <?php endif; ?>
            <div class="card" onclick="location.href='reportes/ventas.php'">
                <img src="img/reportes.png" alt="Reportes" class="card-icon-img">
                <h3>Reportes</h3>
                <p>Historial de ventas</p>
            </div>
        </div>
    </div>
</body>
</html>