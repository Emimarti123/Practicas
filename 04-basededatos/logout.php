<?php
include_once "auth.php";

$rol = $_SESSION["rol"] ?? "alumno";
cerrarSesion();

// Solo se permite volver a estas páginas (evita redirecciones a sitios externos)
$destinos = ["login.php", "login_docente.php", "registro.php", "registro_docente.php"];
$volver = $_GET["volver"] ?? "";
if (!in_array($volver, $destinos)) {
    $volver = loginDeRol($rol);
}

header("Location: " . $volver);
exit;
