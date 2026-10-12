<?php
include_once 'conexion.php';

$sql = "SELECT id, matricula, nombre, carrera, semestre FROM alumnos ORDER BY nombre";
$resultado = $conexion->query($sql);

$titulo = "Alumnos";
include_once 'header.php';
?>
<h1>Lista de alumnos</h1>
<section class="tarjeta">
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Matrícula</th>
                    <th>Nombre</th>
                    <th>Carrera</th>
                    <th class="numero">Semestre</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fila = $resultado->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $fila["id"]; ?></td>
                    <td><?php echo htmlspecialchars($fila["matricula"]); ?></td>
                    <td><?php echo htmlspecialchars($fila["nombre"]); ?></td>
                    <td><?php echo htmlspecialchars($fila["carrera"]); ?></td>
                    <td class="numero"><?php echo $fila["semestre"]; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php include_once 'footer.php'; ?>
