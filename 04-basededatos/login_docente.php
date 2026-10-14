<?php
include_once "auth.php";
// Si ya hay una sesión abierta no se muestra el formulario, sino un aviso (sesion_activa.php)
$sesionActiva = usuarioEnSesion();
$paginaActual = "login_docente.php";

$mensaje = "";
if (!$sesionActiva && $_SERVER["REQUEST_METHOD"] === "POST") {
    // El administrador también entra por aquí (acceso de personal)
    $mensaje = iniciarSesion(
        trim($_POST["email"]),
        $_POST["password"],
        ["maestro", "admin"]
    );
}

$titulo = "Acceso docentes";
include_once "header.php";

if ($sesionActiva) {
    include "sesion_activa.php";
} else {
?>
<section class="tarjeta tarjeta-formulario">
    <h1>Acceso docentes</h1>
    <?php if ($mensaje !== "") { ?>
        <p class="mensaje mensaje-error"><?php echo htmlspecialchars($mensaje); ?></p>
    <?php } ?>
    <form method="POST" class="formulario">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="boton">Entrar</button>
    </form>
    <p class="texto-secundario">
        ¿No tienes cuenta? <a href="registro_docente.php">Solicitar cuenta de docente</a>
    </p>
    <p class="texto-secundario texto-pequeno">
        ¿Eres alumno? <a href="login.php">Inicia sesión aquí</a>
    </p>
</section>
<?php } ?>
<?php include_once "footer.php"; ?>
