<?php
include_once "auth.php";
requiereRol(["maestro", "admin"]);

$materiaId = $_GET["materia"] ?? "";

$sql = "SELECT materias.id, materias.nombre, materias.maestro_id, usuarios.nombre AS maestro
        FROM materias
        LEFT JOIN usuarios ON usuarios.id = materias.maestro_id
        WHERE materias.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $materiaId);
$stmt->execute();
$materia = $stmt->get_result()->fetch_assoc();

// Un docente solo puede capturar en SUS materias; el admin, en cualquiera
$esAdmin = $_SESSION["rol"] === "admin";
if (!$materia || (!$esAdmin && (int) $materia["maestro_id"] !== (int) $_SESSION["usuario_id"])) {
    guardarAviso("error", "No tienes acceso a esa materia.");
    header("Location: " . panelDeRol($_SESSION["rol"]));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();

    // materia_id en el WHERE: solo se pueden tocar filas de esta materia
    $sql = "UPDATE calificaciones SET calificacion = ? WHERE id = ? AND materia_id = ?";
    $stmt = $conexion->prepare($sql);
    $invalidas = 0;

    foreach ($_POST["calificacion"] ?? [] as $calificacionId => $valor) {
        $valor = trim($valor);
        if ($valor === "") {
            $valor = null; // vacío = sin calificar
        } elseif (!is_numeric($valor) || $valor < 0 || $valor > 10) {
            $invalidas++;
            continue;
        } else {
            $valor = round((float) $valor, 2);
        }
        $calificacionId = (int) $calificacionId;
        $stmt->bind_param("dis", $valor, $calificacionId, $materiaId);
        $stmt->execute();
    }

    if ($invalidas > 0) {
        guardarAviso("error", "Se guardó lo demás, pero $invalidas calificación(es) no eran válidas (deben estar entre 0 y 10).");
    } else {
        guardarAviso("exito", "Calificaciones guardadas.");
    }
    header("Location: capturar.php?materia=" . urlencode($materiaId));
    exit;
}

$sql = "SELECT calificaciones.id, calificaciones.calificacion,
               usuarios.id AS control, usuarios.nombre, carreras.nombre AS carrera
        FROM calificaciones
        INNER JOIN usuarios ON usuarios.id = calificaciones.usuario_id
        LEFT JOIN carreras ON carreras.id = usuarios.carrera_id
        WHERE calificaciones.materia_id = ?
        ORDER BY usuarios.nombre";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $materiaId);
$stmt->execute();
$alumnos = $stmt->get_result();

$titulo = $materia["nombre"];
include_once "header.php";
?>
<p><a href="<?php echo $esAdmin ? "calificaciones.php" : "maestro.php"; ?>">&larr; Volver</a></p>
<h1><?php echo htmlspecialchars($materia["nombre"]); ?></h1>
<p class="texto-secundario">
    Clave <?php echo htmlspecialchars($materia["id"]); ?> ·
    Docente: <?php echo htmlspecialchars($materia["maestro"] ?? "Sin asignar"); ?>
</p>

<section class="tarjeta">
    <h2>Captura de calificaciones</h2>
    <?php if ($alumnos->num_rows === 0) { ?>
        <p class="texto-secundario">Todavía no hay alumnos inscritos en esta materia.</p>
    <?php } else { ?>
        <p class="texto-secundario">Escala de 0 a 10. Deja el campo vacío si aún no tiene calificación.</p>
        <form method="POST">
            <?php echo campoCsrf(); ?>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Número de control</th>
                            <th>Nombre</th>
                            <th>Carrera</th>
                            <th class="numero">Calificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($alumno = $alumnos->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $alumno["control"]; ?></td>
                            <td><?php echo htmlspecialchars($alumno["nombre"]); ?></td>
                            <td class="texto-secundario texto-chico"><?php echo htmlspecialchars($alumno["carrera"]); ?></td>
                            <td class="numero">
                                <input type="number" class="campo-tabla campo-calificacion"
                                       name="calificacion[<?php echo $alumno["id"]; ?>]"
                                       value="<?php echo $alumno["calificacion"]; ?>"
                                       min="0" max="10" step="0.01"
                                       aria-label="Calificación de <?php echo htmlspecialchars($alumno["nombre"]); ?>">
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="pie-formulario">
                <button type="submit" class="boton">Guardar calificaciones</button>
            </div>
        </form>
    <?php } ?>
</section>
<?php include_once "footer.php"; ?>
