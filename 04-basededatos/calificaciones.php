<?php
include_once 'conexion.php';

$sql = "SELECT calificaciones.id,
               usuarios.nombre AS nombre,
               materias.nombre AS materia,
               calificaciones.calificacion
        FROM calificaciones
        INNER JOIN usuarios ON usuarios.id = calificaciones.usuario_id
        INNER JOIN materias ON materias.id = calificaciones.materia_id
        ORDER BY usuarios.nombre, materias.nombre";
$resultado = $conexion->query($sql);

$titulo = "Calificaciones";
include_once 'header.php';
?>
<h1>Lista de calificaciones</h1>
<section class="tarjeta">
    <?php if ($resultado->num_rows === 0) { ?>
        <p class="texto-secundario">Todavía no hay calificaciones registradas.</p>
    <?php } else { ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Materia</th>
                        <th class="numero">Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = $resultado->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $fila["id"]; ?></td>
                        <td><?php echo htmlspecialchars($fila["nombre"]); ?></td>
                        <td><?php echo htmlspecialchars($fila["materia"]); ?></td>
                        <td class="numero"><?php echo $fila["calificacion"]; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</section>
<?php include_once 'footer.php'; ?>
