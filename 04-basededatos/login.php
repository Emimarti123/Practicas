<?php
session_start();
include_once "conexion.php";

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conexion->prepare($sql);

    $stmt->bind_param("s", $email);

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1)
    {
        $usuario = $resultado->fetch_assoc();
        if (!$usuario["email_verificado"]) {
            $mensaje = "Primero confirma tu email.";
        }
        elseif (
            password_verify(
                $password,
                $usuario["password"]
            )
        ) {
            session_regenerate_id(true);
            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            header("Location: dashboard.php");
            exit;

        } else {

            $mensaje = "Contraseña incorrecta.";
        }

    } else {
        $mensaje = "Usuario no encontrado.";
    }
}
?>
<?php
$titulo = "Iniciar sesión";
include_once "header.php";
?>
<section class="tarjeta tarjeta-formulario">
    <h1>Iniciar sesión</h1>
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
        ¿No tienes cuenta? <a href="registro.php">Crear cuenta</a>
    </p>
</section>
<?php include_once "footer.php"; ?>