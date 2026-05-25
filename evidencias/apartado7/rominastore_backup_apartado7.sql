-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: rominastore
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `abonos`
--

DROP TABLE IF EXISTS `abonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abonos` (
  `id_abono` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `monto` decimal(10,2) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  PRIMARY KEY (`id_abono`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `abonos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abonos`
--

LOCK TABLES `abonos` WRITE;
/*!40000 ALTER TABLE `abonos` DISABLE KEYS */;
INSERT INTO `abonos` VALUES (1,'2026-03-26 10:00:30',10.00,3),(2,'2026-03-26 14:16:22',10.00,4),(3,'2026-04-16 09:26:43',20.00,3);
/*!40000 ALTER TABLE `abonos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cierres_caja`
--

DROP TABLE IF EXISTS `cierres_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cierres_caja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `ventas_contado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ventas_credito` decimal(12,2) NOT NULL DEFAULT 0.00,
  `abonos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `efectivo_sistema` decimal(12,2) NOT NULL DEFAULT 0.00,
  `efectivo_declarado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `diferencia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado` enum('ok','exceso','faltante') NOT NULL DEFAULT 'ok',
  `nota` varchar(255) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `cierres_caja_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cierres_caja`
--

LOCK TABLES `cierres_caja` WRITE;
/*!40000 ALTER TABLE `cierres_caja` DISABLE KEYS */;
/*!40000 ALTER TABLE `cierres_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `adeudo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (2,'Karlo','2871246175',55.00,'2026-03-26 15:14:03'),(3,'GaelElMascaPito','12345566688',10.00,'2026-03-26 15:14:48'),(4,'foraneo','213412',10.00,'2026-03-26 15:56:04');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_venta` (
  `id_detalle` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle`),
  KEY `id_venta` (`id_venta`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_venta_ibfk_1` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE,
  CONSTRAINT `detalle_venta_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_venta`
--

LOCK TABLES `detalle_venta` WRITE;
/*!40000 ALTER TABLE `detalle_venta` DISABLE KEYS */;
INSERT INTO `detalle_venta` VALUES (1,1,1,1,20.00,20.00),(2,1,1,1,20.00,20.00),(3,2,1,1,20.00,20.00),(4,2,1,1,20.00,20.00),(5,3,5,1,1.00,1.00),(6,3,5,1,1.00,1.00),(7,4,5,1,1.00,1.00),(8,4,4,2,24.50,49.00),(9,4,4,2,24.50,49.00),(10,5,5,3,1.00,3.00),(11,5,2,3,15.00,45.00),(12,5,2,3,15.00,45.00),(13,6,1,2,20.00,40.00),(14,6,4,2,24.50,49.00),(15,6,2,2,15.00,30.00),(16,6,5,1,1.00,1.00),(17,7,1,1,20.00,20.00),(18,8,1,1,20.00,20.00),(19,9,4,2,24.50,49.00),(20,9,1,1,20.00,20.00),(21,10,1,1,20.00,20.00),(22,11,1,1,20.00,20.00),(23,11,4,1,24.50,24.50),(24,11,2,1,15.00,15.00),(25,12,1,1,20.00,20.00),(26,13,1,1,20.00,20.00),(27,14,1,1,20.00,20.00),(28,15,2,1,15.00,15.00),(29,16,6,1,15.00,15.00),(30,17,6,1,15.00,15.00),(31,18,6,2,15.00,30.00),(32,19,4,1,24.50,24.50),(33,20,5,1,1.00,1.00),(34,20,4,1,24.50,24.50),(35,21,5,1,1.00,1.00),(36,22,6,3,15.00,45.00),(37,22,2,1,15.00,15.00),(38,23,6,2,15.00,30.00),(39,24,6,1,15.00,15.00);
/*!40000 ALTER TABLE `detalle_venta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'Coca-Cola 600ml',20.00,36,'img/productos/producto_1_1776499361.jpg','2026-03-26 14:14:53'),(2,'Sabritas 45g',15.00,19,'img/productos/producto_2_1776499395.jpg','2026-03-26 14:14:53'),(4,'Leche Lala 1L',24.50,5,'img/productos/producto_4_1776499328.jpg','2026-03-26 14:14:53'),(5,'Gael',1.00,93,'img/productos/producto_5_1776499378.jpg','2026-03-26 15:08:44'),(6,'Agua Ciel 1LT',15.00,90,'img/productos/producto_6_1776500277.jpg','2026-04-18 08:17:57');
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('admin','cajero') NOT NULL DEFAULT 'cajero',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','$2y$10$CGOMTacxU/AedlkY9ZYnHO86cqYjx7UFIXVyeQEOKZd1FCsRgEbpK','admin','2026-03-26 14:14:52'),(2,'Karlocajero','$2y$10$9alTZOoW6ne6d1MXB8rd7OgX8teEo.JmzSf9khR38SfuUii/twUnC','cajero','2026-03-26 16:34:10'),(3,'gestion','$2y$10$GqyIHD59oNqRI6uE/CEAeOjspVKCsYoehy6Ikgis706.mxX5MjT/6','cajero','2026-05-05 23:16:09');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_venta`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL,
  CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
INSERT INTO `ventas` VALUES (1,'2026-03-26 09:21:18',40.00,NULL,1),(2,'2026-03-26 09:22:38',40.00,NULL,1),(3,'2026-03-26 09:24:42',2.00,NULL,1),(4,'2026-03-26 09:40:15',99.00,NULL,1),(5,'2026-03-26 09:40:46',93.00,NULL,1),(6,'2026-03-26 09:45:58',120.00,NULL,1),(7,'2026-03-26 10:00:19',20.00,3,1),(8,'2026-03-26 10:00:53',20.00,3,1),(9,'2026-03-26 14:15:28',69.00,NULL,1),(10,'2026-03-26 14:16:16',20.00,4,1),(11,'2026-04-16 09:27:37',59.50,NULL,1),(12,'2026-04-17 14:16:03',20.00,2,1),(13,'2026-04-17 14:18:31',20.00,2,1),(14,'2026-04-18 01:14:27',20.00,NULL,1),(15,'2026-04-18 02:15:48',15.00,NULL,1),(16,'2026-04-19 20:29:47',15.00,2,1),(17,'2026-05-17 04:12:42',15.00,NULL,3),(18,'2026-05-17 04:35:49',30.00,NULL,1),(19,'2026-05-17 04:36:49',24.50,NULL,1),(20,'2026-05-17 04:37:18',25.50,NULL,1),(21,'2026-05-17 04:38:07',1.00,NULL,1),(22,'2026-05-17 05:11:37',60.00,NULL,1),(23,'2026-05-17 05:17:48',30.00,NULL,1),(24,'2026-05-17 05:23:48',15.00,NULL,1);
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-17 18:01:13
