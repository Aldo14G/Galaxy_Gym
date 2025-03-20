<?php
// Archivo de conexión a la base de datos del gimnasio
$conexion = mysqli_connect("localhost", "root", "", "galaxy_gym");

// Verificar conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Configurar el juego de caracteres de la conexión
mysqli_set_charset($conexion, "utf8");
?>