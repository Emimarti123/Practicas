<?php
include_once 'auth.php';
requiereRol(["admin"]);

// Resumen por materia; el detalle y la captura están en capturar.php
$sql = "SELECT materias.id, materias.nombre,
               usuarios.nombre AS maestro,
               COUNT(calificaciones.id) AS inscritos,
               COUNT(calificaciones.calificacion) AS calificados,
               AVG(calificaciones.calificacion) AS promedio
        FROM materias
        LEFT JOIN usuarios ON usuarios.id = materias.maestro_id
        LEFT JOIN calificaciones ON calificaciones.materia_id = materias.id
        GROUP BY materias.id
        ORDER BY materias.nombre";
$resultado = $conexion->query($sql);

$titulo = "Calificaciones";
include_once 'header.php';
?>
<h1>Calificaciones por materia</h1>
<section class="tarjeta">
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Docente</th>
                    <th class="numero">Calificados</th>
                    <th class="numero">Promedio</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = $resultado->fetch_assoc()) { ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($fila["nombre"]); ?>
                        <div class="texto-secundario texto-chico"><?php echo htmlspecialchars($fila["id"]); ?></div>
                    </td>
                    <td>
                        <?php if ($fila["maestro"] === null) { ?>
                            <span class="etiqueta etiqueta-aviso">Sin asignar</span>
                        <?php } else { ?>
                            <?php echo htmlspecialchars($fila["maestro"]); ?>
                        <?php } ?>
                    </td>
                    <td class="numero"><?php echo $fila["calificados"]; ?> / <?php echo $fila["inscritos"]; ?></td>
                    <td class="numero">
                        <?php echo $fila["promedio"] === null ? "—" : number_format($fila["promedio"], 2); ?>
                    </td>
                    <td class="numero">
                        <a href="capturar.php?materia=<?php echo urlencode($fila["id"]); ?>" class="boton boton-chico">Ver / editar</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php include_once 'footer.php'; ?>
