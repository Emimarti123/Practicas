<?php
$host      = "localhost";
$usuario   = "root";
$password  = "Sistemas";
$baseDatos = "escuela";

$conexion = new mysqli($host, $usuario, $password, $baseDatos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
echo "Conexión correcta";