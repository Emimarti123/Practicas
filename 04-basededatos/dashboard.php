<?php
session_start();
include_once "conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}
$usuarioId = $_SESSION["usuario_id"];

$sql = "SELECT id, nombre, email, created_at
        FROM usuarios
        WHERE id = ?";
$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "i",
    $usuarioId
);

$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$sql = "SELECT materias.nombre AS materia, calificaciones.calificacion
        FROM calificaciones
        INNER JOIN materias
        ON materias.id =
            calificaciones.materia_id
        WHERE calificaciones.usuario_id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "i",
    $usuarioId
);
$stmt->execute();
$calificaciones = $stmt->get_result();

$titulo = "Mi panel";
include_once "header.php";
?>
<h1>
    Bienvenido,
    <?php
        echo htmlspecialchars(
            $usuario["nombre"]
        );
    ?>
</h1>

<section class="tarjeta">
    <h2>Mis datos</h2>
    <dl class="datos">
        <dt>Número de control</dt>
        <dd><?php echo $usuario["id"]; ?></dd>

        <dt>Email</dt>
        <dd><?php echo htmlspecialchars($usuario["email"]); ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?php echo $usuario["created_at"]; ?></dd>
    </dl>
</section>

<section class="tarjeta">
    <h2>Mis materias</h2>
    <?php if ($calificaciones->num_rows === 0) { ?>
        <p class="texto-secundario">Aún no tienes calificaciones registradas.</p>
    <?php } else { ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th class="numero">Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = $calificaciones->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($fila["materia"]); ?></td>
                        <td class="numero"><?php echo $fila["calificacion"]; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php include_once "footer.php"; ?>