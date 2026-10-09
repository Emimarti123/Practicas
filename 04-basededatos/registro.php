<?php
include_once "conexion.php";
$mensaje = "";
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

    if ($stmt->execute()) {
        $mensaje = "Usuario registrado correctamente.";
        echo "<p>
            Para confirmar tu correo:
            <a href='verificar.php?token=$token'>
                Confirmar email
            </a>
        </p>";

    } else {
        $mensaje = "No se pudo registrar el usuario.";
    }
}
?>
<!-- include_once "header.php"; -->
<h1>Registro de alumno</h1>
<form method="POST">
    <label>Número de control</label>
    <input type="number" name="id" min="1" required>
    <label>Nombre</label>
    <input type="text" name="nombre" required >
    <label>Email</label>
    <input type="email" name="email" required>
    <label>Contraseña</label>
    <input type="password" name="password" required>
    <button type="submit">Registrarme</button>
</form>
<p><?php echo $mensaje; ?></p>
<a href="login.php">Iniciar sesión</a>
<!-- include_once "footer.php"; -->