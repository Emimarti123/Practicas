<?php
include_once "auth.php";
$token = $_GET["token"] ?? "";
$sql = "SELECT id, rol FROM usuarios WHERE token_verification = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "s",
    $token
);
$stmt->execute();
$resultado = $stmt->get_result();
$verificado = false;
if ($resultado->num_rows === 1) {

    $usuario = $resultado->fetch_assoc();
    $sql = "UPDATE usuarios
            SET email_verificado = 1,
                token_verification = NULL
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "i",
        $usuario["id"]
    );
    $stmt->execute();
    $verificado = true;
    $paginaLogin = $usuario["rol"] === "alumno" ? "login.php" : "login_docente.php";
}

$titulo = "Verificar email";
include_once "header.php";
?>
<section class="tarjeta tarjeta-formulario">
    <?php if ($verificado) { ?>
        <h1>Email confirmado</h1>
        <p class="mensaje mensaje-exito">
            Tu correo quedó confirmado. Podrás iniciar sesión cuando el administrador apruebe tu cuenta.
        </p>
        <a href="<?php echo $paginaLogin; ?>" class="boton">Ir a iniciar sesión</a>
    <?php } else { ?>
        <h1>Enlace no válido</h1>
        <p class="mensaje mensaje-error">
            El token es inválido o ya fue usado.
        </p>
        <a href="registro.php">Volver al registro</a>
    <?php } ?>
</section>
<?php include_once "footer.php"; ?>
