<?php
include_once "auth.php";
requiereRol(["alumno"]);

$usuarioId = $_SESSION["usuario_id"];

$sql = "SELECT usuarios.id, usuarios.nombre, usuarios.email, usuarios.created_at,
               carreras.nombre AS carrera
        FROM usuarios
        LEFT JOIN carreras ON carreras.id = usuarios.carrera_id
        WHERE usuarios.id = ?";
$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "i",
    $usuarioId
);

$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$sql = "SELECT materias.id, materias.nombre AS materia,
               maestros.nombre AS maestro,
               calificaciones.calificacion
        FROM calificaciones
        INNER JOIN materias
        ON materias.id =
            calificaciones.materia_id
        LEFT JOIN usuarios AS maestros
        ON maestros.id = materias.maestro_id
        WHERE calificaciones.usuario_id = ?
        ORDER BY materias.nombre";

$stmt = $conexion->prepare($sql);
$stmt->bind_param(
    "i",
    $usuarioId
);
$stmt->execute();
$calificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Promedio solo de las materias que ya tienen calificación
$calificadas = array_filter(array_column($calificaciones, "calificacion"), fn($c) => $c !== null);
$promedio = count($calificadas) > 0 ? array_sum($calificadas) / count($calificadas) : null;

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

        <dt>Carrera</dt>
        <dd><?php echo htmlspecialchars($usuario["carrera"]); ?></dd>

        <dt>Email</dt>
        <dd><?php echo htmlspecialchars($usuario["email"]); ?></dd>

        <dt>Fecha de registro</dt>
        <dd><?php echo $usuario["created_at"]; ?></dd>

        <dt>Promedio</dt>
        <dd><?php echo $promedio === null ? "Sin calificaciones aún" : number_format($promedio, 2); ?></dd>
    </dl>
</section>

<section class="tarjeta">
    <h2>Mis materias</h2>
    <?php if (count($calificaciones) === 0) { ?>
        <p class="texto-secundario">Aún no tienes materias inscritas.</p>
    <?php } else { ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Docente</th>
                        <th class="numero">Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calificaciones as $fila) { ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($fila["materia"]); ?>
                            <div class="texto-secundario texto-chico"><?php echo htmlspecialchars($fila["id"]); ?></div>
                        </td>
                        <td>
                            <?php if ($fila["maestro"] === null) { ?>
                                <span class="texto-secundario">Sin asignar</span>
                            <?php } else { ?>
                                <?php echo htmlspecialchars($fila["maestro"]); ?>
                            <?php } ?>
                        </td>
                        <td class="numero">
                            <?php if ($fila["calificacion"] === null) { ?>
                                <span class="etiqueta">Sin calificar</span>
                            <?php } else { ?>
                                <?php echo $fila["calificacion"]; ?>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php include_once "footer.php"; ?>
