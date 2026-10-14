<?php
include_once "auth.php";
// Si ya hay una sesión abierta no se muestra el formulario, sino un aviso (sesion_activa.php)
$sesionActiva = usuarioEnSesion();
$paginaActual = "registro.php";

$resultado = null;
if (!$sesionActiva && $_SERVER["REQUEST_METHOD"] === "POST") {
    $resultado = registrarUsuario(
        (int) trim($_POST["id"]),
        trim($_POST["nombre"]),
        trim($_POST["email"]),
        $_POST["password"],
        "alumno",
        (int) $_POST["carrera_id"]
    );
}
$carreras = obtenerCarreras();

$titulo = "Registro de alumno";
include_once "header.php";

if ($sesionActiva) {
    include "sesion_activa.php";
} else {
?>
<section class="tarjeta tarjeta-formulario">
    <h1>Registro de alumno</h1>
    <?php if ($resultado) { ?>
        <div class="mensaje mensaje-<?php echo $resultado["tipo"]; ?>">
            <?php echo htmlspecialchars($resultado["mensaje"]); ?>
            <?php if ($resultado["enlace"] !== "") { ?>
                <br>
                Para confirmar tu correo:
                <a href="<?php echo htmlspecialchars($resultado["enlace"]); ?>">Confirmar email</a>
            <?php } ?>
        </div>
    <?php } ?>
    <form method="POST" class="formulario">
        <label for="id">Número de control</label>
        <input type="number" id="id" name="id" min="1" required>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="carrera_id">Carrera</label>
        <select id="carrera_id" name="carrera_id" required>
            <option value="">Selecciona tu carrera</option>
            <?php while ($carrera = $carreras->fetch_assoc()) { ?>
                <option value="<?php echo $carrera["id"]; ?>">
                    <?php echo htmlspecialchars($carrera["nombre"]); ?>
                </option>
            <?php } ?>
        </select>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="boton">Registrarme</button>
    </form>
    <p class="texto-secundario">
        ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
    </p>
    <p class="texto-secundario texto-pequeno">
        ¿Eres docente? <a href="registro_docente.php">Regístrate aquí</a>
    </p>
</section>
<?php } ?>
<?php include_once "footer.php"; ?>
