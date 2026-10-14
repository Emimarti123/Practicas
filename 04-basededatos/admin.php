<?php
include_once "auth.php";
requiereRol(["admin"]);

// ---------------------------------------------------------------
// Acciones (POST): procesar y redirigir para no reenviar al recargar
// ---------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verificarCsrf();
    $accion = $_POST["accion"] ?? "";

    if ($accion === "aprobar" || $accion === "rechazar") {
        $usuarioId = (int) $_POST["usuario_id"];

        $sql = "SELECT nombre, rol, carrera_id, email_verificado
                FROM usuarios
                WHERE id = ? AND estado <> 'activo' AND rol <> 'admin'";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario) {
            guardarAviso("error", "Esa solicitud ya no existe o ya fue aprobada.");
        } elseif ($accion === "rechazar") {
            $sql = "UPDATE usuarios SET estado = 'rechazado' WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $usuarioId);
            $stmt->execute();
            guardarAviso("exito", "Se rechazó la solicitud de " . $usuario["nombre"] . ".");
        } elseif (!$usuario["email_verificado"]) {
            guardarAviso("error", $usuario["nombre"] . " todavía no confirma su email.");
        } else {
            // Activar la cuenta y, si es alumno, inscribirlo en su plan de estudios.
            // Va en transacción: o se hacen las dos cosas o ninguna.
            $conexion->begin_transaction();
            try {
                $sql = "UPDATE usuarios SET estado = 'activo' WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $usuarioId);
                $stmt->execute();

                $mensaje = "Se aprobó la cuenta de " . $usuario["nombre"] . " como " . strtolower(nombreRol($usuario["rol"])) . ".";

                if ($usuario["rol"] === "alumno") {
                    // Carga automática: una fila en calificaciones por cada materia
                    // de su carrera. IGNORE evita duplicados si ya estaba inscrito.
                    $sql = "INSERT IGNORE INTO calificaciones (usuario_id, materia_id)
                            SELECT ?, materia_id
                            FROM plan_estudios
                            WHERE carrera_id = ?";
                    $stmt = $conexion->prepare($sql);
                    $stmt->bind_param("ii", $usuarioId, $usuario["carrera_id"]);
                    $stmt->execute();
                    $mensaje .= " Se le inscribió en " . $stmt->affected_rows . " materias.";
                }

                $conexion->commit();
                guardarAviso("exito", $mensaje);
            } catch (mysqli_sql_exception $e) {
                $conexion->rollback();
                guardarAviso("error", "No se pudo aprobar la cuenta.");
            }
        }

    } elseif ($accion === "asignar") {
        // Solo se aceptan docentes activos (evita que alguien mande el id de un alumno)
        $resultado = $conexion->query("SELECT id FROM usuarios WHERE rol = 'maestro' AND estado = 'activo'");
        $docentesValidos = array_column($resultado->fetch_all(MYSQLI_ASSOC), "id");

        $sql = "UPDATE materias SET maestro_id = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        foreach ($_POST["maestro"] ?? [] as $materiaId => $maestroId) {
            $maestroId = (int) $maestroId;
            if (!in_array($maestroId, $docentesValidos)) {
                $maestroId = null; // "Sin asignar"
            }
            $stmt->bind_param("is", $maestroId, $materiaId);
            $stmt->execute();
        }
        guardarAviso("exito", "Asignaciones de docentes guardadas.");
    }

    header("Location: admin.php");
    exit;
}

// ---------------------------------------------------------------
// Datos para mostrar
// ---------------------------------------------------------------
$resumen = $conexion->query(
    "SELECT
        SUM(rol = 'alumno'  AND estado = 'activo')    AS alumnos,
        SUM(rol = 'maestro' AND estado = 'activo')    AS docentes,
        SUM(estado = 'pendiente')                     AS pendientes
     FROM usuarios"
)->fetch_assoc();

// Pendientes primero; los rechazados se pueden aprobar después si fue un error
$solicitudes = $conexion->query(
    "SELECT usuarios.id, usuarios.nombre, usuarios.email, usuarios.rol, usuarios.estado,
            usuarios.email_verificado, carreras.nombre AS carrera
     FROM usuarios
     LEFT JOIN carreras ON carreras.id = usuarios.carrera_id
     WHERE usuarios.estado <> 'activo'
     ORDER BY usuarios.estado = 'rechazado', usuarios.created_at"
);

$docentes = $conexion->query(
    "SELECT id, nombre FROM usuarios
     WHERE rol = 'maestro' AND estado = 'activo'
     ORDER BY nombre"
)->fetch_all(MYSQLI_ASSOC);

$materias = $conexion->query(
    "SELECT materias.id, materias.nombre, materias.maestro_id,
            GROUP_CONCAT(carreras.nombre ORDER BY carreras.nombre SEPARATOR ', ') AS carreras
     FROM materias
     LEFT JOIN plan_estudios ON plan_estudios.materia_id = materias.id
     LEFT JOIN carreras ON carreras.id = plan_estudios.carrera_id
     GROUP BY materias.id
     ORDER BY materias.nombre"
);

$alumnos = $conexion->query(
    "SELECT usuarios.id, usuarios.nombre, carreras.nombre AS carrera,
            COUNT(calificaciones.id) AS materias,
            AVG(calificaciones.calificacion) AS promedio
     FROM usuarios
     LEFT JOIN carreras ON carreras.id = usuarios.carrera_id
     LEFT JOIN calificaciones ON calificaciones.usuario_id = usuarios.id
     WHERE usuarios.rol = 'alumno' AND usuarios.estado = 'activo'
     GROUP BY usuarios.id
     ORDER BY carreras.nombre, usuarios.nombre"
);

$titulo = "Panel de administración";
include_once "header.php";
?>
<h1>Panel de administración</h1>

<div class="resumen">
    <section class="tarjeta">
        <div class="resumen-numero"><?php echo (int) $resumen["pendientes"]; ?></div>
        <div class="texto-secundario">Solicitudes pendientes</div>
    </section>
    <section class="tarjeta">
        <div class="resumen-numero"><?php echo (int) $resumen["alumnos"]; ?></div>
        <div class="texto-secundario">Alumnos activos</div>
    </section>
    <section class="tarjeta">
        <div class="resumen-numero"><?php echo (int) $resumen["docentes"]; ?></div>
        <div class="texto-secundario">Docentes activos</div>
    </section>
</div>

<section class="tarjeta">
    <h2>Solicitudes de registro</h2>
    <?php if ($solicitudes->num_rows === 0) { ?>
        <p class="texto-secundario">No hay solicitudes por revisar.</p>
    <?php } else { ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = $solicitudes->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $fila["id"]; ?></td>
                        <td><?php echo htmlspecialchars($fila["nombre"]); ?></td>
                        <td>
                            <?php echo nombreRol($fila["rol"]); ?>
                            <?php if ($fila["carrera"] !== null) { ?>
                                <div class="texto-secundario texto-chico"><?php echo htmlspecialchars($fila["carrera"]); ?></div>
                            <?php } ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($fila["email"]); ?>
                            <?php if (!$fila["email_verificado"]) { ?>
                                <div><span class="etiqueta etiqueta-aviso">Sin confirmar</span></div>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($fila["estado"] === "pendiente") { ?>
                                <span class="etiqueta etiqueta-aviso">Pendiente</span>
                            <?php } else { ?>
                                <span class="etiqueta etiqueta-error">Rechazado</span>
                            <?php } ?>
                        </td>
                        <td>
                            <div class="acciones">
                                <form method="POST">
                                    <?php echo campoCsrf(); ?>
                                    <input type="hidden" name="accion" value="aprobar">
                                    <input type="hidden" name="usuario_id" value="<?php echo $fila["id"]; ?>">
                                    <button type="submit" class="boton boton-chico"
                                        <?php echo $fila["email_verificado"] ? "" : "disabled title=\"Primero debe confirmar su email\""; ?>>
                                        Aprobar
                                    </button>
                                </form>
                                <?php if ($fila["estado"] === "pendiente") { ?>
                                <form method="POST">
                                    <?php echo campoCsrf(); ?>
                                    <input type="hidden" name="accion" value="rechazar">
                                    <input type="hidden" name="usuario_id" value="<?php echo $fila["id"]; ?>">
                                    <button type="submit" class="boton boton-chico boton-peligro">Rechazar</button>
                                </form>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>

<section class="tarjeta">
    <h2>Docentes por materia</h2>
    <?php if (count($docentes) === 0) { ?>
        <p class="texto-secundario">
            Todavía no hay docentes aprobados. Cuando apruebes uno podrás asignarle materias aquí.
        </p>
    <?php } ?>
    <form method="POST">
        <?php echo campoCsrf(); ?>
        <input type="hidden" name="accion" value="asignar">
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th>Carreras</th>
                        <th>Docente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($materia = $materias->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($materia["nombre"]); ?>
                            <div class="texto-secundario texto-chico"><?php echo htmlspecialchars($materia["id"]); ?></div>
                        </td>
                        <td class="texto-secundario texto-chico"><?php echo htmlspecialchars($materia["carreras"] ?? "—"); ?></td>
                        <td>
                            <select name="maestro[<?php echo htmlspecialchars($materia["id"]); ?>]" class="campo-tabla">
                                <option value="">Sin asignar</option>
                                <?php foreach ($docentes as $docente) { ?>
                                    <option value="<?php echo $docente["id"]; ?>"
                                        <?php echo (int) $docente["id"] === (int) $materia["maestro_id"] ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($docente["nombre"]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="pie-formulario">
            <button type="submit" class="boton" <?php echo count($docentes) === 0 ? "disabled" : ""; ?>>
                Guardar asignaciones
            </button>
        </div>
    </form>
</section>

<section class="tarjeta">
    <h2>Alumnos activos</h2>
    <?php if ($alumnos->num_rows === 0) { ?>
        <p class="texto-secundario">Todavía no hay alumnos aprobados.</p>
    <?php } else { ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Número de control</th>
                        <th>Nombre</th>
                        <th>Carrera</th>
                        <th class="numero">Materias</th>
                        <th class="numero">Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($alumno = $alumnos->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $alumno["id"]; ?></td>
                        <td><?php echo htmlspecialchars($alumno["nombre"]); ?></td>
                        <td><?php echo htmlspecialchars($alumno["carrera"]); ?></td>
                        <td class="numero"><?php echo $alumno["materias"]; ?></td>
                        <td class="numero">
                            <?php echo $alumno["promedio"] === null ? "—" : number_format($alumno["promedio"], 2); ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php include_once "footer.php"; ?>
