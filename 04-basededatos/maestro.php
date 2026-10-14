<?php
include_once "auth.php";
requiereRol(["maestro"]);

$sql = "SELECT materias.id, materias.nombre,
               COUNT(calificaciones.id) AS inscritos,
               COUNT(calificaciones.calificacion) AS calificados
        FROM materias
        LEFT JOIN calificaciones ON calificaciones.materia_id = materias.id
        WHERE materias.maestro_id = ?
        GROUP BY materias.id
        ORDER BY materias.nombre";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $_SESSION["usuario_id"]);
$stmt->execute();
$materias = $stmt->get_result();

$titulo = "Mis materias";
include_once "header.php";
?>
<h1>Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre"]); ?></h1>

<section class="tarjeta">
    <h2>Mis materias</h2>
    <?php if ($materias->num_rows === 0) { ?>
        <p class="texto-secundario">
            Aún no tienes materias asignadas. El administrador debe asignártelas.
        </p>
    <?php } else { ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Materia</th>
                        <th class="numero">Alumnos</th>
                        <th class="numero">Calificados</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($materia = $materias->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($materia["nombre"]); ?>
                            <div class="texto-secundario texto-chico"><?php echo htmlspecialchars($materia["id"]); ?></div>
                        </td>
                        <td class="numero"><?php echo $materia["inscritos"]; ?></td>
                        <td class="numero"><?php echo $materia["calificados"]; ?> / <?php echo $materia["inscritos"]; ?></td>
                        <td class="numero">
                            <a href="capturar.php?materia=<?php echo urlencode($materia["id"]); ?>" class="boton boton-chico">
                                Capturar calificaciones
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php include_once "footer.php"; ?>
