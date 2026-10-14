<?php
// auth.php inicia la sesión (si no estaba iniciada) y define panelDeRol()
include_once "auth.php";
// Cada página puede definir $titulo antes de incluir este archivo
$titulo = $titulo ?? "Escuelinha";
// El menú usa la sesión ya validada contra la BD, nunca $_SESSION directo
$usuarioMenu = usuarioEnSesion();
$haySesion = $usuarioMenu !== null;
$rol = $haySesion ? $usuarioMenu["rol"] : null;
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
        <a href="<?php echo $haySesion ? panelDeRol($rol) : "login.php"; ?>" class="logo">
            Escuelinha
        </a>
        <nav class="menu">
            <?php if ($haySesion) { ?>
                <a href="<?php echo panelDeRol($rol); ?>">
                    <?php echo $rol === "maestro" ? "Mis materias" : "Mi panel"; ?>
                </a>
                <?php if ($rol === "admin") { ?>
                    <a href="calificaciones.php">Calificaciones</a>
                <?php } ?>
                <a href="logout.php" class="boton boton-secundario">Cerrar sesión</a>
            <?php } else { ?>
                <a href="login.php">Iniciar sesión</a>
                <a href="registro.php" class="boton">Registrarme</a>
            <?php } ?>
        </nav>
    </div>
</header>
<main class="contenedor">
<?php if (isset($_SESSION["aviso"])) { ?>
    <p class="mensaje mensaje-<?php echo $_SESSION["aviso"]["tipo"]; ?>">
        <?php echo htmlspecialchars($_SESSION["aviso"]["mensaje"]); ?>
    </p>
    <?php unset($_SESSION["aviso"]); ?>
<?php } ?>
