<?php
// Algunas páginas (login, dashboard) ya iniciaron la sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cada página puede definir $titulo antes de incluir este archivo
$titulo = $titulo ?? "Escuelinha";
$haySesion = isset($_SESSION["usuario_id"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?> | Escuelinha</title>
    <!-- Fuentes: Source Serif 4 (títulos) y Source Sans 3 (texto) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<header class="barra">
    <div class="barra-contenido">
        <a href="<?php echo $haySesion ? "dashboard.php" : "login.php"; ?>" class="logo">
            Escuelinha
        </a>
        <nav class="menu">
            <?php // ALUMNOS: desactivado. Para activarlo, quita el comentario <!-- --> de la línea de abajo ?>
            <!-- <a href="alumnos.php">Alumnos</a> -->
            <a href="calificaciones.php">Calificaciones</a>
            <?php if ($haySesion) { ?>
                <a href="dashboard.php">Mi panel</a>
                <a href="logout.php" class="boton boton-secundario">Cerrar sesión</a>
            <?php } else { ?>
                <a href="login.php">Iniciar sesión</a>
                <a href="registro.php" class="boton">Registrarme</a>
            <?php } ?>
        </nav>
    </div>
</header>
<main class="contenedor">
