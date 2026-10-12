<?php
include_once "conexion.php";
$mensaje = "";
$tipoMensaje = "";
$enlaceVerificacion = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = (int) trim($_POST["id"]);
    $nombre = trim($_POST["nombre"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    // Convertir contraseña a hash
    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );
    // Crear token
    $token = bin2hex(random_bytes(32));

    $sql = "INSERT INTO usuarios
            (id, nombre, email, password, token_verification)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);

    $stmt->bind_param(
        "issss",
        $id,
        $nombre,
        $email,
        $passwordHash,
        $token
    );

    try {
        $stmt->execute();
        $mensaje = "Usuario registrado correctamente.";
        $tipoMensaje = "exito";
        // Simula el correo de confirmación: el enlace se muestra en la página
        $enlaceVerificacion = "verificar.php?token=" . $token;

    } catch (mysqli_sql_exception $e) {
        $tipoMensaje = "error";
        // 1062 = entrada duplicada (id o email ya existen)
        if ($e->getCode() === 1062) {
            $mensaje = "Ese número de control o email ya está registrado.";
        } else {
            $mensaje = "No se pudo registrar el usuario.";
        }
    }
}

$titulo = "Registro";
include_once "header.php";
?>
<section class="tarjeta tarjeta-formulario">
    <h1>Registro de alumno</h1>
    <?php if ($mensaje !== "") { ?>
        <div class="mensaje mensaje-<?php echo $tipoMensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
            <?php if ($enlaceVerificacion !== "") { ?>
                <br>
                Para confirmar tu correo:
                <a href="<?php echo htmlspecialchars($enlaceVerificacion); ?>">Confirmar email</a>
            <?php } ?>
        </div>
    <?php } ?>
    <form method="POST" class="formulario">
        <label for="id">Número de control</label>
        <input type="number" id="id" name="id" min="1" required>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" required>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" class="boton">Registrarme</button>
    </form>
    <p class="texto-secundario">
        ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
    </p>
</section>
<?php include_once "footer.php"; ?>