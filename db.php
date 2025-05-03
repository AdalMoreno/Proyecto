<?php
$host = 'localhost';    // Dirección del servidor de la base de datos
$puerto = '5433';       // Puerto por defecto de PostgreSQL
$usuario = 'user_citas';// Nombre de usuario de PostgreSQL
$clave = 'qwerty123';   // Contraseña de usuario
$base_de_datos = 'citas'; // Nombre de la base de datos

try {
    // Crear la conexión PDO
    $conexion = new PDO("pgsql:host=$host;port=$puerto;dbname=$base_de_datos", $usuario, $clave);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}
?>
