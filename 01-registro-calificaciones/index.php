<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de calificaciones</title>
</head>
<body>
    <h1>Registro de calificaciones</h1>

    <form action="resultado.php" method="GET">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>
        <br><br>

        <label for="materia">Materia:</label>
        <input type="text" id="materia" name="materia" required>
        <br><br>

        <label for="calificacion">Calificación:</label>
        <input type="number" id="calificacion" name="calificacion"
               min="0" max="10" step="0.1" required>
        <br><br>

        <button type="submit">Enviar</button>
    </form>

  

</body>
</html>