<?php
include_once 'conexion.php';

$sql = "SELECT id, matricula, nombre, carrera, semestre FROM alumnos ORDER BY nombre";
$resultado = $conexion->query($sql);
?>
<h1>Lista de alumnos</h1>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Matrícula</th>
        <th>Nombre</th>
        <th>Carrera</th>
        <th>Semestre</th>
    </tr>
    <?php while ($fila = $resultado->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $fila["id"]; ?></td>
        <td><?php echo htmlspecialchars($fila["matricula"]); ?></td>
        <td><?php echo htmlspecialchars($fila["nombre"]); ?></td>
        <td><?php echo htmlspecialchars($fila["carrera"]); ?></td>
        <td><?php echo $fila["semestre"]; ?></td>
    </tr>
    <?php } ?>
</table>